<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\ShopifyShop;
use App\Services\Offer\OfferService;
use App\Services\Shopify\ShopifyOrderService;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class OfferVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private $shopifyProductService;
    private $shopifyOrderService;
    private $offerService;
    private $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = ShopifyShop::create([
            'id' => 1,
            'name' => 'Test Shop',
            'shop_domain' => 'test.myshopify.com',
            'is_active' => true,
        ]);

        $this->shopifyProductService = Mockery::mock(ShopifyProductService::class);
        $this->shopifyOrderService = Mockery::mock(ShopifyOrderService::class);
        $this->offerService = new OfferService(
            $this->shopifyProductService,
            $this->shopifyOrderService
        );
    }

    public function test_active_offer_shows_in_list()
    {
        // 1. Create an active offer
        $offer = Offer::create([
            'shop_id' => $this->shop->id,
            'offer_name' => 'Active Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/1',
            'offer_product_name' => 'Active Product',
            'is_archived' => false,
        ]);

        // 2. Mock Shopify returning data
        $this->shopifyProductService->shouldReceive('getProductDataByVariantIds')
            ->with(['gid://shopify/ProductVariant/1'])
            ->andReturn([
                'gid://shopify/ProductVariant/1' => [
                    'variantId' => 'gid://shopify/ProductVariant/1',
                    'title' => 'Active Product',
                    'sku' => 'SKU1',
                ]
            ]);

        // 3. Load list
        $result = $this->offerService->loadOfferList($this->shop->id, 'active');

        // 4. Assert present
        $this->assertEquals(1, $result->count());
        $this->assertEquals($offer->offer_id, $result->items()[0]['offer_id']);
        $this->assertEquals('Active Product', $result->items()[0]['offer_product_name']);
    }

    public function test_active_offer_shows_even_if_shopify_returns_null()
    {
        // 1. Create an active offer
        $offer = Offer::create([
            'shop_id' => $this->shop->id,
            'offer_name' => 'Future Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/2',
            'offer_product_name' => 'Future Product',
            'is_archived' => false,
        ]);

        // 2. Mock Shopify returning empty (simulating node not found or filtered by Shopify)
        $this->shopifyProductService->shouldReceive('getProductDataByVariantIds')
            ->with(['gid://shopify/ProductVariant/2'])
            ->andReturn([]);

        // 3. Load list
        $result = $this->offerService->loadOfferList($this->shop->id, 'active');

        // 4. Assert present
        $this->assertEquals(1, $result->count());
        $this->assertEquals($offer->offer_id, $result->items()[0]['offer_id']);
        $this->assertEquals('Future Product', $result->items()[0]['offer_product_name']);
        $this->assertNull($result->items()[0]['offerProductData']);
    }

    public function test_archived_offer_does_not_show_in_active_list()
    {
        // 1. Create an archived offer
        $offer = Offer::create([
            'shop_id' => $this->shop->id,
            'offer_name' => 'Archived Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/3',
            'offer_product_name' => 'Archived Product',
            'is_archived' => true,
        ]);

        // 2. Expect getProductDataByVariantIds to be called with empty array (or not called if paginator is empty, but implementation calls it)
        // Since paginator returns 0 items, pluck returns empty array.
        $this->shopifyProductService->shouldReceive('getProductDataByVariantIds')
            ->with([])
            ->andReturn([]);

        // 3. Load list
        $result = $this->offerService->loadOfferList($this->shop->id, 'active');

        // 4. Assert NOT present
        $this->assertEquals(0, $result->count());
    }
}
