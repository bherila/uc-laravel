<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\AuditLog;
use App\Models\CombineOperation;
use App\Models\CombineOperationLog;
use App\Services\Shopify\Dto\FulfillmentOrderLineItemInput;
use App\Services\Shopify\Dto\FulfillmentOrderMergeInputDto;
use App\Services\Shopify\Dto\FulfillmentOrderMergeInputMergeIntent;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing fulfillment order operations including merging.
 * 
 * This service handles combine shipping operations with full audit logging,
 * supporting both manual (admin-triggered) and webhook-triggered combines.
 */
class FulfillmentOrderService
{
    private int $startTime;

    public function __construct(
        private ShopifyFulfillmentService $fulfillmentService,
        private ShopifyOrderService $orderService
    ) {}

    /**
     * Combine (merge) fulfillment orders for a specific order.
     * 
     * This method creates a CombineOperation record with detailed logging,
     * fetches the order's shipping information, and attempts to merge
     * fulfillment orders that are at the same location.
     * 
     * @param string $orderId The Shopify order ID (URI format)
     * @param int|null $shopId The shop ID for logging
     * @param int|null $userId The user ID who triggered the combine (for admin-triggered)
     * @param int|null $webhookId The webhook ID (for webhook-triggered combines)
     * @return CombineOperation The combine operation record
     */
    public function combineFulfillmentOrders(
        string $orderId,
        ?int $shopId = null,
        ?int $userId = null,
        ?int $webhookId = null
    ): CombineOperation {
        $orderIdNumeric = $this->extractOrderIdNumeric($orderId);
        $orderIdUri = "gid://shopify/Order/{$orderIdNumeric}";
        $this->startTime = (int)(microtime(true) * 1000);

        // Create audit log entry (only for manual combines, not webhook-triggered)
        $auditLog = null;
        if ($webhookId === null) {
            $auditLog = AuditLog::create([
                'event_name' => 'order.combine_shipping',
                'event_ts' => now(),
                'event_userid' => $userId,
                'event_ext' => json_encode([
                    'order_id' => $orderIdUri,
                    'shop_id' => $shopId,
                    'status' => 'started',
                ]),
                'order_id' => $orderIdNumeric,
            ]);
        }

        // Create combine operation record
        $combineOperation = CombineOperation::create([
            'audit_log_id' => $auditLog?->id,
            'webhook_id' => $webhookId,
            'shop_id' => $shopId,
            'order_id' => $orderIdUri,
            'order_id_numeric' => $orderIdNumeric,
            'user_id' => $userId,
            'status' => 'pending',
        ]);

        try {
            $this->executeCombineOperation($orderIdUri, $combineOperation);

            // Update audit log with success (if exists)
            if ($auditLog) {
                $auditLog->update([
                    'event_ext' => json_encode([
                        'order_id' => $orderIdUri,
                        'shop_id' => $shopId,
                        'status' => 'success',
                        'combine_operation_id' => $combineOperation->id,
                        'original_shipping_method' => $combineOperation->original_shipping_method,
                        'fulfillment_orders_before' => $combineOperation->fulfillment_orders_before,
                        'fulfillment_orders_after' => $combineOperation->fulfillment_orders_after,
                    ]),
                ]);
            }

            return $combineOperation;
        } catch (\Exception $e) {
            $combineOperation->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            $this->logCombine($combineOperation, 'Error during combine operation: ' . $e->getMessage());

            // Update audit log with error (if exists)
            if ($auditLog) {
                $auditLog->update([
                    'event_ext' => json_encode([
                        'order_id' => $orderIdUri,
                        'shop_id' => $shopId,
                        'status' => 'error',
                        'combine_operation_id' => $combineOperation->id,
                        'error' => $e->getMessage(),
                    ]),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Execute the combine operation (fetch data, analyze, merge).
     */
    private function executeCombineOperation(string $orderIdUri, CombineOperation $combineOperation): void
    {
        // Fetch order to get original shipping line
        $orders = $this->orderService->getOrdersWithLineItems([$orderIdUri]);

        // Get fulfillment orders
        $fulfillmentOrdersBefore = $this->fulfillmentService->getFulfillmentOrders($orderIdUri);
        $combineOperation->update([
            'fulfillment_orders_before' => count($fulfillmentOrdersBefore),
        ]);

        $this->logCombine($combineOperation, 'Fetched order data from Shopify', [
            'found' => !empty($orders),
            'shipping_lines_count' => isset($orders[0]['shippingLines']['nodes']) ? count($orders[0]['shippingLines']['nodes']) : 0,
        ]);

        $shippingData = $this->orderService->identifyOriginalShippingMethod($orders[0] ?? [], $fulfillmentOrdersBefore);
        $originalShippingTitle = $shippingData['title'] ?? null;
        $originalShippingLineId = $shippingData['id'] ?? null;

        $combineOperation->update([
            'original_shipping_method' => $originalShippingTitle,
        ]);

        $this->logCombine($combineOperation, 'Identified original shipping method', [
            'original_shipping_method' => $originalShippingTitle,
            'shipping_line_id' => $originalShippingLineId,
        ]);

        $this->logCombine($combineOperation, 'Fetched fulfillment orders before merge', [
            'count' => count($fulfillmentOrdersBefore),
            'orders' => array_map(fn($fo) => [
                'id' => $fo['id'],
                'status' => $fo['status'],
                'deliveryMethod' => $fo['deliveryMethod']['presentedName'] ?? null,
            ], $fulfillmentOrdersBefore),
        ]);

        // Perform the merge with the original shipping title
        $this->tryMergeFulfillmentOrders($orderIdUri, $originalShippingTitle, $originalShippingLineId, $combineOperation);

        // Get fulfillment orders after merge
        $fulfillmentOrdersAfter = $this->fulfillmentService->getFulfillmentOrders($orderIdUri);
        $combineOperation->update([
            'fulfillment_orders_after' => count($fulfillmentOrdersAfter),
            'status' => 'success',
        ]);

        $this->logCombine($combineOperation, 'Combine operation completed successfully', [
            'fulfillment_orders_after' => count($fulfillmentOrdersAfter),
            'orders' => array_map(fn($fo) => [
                'id' => $fo['id'],
                'status' => $fo['status'],
                'deliveryMethod' => $fo['deliveryMethod']['presentedName'] ?? null,
            ], $fulfillmentOrdersAfter),
        ]);
    }

    /**
     * Try to merge fulfillment orders with detailed logging.
     */
    private function tryMergeFulfillmentOrders(
        string $orderIdUri,
        ?string $orderShippingTitle,
        ?string $orderShippingLineId,
        CombineOperation $combineOperation
    ): void {
        $fulfillmentOrders = $this->fulfillmentService->getFulfillmentOrders($orderIdUri);

        $this->logCombine($combineOperation, 'Found fulfillment orders', [
            'count' => count($fulfillmentOrders),
        ]);

        if (count($fulfillmentOrders) < 1) {
            $this->logCombine($combineOperation, 'No fulfillment orders to merge');
            return;
        }

        $openOrders = array_values(array_filter(
            $fulfillmentOrders,
            function ($fo) {
                if ($fo['status'] !== 'OPEN') {
                    return false;
                }
                // Only include if there's at least one item with quantity > 0
                foreach ($fo['lineItems']['nodes'] ?? [] as $li) {
                    if ((int)$li['totalQuantity'] > 0) {
                        return true;
                    }
                }
                return false;
            }
        ));

        $this->logCombine($combineOperation, 'Filtered to OPEN fulfillment orders with items', [
            'count' => count($openOrders),
        ]);

        if (count($openOrders) < 2) {
            $this->logCombine($combineOperation, 'Fewer than 2 OPEN fulfillment orders with items, nothing to merge');

            // If we have a singular OPEN order with a generic name, and a better name from the order's shipping lines,
            // we should rename the shipping line on the order which often updates the fulfillment order name.
            if (count($openOrders) === 1 && $orderShippingTitle && $orderShippingLineId) {
                $fo = $openOrders[0];
                $currentName = $fo['deliveryMethod']['presentedName'] ?? '';
                if (($currentName === 'Shipping' || $currentName === '') && $orderShippingTitle !== 'Shipping') {
                    $this->logCombine($combineOperation, "Attempting to rename singular OPEN fulfillment order to match original shipping method: {$orderShippingTitle}");
                    try {
                        $calcId = $this->orderService->beginEdit($orderIdUri);
                        if ($calcId) {
                            $this->orderService->updateShippingLine($calcId, $orderShippingLineId, $orderShippingTitle);
                            $this->orderService->commitEdit($calcId);
                            $this->logCombine($combineOperation, "Successfully renamed fulfillment order via order edit");
                        }
                    } catch (\Exception $e) {
                        $this->logCombine($combineOperation, "Failed to rename fulfillment order: " . $e->getMessage());
                    }
                }
            }
            return;
        }

        $mergeability = $this->analyzeMergeability($openOrders, $orderShippingTitle);

        $this->logCombine($combineOperation, 'Analyzed fulfillment orders for mergeability', [
            'areMergeable' => $mergeability['areMergeable'],
            'specificNames' => $mergeability['specificNames'],
            'orderDetails' => $mergeability['orderDetails'],
        ]);

        // Check if we can merge
        if (!$mergeability['areMergeable']) {
            $this->logCombine($combineOperation, 'Fulfillment orders are NOT mergeable - different locations or fulfillAt times');
            return;
        }

        if (count($mergeability['specificNames']) > 1) {
            $this->logCombine($combineOperation, 'Fulfillment orders have conflicting specific shipping methods', [
                'methods' => $mergeability['specificNames'],
            ]);
            return;
        }

        // Sort so non-shipping comes first (higher priority)
        usort($openOrders, fn($a, $b) => $this->compareByShippingPriority($a, $b));

        $mergeIntents = $this->buildMergeIntents($openOrders);

        $this->logCombine($combineOperation, 'Prepared merge intents', [
            'intents' => $mergeIntents,
        ]);

        $mergeInput = new FulfillmentOrderMergeInputDto(mergeIntents: $mergeIntents);

        // Execute the merge
        $mergeResult = $this->fulfillmentService->mergeFulfillmentOrders([$mergeInput]);

        // Log the Shopify API request and response
        CombineOperationLog::create([
            'combine_operation_id' => $combineOperation->id,
            'event' => 'Shopify fulfillmentOrderMerge API call',
            'time_taken_ms' => (int)(microtime(true) * 1000) - $this->startTime,
            'shopify_request' => json_encode(['fulfillmentOrderMergeInputs' => [$mergeInput->toArray()]]),
            'shopify_response' => json_encode($mergeResult),
        ]);

        $this->logCombine($combineOperation, 'Merge completed', [
            'result' => $mergeResult,
        ]);
    }

    /**
     * Analyze whether fulfillment orders can be merged.
     * 
     * @return array{areMergeable: bool, specificNames: array, orderDetails: array}
     */
    private function analyzeMergeability(array $openOrders, ?string $orderShippingTitle): array
    {
        $firstOrder = $openOrders[0];
        $locationId = $firstOrder['assignedLocation']['location']['id'] ?? null;
        $fulfillAt = $firstOrder['fulfillAt'];

        $areMergeable = true;
        $specificNames = [];
        $orderDetails = [];

        foreach ($openOrders as $fo) {
            $currentLocationId = $fo['assignedLocation']['location']['id'] ?? null;
            $currentFulfillAt = $fo['fulfillAt'];

            $orderDetails[] = [
                'id' => $fo['id'],
                'locationId' => $currentLocationId,
                'fulfillAt' => $currentFulfillAt,
                'deliveryMethod' => $fo['deliveryMethod']['presentedName'] ?? null,
            ];

            if ($currentLocationId !== $locationId || $currentFulfillAt !== $fulfillAt) {
                $areMergeable = false;
            }

            $name = $fo['deliveryMethod']['presentedName'] ?? '';

            // If name is generic "Shipping", try to resolve to order's shipping title
            if (($name === 'Shipping' || $name === '') && $orderShippingTitle) {
                $name = $orderShippingTitle;
            }

            if ($name !== 'Shipping' && $name !== '') {
                $specificNames[$name] = true;
            }
        }

        return [
            'areMergeable' => $areMergeable,
            'specificNames' => array_keys($specificNames),
            'orderDetails' => $orderDetails,
        ];
    }

    /**
     * Compare fulfillment orders for sorting - non-"Shipping" comes first.
     */
    private function compareByShippingPriority(array $a, array $b): int
    {
        $aName = $a['deliveryMethod']['presentedName'] ?? '';
        $bName = $b['deliveryMethod']['presentedName'] ?? '';

        $aIsShipping = ($aName === 'Shipping' || $aName === '') ? 1 : 0;
        $bIsShipping = ($bName === 'Shipping' || $bName === '') ? 1 : 0;

        return $aIsShipping - $bIsShipping;
    }

    /**
     * Build merge intents for the Shopify API.
     * 
     * @param array $openOrders
     * @return array<FulfillmentOrderMergeInputMergeIntent>
     */
    private function buildMergeIntents(array $openOrders): array
    {
        return array_map(function ($fo) {
            $lineItems = array_filter(
                $fo['lineItems']['nodes'] ?? [],
                fn($li) => (int)$li['totalQuantity'] > 0
            );

            return new FulfillmentOrderMergeInputMergeIntent(
                fulfillmentOrderId: $fo['id'],
                fulfillmentOrderLineItems: array_map(
                    fn($li) => new FulfillmentOrderLineItemInput(
                        id: $li['id'],
                        quantity: (int)$li['totalQuantity']
                    ),
                    array_values($lineItems)
                ),
            );
        }, $openOrders);
    }

    /**
     * Log a combine operation event.
     */
    private function logCombine(CombineOperation $combineOperation, string $event, ?array $data = null): void
    {
        $timeTaken = isset($this->startTime) ? (int)(microtime(true) * 1000) - $this->startTime : 0;

        CombineOperationLog::create([
            'combine_operation_id' => $combineOperation->id,
            'event' => $event . ($data ? ': ' . json_encode($data) : ''),
            'time_taken_ms' => $timeTaken,
        ]);
    }

    /**
     * Extract numeric order ID from Shopify order URI.
     */
    private function extractOrderIdNumeric(string $orderId): ?int
    {
        $numeric = str_replace('gid://shopify/Order/', '', $orderId);
        return is_numeric($numeric) ? (int)$numeric : null;
    }

    /**
     * Try to merge fulfillment orders (simplified version for webhook processing).
     * 
     * This is called during order processing and does not create a CombineOperation record.
     * It's a quick merge attempt without detailed logging.
     */
    public function tryQuickMerge(string $orderIdUri, ?string $orderShippingTitle = null, ?string $orderShippingLineId = null): void
    {
        try {
            $fulfillmentOrders = $this->fulfillmentService->getFulfillmentOrders($orderIdUri);

            if (count($fulfillmentOrders) < 1) {
                return;
            }

            // If shipping info is generic or missing, try to find better info
            if (!$orderShippingTitle || $orderShippingTitle === 'Shipping') {
                $orders = $this->orderService->getOrdersWithLineItems([$orderIdUri]);
                $shippingData = $this->orderService->identifyOriginalShippingMethod($orders[0] ?? [], $fulfillmentOrders);
                $orderShippingTitle = $shippingData['title'] ?? $orderShippingTitle;
                $orderShippingLineId = $shippingData['id'] ?? $orderShippingLineId;
            }

            $openOrders = array_values(array_filter(
                $fulfillmentOrders,
                function ($fo) {
                    if ($fo['status'] !== 'OPEN') {
                        return false;
                    }
                    foreach ($fo['lineItems']['nodes'] ?? [] as $li) {
                        if ((int)$li['totalQuantity'] > 0) {
                            return true;
                        }
                    }
                    return false;
                }
            ));

            if (count($openOrders) === 1 && $orderShippingTitle && $orderShippingLineId) {
                $fo = $openOrders[0];
                $currentName = $fo['deliveryMethod']['presentedName'] ?? '';
                if (($currentName === 'Shipping' || $currentName === '') && $orderShippingTitle !== 'Shipping') {
                    try {
                        $calcId = $this->orderService->beginEdit($orderIdUri);
                        if ($calcId) {
                            $this->orderService->updateShippingLine($calcId, $orderShippingLineId, $orderShippingTitle);
                            $this->orderService->commitEdit($calcId);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to quick rename fulfillment order: " . $e->getMessage());
                    }
                }
            }

            if (count($openOrders) < 2) {
                return;
            }

            $mergeability = $this->analyzeMergeability($openOrders, $orderShippingTitle);

            if (!$mergeability['areMergeable'] || count($mergeability['specificNames']) > 1) {
                return;
            }

            // Sort and merge
            usort($openOrders, fn($a, $b) => $this->compareByShippingPriority($a, $b));
            $mergeIntents = $this->buildMergeIntents($openOrders);

            $this->fulfillmentService->mergeFulfillmentOrders([
                new FulfillmentOrderMergeInputDto(mergeIntents: $mergeIntents),
            ]);
        } catch (\Exception $e) {
            Log::error("Fulfillment merge error for {$orderIdUri}: " . $e->getMessage());
        }
    }
}