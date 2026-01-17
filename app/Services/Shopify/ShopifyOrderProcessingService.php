<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\AuditLog;
use App\Models\Offer;
use App\Models\OfferManifest;
use App\Models\OrderLock;
use App\Models\OrderToVariant;
use Illuminate\Support\Facades\DB;

/**
 * Service for processing Shopify orders - allocates manifest items to orders
 */
class ShopifyOrderProcessingService
{
    private const SHOULD_REPICK_ALL_BOTTLES = true;

    private ?int $currentOfferId = null;
    private ?int $currentOrderIdNumeric = null;
    private int $startTime;
    /** @var array<string> */
    private array $logBuffer = [];

    public function __construct(
        private ShopifyClient $client,
        private ShopifyOrderService $orderService,
        private ShopifyOrderEditService $orderEditService,
        private ShopifyFulfillmentService $fulfillmentService,
        private ShopifyProductService $productService
    ) {}

    /**
     * Process a Shopify order - main entry point
     */
    public function processOrder(string $orderId): void
    {
        $orderIdNumeric = $this->extractOrderIdNumeric($orderId);
        $orderIdUri = "gid://shopify/Order/{$orderIdNumeric}";

        // Check for existing lock
        $existingLock = OrderLock::find($orderIdUri);
        $now = now();
        $fiveMinutesAgo = $now->copy()->subMinutes(5);

        if ($existingLock && $existingLock->locked_at > $fiveMinutesAgo) {
            \Log::info("Order {$orderId} is already being processed, skipping");
            return;
        }

        // Acquire lock
        OrderLock::updateOrCreate(
            ['order_id' => $orderIdUri],
            ['locked_at' => $now]
        );

        $this->startTime = (int)(microtime(true) * 1000);

        try {
            $this->processOrderInternal($orderId);
        } finally {
            // Flush any pending logs
            $this->flushLogs();

            // Release lock
            OrderLock::where('order_id', $orderIdUri)->delete();
        }

        $elapsed = (int)(microtime(true) * 1000) - $this->startTime;
        \Log::info("Order processing done in {$elapsed}ms");
    }

    private function processOrderInternal(string $orderId): void
    {
        $this->currentOrderIdNumeric = $this->extractOrderIdNumeric($orderId);
        $orderIdUri = "gid://shopify/Order/{$this->currentOrderIdNumeric}";

        // Fetch order from Shopify
        $orders = $this->orderService->getOrdersWithLineItems([$orderIdUri]);
        if (empty($orders)) {
            $this->pushLog("Order {$orderIdUri} not found");
            return;
        }

        $shopifyOrder = $orders[0];

        if ($shopifyOrder['cancelledAt'] !== null) {
            $this->pushLog("Order {$orderIdUri} is cancelled, skipping processing.");
            return;
        }

        // Build map of deal line items by variant
        $variant2DealItemMap = [];
        foreach ($shopifyOrder['lineItems_nodes'] as $lineItem) {
            if (in_array('deal', $lineItem['product_tags'] ?? [])) {
                $variantId = $lineItem['variant_variant_graphql_id'];
                if (!$variantId) {
                    continue;
                }

                if (isset($variant2DealItemMap[$variantId])) {
                    $variant2DealItemMap[$variantId]['currentQuantity'] += $lineItem['currentQuantity'];
                } else {
                    $variant2DealItemMap[$variantId] = $lineItem;
                }
            }
        }

        $this->pushLog("Found " . count($variant2DealItemMap) . " deal line items");

        // Get offers and build variant->offer mapping
        $offers = Offer::all(['offer_id', 'offer_variant_id'])->keyBy('offer_variant_id');
        $offerIdFromVariantId = $offers->mapWithKeys(fn($offer) => [$offer->offer_variant_id => $offer->offer_id])->toArray();

        // Update v3_order_to_variant
        foreach ($variant2DealItemMap as $variantId => $item) {
            $offerId = $offerIdFromVariantId[$variantId] ?? null;
            if ($offerId) {
                OrderToVariant::updateOrCreate(
                    ['order_id' => $orderIdUri, 'variant_id' => $variantId],
                    ['offer_id' => $offerId]
                );
            }
        }

        // Process each deal variant
        foreach ($variant2DealItemMap as $variantId => $orderLineItem) {
            $purchasedDealVariantUri = $orderLineItem['variant_variant_graphql_id'];

            // Skip free items
            if (!$orderLineItem['discountedTotalSet_shopMoney_amount']) {
                $this->pushLog("Skip free item " . $orderLineItem['line_item_id']);
                continue;
            }

            $this->currentOfferId = $offerIdFromVariantId[$variantId] ?? null;

            if (!$this->currentOfferId) {
                $this->pushLog("No match to offer for variant {$variantId}");
                continue;
            }

            $this->pushLog("Match offer_id: {$this->currentOfferId} for variant {$variantId}");

            // Get existing manifests for this order/offer
            $alreadyHaveQty = OfferManifest::where('assignee_id', $orderIdUri)
                ->where('offer_id', $this->currentOfferId)
                ->count();

            $needQty = $shopifyOrder['cancelledAt'] === null
                ? $orderLineItem['currentQuantity'] - $alreadyHaveQty
                : -$alreadyHaveQty;

            $this->pushLog("{$alreadyHaveQty} already allocated, need {$needQty} more");

            // Maybe repick all bottles
            if (self::SHOULD_REPICK_ALL_BOTTLES && $needQty > 0 && $alreadyHaveQty > 0) {
                $rowsReverted = OfferManifest::where('assignee_id', $orderIdUri)
                    ->where('offer_id', $this->currentOfferId)
                    ->orderBy('assignment_ordering')
                    ->limit($alreadyHaveQty)
                    ->update(['assignee_id' => null]);

                $this->pushLog("Reverted {$rowsReverted} bottles due to REPICK");

                // Reshuffle all available bottles
                $reshuffleQty = OfferManifest::where('offer_id', $this->currentOfferId)
                    ->whereNull('assignee_id')
                    ->update(['assignment_ordering' => DB::raw('RAND()')]);

                $this->pushLog("Reshuffled {$reshuffleQty} unpicked bottles");

                $needQty += $alreadyHaveQty;
            }

            if ($needQty > 0) {
                // Allocate bottles
                $rowsAffected = DB::update(
                    "UPDATE v3_offer_manifest SET assignee_id = ? WHERE offer_id = ? AND assignee_id IS NULL ORDER BY assignment_ordering LIMIT ?",
                    [$orderIdUri, $this->currentOfferId, $needQty]
                );

                if ($rowsAffected < $needQty) {
                    // Not enough bottles - revert and cancel
                    $rowsReverted = OfferManifest::where('assignee_id', $orderIdUri)
                        ->where('offer_id', $this->currentOfferId)
                        ->orderBy('assignment_ordering')
                        ->limit($rowsAffected)
                        ->update(['assignee_id' => null]);

                    $this->pushLog("Reverted {$rowsReverted} rows due to insufficient allocation");

                    // Cancel order
                    $this->pushLog("Attempting to cancel order {$orderIdUri}");
                    try {
                        $this->orderService->cancelOrder($orderIdUri);
                    } catch (\Exception $e) {
                        $this->pushLog("Cancel error: " . $e->getMessage());
                    }

                    // Set variant quantity to 0
                    try {
                        $this->productService->setVariantQuantity($purchasedDealVariantUri, 0);
                    } catch (\Exception $e) {
                        $this->pushLog("Set quantity error: " . $e->getMessage());
                    }
                }
            } elseif ($needQty < 0) {
                // Release bottles
                $rowsAffected = DB::update(
                    "UPDATE v3_offer_manifest SET assignee_id = NULL WHERE assignee_id = ? AND offer_id = ? ORDER BY assignment_ordering LIMIT ?",
                    [$orderIdUri, $this->currentOfferId, -$needQty]
                );

                $this->pushLog("Released {$rowsAffected} bottles");
            }
        }

        // Now update the Shopify order with manifest items
        $this->syncOrderLineItems($orderIdUri, $shopifyOrder);
    }

    private function syncOrderLineItems(string $orderIdUri, array $shopifyOrder): void
    {
        // Get allocated manifests for this order
        $offerManifests = OfferManifest::where('assignee_id', $orderIdUri)
            ->where('offer_id', $this->currentOfferId)
            ->orderBy('mf_variant')
            ->get();

        // Sanity check - for now just log
        $totalQty = $offerManifests->count();
        $this->pushLog("Total manifests allocated: {$totalQty}");

        // Begin order edit
        $editResult = $this->orderEditService->beginEdit($orderIdUri);
        $calculatedOrderId = $editResult['calculatedOrderId'];

        if (!$calculatedOrderId) {
            $this->pushLog('CalculatedOrderId was null, aborting');
            return;
        }

        $this->pushLog("Opened CalculatedOrder {$calculatedOrderId}");

        // Get existing manifest items in order
        $preExistingManifests = array_filter(
            $editResult['editableLineItems'],
            fn($item) => in_array('manifest-item', $item['productTags'])
        );

        // Group manifests by variant
        $manifestsByVariant = [];
        foreach ($offerManifests as $manifest) {
            $variantId = $manifest->mf_variant;
            if (!isset($manifestsByVariant[$variantId])) {
                $manifestsByVariant[$variantId] = [];
            }
            $manifestsByVariant[$variantId][] = $manifest;
        }

        // Build actions
        $actions = [];
        $allVariantIds = array_unique(array_merge(
            array_keys($manifestsByVariant),
            array_map(fn($item) => $item['variantId'], $preExistingManifests)
        ));

        foreach ($allVariantIds as $variantId) {
            $desiredQty = count($manifestsByVariant[$variantId] ?? []);
            $existing = array_filter($preExistingManifests, fn($item) => $item['variantId'] === $variantId);
            $combinedExistingQty = array_sum(array_map(fn($item) => $item['quantity'], $existing));

            if ($combinedExistingQty === $desiredQty) {
                continue; // No change needed
            }

            if ($desiredQty === 0) {
                foreach ($existing as $item) {
                    $actions[] = [
                        'updateLineItemId' => $item['calculatedLineItemId'],
                        'qty' => 0,
                        'variantId' => $variantId,
                    ];
                }
            } elseif ($combinedExistingQty === 0) {
                $actions[] = [
                    'qty' => $desiredQty,
                    'variantId' => $variantId,
                ];
            } else {
                $existingArray = array_values($existing);
                $actions[] = [
                    'updateLineItemId' => $existingArray[0]['calculatedLineItemId'],
                    'qty' => $desiredQty,
                    'variantId' => $variantId,
                ];

                // Remove extra line items
                for ($i = 1; $i < count($existingArray); $i++) {
                    $actions[] = [
                        'updateLineItemId' => $existingArray[$i]['calculatedLineItemId'],
                        'qty' => 0,
                        'variantId' => $variantId,
                    ];
                }
            }
        }

        $this->pushLog('Calculated orderEdit actions: ' . json_encode($actions));

        if (count($actions) > 0) {
            // Perform additions first to preserve shipping method
            $additions = array_filter($actions, fn($a) => !isset($a['updateLineItemId']));
            $modifications = array_filter($actions, fn($a) => isset($a['updateLineItemId']));
            $executionActions = array_merge(array_values($additions), array_values($modifications));

            foreach ($executionActions as $action) {
                if (isset($action['updateLineItemId'])) {
                    $this->orderEditService->setLineItemQuantity(
                        $calculatedOrderId,
                        $action['updateLineItemId'],
                        $action['qty']
                    );
                    $this->pushLog("Updated line item to qty {$action['qty']}");
                } else {
                    $addResult = $this->orderEditService->addVariant(
                        $calculatedOrderId,
                        $action['variantId'],
                        $action['qty']
                    );
                    $this->pushLog("Added variant {$action['variantId']} with qty {$action['qty']}");

                    // Add 100% discount
                    if (isset($addResult['calculatedLineItem']['id'])) {
                        $this->orderEditService->addDiscount(
                            $calculatedOrderId,
                            $addResult['calculatedLineItem']['id'],
                            [
                                'percentValue' => 100,
                                'description' => 'UPGRADED',
                            ]
                        );
                        $this->pushLog('Added 100% discount');
                    }
                }
            }

            // Commit the edit
            $commitResult = $this->orderEditService->commit($calculatedOrderId);
            $this->pushLog('orderEditCommit: ' . json_encode($commitResult));

            // Try to merge fulfillment orders
            $this->tryMergeFulfillmentOrders($orderIdUri);
        } else {
            $this->pushLog('SKIP orderEditCommit - Nothing to do');
        }
    }

    private function tryMergeFulfillmentOrders(string $orderIdUri): void
    {
        try {
            $fulfillmentOrders = $this->fulfillmentService->getFulfillmentOrders($orderIdUri);
            $this->pushLog("Found " . count($fulfillmentOrders) . " fulfillment orders");

            if (count($fulfillmentOrders) > 1) {
                $openOrders = array_filter($fulfillmentOrders, fn($fo) => $fo['status'] === 'OPEN');

                if (count($openOrders) > 1) {
                    $openOrders = array_values($openOrders);
                    $firstOrder = $openOrders[0];
                    $locationId = $firstOrder['assignedLocation']['location']['id'] ?? null;
                    $fulfillAt = $firstOrder['fulfillAt'];

                    $areMergeable = true;
                    $hasNonShipping = false;

                    foreach ($openOrders as $fo) {
                        if (($fo['assignedLocation']['location']['id'] ?? null) !== $locationId
                            || $fo['fulfillAt'] !== $fulfillAt) {
                            $areMergeable = false;
                            break;
                        }
                        if (($fo['deliveryMethod']['presentedName'] ?? '') !== 'Shipping') {
                            $hasNonShipping = true;
                        }
                    }

                    if ($areMergeable && $hasNonShipping) {
                        $this->pushLog('Fulfillment orders are mergeable. Merging now.');

                        // Sort so non-shipping comes first
                        usort($openOrders, function ($a, $b) {
                            $aIsShipping = ($a['deliveryMethod']['presentedName'] ?? '') === 'Shipping' ? 1 : 0;
                            $bIsShipping = ($b['deliveryMethod']['presentedName'] ?? '') === 'Shipping' ? 1 : 0;
                            return $aIsShipping - $bIsShipping;
                        });

                        $mergeIntents = array_map(fn($fo) => [
                            'fulfillmentOrderId' => $fo['id'],
                            'fulfillmentOrderLineItems' => array_map(
                                fn($li) => ['id' => $li['id'], 'quantity' => $li['totalQuantity']],
                                $fo['lineItems']['nodes'] ?? []
                            ),
                        ], $openOrders);

                        $mergeResult = $this->fulfillmentService->mergeFulfillmentOrders([
                            ['mergeIntents' => $mergeIntents],
                        ]);

                        $this->pushLog('Merge result: ' . json_encode($mergeResult));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->pushLog('Fulfillment merge error: ' . $e->getMessage());
        }
    }

    private function extractOrderIdNumeric(string $orderId): ?int
    {
        $numeric = str_replace('gid://shopify/Order/', '', $orderId);
        return is_numeric($numeric) ? (int)$numeric : null;
    }

    private function pushLog(string $message): void
    {
        $this->logBuffer[] = $message;
        \Log::info('[shopifyProcessOrder] ' . $message);
    }

    private function flushLogs(): void
    {
        foreach ($this->logBuffer as $message) {
            AuditLog::create([
                'event_name' => 'shopifyProcessOrder',
                'event_ext' => $message,
                'offer_id' => $this->currentOfferId,
                'order_id' => $this->currentOrderIdNumeric,
                'time_taken_ms' => (int)(microtime(true) * 1000) - $this->startTime,
            ]);
        }
        $this->logBuffer = [];
    }
}
