<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Webhook;
use App\Models\ShopifyShop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $shop1;
    protected $shop2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_admin' => true]);
        
        $this->shop1 = ShopifyShop::create([
            'name' => 'Shop 1',
            'shop_domain' => 'shop1.myshopify.com',
            'api_version' => '2025-01',
        ]);

        $this->shop2 = ShopifyShop::create([
            'name' => 'Shop 2',
            'shop_domain' => 'shop2.myshopify.com',
            'api_version' => '2025-01',
        ]);

        // Create webhooks for shop 1
        Webhook::create(['shop_id' => $this->shop1->id, 'shopify_topic' => 'orders/paid']);
        Webhook::create(['shop_id' => $this->shop1->id, 'shopify_topic' => 'orders/fulfilled']);

        // Create webhooks for shop 2
        Webhook::create(['shop_id' => $this->shop2->id, 'shopify_topic' => 'orders/paid']);

        // Create unmatched webhooks
        Webhook::create(['shop_id' => null, 'shopify_topic' => 'unmatched/topic']);
    }

    public function test_can_list_all_webhooks(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/webhooks');

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_can_filter_webhooks_by_shop(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/webhooks?shop_id=' . $this->shop1->id);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        foreach ($response->json('data') as $webhook) {
            $this->assertEquals($this->shop1->id, $webhook['shop_id']);
        }
    }

    public function test_can_filter_unmatched_webhooks(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/webhooks?shop_id=unmatched');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertNull($response->json('data.0.shop_id'));
    }

    public function test_can_explicitly_request_all_webhooks(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/webhooks?shop_id=all');

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }
}
