<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\OfferManifest;
use App\Services\Offer\OfferManifestService;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class OfferManifestServiceTest extends TestCase
{
    use RefreshDatabase;

    private $shopifyProductService;
    private $manifestService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shopifyProductService = Mockery::mock(ShopifyProductService::class);
        $this->manifestService = new OfferManifestService($this->shopifyProductService);
    }

    public function test_put_sku_qty_adds_manifests_correctly()
    {
        // 1. Create an offer
        $offer = Offer::create([
            'offer_name' => 'Test Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/123',
            'offer_product_name' => 'Test Product',
        ]);

        $sku = 'gid://shopify/ProductVariant/456';

        // 2. Initial state: 0 manifests
        $this->assertEquals(0, OfferManifest::where('offer_id', $offer->offer_id)->count());

        // 3. Set qty to 5
        $this->manifestService->putSkuQty($offer->offer_id, [
            ['sku' => $sku, 'qty' => 5]
        ]);

        $this->assertEquals(5, OfferManifest::where('offer_id', $offer->offer_id)->where('mf_variant', $sku)->count());
    }

    public function test_put_sku_qty_removes_unassigned_manifests_correctly()
    {
        $offer = Offer::create([
            'offer_name' => 'Test Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/123',
            'offer_product_name' => 'Test Product',
        ]);

        $sku = 'gid://shopify/ProductVariant/456';

        // 1. Create 5 manifests, 2 of which are assigned
        for ($i = 1; $i <= 3; $i++) {
            OfferManifest::create([
                'offer_id' => $offer->offer_id,
                'mf_variant' => $sku,
                'assignee_id' => null,
                'assignment_ordering' => $i
            ]);
        }
        for ($i = 4; $i <= 5; $i++) {
            OfferManifest::create([
                'offer_id' => $offer->offer_id,
                'mf_variant' => $sku,
                'assignee_id' => 'gid://shopify/Order/999',
                'assignment_ordering' => $i
            ]);
        }

        $this->assertEquals(5, OfferManifest::where('offer_id', $offer->offer_id)->count());
        $this->assertEquals(3, OfferManifest::where('offer_id', $offer->offer_id)->whereNull('assignee_id')->count());

        // 2. Set qty to 3 (we have 2 assigned, so it should keep 2 assigned + 1 unassigned = 3 total)
        // It should delete 2 unassigned manifests.
        $this->manifestService->putSkuQty($offer->offer_id, [
            ['sku' => $sku, 'qty' => 3]
        ]);

        $this->assertEquals(3, OfferManifest::where('offer_id', $offer->offer_id)->count(), 'Total count should be 3');
        $this->assertEquals(2, OfferManifest::where('offer_id', $offer->offer_id)->whereNotNull('assignee_id')->count(), 'Assigned should remain');
        $this->assertEquals(1, OfferManifest::where('offer_id', $offer->offer_id)->whereNull('assignee_id')->count(), 'Should have 1 unassigned left');
    }

    public function test_put_sku_qty_does_not_remove_assigned_manifests()
    {
        $offer = Offer::create([
            'offer_name' => 'Test Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/123',
            'offer_product_name' => 'Test Product',
        ]);

        $sku = 'gid://shopify/ProductVariant/456';

        // 1. Create 3 manifests, all assigned
        for ($i = 1; $i <= 3; $i++) {
            OfferManifest::create([
                'offer_id' => $offer->offer_id,
                'mf_variant' => $sku,
                'assignee_id' => 'gid://shopify/Order/999',
                'assignment_ordering' => $i
            ]);
        }

        // 2. Set qty to 1 (we have 3 assigned, none unassigned)
        // It cannot delete assigned ones, so it should stay at 3.
        $this->manifestService->putSkuQty($offer->offer_id, [
            ['sku' => $sku, 'qty' => 1]
        ]);

        $this->assertEquals(3, OfferManifest::where('offer_id', $offer->offer_id)->count(), 'Should still have 3 because all are assigned');
    }

    public function test_put_sku_qty_increases_correctly_when_some_are_assigned()
    {
        $offer = Offer::create([
            'offer_name' => 'Test Offer',
            'offer_variant_id' => 'gid://shopify/ProductVariant/123',
            'offer_product_name' => 'Test Product',
        ]);

        $sku = 'gid://shopify/ProductVariant/456';

        // 1. Create 3 manifests, 2 assigned
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $sku, 'assignee_id' => 'order1', 'assignment_ordering' => 1]);
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $sku, 'assignee_id' => 'order2', 'assignment_ordering' => 2]);
        OfferManifest::create(['offer_id' => $offer->offer_id, 'mf_variant' => $sku, 'assignee_id' => null, 'assignment_ordering' => 3]);

        // Total = 3.
        
        // 2. Set qty to 5. Should add 2 more.
        $this->manifestService->putSkuQty($offer->offer_id, [
            ['sku' => $sku, 'qty' => 5]
        ]);

        $this->assertEquals(5, OfferManifest::where('offer_id', $offer->offer_id)->count());
        $this->assertEquals(2, OfferManifest::where('offer_id', $offer->offer_id)->whereNotNull('assignee_id')->count());
        $this->assertEquals(3, OfferManifest::where('offer_id', $offer->offer_id)->whereNull('assignee_id')->count());
    }
}
