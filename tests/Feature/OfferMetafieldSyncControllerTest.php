<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\ShopifyShop;
use App\Services\Offer\OfferService;
use App\Services\Shopify\ShopifyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class OfferMetafieldSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    private $shop;
    private $offer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = ShopifyShop::create([
            'id' => 1,
            'name' => 'Test Shop',
            'shop_domain' => 'test.myshopify.com',
            'is_active' => true,
            'admin_api_token' => 'token',
        ]);

        $this->offer = Offer::create([
            'shop_id' => $this->shop->id,
            'offer_name' => 'Test Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/123',
            'offer_product_name' => 'Test Product',
        ]);

        // Mock User
        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('hasShopAccess')->andReturn(true);
        $user->shouldReceive('hasShopWriteAccess')->andReturn(true);
        $this->actingAs($user);
    }

    public function test_metafields_get_request_only_previews_data()
    {
        $mock = Mockery::mock(OfferService::class);
        $mock->shouldReceive('generateOfferMetafields')
            ->once()
            ->with($this->offer->offer_id)
            ->andReturn(['offerV3' => '{}', 'offerV3Array' => '{}']);
        
        $mock->shouldNotReceive('updateOfferMetafields');

        app()->instance(OfferService::class, $mock);

        $response = $this->get("/api/shops/{$this->shop->id}/offers/{$this->offer->offer_id}/metafields");

        $response->assertStatus(200);
    }

    public function test_metafields_push_request_triggers_sync()
    {
        $mock = Mockery::mock(OfferService::class);
        $mock->shouldReceive('updateOfferMetafields')
            ->once()
            ->with($this->offer->offer_id)
            ->andReturn(['offerV3' => '{}', 'offerV3Array' => '{}']);

        app()->instance(OfferService::class, $mock);

        $response = $this->get("/api/shops/{$this->shop->id}/offers/{$this->offer->offer_id}/metafields?push=true");

        $response->assertStatus(200);
    }

    public function test_manifest_update_triggers_metafield_sync()
    {
        $mock = Mockery::mock(OfferService::class);
        $mock->shouldReceive('updateOfferMetafields')
            ->once()
            ->with($this->offer->offer_id)
            ->andReturn([]);

        app()->instance(OfferService::class, $mock);

        $response = $this->putJson("/api/shops/{$this->shop->id}/offers/{$this->offer->offer_id}/manifests", [
            'manifests' => [['sku' => 'gid://shopify/ProductVariant/456', 'qty' => 5]]
        ]);

        $response->assertStatus(200);
    }

    public function test_manifest_import_triggers_metafield_sync()
    {
        $mock = Mockery::mock(OfferService::class);
        $mock->shouldReceive('updateOfferMetafields')
            ->once()
            ->with($this->offer->offer_id)
            ->andReturn([]);

        app()->instance(OfferService::class, $mock);

        $response = $this->postJson("/api/shops/{$this->shop->id}/offers/{$this->offer->offer_id}/manifests/import", [
            'items' => [['sku' => 'gid://shopify/ProductVariant/456', 'qty' => 5]]
        ]);

        $response->assertStatus(200);
    }

    public function test_manifest_delete_item_triggers_metafield_sync()
    {
        $mock = Mockery::mock(OfferService::class);
        $mock->shouldReceive('updateOfferMetafields')
            ->once()
            ->with($this->offer->offer_id)
            ->andReturn([]);

        app()->instance(OfferService::class, $mock);

        // Deleting item is done via PUT manifests with qty 0
        $response = $this->putJson("/api/shops/{$this->shop->id}/offers/{$this->offer->offer_id}/manifests", [
            'manifests' => [['sku' => 'gid://shopify/ProductVariant/456', 'qty' => 0]]
        ]);

        $response->assertStatus(200);
    }
}
