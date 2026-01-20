<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Webhook;
use App\Models\ShopifyShop;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyOrderProcessingService;
use App\Services\Shopify\ShopifyOrderService;
use App\Services\Shopify\ShopifyOrderEditService;
use App\Services\Shopify\ShopifyFulfillmentService;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyWebhookController extends Controller
{
    /**
     * Handle Shopify webhook (orders/paid, orders/cancelled, order_edit, etc.)
     */
    public function handle(Request $request): JsonResponse
    {
        set_time_limit(180); // 3 minutes

        $payload = $request->getContent();
        $headers = json_encode($request->headers->all());
        $rerunOfId = $request->header('X-Rerun-Of-Id');
        $topic = $request->header('X-Shopify-Topic');
        
        $webhook = Webhook::create([
            'payload' => $payload,
            'headers' => $headers,
            'shopify_topic' => $topic,
            'rerun_of_id' => $rerunOfId ? (int)$rerunOfId : null,
        ]);

        $webhookData = json_decode($payload, true);
        if (!$webhookData) {
            // Fallback to request->all() if JSON decode fails or content was parsed by middleware
            $webhookData = $request->all();
            if (empty($webhookData) && !empty($payload)) {
                 // Payload was something else?
            } else {
                 // Update payload with what we have
                 $webhook->update(['payload' => json_encode($webhookData)]);
            }
        }

        $orderId = null;
        $adminGraphqlApiId = null;
        $validShopMatched = false;
        $validHmac = false;
        $errorMessage = null;

        try {
            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            
            // Find the shop by domain (leniently)
            $shopDomainClean = strtolower(trim($shopDomain ?? ''));
            $shopHandle = str_replace('.myshopify.com', '', $shopDomainClean);
            
            $shop = ShopifyShop::where('shop_domain', $shopDomainClean)
                ->orWhere('shop_domain', $shopHandle)
                ->orWhere('shop_domain', $shopHandle . '.myshopify.com')
                ->first();
            
            if ($shop) {
                $validShopMatched = true;
                $webhook->update(['shop_id' => $shop->id]);
                
                if ($shop->is_active) {
                     // Active
                } else {
                     $errorMessage = 'Shop is inactive';
                }
            } else {
                $errorMessage = 'Shop not found for domain: ' . $shopDomain;
            }

            // Verify HMAC with shop-specific secret
            if ($shop && $this->verifyHmac($request, $shop)) {
                $validHmac = true;
            } elseif ($shop) {
                 $errorMessage = 'HMAC verification failed';
            }

            // Update webhook with validation status
            $webhook->update([
                'valid_shop_matched' => $validShopMatched,
                'valid_hmac' => $validHmac,
            ]);

            if (!$validShopMatched) {
                $webhook->update(['error_ts' => now(), 'error_message' => $errorMessage ?? 'Shop not found']);
                return response()->json(['error' => 'Shop not found'], 404);
            }
            
            if (!$shop->is_active) {
                $webhook->update(['error_ts' => now(), 'error_message' => 'Shop is inactive']);
                return response()->json(['error' => 'Shop is inactive'], 403);
            }

            if (!$validHmac) {
                $webhook->update(['error_ts' => now(), 'error_message' => 'HMAC verification failed']);
                return response()->json(['error' => 'HMAC verification failed'], 401);
            }

            if (!$shop->admin_api_token) {
                $webhook->update(['error_ts' => now(), 'error_message' => 'Shop admin_api_token is missing']);
                return response()->json(['error' => 'Shop access token missing'], 401);
            }

            // Handle order_edit webhook
            if (isset($webhookData['order_edit'])) {
                $orderEditId = $webhookData['order_edit']['order_id'] ?? null;
                if ($orderEditId) {
                    $orderId = (int)$orderEditId;
                    $adminGraphqlApiId = "gid://shopify/Order/{$orderEditId}";
                }
            } else {
                // Handle other webhooks
                $adminGraphqlApiId = $webhookData['admin_graphql_api_id'] ?? null;

                if (!$adminGraphqlApiId && isset($webhookData['id'])) {
                    $adminGraphqlApiId = "gid://shopify/Order/{$webhookData['id']}";
                }

                if ($adminGraphqlApiId) {
                    $orderId = (int)str_replace('gid://shopify/Order/', '', $adminGraphqlApiId);
                }
            }

            if (!$adminGraphqlApiId) {
                throw new \RuntimeException('Could not determine admin_graphql_api_id from webhook data');
            }

            // Create shop-specific services
            $orderProcessingService = $this->createOrderProcessingService($shop);
            $orderProcessingService->processOrder($adminGraphqlApiId, $webhook->id);

            $webhook->update(['success_ts' => now()]);

            return response()->json(null, 200);
        } catch (\Exception $e) {
            $webhook->update([
                'valid_shop_matched' => $validShopMatched,
                'valid_hmac' => $validHmac,
                'error_ts' => now(),
                'error_message' => $e->getMessage()
            ]);

            return response()->json(null, 400);
        }
    }

    /**
     * Create ShopifyOrderProcessingService for a specific shop.
     */
    private function createOrderProcessingService(ShopifyShop $shop): ShopifyOrderProcessingService
    {
        $client = new ShopifyClient($shop);
        $orderService = new ShopifyOrderService($client);
        $orderEditService = new ShopifyOrderEditService($client);
        $fulfillmentService = new ShopifyFulfillmentService($client);
        $productService = new ShopifyProductService($client);

        return new ShopifyOrderProcessingService(
            $client,
            $orderService,
            $orderEditService,
            $fulfillmentService,
            $productService
        );
    }

    /**
     * Verify Shopify webhook HMAC signature using shop-specific secret.
     */
    public function verifyHmac(Request $request, ShopifyShop $shop): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $secret = $shop->webhook_secret;

        if (!$hmacHeader || !$secret) {
            return false;
        }

        $data = $request->getContent();
        $computedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        return hash_equals($hmacHeader, $computedHmac);
    }
}
