<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

/**
 * Service for order editing operations in Shopify
 */
class ShopifyOrderEditService
{
    private const GQL_BEGIN_EDIT = <<<'GRAPHQL'
        mutation beginEdit($order_id: ID!) {
            orderEditBegin(id: $order_id) {
                calculatedOrder {
                    id
                    lineItems(first: 250) {
                        nodes {
                            id
                            variant {
                                id
                                product {
                                    tags
                                }
                            }
                            quantity
                        }
                    }
                    shippingLines {
                        id
                        title
                        price {
                            shopMoney {
                                amount
                                currencyCode
                            }
                        }
                        stagedStatus
                    }
                    totalPriceSet {
                        shopMoney {
                            amount
                            currencyCode
                        }
                    }
                }
            }
        }
        GRAPHQL;

    private const GQL_ADD_VARIANT = <<<'GRAPHQL'
        mutation orderEditAddVariant($calculatedOrderId: ID!, $quantity: Int!, $variantId: ID!) {
            orderEditAddVariant(id: $calculatedOrderId, quantity: $quantity, variantId: $variantId, allowDuplicates: false) {
                calculatedLineItem {
                    id
                }
                calculatedOrder {
                    totalPriceSet {
                        shopMoney {
                            amount
                            currencyCode
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

    private const GQL_ADD_DISCOUNT = <<<'GRAPHQL'
        mutation orderEditAddLineItemDiscount($discount: OrderEditAppliedDiscountInput!, $calculated_order_id: ID!, $calculated_line_item_id: ID!) {
            orderEditAddLineItemDiscount(discount: $discount, id: $calculated_order_id, lineItemId: $calculated_line_item_id) {
                addedDiscountStagedChange {
                    id
                    description
                    value {
                        __typename
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

    private const GQL_SET_QUANTITY = <<<'GRAPHQL'
        mutation changeLineItemQuantity($orderId: ID!, $lineItemId: ID!, $quantity: Int!) {
            orderEditSetQuantity(id: $orderId, lineItemId: $lineItemId, quantity: $quantity) {
                calculatedOrder {
                    id
                    addedLineItems(first: 5) {
                        edges {
                            node {
                                id
                                quantity
                            }
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

    private const GQL_COMMIT = <<<'GRAPHQL'
        mutation orderEditCommit($calculated_order_id: ID!) {
            orderEditCommit(id: $calculated_order_id, notifyCustomer: true) {
                order {
                    id
                    totalPriceSet {
                        shopMoney {
                            amount
                            currencyCode
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

    private const GQL_ADD_SHIPPING_LINE = <<<'GRAPHQL'
        mutation orderEditAddShippingLine($id: ID!, $shippingLine: OrderEditAddShippingLineInput!) {
            orderEditAddShippingLine(id: $id, shippingLine: $shippingLine) {
                calculatedOrder {
                    id
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

    private const GQL_UPDATE_SHIPPING_LINE = <<<'GRAPHQL'
        mutation orderEditUpdateShippingLine($id: ID!, $shippingLineId: ID!, $shippingLine: OrderEditUpdateShippingLineInput!) {
            orderEditUpdateShippingLine(id: $id, shippingLineId: $shippingLineId, shippingLine: $shippingLine) {
                calculatedOrder {
                    id
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
     * Begin an order edit session
     *
     * @param string $orderId
     * @return array{calculatedOrderId: string, totalPrice: string, editableLineItems: array, shippingLines: array}
     */
    public function beginEdit(string $orderId): array
    {
        $response = $this->client->graphql(self::GQL_BEGIN_EDIT, ['order_id' => $orderId]);

        $calculatedOrder = $response['orderEditBegin']['calculatedOrder'] ?? null;
        $editableLineItems = [];

        foreach ($calculatedOrder['lineItems']['nodes'] ?? [] as $item) {
            if (isset($item['variant'])) {
                $editableLineItems[] = [
                    'calculatedLineItemId' => $item['id'],
                    'variantId' => $item['variant']['id'],
                    'quantity' => $item['quantity'],
                    'productTags' => $item['variant']['product']['tags'] ?? [],
                ];
            }
        }

        $shippingLines = [];
        foreach ($calculatedOrder['shippingLines'] ?? [] as $line) {
            $shippingLines[] = [
                'id' => $line['id'],
                'title' => $line['title'],
                'price' => $line['price']['shopMoney'],
                'stagedStatus' => $line['stagedStatus'],
            ];
        }

        return [
            'calculatedOrderId' => $calculatedOrder['id'] ?? '',
            'totalPrice' => $calculatedOrder['totalPriceSet']['shopMoney']['amount'] ?? '0',
            'editableLineItems' => $editableLineItems,
            'shippingLines' => $shippingLines,
        ];
    }

    /**
     * Add a variant to an order edit
     *
     * @param string $calculatedOrderId
     * @param string $variantId
     * @param int $quantity
     * @return array
     */
    public function addVariant(string $calculatedOrderId, string $variantId, int $quantity): array
    {
        $response = $this->client->graphql(self::GQL_ADD_VARIANT, [
            'calculatedOrderId' => $calculatedOrderId,
            'variantId' => $variantId,
            'quantity' => $quantity,
        ]);

        $result = $response['orderEditAddVariant'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Failed to add variant: ' . json_encode($result['userErrors']));
        }

        return $result;
    }

    /**
     * Add a discount to a line item
     *
     * @param string $calculatedOrderId
     * @param string $calculatedLineItemId
     * @param array{percentValue?: float, description?: string} $discount
     * @return array
     */
    public function addDiscount(string $calculatedOrderId, string $calculatedLineItemId, array $discount): array
    {
        $response = $this->client->graphql(self::GQL_ADD_DISCOUNT, [
            'calculated_order_id' => $calculatedOrderId,
            'calculated_line_item_id' => $calculatedLineItemId,
            'discount' => $discount,
        ]);

        return $response['orderEditAddLineItemDiscount'] ?? [];
    }

    /**
     * Set the quantity of a line item
     *
     * @param string $calculatedOrderId
     * @param string $calculatedLineItemId
     * @param int $quantity
     * @return array
     */
    public function setLineItemQuantity(string $calculatedOrderId, string $calculatedLineItemId, int $quantity): array
    {
        if (!str_starts_with($calculatedOrderId, 'gid://shopify/CalculatedOrder/')) {
            throw new \InvalidArgumentException('Invalid calculated order id');
        }
        if (!str_starts_with($calculatedLineItemId, 'gid://shopify/CalculatedLineItem/')) {
            throw new \InvalidArgumentException('Invalid calculated line item id: "' . $calculatedLineItemId . '"');
        }

        $response = $this->client->graphql(self::GQL_SET_QUANTITY, [
            'orderId' => $calculatedOrderId,
            'lineItemId' => $calculatedLineItemId,
            'quantity' => $quantity,
        ]);

        return [
            'calculated_lineitem_id' => $calculatedLineItemId,
        ];
    }

    /**
     * Remove a line item by setting quantity to 0
     *
     * @param string $calculatedOrderId
     * @param string $calculatedLineItemId
     * @return array
     */
    public function removeLineItem(string $calculatedOrderId, string $calculatedLineItemId): array
    {
        return $this->setLineItemQuantity($calculatedOrderId, $calculatedLineItemId, 0);
    }

    /**
     * Add a shipping line to an order edit
     *
     * @param string $calculatedOrderId
     * @param array{amount: string, currencyCode: string} $price
     * @param string $title
     * @return array
     */
    public function addShippingLine(string $calculatedOrderId, array $price, string $title): array
    {
        $response = $this->client->graphql(self::GQL_ADD_SHIPPING_LINE, [
            'id' => $calculatedOrderId,
            'shippingLine' => [
                'price' => $price,
                'title' => $title,
            ],
        ]);

        $result = $response['orderEditAddShippingLine'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Failed to add shipping line: ' . json_encode($result['userErrors']));
        }

        return $result;
    }

    /**
     * Update a shipping line in an order edit
     *
     * @param string $calculatedOrderId
     * @param string $shippingLineId
     * @param array{amount: string, currencyCode: string} $price
     * @param string $title
     * @return array
     */
    public function updateShippingLine(string $calculatedOrderId, string $shippingLineId, array $price, string $title): array
    {
        $response = $this->client->graphql(self::GQL_UPDATE_SHIPPING_LINE, [
            'id' => $calculatedOrderId,
            'shippingLineId' => $shippingLineId,
            'shippingLine' => [
                'price' => $price,
                'title' => $title,
            ],
        ]);

        $result = $response['orderEditUpdateShippingLine'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Failed to update shipping line: ' . json_encode($result['userErrors']));
        }

        return $result;
    }

    /**
     * Commit an order edit
     *
     * @param string $calculatedOrderId
     * @return array|null
     */
    public function commit(string $calculatedOrderId): ?array
    {
        try {
            $response = $this->client->graphql(self::GQL_COMMIT, [
                'calculated_order_id' => $calculatedOrderId,
            ]);

            return $response['orderEditCommit'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
