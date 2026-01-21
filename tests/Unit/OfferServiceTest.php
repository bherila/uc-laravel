<?php

namespace Tests\Unit;

use App\Models\Offer;
use App\Models\OfferManifest;
use App\Services\Offer\OfferService;
use App\Services\Shopify\ShopifyOrderService;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class OfferServiceTest extends TestCase
{
    use RefreshDatabase;

    private $shopifyProductService;
    private $shopifyOrderService;
    private $offerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shopifyProductService = Mockery::mock(ShopifyProductService::class);
        $this->shopifyOrderService = Mockery::mock(ShopifyOrderService::class);
        $this->offerService = new OfferService(
            $this->shopifyProductService,
            $this->shopifyOrderService
        );
    }

    public function test_update_offer_metafields_generates_correct_structure()
    {
        // 1. Setup Data
        $offer = Offer::create([
            'offer_name' => 'Test Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/DEAL123',
            'offer_product_name' => 'Deal Product',
        ]);

        $itemVariant1 = 'gid://shopify/ProductVariant/ITEM1';
        $itemVariant2 = 'gid://shopify/ProductVariant/ITEM2';

        // 3 items of variant 1
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $itemVariant1, 'assignment_ordering' => 1]);
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $itemVariant1, 'assignment_ordering' => 2]);
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $itemVariant1, 'assignment_ordering' => 3]);

        // 1 item of variant 2
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $itemVariant2, 'assignment_ordering' => 4]);

        // Total qty = 4.
        // Variant 1 qty = 3. Chance = 75%.
        // Variant 2 qty = 1. Chance = 25%.

        // 2. Mock Shopify Responses

        // For the Deal Product
        $this->shopifyProductService->shouldReceive('getProductDataByVariantId')
            ->with('gid://shopify/ProductVariant/DEAL123')
            ->andReturn([
                'productId' => 'gid://shopify/Product/DEAL_PROD_ID',
                'inventoryQuantity' => 10,
                'inventoryItem' => [],
            ]);

        // For the Manifest Items
        $this->shopifyProductService->shouldReceive('getProductDataByVariantIds')
            ->with(Mockery::any()) // It might receive keys of manifestGroups
            ->andReturn([
                $itemVariant1 => [
                    'variantId' => $itemVariant1,
                    'productId' => 'gid://shopify/Product/PROD1',
                    'title' => 'Product 1',
                    'sku' => 'SKU1',
                    'inventoryQuantity' => 100,
                    'priceRange' => [
                        'maxVariantPrice' => ['amount' => '50.0', 'currencyCode' => 'USD']
                    ],
                    'featuredImage' => 'http://img1.com',
                    'inventoryItem' => [
                        'measurement' => ['weight' => ['value' => 1.5, 'unit' => 'kg']],
                        'unitCost' => ['amount' => '20.0', 'currencyCode' => 'USD']
                    ],
                ],
                $itemVariant2 => [
                    'variantId' => $itemVariant2,
                    'productId' => 'gid://shopify/Product/PROD2',
                    'title' => 'Product 2',
                    'sku' => 'SKU2',
                    'inventoryQuantity' => 50,
                    'priceRange' => [
                        'maxVariantPrice' => ['amount' => '100.0', 'currencyCode' => 'USD']
                    ],
                    'featuredImage' => 'http://img2.com',
                    'inventoryItem' => [
                        'measurement' => ['weight' => ['value' => 2.0, 'unit' => 'kg']],
                        'unitCost' => ['amount' => '40.0', 'currencyCode' => 'USD']
                    ],
                ],
            ]);

        // 3. Expect Write Calls
        $this->shopifyProductService->shouldReceive('writeProductMetafield')
            ->with('gid://shopify/Product/DEAL_PROD_ID', 'offer_v3', Mockery::on(function ($json) use ($itemVariant1, $itemVariant2) {
                $data = json_decode($json, true);
                
                // Verify Structure Map
                if (!isset($data[$itemVariant1]) || !isset($data[$itemVariant2])) return false;
                
                // Verify content of item 1
                $i1 = $data[$itemVariant1];
                if ($i1['qty'] !== 3) return false;
                if ($i1['percentChance'] != 75) return false; // 3/4 - loose comparison for int/float
                if ($i1['maxVariantPriceAmount'] !== '50.0') return false;
                if ($i1['featuredImageUrl'] !== 'http://img1.com') return false;
                if ($i1['weight'] !== 1.5) return false;
                if ($i1['unitCost']['amount'] !== '20.0') return false;

                return true;
            }));

        $this->shopifyProductService->shouldReceive('writeProductMetafield')
            ->with('gid://shopify/Product/DEAL_PROD_ID', 'offer_v3_array', Mockery::on(function ($json) {
                $data = json_decode($json, true);
                
                // Verify Items List
                if (!isset($data['items']) || !is_array($data['items'])) return false;
                if (count($data['items']) !== 2) return false;

                // Verify Sorting (Price 50.0 < 100.0)
                if ($data['items'][0]['maxVariantPriceAmount'] !== '50.0') return false;
                if ($data['items'][1]['maxVariantPriceAmount'] !== '100.0') return false;

                // Verify Max Price
                if ($data['maxPrice'] != 100) return false;

                return true;
            }));

        // 4. Run Code
        $this->offerService->updateOfferMetafields($offer->offer_id);
    }
}
