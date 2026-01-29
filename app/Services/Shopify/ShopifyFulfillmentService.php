<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\AuditLog;

/**
 * Service for fulfillment order operations in Shopify
 */
class ShopifyFulfillmentService
{
    private const GQL_GET_FULFILLMENT_ORDERS = <<<'GRAPHQL'
        query GetFulfillmentOrders($orderId: ID!) {
            order(id: $orderId) {
                fulfillmentOrders(first: 10) {
                    nodes {
                        id
                        status
                        fulfillAt
                        assignedLocation {
                            location {
                                id
                                name
                            }
                        }
                        deliveryMethod {
                            presentedName
                        }
                        lineItems(first: 10) {
                            nodes {
                                id
                                totalQuantity
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_FULFILLMENT_ORDER_MERGE = <<<'GRAPHQL'
        mutation fulfillmentOrderMerge($mergeIntents: [FulfillmentOrderMergeInput!]!) {
            fulfillmentOrderMerge(mergeIntents: $mergeIntents) {
                fulfillmentOrderMerges {
                    fulfillmentOrder {
                        id
                        status
                        assignedLocation {
                            id
                            name
                        }
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

    public function __construct(
        private ShopifyClient $client
    ) {}

    /**
     * Get fulfillment orders for an order
     *
     * @param string $orderId
     * @return array
     */
    public function getFulfillmentOrders(string $orderId): array
    {
        $response = $this->client->graphql(self::GQL_GET_FULFILLMENT_ORDERS, ['orderId' => $orderId]);

        $order = $response['order'] ?? null;
        if (!$order) {
            return [];
        }

        $result = [];
        foreach ($order['fulfillmentOrders']['nodes'] ?? [] as $node) {
            $lineItems = [];
            foreach ($node['lineItems']['nodes'] ?? [] as $lineItem) {
                $lineItems[] = [
                    'id' => $lineItem['id'],
                    'totalQuantity' => $lineItem['totalQuantity'],
                ];
            }

            $result[] = [
                'id' => $node['id'],
                'status' => $node['status'],
                'fulfillAt' => $node['fulfillAt'],
                'assignedLocation' => [
                    'location' => $node['assignedLocation']['location'] ?? null,
                ],
                'deliveryMethod' => $node['deliveryMethod'],
                'lineItems' => ['nodes' => $lineItems],
            ];
        }

        return $result;
    }

    /**
     * Merge fulfillment orders
     *
     * @param array $inputs Array of merge inputs with fulfillmentOrderId and fulfillmentOrderLineItems
     * @return array
     */
    public function mergeFulfillmentOrders(array $inputs): array
    {
        $response = $this->client->graphql(self::GQL_FULFILLMENT_ORDER_MERGE, [
            'mergeIntents' => $inputs,
        ]);

        $result = $response['fulfillmentOrderMerge'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Fulfillment order merge failed: ' . json_encode($result['userErrors']));
        }

        return $result['fulfillmentOrderMerges'] ?? [];
    }
}
