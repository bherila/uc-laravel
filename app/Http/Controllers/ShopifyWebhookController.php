<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
        $webhookData = $request->all();
        $orderId = null;
        $adminGraphqlApiId = null;

        try {
            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            
            AuditLog::create([
                'source' => 'shopify_webhook',
                'message' => "Webhook received from {$shopDomain}",
                'payload' => $webhookData,
            ]);

            // Find the shop by domain (leniently)
            $shopDomainClean = strtolower(trim($shopDomain));
            $shopHandle = str_replace('.myshopify.com', '', $shopDomainClean);
            
            $shop = ShopifyShop::where('shop_domain', $shopDomainClean)
                ->orWhere('shop_domain', $shopHandle)
                ->orWhere('shop_domain', $shopHandle . '.myshopify.com')
                ->first();
            
            if (!$shop) {
                $this->log("Shop not found for domain: {$shopDomain}", null);
                return response()->json(['error' => 'Shop not found'], 404);
            }

            if (!$shop->is_active) {
                $this->log("Shop is inactive: {$shopDomain}", null);
                return response()->json(['error' => 'Shop is inactive'], 403);
            }

            // Verify HMAC with shop-specific secret
            if (!$this->verifyHmac($request, $shop)) {
                $this->log("HMAC verification failed for shop: {$shopDomain}", null);
                return response()->json(['error' => 'HMAC verification failed'], 401);
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

            $this->log('About to process webhook: ' . json_encode($webhookData), $orderId);

            // Create shop-specific services
            $orderProcessingService = $this->createOrderProcessingService($shop);
            $orderProcessingService->processOrder($adminGraphqlApiId);

            return response()->json(null, 200);
        } catch (\Exception $e) {
            $this->log('Error parsing webhook data: ' . json_encode($webhookData), $orderId);
            $this->log($e->getMessage(), $orderId);

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

    private function log(string $message, ?int $orderId = null): void
    {
        AuditLog::create([
            'event_name' => 'webhook',
            'event_ext' => $message,
            'order_id' => $orderId,
        ]);
    }
}
