<?php

declare(strict_types=1);

namespace App\Services\Offer;

use App\Models\Offer;
use App\Models\OfferManifest;
use App\Models\OrderToVariant;
use App\Services\Shopify\ShopifyProductService;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function __construct(
        private ShopifyProductService $shopifyProductService,
        private ShopifyOrderService $shopifyOrderService
    ) {
    }

    /**
     * Load all offers with their Shopify product data (paginated)
     *
     * @param int|null $shopId Filter by shop ID if provided
     * @param string|null $status Filter by archived status ('active', 'archived')
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function loadOfferList(?int $shopId = null, ?string $status = 'active', int $perPage = 25)
    {
        $query = Offer::with('shop:id,name,shop_domain')
            ->withCount('manifests')
            ->withCount([
                'manifests as allocated_manifests_count' => function ($q) {
                    $q->whereNotNull('assignee_id');
                }
            ])
            ->orderBy('offer_id', 'desc');

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        if ($status === 'archived') {
            $query->where('is_archived', true);
        } else {
            $query->where('is_archived', false);
        }

        $paginatedOffers = $query->paginate($perPage, ['offer_id', 'offer_name', 'offer_variant_id', 'offer_product_name', 'shop_id', 'is_archived']);

        $variantIds = collect($paginatedOffers->items())->pluck('offer_variant_id')->toArray();
        $offerProductData = $this->shopifyProductService->getProductDataByVariantIds($variantIds);

        $paginatedOffers->getCollection()->transform(function ($offer) use ($offerProductData) {
            $productData = $offerProductData[$offer->offer_variant_id] ?? null;
            return [
                'offer_id' => $offer->offer_id,
                'offer_name' => $offer->offer_name,
                'offer_product_name' => $offer->offer_product_name,
                'shop_id' => $offer->shop_id,
                'shop' => $offer->shop,
                'is_archived' => $offer->is_archived,
                'total_manifests_count' => (int) $offer->manifests_count,
                'allocated_manifests_count' => (int) $offer->allocated_manifests_count,
                'offerProductData' => $productData ? [
                    ...$productData,
                    'variantId' => $offer->offer_variant_id,
                    'sku' => $productData['sku'] ?? null,
                ] : null,
            ];
        });

        return $paginatedOffers;
    }

    /**
     * Archive or unarchive an offer
     *
     * @param int $offerId
     * @param bool $isArchived
     * @return void
     * @throws \RuntimeException
     */
    public function setArchived(int $offerId, bool $isArchived): void
    {
        if ($isArchived) {
            $offer = Offer::findOrFail($offerId);
            $productData = $this->shopifyProductService->getProductDataByVariantId($offer->offer_variant_id);

            $endDate = $productData['endDate'] ?? null;
            if (!$endDate || new \DateTime($endDate) > new \DateTime()) {
                throw new \RuntimeException('Only ended offers can be archived');
            }
        }

        Offer::where('offer_id', $offerId)->update(['is_archived' => $isArchived]);
    }

    /**
     * Get offers that have ended more than 30 days ago and are not archived.
     * 
     * @param int $shopId
     * @return \Illuminate\Support\Collection
     */
    public function getCleanupOffers(int $shopId)
    {
        $offers = Offer::where('shop_id', $shopId)
            ->where('is_archived', false)
            ->get(['offer_id', 'offer_variant_id']);

        if ($offers->isEmpty()) {
            return collect();
        }

        $variantIds = $offers->pluck('offer_variant_id')->toArray();
        $offerProductData = $this->shopifyProductService->getProductDataByVariantIds($variantIds);

        $thirtyDaysAgo = now()->subDays(30);

        return $offers->filter(function ($offer) use ($offerProductData, $thirtyDaysAgo) {
            $productData = $offerProductData[$offer->offer_variant_id] ?? null;
            $endDate = $productData['endDate'] ?? null;
            if (!$endDate)
                return false;

            try {
                return new \DateTime($endDate) < $thirtyDaysAgo;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Bulk archive offers that ended >30 days ago.
     * 
     * @param int $shopId
     * @return int Number of offers archived
     */
    public function cleanupOffers(int $shopId): int
    {
        $toArchive = $this->getCleanupOffers($shopId);
        if ($toArchive->isEmpty()) {
            return 0;
        }

        $ids = $toArchive->pluck('offer_id')->toArray();
        return Offer::whereIn('offer_id', $ids)->update(['is_archived' => true]);
    }

    /**
     * Create a new offer
     *
     * @param string $offerName
     * @param string $offerVariantId
     * @param string $offerProductName
     * @param int|null $shopId
     * @return Offer
     * @throws \RuntimeException
     */
    public function createOffer(string $offerName, string $offerVariantId, string $offerProductName, ?int $shopId = null): Offer
    {
        // Check if an offer with the same variant ID already exists
        $existingOffer = Offer::where('offer_variant_id', $offerVariantId)->first();

        if ($existingOffer) {
            throw new \RuntimeException("An offer for this variant ({$offerVariantId}) already exists");
        }

        return Offer::create([
            'offer_name' => $offerName,
            'offer_variant_id' => $offerVariantId,
            'offer_product_name' => $offerProductName,
            'shop_id' => $shopId,
        ]);
    }

    /**
     * Delete an offer and its unassigned manifests
     *
     * @param int $offerId
     * @return void
     * @throws \RuntimeException
     */
    public function deleteOffer(int $offerId): void
    {
        // Check for allocated manifests first
        $allocatedCount = OfferManifest::where('offer_id', $offerId)
            ->whereNotNull('assignee_id')
            ->count();

        if ($allocatedCount > 0) {
            throw new \RuntimeException('Failed to delete offer due to allocated manifests');
        }

        // Delete unassigned manifests
        OfferManifest::where('offer_id', $offerId)
            ->whereNull('assignee_id')
            ->delete();

        Offer::where('offer_id', $offerId)->delete();
    }

    /**
     * Get a single offer with its Shopify product data
     *
     * @param int $offerId
     * @return array|null
     */
    public function getOffer(int $offerId): ?array
    {
        $offer = Offer::with('shop:id,name,shop_domain')->find($offerId);

        if (!$offer) {
            return null;
        }

        $productData = $this->shopifyProductService->getProductDataByVariantId($offer->offer_variant_id);

        return [
            'offer_id' => $offer->offer_id,
            'offer_name' => $offer->offer_name,
            'offer_variant_id' => $offer->offer_variant_id,
            'offer_product_name' => $offer->offer_product_name,
            'shop_id' => $offer->shop_id,
            'shop' => $offer->shop,
            'offerProductData' => $productData,
        ];
    }

    /**
     * Get detailed offer with manifests grouped by variant
     *
     * @param int $offerId
     * @return array|null
     */
    public function getOfferDetail(int $offerId): ?array
    {
        $offer = Offer::with('shop:id,name,shop_domain')->find($offerId);

        if (!$offer) {
            return null;
        }

        // Get all manifests for this offer
        $manifests = OfferManifest::where('offer_id', $offerId)->get();

        // Group manifests by variant
        $manifestGroups = [];
        foreach ($manifests as $manifest) {
            $variantId = $manifest->mf_variant;
            if (!isset($manifestGroups[$variantId])) {
                $manifestGroups[$variantId] = [
                    'total' => 0,
                    'allocated' => 0,
                    'manifests' => [],
                ];
            }
            $manifestGroups[$variantId]['total']++;
            if ($manifest->assignee_id) {
                $manifestGroups[$variantId]['allocated']++;
            }
            $manifestGroups[$variantId]['manifests'][] = [
                'manifest_id' => $manifest->manifest_id,
                'assignee_id' => $manifest->assignee_id,
                'assignment_ordering' => $manifest->assignment_ordering,
            ];
        }

        // Get offer product data (the deal SKU)
        $offerProductData = $this->shopifyProductService->getProductDataByVariantId($offer->offer_variant_id);

        // Get manifest product data for all variants in manifests
        $manifestVariantIds = array_keys($manifestGroups);
        $manifestProductData = [];
        if (!empty($manifestVariantIds)) {
            $manifestProductData = $this->shopifyProductService->getProductDataByVariantIds($manifestVariantIds);
        }

        // Add qty to manifestProductData
        foreach ($manifestProductData as $variantId => &$data) {
            $data['qty'] = $manifestGroups[$variantId]['total'] ?? 0;
        }

        // Count unique order IDs (assignees)
        $orderIds = $manifests->pluck('assignee_id')->filter()->unique()->toArray();

        // Calculate deficit
        $unassignedCount = $manifests->filter(fn($m) => $m->assignee_id === null)->count();
        $inventoryQty = $offerProductData['inventoryQuantity'] ?? 0;
        $deficit = $unassignedCount - $inventoryQty;

        return [
            'offer_id' => $offer->offer_id,
            'offer_name' => $offer->offer_name,
            'offer_variant_id' => $offer->offer_variant_id,
            'offer_product_name' => $offer->offer_product_name,
            'shop_id' => $offer->shop_id,
            'shop' => $offer->shop,
            'offerProductData' => $offerProductData ? [
                ...$offerProductData,
                'variantId' => $offer->offer_variant_id,
            ] : null,
            'manifestGroups' => $manifestGroups,
            'manifestProductData' => $manifestProductData,
            'hasOrders' => count($orderIds) > 0,
            'orderCount' => count($orderIds),
            'unassignedCount' => $unassignedCount,
            'inventoryQty' => $inventoryQty,
            'deficit' => $deficit,
        ];
    }

    /**
     * Generate offer metafields data without writing to Shopify
     *
     * @param int $offerId
     * @return array|null
     */
    public function generateOfferMetafields(int $offerId): ?array
    {
        $offerDetail = $this->getOfferDetail($offerId);

        if (!$offerDetail || !$offerDetail['offerProductData']['productId']) {
            return null;
        }

        $manifestProductData = $offerDetail['manifestProductData'];

        // Calculate total quantity for percentage
        $totalQty = array_sum(array_column($manifestProductData, 'qty'));

        // Transform data to match required structure
        $transformedData = [];
        foreach ($manifestProductData as $variantId => $data) {
            $transformedData[$variantId] = [
                'featuredImageUrl' => $data['featuredImage'] ?? null,
                'maxVariantPriceAmount' => $data['priceRange']['maxVariantPrice']['amount'] ?? '0.0',
                'productId' => $data['productId'] ?? null,
                'qty' => $data['qty'] ?? 0,
                'variantInventoryQuantity' => $data['inventoryQuantity'] ?? 0,
                'percentChance' => $totalQty > 0 ? round((($data['qty'] ?? 0) / $totalQty) * 100, 2) : 0,
                'title' => $data['title'] ?? '',
                'weight' => $data['inventoryItem']['measurement']['weight']['value'] ?? 0,
                'unitCost' => $data['inventoryItem']['unitCost'] ?? null,
            ];
        }

        // Build offerV3 - the transformed map
        // We cast to object to ensure empty maps are {} instead of [] in JSON
        $offerV3 = json_encode((object) $transformedData);

        // Build offerV3Array - sorted items with maxPrice
        $items = array_values($transformedData);
        usort($items, function ($a, $b) {
            $priceA = (float) ($a['maxVariantPriceAmount'] ?? 0);
            $priceB = (float) ($b['maxVariantPriceAmount'] ?? 0);
            return $priceA <=> $priceB;
        });

        $maxPrice = 0;
        foreach ($items as $item) {
            $price = (float) ($item['maxVariantPriceAmount'] ?? 0);
            if ($price > $maxPrice) {
                $maxPrice = $price;
            }
        }

        $offerV3Array = json_encode([
            'items' => $items,
            'maxPrice' => $maxPrice > 0 ? $maxPrice : null,
        ]);

        return [
            'offerV3' => $offerV3,
            'offerV3Array' => $offerV3Array,
            'productId' => $offerDetail['offerProductData']['productId'],
        ];
    }

    /**
     * Update offer metafields in Shopify
     *
     * @param int $offerId
     * @return array|null
     */
    public function updateOfferMetafields(int $offerId): ?array
    {
        $data = $this->generateOfferMetafields($offerId);

        if (!$data) {
            return null;
        }

        $productId = $data['productId'];
        $offerV3 = $data['offerV3'];
        $offerV3Array = $data['offerV3Array'];

        // Write metafields to Shopify in a single call
        $this->shopifyProductService->writeProductMetafields($productId, [
            'offer_v3' => $offerV3,
            'offer_v3_array' => $offerV3Array,
        ]);

        return [
            'offerV3' => $offerV3,
            'offerV3Array' => $offerV3Array,
        ];
    }

    /**
     * Get orders for an offer with manifest details
     *
     * @param int $offerId
     * @return array|null
     */
    public function getOfferOrders(int $offerId): ?array
    {
        $offer = Offer::with('shop:id,name,shop_domain')->find($offerId);

        if (!$offer) {
            return null;
        }

        // Total manifests available for this offer (total bottles in manifests)
        $totalManifestQty = OfferManifest::where('offer_id', $offerId)->count();

        // Get order IDs linked to this offer's variant
        $orderVariants = OrderToVariant::where('variant_id', $offer->offer_variant_id)
            ->select('order_id', 'variant_id')
            ->get();

        if ($orderVariants->isEmpty()) {
            return [
                'offer_id' => $offerId,
                'offer_name' => $offer->offer_name,
                'variant_id' => $offer->offer_variant_id,
                'shop_id' => $offer->shop_id,
                'shop' => $offer->shop,
                'orders' => [],
                'totals' => [
                    'orderCount' => 0,
                    'purchasedQty' => 0,
                    'upgradeQty' => 0,
                    'purchasedValue' => 0,
                    'upgradeValue' => 0,
                    'upgradeCost' => 0,
                    'totalManifests' => (int) $totalManifestQty,
                ],
            ];
        }

        $orderIds = $orderVariants->pluck('order_id')->toArray();

        // Get offer detail to get manifest product data
        $offerDetail = $this->getOfferDetail($offerId);
        $manifestProductData = $offerDetail['manifestProductData'] ?? [];

        // Get orders from Shopify
        $shopifyOrders = $this->shopifyOrderService->getOrdersWithLineItems($orderIds);

        // Process orders
        $processedOrders = [];
        $totals = [
            'orderCount' => count($shopifyOrders),
            'purchasedQty' => 0,
            'upgradeQty' => 0,
            'purchasedValue' => 0,
            'upgradeValue' => 0,
            'upgradeCost' => 0,
            'totalManifests' => (int) $totalManifestQty,
        ];

        foreach ($shopifyOrders as $order) {
            $lineItems = $order['lineItems_nodes'] ?? [];

            // Separate purchased items (paid for) from upgrade items (free upgrades)
            $purchasedItems = array_filter($lineItems, fn($li) => $li['discountedTotalSet_shopMoney_amount'] > 0);
            $upgradeItems = array_filter($lineItems, fn($li) => $li['discountedTotalSet_shopMoney_amount'] <= 0);

            // Calculate totals
            $purchasedQty = array_sum(array_map(fn($li) => $li['currentQuantity'], $purchasedItems));
            $upgradeQty = 0;
            foreach ($upgradeItems as $li) {
                $variantId = $li['variant_variant_graphql_id'];
                $unitCost = (float) ($manifestProductData[$variantId]['inventoryItem']['unitCost']['amount'] ?? 0);
                $originalPrice = $li['originalUnitPriceSet_shopMoney_amount'];
                // Free items don't count against allocation if both price and cost are 0
                $isFreeItem = $originalPrice == 0 && $unitCost == 0;
                if (!$isFreeItem) {
                    $upgradeQty += $li['currentQuantity'];
                }
            }

            $purchasedValue = array_sum(array_map(
                fn($li) => $li['originalUnitPriceSet_shopMoney_amount'] * $li['currentQuantity'],
                $purchasedItems
            ));
            $upgradeValue = array_sum(array_map(
                fn($li) => $li['originalUnitPriceSet_shopMoney_amount'] * $li['currentQuantity'],
                $upgradeItems
            ));
            $upgradeCost = 0;
            foreach ($upgradeItems as $li) {
                $variantId = $li['variant_variant_graphql_id'];
                $unitCost = (float) ($manifestProductData[$variantId]['inventoryItem']['unitCost']['amount'] ?? 0);
                $upgradeCost += $unitCost * $li['currentQuantity'];
            }

            $isQtyEqual = $purchasedQty === $upgradeQty;

            $processedOrders[] = [
                'id' => $order['id'],
                'createdAt' => $order['createdAt'],
                'email' => $order['email'],
                'displayFinancialStatus' => $order['displayFinancialStatus'],
                'displayFulfillmentStatus' => $order['displayFulfillmentStatus'],
                'cancelledAt' => $order['cancelledAt'],
                'totalPrice' => $order['totalPriceSet_shopMoney_amount'],
                'purchasedItems' => array_values($purchasedItems),
                'upgradeItems' => array_values($upgradeItems),
                'purchasedQty' => $purchasedQty,
                'upgradeQty' => $upgradeQty,
                'purchasedValue' => $purchasedValue,
                'upgradeValue' => $upgradeValue,
                'upgradeCost' => $upgradeCost,
                'isQtyEqual' => $isQtyEqual,
                'fulfillments_nodes' => $order['fulfillments_nodes'],
                'fulfillmentOrders_nodes' => $order['fulfillmentOrders_nodes'],
            ];

            // Accumulate totals
            $totals['purchasedQty'] += $purchasedQty;
            $totals['upgradeQty'] += $upgradeQty;
            $totals['purchasedValue'] += $purchasedValue;
            $totals['upgradeValue'] += $upgradeValue;
            $totals['upgradeCost'] += $upgradeCost;
        }

        // Sort: non-equal qty first, then by upgrade value descending
        usort($processedOrders, function ($a, $b) {
            if ($a['isQtyEqual'] !== $b['isQtyEqual']) {
                return $a['isQtyEqual'] ? 1 : -1;
            }
            return $b['upgradeValue'] <=> $a['upgradeValue'];
        });

        return [
            'offer_id' => $offerId,
            'offer_name' => $offer->offer_name,
            'variant_id' => $offer->offer_variant_id,
            'shop_id' => $offer->shop_id,
            'shop' => $offer->shop,
            'orders' => $processedOrders,
            'totals' => $totals,
        ];
    }

    /**
     * Flush caches for all products/variants associated with an offer
     *
     * @param int $offerId
     * @return void
     */
    public function forceReload(int $offerId): void
    {
        $offer = Offer::findOrFail($offerId);

        // 1. Clear deal SKU variant cache
        $this->shopifyProductService->clearVariantCaches($offer->offer_variant_id);

        // 2. Clear all manifest item variant caches
        $manifestVariants = OfferManifest::where('offer_id', $offerId)
            ->distinct()
            ->pluck('mf_variant');

        foreach ($manifestVariants as $variantId) {
            $this->shopifyProductService->clearVariantCaches($variantId);
        }

        // 3. Ensure general product lists are cleared (in case offer has no manifests yet)
        $this->shopifyProductService->clearProductCaches($offer->offer_variant_id);
    }

    /**
     * Generate 1011 report data for an offer
     *
     * @param int $offerId
     * @return array|null
     */
    public function generate1011Report(int $offerId): ?array
    {
        $offer = Offer::with('shop:id,name,shop_domain')->find($offerId);

        if (!$offer) {
            return null;
        }

        // Get order IDs linked to this offer's variant
        $orderVariants = OrderToVariant::where('variant_id', $offer->offer_variant_id)
            ->select('order_id', 'variant_id')
            ->get();

        if ($orderVariants->isEmpty()) {
            return [
                'offer_id' => $offerId,
                'offer_name' => $offer->offer_name,
                'variant_id' => $offer->offer_variant_id,
                'shop_id' => $offer->shop_id,
                'shop' => $offer->shop,
                'rows' => [],
            ];
        }

        $orderIds = $orderVariants->pluck('order_id')->toArray();

        // Get orders from Shopify with extended customer details
        $shopifyOrders = $this->shopifyOrderService->getOrdersWithLineItems($orderIds, true);

        // Process each order and create rows for the 1011 report
        $rows = [];
        foreach ($shopifyOrders as $order) {
            $lineItems = $order['lineItems_nodes'] ?? [];
            
            foreach ($lineItems as $lineItem) {
                // Extract order number from name (e.g., "#1234")
                $orderName = $order['name'] ?? '';
                $orderNumber = ltrim($orderName, '#');
                
                // Sales Order Key = Order Number + Item SKU
                $itemSku = $lineItem['sku'] ?? '';
                $salesOrderKey = $orderNumber . '_' . $itemSku;
                
                // Get customer birthday
                $birthday = $order['customer']['birthday'] ?? '';
                
                // Get billing address
                $billingAddress = $order['billingAddress'] ?? [];
                
                // Get shipping address
                $shippingAddress = $order['shippingAddress'] ?? [];
                
                // Get fulfillment info
                $fulfillments = $order['fulfillments_nodes'] ?? [];
                $carrierService = '';
                $shipDate = '';
                $trackingNumbers = [];
                
                foreach ($fulfillments as $fulfillment) {
                    if (!empty($fulfillment['trackingInfo'])) {
                        foreach ($fulfillment['trackingInfo'] as $tracking) {
                            if (!empty($tracking['company'])) {
                                $carrierService = $tracking['company'];
                            }
                            if (!empty($tracking['number'])) {
                                $trackingNumbers[] = $tracking['number'];
                            }
                        }
                        if (!empty($fulfillment['createdAt'])) {
                            $shipDate = $fulfillment['createdAt'];
                        }
                    }
                }
                
                // Join tracking numbers with semicolon
                $trackingNumber = implode(';', $trackingNumbers);
                
                // Get discount percentage
                $discountPercent = '';
                $discountApplications = $order['discountApplications'] ?? [];
                foreach ($discountApplications as $app) {
                    if (isset($app['value']['percentage'])) {
                        $discountPercent = $app['value']['percentage'];
                        break;
                    }
                }
                
                // Map fulfillment status to shipment status enum
                $fulfillmentStatus = $order['fulfillmentStatus'] ?? 'UNFULFILLED';
                $shipmentStatus = match($fulfillmentStatus) {
                    'FULFILLED' => 'Shipped',
                    'PARTIAL' => 'InProcess',
                    'UNFULFILLED' => 'Open',
                    'RESTOCKED' => 'Cancelled',
                    'SCHEDULED' => 'Pending',
                    default => 'Open'
                };
                
                // Get gift note from customAttributes or order note
                $giftNote = '';
                foreach ($lineItem['customAttributes'] ?? [] as $attr) {
                    if (strtolower($attr['key']) === 'gift_note' || strtolower($attr['key']) === 'giftnote') {
                        $giftNote = $attr['value'];
                        break;
                    }
                }
                if (empty($giftNote)) {
                    $giftNote = $order['note'] ?? '';
                }
                
                // Get weight - prefer variant weight, fallback to inventoryItem measurement
                $weight = $lineItem['variant_weight'] ?? 
                         $lineItem['variant_inventoryItem_measurement_weight_value'] ?? '';
                
                $rows[] = [
                    'Version' => '1011',
                    'Company' => '',
                    'Sales Order Key' => $salesOrderKey,
                    'Fulfillment Account Key' => '',
                    'Shipment Key' => '',
                    'Club Type' => '',
                    'Sub Club' => '',
                    'Pickup' => '',
                    '3 Tier' => '',
                    'Tags' => 'Add-20133',
                    'Reserved 1' => '',
                    'Reserved 2' => '',
                    'Reserved 3' => '',
                    'Billing Last Name' => $billingAddress['lastName'] ?? '',
                    'Billing First Name' => $billingAddress['firstName'] ?? '',
                    'Billing Company' => $billingAddress['company'] ?? '',
                    'Billing Address 1' => $billingAddress['address1'] ?? '',
                    'Billing Address 2' => $billingAddress['address2'] ?? '',
                    'Billing City' => $billingAddress['city'] ?? '',
                    'Billing State' => $billingAddress['provinceCode'] ?? '',
                    'Billing Zip' => $billingAddress['zip'] ?? '',
                    'Billing Date Of Birth' => $birthday,
                    'Billing Email' => $order['email'] ?? '',
                    'Billing Phone' => $billingAddress['phone'] ?? '',
                    'Sales Type' => 'Offsite',
                    'Order Type' => 'Internet',
                    'Customer Number' => '',
                    'Payment Date' => $order['processedAt'] ?? '',
                    'Shipping Last Name' => $shippingAddress['lastName'] ?? '',
                    'Shipping First Name' => $shippingAddress['firstName'] ?? '',
                    'Shipping Company' => $shippingAddress['company'] ?? '',
                    'Shipping Address 1' => $shippingAddress['address1'] ?? '',
                    'Shipping Address 2' => $shippingAddress['address2'] ?? '',
                    'Shipping City' => $shippingAddress['city'] ?? '',
                    'Shipping State' => $shippingAddress['provinceCode'] ?? '',
                    'Shipping Zip' => $shippingAddress['zip'] ?? '',
                    'Shipping County' => $shippingAddress['province'] ?? '',
                    'Shipping Date Of Birth' => $birthday,
                    'Shipping Email' => $order['email'] ?? '',
                    'Shipping Phone' => $shippingAddress['phone'] ?? '',
                    'Carrier Service' => $carrierService,
                    'Ship Date' => $shipDate,
                    'Freight Cost' => (string)$order['totalShippingPriceSet_shopMoney_amount'],
                    'Tracking Number' => $trackingNumber,
                    'Sample Type' => '',
                    'Age Check ID' => '',
                    'Discount Amount' => (string)$order['currentTotalDiscountsSet_shopMoney_amount'],
                    'Discount %' => $discountPercent,
                    'RDBI' => '',
                    'Compliant' => '',
                    'Compliance Results' => '',
                    'Fulfillment House' => '',
                    'Shipment Status' => $shipmentStatus,
                    'License Relationship' => 'Default',
                    'Insured Amount' => '',
                    'Sales Tax Charged' => (string)$order['currentTotalTaxSet_shopMoney_amount'],
                    'Handling Fees' => '',
                    'Gift Note' => $giftNote,
                    'Special Instructions' => $order['note'] ?? '',
                    'Brand Key' => '',
                    'Product Key' => $itemSku,
                    'Quantity' => (string)$lineItem['currentQuantity'],
                    'Unit Price' => (string)$lineItem['originalUnitPriceSet_shopMoney_amount'],
                    'Weight' => (string)$weight,
                ];
            }
        }

        return [
            'offer_id' => $offerId,
            'offer_name' => $offer->offer_name,
            'variant_id' => $offer->offer_variant_id,
            'shop_id' => $offer->shop_id,
            'shop' => $offer->shop,
            'rows' => $rows,
        ];
    }
}
