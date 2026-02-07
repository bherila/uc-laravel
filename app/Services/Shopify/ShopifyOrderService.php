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
        query GetOrders(
            $ids: [ID!]!,
            $should_fetch_extended_customer_details: Boolean! = false
        ) {
            nodes(ids: $ids) {
                ... on Order {
                    id
                    name
                    cancelledAt
                    createdAt
                    processedAt
                    email
                    note
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
                    currentTotalDiscountsSet {
                        shopMoney {
                            amount
                        }
                    }
                    currentTotalTaxSet {
                        shopMoney {
                            amount
                        }
                    }
                    discountApplications(first: 10) {
                        nodes {
                            ... on DiscountCodeApplication {
                                code
                                value {
                                    ... on MoneyV2 {
                                        amount
                                    }
                                    ... on PricingPercentageValue {
                                        percentage
                                    }
                                }
                            }
                            ... on ManualDiscountApplication {
                                value {
                                    ... on MoneyV2 {
                                        amount
                                    }
                                    ... on PricingPercentageValue {
                                        percentage
                                    }
                                }
                            }
                            ... on AutomaticDiscountApplication {
                                title
                                value {
                                    ... on MoneyV2 {
                                        amount
                                    }
                                    ... on PricingPercentageValue {
                                        percentage
                                    }
                                }
                            }
                        }
                    }
                    billingAddress {
                        firstName
                        lastName
                        company
                        address1
                        address2
                        city
                        provinceCode
                        province
                        zip
                        phone
                    }
                    shippingAddress {
                        firstName
                        lastName
                        company
                        address1
                        address2
                        city
                        provinceCode
                        province
                        zip
                        phone
                    }
                    shippingLines(first: 10, includeRemovals: true) {
                        nodes {
                            id
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
                            sku
                            customAttributes {
                                key
                                value
                            }
                            variant {
                                id
                                sku
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
                        createdAt
                        trackingInfo {
                            number
                            url
                            company
                        }
                    }
                    customer @include(if: $should_fetch_extended_customer_details) {
                        id
                        email
                        firstName
                        lastName
                        ...CustomerExtendedFields
                    }
                }
            }
        }

        fragment CustomerExtendedFields on Customer {
            metafield(namespace: "custom", key: "birthday") {
                value
            }
            defaultAddress {
                ...AddressFields
            }
            addresses(first: 10) {
                ...AddressFields
            }
        }

        fragment AddressFields on MailingAddress {
            id
            firstName
            lastName
            address1
            address2
            city
            provinceCode
            province
            zip
            phone
        }
        GRAPHQL;

    private const GQL_ORDER_EDIT_BEGIN = <<<'GRAPHQL'
        mutation orderEditBegin($id: ID!) {
            orderEditBegin(id: $id) {
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

    private const GQL_ORDER_EDIT_UPDATE_SHIPPING_LINE = <<<'GRAPHQL'
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

    private const GQL_ORDER_EDIT_COMMIT = <<<'GRAPHQL'
        mutation orderEditCommit($id: ID!) {
            orderEditCommit(id: $id) {
                order {
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
     * @param bool $shouldFetchExtendedCustomerDetails
     * @return array
     */
    public function getOrdersWithLineItems(array $orderIds, bool $shouldFetchExtendedCustomerDetails = false): array
    {
        $response = $this->client->graphql(self::GQL_GET_ORDERS, [
            'ids' => $orderIds,
            'should_fetch_extended_customer_details' => $shouldFetchExtendedCustomerDetails,
        ]);

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
                    'sku' => $item['sku'] ?? $item['variant']['sku'] ?? '',
                    'customAttributes' => $item['customAttributes'] ?? [],
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
                        'company' => $ti['company'] ?? null,
                    ];
                }
                $fulfillments[] = [
                    'status' => $f['status'],
                    'createdAt' => $f['createdAt'] ?? null,
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

            $customer = null;
            if (isset($node['customer'])) {
                $c = $node['customer'];
                $customer = [
                    'id' => $c['id'],
                    'email' => $c['email'],
                    'firstName' => $c['firstName'],
                    'lastName' => $c['lastName'],
                    'birthday' => $c['metafield']['value'] ?? null,
                    'defaultAddress' => $c['defaultAddress'],
                    'addresses' => $c['addresses'],
                ];
            }

            $orders[] = [
                'id' => $node['id'],
                'name' => $node['name'] ?? '',
                'cancelledAt' => $node['cancelledAt'],
                'createdAt' => $node['createdAt'],
                'processedAt' => $node['processedAt'] ?? null,
                'email' => $node['email'],
                'note' => $node['note'] ?? '',
                'displayFinancialStatus' => $node['displayFinancialStatus'],
                'displayFulfillmentStatus' => $node['displayFulfillmentStatus'],
                'fulfillmentStatus' => $node['displayFulfillmentStatus'],
                'totalPriceSet_shopMoney_amount' => (float)($node['totalPriceSet']['shopMoney']['amount'] ?? 0),
                'totalShippingPriceSet_shopMoney_amount' => (float)($node['totalShippingPriceSet']['shopMoney']['amount'] ?? 0),
                'totalShippingPriceSet_shopMoney_currencyCode' => $node['totalShippingPriceSet']['shopMoney']['currencyCode'] ?? 'USD',
                'currentTotalDiscountsSet_shopMoney_amount' => (float)($node['currentTotalDiscountsSet']['shopMoney']['amount'] ?? 0),
                'currentTotalTaxSet_shopMoney_amount' => (float)($node['currentTotalTaxSet']['shopMoney']['amount'] ?? 0),
                'discountApplications' => $node['discountApplications']['nodes'] ?? [],
                'billingAddress' => $node['billingAddress'] ?? null,
                'shippingAddress' => $node['shippingAddress'] ?? null,
                'shippingLine' => $shippingLine,
                'shippingLines' => $node['shippingLines'] ?? [],
                'lineItems_nodes' => $lineItems,
                'transactions_nodes' => $transactions,
                'fulfillmentOrders_nodes' => $fulfillmentOrders,
                'fulfillments_nodes' => $fulfillments,
                'customer' => $customer,
            ];
        }

        return $orders;
    }

    public function beginEdit(string $orderId): ?string
    {
        $response = $this->client->graphql(self::GQL_ORDER_EDIT_BEGIN, ['id' => $orderId]);
        $result = $response['orderEditBegin'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Order edit begin failed: ' . json_encode($result['userErrors']));
        }

        return $result['calculatedOrder']['id'] ?? null;
    }

    public function updateShippingLine(string $calculatedOrderId, string $shippingLineId, string $newTitle): void
    {
        $response = $this->client->graphql(self::GQL_ORDER_EDIT_UPDATE_SHIPPING_LINE, [
            'id' => $calculatedOrderId,
            'shippingLineId' => $shippingLineId,
            'shippingLine' => ['title' => $newTitle],
        ]);

        $result = $response['orderEditUpdateShippingLine'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Order edit update shipping line failed: ' . json_encode($result['userErrors']));
        }
    }

    public function commitEdit(string $calculatedOrderId): void
    {
        $response = $this->client->graphql(self::GQL_ORDER_EDIT_COMMIT, ['id' => $calculatedOrderId]);
        $result = $response['orderEditCommit'] ?? [];

        if (!empty($result['userErrors'])) {
            throw new \RuntimeException('Order edit commit failed: ' . json_encode($result['userErrors']));
        }
    }

    /**
     * Identify the original shipping method from order and fulfillment order data.
     * 
     * @param array $orderData The order data from getOrdersWithLineItems
     * @param array $fulfillmentOrders The fulfillment orders for this order
     * @return array{title: string|null, id: string|null}
     */
    public function identifyOriginalShippingMethod(array $orderData, array $fulfillmentOrders): array
    {
        $shippingLines = $orderData['shippingLines']['nodes'] ?? [];
        
        // 1. Try to find a good name in shipping lines (removed first)
        foreach ($shippingLines as $line) {
            if (($line['isRemoved'] ?? false) === true) {
                $title = $line['title'] ?? '';
                if ($title !== '' && $title !== 'Shipping') {
                    return ['title' => $title, 'id' => $line['id']];
                }
            }
        }

        // 2. Try to find a good name in existing fulfillment orders (even closed ones)
        $bestNameFromFO = null;
        foreach ($fulfillmentOrders as $fo) {
            $name = $fo['deliveryMethod']['presentedName'] ?? '';
            if ($name !== '' && $name !== 'Shipping') {
                $bestNameFromFO = $name;
                break;
            }
        }

        if ($bestNameFromFO) {
            // Try to find a shipping line that matches this name
            foreach ($shippingLines as $line) {
                if (($line['title'] ?? '') === $bestNameFromFO) {
                    return ['title' => $bestNameFromFO, 'id' => $line['id']];
                }
            }
            // If no exact match but we have a name, return it with the first shipping line ID as a target
            if (!empty($shippingLines)) {
                return ['title' => $bestNameFromFO, 'id' => $shippingLines[0]['id']];
            }
        }

        // 3. Try active shipping lines
        foreach ($shippingLines as $line) {
            if (!($line['isRemoved'] ?? false)) {
                $title = $line['title'] ?? '';
                if ($title !== '' && $title !== 'Shipping') {
                    return ['title' => $title, 'id' => $line['id']];
                }
            }
        }

        // Fallback to first shipping line
        if (!empty($shippingLines)) {
            return ['title' => $shippingLines[0]['title'] ?? null, 'id' => $shippingLines[0]['id'] ?? null];
        }

        return ['title' => null, 'id' => null];
    }

    private function log(mixed $data, string $eventName = 'shopifyOrder'): void
    {
        AuditLog::create([
            'event_name' => $eventName,
            'event_ext' => is_string($data) ? $data : json_encode($data),
        ]);
    }
}
