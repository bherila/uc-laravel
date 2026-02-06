<?php

namespace Tests\Unit;

use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyOrderService;
use Tests\TestCase;
use Mockery;

class ShopifyOrderServiceTest extends TestCase
{
    private $shopifyClient;
    private $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopifyClient = Mockery::mock(ShopifyClient::class);
        $this->orderService = new ShopifyOrderService($this->shopifyClient);
    }

    public function test_get_orders_with_line_items_calls_graphql_with_correct_variables()
    {
        $orderIds = ['gid://shopify/Order/1', 'gid://shopify/Order/2'];

        $this->shopifyClient->shouldReceive('graphql')
            ->once()
            ->with(Mockery::any(), [
                'ids' => $orderIds,
                'should_fetch_extended_customer_details' => false,
            ])
            ->andReturn([
                'nodes' => [
                    [
                        'id' => 'gid://shopify/Order/1',
                        'cancelledAt' => null,
                        'createdAt' => '2023-01-01T00:00:00Z',
                        'email' => 'test@example.com',
                        'displayFinancialStatus' => 'PAID',
                        'displayFulfillmentStatus' => 'UNFULFILLED',
                        'totalPriceSet' => ['shopMoney' => ['amount' => '100.00']],
                        'totalShippingPriceSet' => ['shopMoney' => ['amount' => '10.00', 'currencyCode' => 'USD']],
                        'shippingLines' => ['nodes' => []],
                        'lineItems' => ['nodes' => []],
                        'transactions' => [],
                        'fulfillmentOrders' => ['nodes' => []],
                        'fulfillments' => [],
                    ]
                ]
            ]);

        $result = $this->orderService->getOrdersWithLineItems($orderIds);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]['customer']);
    }

    public function test_get_orders_with_line_items_with_extended_customer_details()
    {
        $orderIds = ['gid://shopify/Order/1'];

        $this->shopifyClient->shouldReceive('graphql')
            ->once()
            ->with(Mockery::any(), [
                'ids' => $orderIds,
                'should_fetch_extended_customer_details' => true,
            ])
            ->andReturn([
                'nodes' => [
                    [
                        'id' => 'gid://shopify/Order/1',
                        'cancelledAt' => null,
                        'createdAt' => '2023-01-01T00:00:00Z',
                        'email' => 'test@example.com',
                        'displayFinancialStatus' => 'PAID',
                        'displayFulfillmentStatus' => 'UNFULFILLED',
                        'totalPriceSet' => ['shopMoney' => ['amount' => '100.00']],
                        'totalShippingPriceSet' => ['shopMoney' => ['amount' => '10.00', 'currencyCode' => 'USD']],
                        'shippingLines' => ['nodes' => []],
                        'lineItems' => ['nodes' => []],
                        'transactions' => [],
                        'fulfillmentOrders' => ['nodes' => []],
                        'fulfillments' => [],
                        'customer' => [
                            'id' => 'gid://shopify/Customer/1',
                            'email' => 'customer@example.com',
                            'firstName' => 'John',
                            'lastName' => 'Doe',
                            'metafield' => ['value' => '1990-01-01'],
                            'defaultAddress' => ['address1' => '123 Main St'],
                            'addresses' => [['address1' => '123 Main St']],
                        ]
                    ]
                ]
            ]);

        $result = $this->orderService->getOrdersWithLineItems($orderIds, true);
        
        $this->assertCount(1, $result);
        $this->assertNotNull($result[0]['customer']);
        $this->assertEquals('gid://shopify/Customer/1', $result[0]['customer']['id']);
        $this->assertEquals('1990-01-01', $result[0]['customer']['birthday']);
        $this->assertEquals('123 Main St', $result[0]['customer']['defaultAddress']['address1']);
    }
}
