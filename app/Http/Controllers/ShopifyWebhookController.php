<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\Shopify\ShopifyOrderProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyWebhookController extends Controller
{
    public function __construct(
        private ShopifyOrderProcessingService $orderProcessingService
    ) {}

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

            // Process the order
            $this->orderProcessingService->processOrder($adminGraphqlApiId);

            return response()->json(null, 200);
        } catch (\Exception $e) {
            $this->log('Error parsing webhook data: ' . json_encode($webhookData), $orderId);
            $this->log($e->getMessage(), $orderId);

            return response()->json(null, 400);
        }
    }

    /**
     * Verify Shopify webhook HMAC signature
     */
    public function verifyHmac(Request $request): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $secret = config('services.shopify.webhook_secret');

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
