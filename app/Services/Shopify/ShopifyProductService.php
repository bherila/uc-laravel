<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

/**
 * Service for product and variant operations in Shopify
 */
class ShopifyProductService
{
    private const GQL_GET_PRODUCT_DATA = <<<'GRAPHQL'
        query GetProductData($IDs: [ID!]!) {
            nodes(ids: $IDs) {
                ... on ProductVariant {
                    id
                    inventoryQuantity
                    inventoryItem {
                        id
                        tracked
                        measurement {
                            id
                            weight {
                                unit
                                value
                            }
                        }
                        unitCost {
                            amount
                            currencyCode
                        }
                    }
                    product {
                        id
                        title
                        priceRangeV2 {
                            maxVariantPrice {
                                amount
                                currencyCode
                            }
                            minVariantPrice {
                                amount
                                currencyCode
                            }
                        }
                        featuredImage {
                            url(transform: { maxWidth: 500, preferredContentType: WEBP })
                        }
                        metafields(keys: ["custom.end_date", "custom.start_date"], first: 10) {
                            nodes {
                                key
                                jsonValue
                            }
                        }
                        status
                        tags
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_GET_VARIANT_DETAIL = <<<'GRAPHQL'
        query GenShopifyDetail($id: ID!) {
            node(id: $id) {
                ... on ProductVariant {
                    inventoryQuantity
                    inventoryItem {
                        tracked
                    }
                    product {
                        title
                    }
                    inventoryItem {
                        id
                        measurement {
                            id
                            weight {
                                unit
                                value
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_WRITE_PRODUCT_METAFIELD = <<<'GRAPHQL'
        mutation UpdateProductMetafield($productId: ID!, $key: String!, $value: String!) {
            productUpdate(input: {
                id: $productId,
                metafields: [
                    {
                        namespace: "custom",
                        key: $key,
                        value: $value,
                        type: "json",
                    },
                ],
            }) {
                product {
                    id
                    metafields(first: 10) {
                        edges {
                            node {
                                key
                                value
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_WRITE_VARIANT_METAFIELD = <<<'GRAPHQL'
        mutation UpdateMetafield($variantId: ID!, $key: String!, $value: String!) {
            productVariantUpdate(input: {
                id: $variantId,
                metafields: [
                    {
                        key: $key,
                        value: $value,
                        type: "json",
                    },
                ],
            }) {
                productVariant {
                    id
                    metafields(first: 10) {
                        edges {
                            node {
                                key
                                value
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_SET_INVENTORY = <<<'GRAPHQL'
        mutation SetInventoryLevel($input: InventorySetQuantitiesInput!) {
            inventorySetQuantities(input: $input) {
                inventoryAdjustmentGroup {
                    createdAt
                    reason
                    referenceDocumentUri
                    changes {
                        name
                        delta
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

    private const GQL_GET_FIRST_LOCATION = <<<'GRAPHQL'
        query GetFirstLocation {
            locations(first: 1) {
                edges {
                    node {
                        id
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_GET_INVENTORY_ITEM = <<<'GRAPHQL'
        query GetInventoryItem($variantId: ID!) {
            productVariant(id: $variantId) {
                inventoryItem {
                    id
                }
            }
        }
        GRAPHQL;

    private const GQL_LOAD_PRODUCTS = <<<'GRAPHQL'
        query($cursor: String, $filter: String!) {
            products(first: 250, after: $cursor, query: $filter) {
                pageInfo {
                    hasNextPage
                }
                edges {
                    cursor
                    node {
                        id
                        title
                        tags
                        variants(first: 250) {
                            nodes {
                                id
                                displayName
                                sku
                                price
                                compareAtPrice
                                inventoryQuantity
                                inventoryItem {
                                    id
                                    measurement {
                                        id
                                        weight {
                                            unit
                                            value
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

    public function __construct(
        private ShopifyClient $client
    ) {}

    /**
     * Get product data by variant IDs
     *
     * @param array<string> $variantIds
     * @return array<string, array>
     */
    public function getProductDataByVariantIds(array $variantIds): array
    {
        if (empty($variantIds)) {
            return [];
        }

        $result = [];
        $idsToFetch = [];

        foreach ($variantIds as $id) {
            $cached = Cache::get('shopify_variant_data_' . md5($id));
            if ($cached) {
                $result[$id] = $cached;
            } else {
                $idsToFetch[] = $id;
            }
        }

        if (!empty($idsToFetch)) {
            try {
                $response = $this->client->graphql(self::GQL_GET_PRODUCT_DATA, ['IDs' => $idsToFetch]);

                foreach ($response['nodes'] ?? [] as $node) {
                    if ($node === null) {
                        continue;
                    }

                    $variantId = $node['id'];
                    $product = $node['product'] ?? [];

                    // Extract metafields
                    $startDate = null;
                    $endDate = null;
                    foreach ($product['metafields']['nodes'] ?? [] as $metafield) {
                        if ($metafield['key'] === 'start_date') {
                            $startDate = $metafield['jsonValue'];
                        } elseif ($metafield['key'] === 'end_date') {
                            $endDate = $metafield['jsonValue'];
                        }
                    }

                    $data = [
                        'variantId' => $variantId,
                        'productId' => $product['id'] ?? null,
                        'title' => $product['title'] ?? null,
                        'inventoryQuantity' => $node['inventoryQuantity'] ?? 0,
                        'inventoryItem' => $node['inventoryItem'],
                        'priceRange' => $product['priceRangeV2'] ?? null,
                        'featuredImage' => $product['featuredImage']['url'] ?? null,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'status' => $product['status'] ?? null,
                        'tags' => $product['tags'] ?? [],
                    ];

                    Cache::put('shopify_variant_data_' . md5($variantId), $data, 3600);
                    $result[$variantId] = $data;
                }
            } catch (\Exception $e) {
                $this->logError($e, 'shopifyGetProductData');
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Get product data for a single variant
     *
     * @param string $variantId
     * @return array|null
     */
    public function getProductDataByVariantId(string $variantId): ?array
    {
        $result = $this->getProductDataByVariantIds([$variantId]);
        return $result[$variantId] ?? null;
    }

    /**
     * Get variant detail (inventory, weight, etc.)
     *
     * @param int $offerId
     * @param string $offerVariantId
     * @return array
     */
    public function getVariantDetail(int $offerId, string $offerVariantId): array
    {
        $cacheKey = 'shopify_variant_detail_' . md5($offerVariantId);
        
        return Cache::remember($cacheKey, 3600, function () use ($offerId, $offerVariantId) {
            try {
                $response = $this->client->graphql(self::GQL_GET_VARIANT_DETAIL, ['id' => $offerVariantId]);
                $node = $response['node'] ?? null;

                if (!$node) {
                    throw new \RuntimeException('Variant not found');
                }

                return [
                    'inventoryQuantity' => $node['inventoryQuantity'] ?? null,
                    'product' => $node['product'],
                    'inventoryItem' => $node['inventoryItem'],
                ];
            } catch (\Exception $e) {
                $this->logError($e, 'genShopifyDetail', $offerId);
                throw $e;
            }
        });
    }

    /**
     * Write a metafield to a product
     *
     * @param string $productId
     * @param string $key
     * @param string $value
     * @return array
     */
    public function writeProductMetafield(string $productId, string $key, string $value): array
    {
        $result = [];
        try {
            $result['vars'] = compact('productId', 'key', 'value');
            $response = $this->client->graphql(self::GQL_WRITE_PRODUCT_METAFIELD, $result['vars']);
            $result['edges'] = $response['productUpdate']['product']['metafields']['edges'] ?? [];
            
            // Clear caches
            $this->clearProductCaches($productId);
            
            return $result['edges'];
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            return [];
        } finally {
            $this->log($result, 'metaField');
        }
    }

    /**
     * Write a metafield to a variant
     *
     * @param string $variantId
     * @param string $key
     * @param string $value
     * @return array
     */
    public function writeVariantMetafield(string $variantId, string $key, string $value): array
    {
        try {
            $vars = compact('variantId', 'key', 'value');
            $this->log($vars, 'metaField');
            $response = $this->client->graphql(self::GQL_WRITE_VARIANT_METAFIELD, $vars);
            $edges = $response['productVariantUpdate']['productVariant']['metafields']['edges'] ?? [];
            $this->log($edges, 'metaField');
            
            // Clear caches
            $this->clearVariantCaches($variantId);
            
            return $edges;
        } catch (\Exception $e) {
            $this->logError($e, 'metaField');
            return [];
        }
    }

    /**
     * Set variant inventory quantity
     *
     * @param string $variantId
     * @param int $availableQuantity
     * @return array
     */
    public function setVariantQuantity(string $variantId, int $availableQuantity): array
    {
        if (!str_starts_with($variantId, 'gid://shopify/ProductVariant/')) {
            throw new \InvalidArgumentException('Invalid variant id');
        }

        // Get location ID
        $locationResponse = $this->client->graphql(self::GQL_GET_FIRST_LOCATION);
        $locationId = $locationResponse['locations']['edges'][0]['node']['id'] ?? null;

        if (!$locationId) {
            throw new \RuntimeException('Unable to get location ID');
        }

        // Get inventory item ID
        $inventoryResponse = $this->client->graphql(self::GQL_GET_INVENTORY_ITEM, ['variantId' => $variantId]);
        $inventoryItemId = $inventoryResponse['productVariant']['inventoryItem']['id'] ?? null;

        if (!$inventoryItemId) {
            throw new \RuntimeException('Unable to get inventory item ID');
        }

        $input = [
            'name' => 'available',
            'reason' => 'correction',
            'ignoreCompareQuantity' => true,
            'quantities' => [
                [
                    'inventoryItemId' => $inventoryItemId,
                    'locationId' => $locationId,
                    'quantity' => $availableQuantity,
                ],
            ],
        ];

        $this->log($input, 'setVariantQty');
        $response = $this->client->graphql(self::GQL_SET_INVENTORY, ['input' => $input]);

        // Clear caches
        $this->clearVariantCaches($variantId);

        return $response['inventorySetQuantities'] ?? [];
    }

    /**
     * Alias for setVariantQuantity - used by controller
     *
     * @param string $variantId
     * @param int $quantity
     * @return array
     */
    public function setInventoryQuantity(string $variantId, int $quantity): array
    {
        return $this->setVariantQuantity($variantId, $quantity);
    }

    /**
     * Load Shopify products by type tag
     *
     * @param string $type 'manifest-item' or 'deal'
     * @return array
     */
    public function loadProducts(string $type): array
    {
        if (!in_array($type, ['manifest-item', 'deal'])) {
            throw new \InvalidArgumentException('Unexpected type: ' . $type);
        }

        $cacheKey = 'shopify_products_' . $type;

        return Cache::remember($cacheKey, 3600, function () use ($type) {
            $result = [];
            $cursor = null;
            $filter = "tag:{$type} status:active";

            do {
                $response = $this->client->graphql(self::GQL_LOAD_PRODUCTS, [
                    'cursor' => $cursor,
                    'filter' => $filter,
                ]);

                $products = $response['products'] ?? [];
                $hasNextPage = $products['pageInfo']['hasNextPage'] ?? false;

                foreach ($products['edges'] ?? [] as $edge) {
                    $cursor = $edge['cursor'];
                    $node = $edge['node'];

                    foreach ($node['variants']['nodes'] ?? [] as $variant) {
                        $result[] = [
                            'variantId' => $variant['id'],
                            'productId' => $node['id'],
                            'productName' => $node['title'],
                            'variantName' => $variant['displayName'],
                            'variantSku' => $variant['sku'],
                            'variantPrice' => $variant['price'],
                            'variantCompareAtPrice' => $variant['compareAtPrice'],
                            'variantInventoryQuantity' => $variant['inventoryQuantity'],
                            'variantWeight' => $variant['inventoryItem']['measurement']['weight']['value'] ?? null,
                            'tags' => $node['tags'],
                        ];
                    }
                }
            } while ($hasNextPage);

            return $result;
        });
    }

    /**
     * Clear all cached data related to a variant
     */
    private function clearVariantCaches(string $variantId): void
    {
        Cache::forget('shopify_variant_data_' . md5($variantId));
        Cache::forget('shopify_variant_detail_' . md5($variantId));
        Cache::forget('shopify_products_deal');
        Cache::forget('shopify_products_manifest-item');
    }

    /**
     * Clear all cached data related to a product
     */
    private function clearProductCaches(string $productId): void
    {
        // Since we don't easily know all variant IDs for a product here, 
        // and product metafields might affect variant data, we clear common caches
        Cache::forget('shopify_products_deal');
        Cache::forget('shopify_products_manifest-item');
    }

    private function log(mixed $data, string $eventName): void
    {
        AuditLog::create([
            'event_name' => $eventName,
            'event_ext' => is_string($data) ? $data : json_encode($data),
        ]);
    }

    private function logError(\Exception $e, string $eventName, ?int $offerId = null): void
    {
        AuditLog::create([
            'event_name' => $eventName,
            'event_ext' => $e->getMessage(),
            'offer_id' => $offerId,
        ]);
    }
}
