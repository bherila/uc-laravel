<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\AuditLog;

/**
 * Service for order-level operations in Shopify
 */
class ShopifyOrderService
{
    private const GQL_CANCEL_ORDER = <<<'GRAPHQL'
        mutation cancelOrder($id: ID!, $restockInventory: Boolean = false, $refund: Boolean = true) {
            orderCancel(
                orderId: $id,
                refund: $refund,
                restock: $restockInventory,
                reason: OTHER
            ) {
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

    private const GQL_ORDER_CAPTURE = <<<'GRAPHQL'
        mutation orderCapture($input: OrderCaptureInput!) {
            orderCapture(input: $input) {
                transaction {
                    id
                    status
                    order {
                        id
                        totalPriceSet {
                            shopMoney {
                                amount
                                currencyCode
                            }
                        }
                        displayFinancialStatus
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

    private const GQL_GET_ORDERS = <<<'GRAPHQL'
        query GetOrders($ids: [ID!]!) {
            nodes(ids: $ids) {
                ... on Order {
                    id
                    cancelledAt
                    createdAt
                    email
                    displayFinancialStatus
                    displayFulfillmentStatus
                    totalPriceSet {
                        shopMoney {
                            amount
                        }
                    }
                    totalShippingPriceSet {
                        shopMoney {
                            amount
                            currencyCode
                        }
                    }
                    shippingLines(first: 10, includeRemovals: true) {
                        nodes {
                            title
                            code
                            shippingRateHandle
                            isRemoved
                        }
                    }
                    lineItems(first: 250) {
                        nodes {
                            id
                            currentQuantity
                            title
                            variant {
                                id
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
                                product {
                                    tags
                                }
                            }
                            originalUnitPriceSet {
                                shopMoney {
                                    amount
                                }
                            }
                            discountedTotalSet {
                                shopMoney {
                                    amount
                                }
                            }
                        }
                    }
                    transactions {
                        id
                        status
                        kind
                    }
                    fulfillmentOrders(first: 10) {
                        nodes {
                            id
                            status
                            deliveryMethod {
                                methodType
                                presentedName
                            }
                        }
                    }
                    fulfillments(first: 5) {
                        status
                        trackingInfo {
                            number
                            url
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
     * Cancel an order
     *
     * @param string $orderId
     * @param bool $restockInventory
     * @return array
     */
    public function cancelOrder(string $orderId, bool $restockInventory = false): array
    {
        if (!str_starts_with($orderId, 'gid://shopify/Order/')) {
            throw new \InvalidArgumentException('Invalid order id');
        }

        $this->log(['orderId' => $orderId, 'restockInventory' => $restockInventory], 'cancelOrder');

        $response = $this->client->graphql(self::GQL_CANCEL_ORDER, [
            'id' => $orderId,
            'restockInventory' => $restockInventory,
        ]);

        $result = $response['orderCancel'] ?? [];
        $this->log($result, 'cancelOrder');

        return ['cancelResult' => $result];
    }

    /**
     * Capture payment for an order
     *
     * @param string $orderId
     * @param string $parentTransactionId
     * @param string $amount
     * @return array
     */
    public function captureOrder(string $orderId, string $parentTransactionId, string $amount): array
    {
        $input = [
            'id' => $orderId,
            'parentTransactionId' => $parentTransactionId,
            'amount' => $amount,
        ];

        $response = $this->client->graphql(self::GQL_ORDER_CAPTURE, ['input' => $input]);

        $result = $response['orderCapture'] ?? [];

        // Log the capture attempt
        AuditLog::create([
            'event_name' => 'shopifyOrderCapture',
            'event_ext' => json_encode([
                'orderId' => $orderId,
                'amount' => $amount,
                'transactionStatus' => $result['transaction']['status'] ?? null,
                'displayFinancialStatus' => $result['transaction']['order']['displayFinancialStatus'] ?? null,
            ]),
            'order_id' => (int)str_replace('gid://shopify/Order/', '', $orderId),
        ]);

        return $result;
    }

    /**
     * Get orders with line items
     *
     * @param array<string> $orderIds
     * @return array
     */
    public function getOrdersWithLineItems(array $orderIds): array
    {
        $response = $this->client->graphql(self::GQL_GET_ORDERS, ['ids' => $orderIds]);

        $orders = [];
        foreach ($response['nodes'] ?? [] as $node) {
            if ($node === null) {
                continue;
            }

            $lineItems = [];
            foreach ($node['lineItems']['nodes'] ?? [] as $item) {
                $lineItems[] = [
                    'line_item_id' => $item['id'],
                    'currentQuantity' => $item['currentQuantity'],
                    'title' => $item['title'],
                    'product_tags' => $item['variant']['product']['tags'] ?? [],
                    'variant_variant_graphql_id' => $item['variant']['id'] ?? null,
                    'variant_inventoryItem_id' => $item['variant']['inventoryItem']['id'] ?? null,
                    'variant_inventoryItem_measurement_id' => $item['variant']['inventoryItem']['measurement']['id'] ?? null,
                    'variant_inventoryItem_measurement_weight_unit' => $item['variant']['inventoryItem']['measurement']['weight']['unit'] ?? null,
                    'variant_inventoryItem_measurement_weight_value' => $item['variant']['inventoryItem']['measurement']['weight']['value'] ?? null,
                    'originalUnitPriceSet_shopMoney_amount' => (float)($item['originalUnitPriceSet']['shopMoney']['amount'] ?? 0),
                    'discountedTotalSet_shopMoney_amount' => (float)($item['discountedTotalSet']['shopMoney']['amount'] ?? 0),
                ];
            }

            $transactions = [];
            foreach ($node['transactions'] ?? [] as $txn) {
                $transactions[] = [
                    'id' => $txn['id'],
                    'status' => $txn['status'],
                    'kind' => $txn['kind'],
                ];
            }

            $fulfillmentOrders = [];
            foreach ($node['fulfillmentOrders']['nodes'] ?? [] as $fo) {
                $fulfillmentOrders[] = [
                    'id' => $fo['id'],
                    'status' => $fo['status'],
                    'deliveryMethod' => $fo['deliveryMethod'] ?? null,
                ];
            }

            $fulfillments = [];
            foreach ($node['fulfillments'] ?? [] as $f) {
                $tracking = [];
                foreach ($f['trackingInfo'] ?? [] as $ti) {
                    $tracking[] = [
                        'number' => $ti['number'],
                        'url' => $ti['url'],
                    ];
                }
                $fulfillments[] = [
                    'status' => $f['status'],
                    'trackingInfo' => $tracking,
                ];
            }

            // Determine original shipping line
            $shippingLines = $node['shippingLines']['nodes'] ?? [];
            $shippingLine = null;
            
            // Priority 1: Removed shipping line (indicates original before edit)
            foreach ($shippingLines as $line) {
                if (($line['isRemoved'] ?? false) === true) {
                    $shippingLine = $line;
                    break;
                }
            }
            
            // Priority 2: Active shipping line (if no removed line found)
            if (!$shippingLine && !empty($shippingLines)) {
                $shippingLine = $shippingLines[0];
            }

            $orders[] = [
                'id' => $node['id'],
                'cancelledAt' => $node['cancelledAt'],
                'createdAt' => $node['createdAt'],
                'email' => $node['email'],
                'displayFinancialStatus' => $node['displayFinancialStatus'],
                'displayFulfillmentStatus' => $node['displayFulfillmentStatus'],
                'totalPriceSet_shopMoney_amount' => (float)($node['totalPriceSet']['shopMoney']['amount'] ?? 0),
                'totalShippingPriceSet_shopMoney_amount' => (float)($node['totalShippingPriceSet']['shopMoney']['amount'] ?? 0),
                'totalShippingPriceSet_shopMoney_currencyCode' => $node['totalShippingPriceSet']['shopMoney']['currencyCode'] ?? 'USD',
                'shippingLine' => $shippingLine,
                'lineItems_nodes' => $lineItems,
                'transactions_nodes' => $transactions,
                'fulfillmentOrders_nodes' => $fulfillmentOrders,
                'fulfillments_nodes' => $fulfillments,
            ];
        }

        return $orders;
    }

    private function log(mixed $data, string $eventName = 'shopifyOrder'): void
    {
        AuditLog::create([
            'event_name' => $eventName,
            'event_ext' => is_string($data) ? $data : json_encode($data),
        ]);
    }
}
