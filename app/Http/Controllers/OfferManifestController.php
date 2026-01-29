<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Offer\OfferManifestService;
use App\Services\Shopify\ShopifyProductService;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferManifestController extends Controller
{
    /**
     * Get the shop from the request (set by shop.access middleware).
     */
    private function getShop(Request $request): \App\Models\ShopifyShop
    {
        return $request->attributes->get('shop');
    }

    /**
     * Create services configured for the current shop.
     */
    private function makeServices(Request $request): array
    {
        $shop = $this->getShop($request);
        $client = new \App\Services\Shopify\ShopifyClient($shop);
        
        // Register services in the container for this request scope
        app()->singleton(\App\Services\Shopify\ShopifyClient::class, fn() => $client);
        app()->singleton(\App\Services\Shopify\ShopifyProductService::class, fn() => new \App\Services\Shopify\ShopifyProductService($client));
        app()->singleton(\App\Services\Shopify\ShopifyOrderService::class, fn() => new \App\Services\Shopify\ShopifyOrderService($client));
        
        $manifestService = app(\App\Services\Offer\OfferManifestService::class);
        $productService = app(\App\Services\Shopify\ShopifyProductService::class);
        $offerService = app(\App\Services\Offer\OfferService::class);

        return [$manifestService, $productService, $offerService];
    }

    /**
     * Get manifest summary for an offer
     */
    public function index(Request $request, int $offer): JsonResponse
    {
        [$manifestService] = $this->makeServices($request);
        $summary = $manifestService->getManifestSummary($offer);
        return response()->json($summary);
    }

    /**
     * Update SKU quantities for an offer
     */
    public function update(Request $request, int $offer): JsonResponse
    {
        [$manifestService, , $offerService] = $this->makeServices($request);
        $payloadKey = $request->has('manifests') ? 'manifests' : 'sku_qty';

        try {
            $validated = $request->validate([
                $payloadKey => 'required|array',
                "$payloadKey.*.sku" => 'required|string',
                "$payloadKey.*.qty" => 'required|integer|min:0',
            ]);

            $manifestService->putSkuQty($offer, $validated[$payloadKey]);
            
            // Sync metafields after manifest update
            $offerService->updateOfferMetafields($offer);
            
            AuditLog::create([
                'event_name' => 'manifest.update',
                'event_ts' => now(),
                'event_userid' => auth()->id(),
                'offer_id' => $offer,
                'event_ext' => json_encode([
                    'payload' => $validated[$payloadKey],
                    'status' => 'success'
                ]),
            ]);

            return response()->json(['message' => 'Manifest quantities updated']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            AuditLog::create([
                'event_name' => 'manifest.update_validation_error',
                'event_ts' => now(),
                'event_userid' => auth()->id(),
                'offer_id' => $offer,
                'event_ext' => json_encode([
                    'payload' => $request->all(),
                    'errors' => $e->errors(),
                ]),
            ]);
            throw $e;
        } catch (\Exception $e) {
            AuditLog::create([
                'event_name' => 'manifest.update_error',
                'event_ts' => now(),
                'event_userid' => auth()->id(),
                'offer_id' => $offer,
                'event_ext' => json_encode([
                    'payload' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]),
            ]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Import manifest items by SKU and quantity
     */
    public function import(Request $request, int $shop, int $offer): JsonResponse
    {
        [$manifestService, $productService, $offerService] = $this->makeServices($request);
        
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|string',
            'items.*.qty' => 'required|integer|min:0',
        ]);

        $items = $validated['items'];
        $skusToResolve = [];
        $resolvedManifests = [];
        $skuToQty = [];

        foreach ($items as $item) {
            $sku = $item['sku'];
            $qty = $item['qty'];

            if (str_starts_with($sku, 'gid://shopify/ProductVariant/')) {
                $resolvedManifests[] = ['sku' => $sku, 'qty' => $qty];
            } else {
                $skusToResolve[] = $sku;
                $skuToQty[$sku] = $qty;
            }
        }

        if (!empty($skusToResolve)) {
            $resolvedSkus = $productService->getVariantIdsBySkus($skusToResolve);
            foreach ($skusToResolve as $sku) {
                if (isset($resolvedSkus[$sku])) {
                    $resolvedManifests[] = ['sku' => $resolvedSkus[$sku], 'qty' => $skuToQty[$sku]];
                } else {
                    return response()->json([
                        'error' => 'Some SKUs could not be resolved',
                        'details' => ["Could not resolve SKU: {$sku}"]
                    ], 422);
                }
            }
        }

        try {
            $manifestService->putSkuQty($offer, $resolvedManifests);

            // Sync metafields after manifest import
            $offerService->updateOfferMetafields($offer);

            AuditLog::create([
                'event_name' => 'manifest.import',
                'event_ts' => now(),
                'event_userid' => auth()->id(),
                'offer_id' => $offer,
                'event_ext' => json_encode([
                    'count' => count($resolvedManifests),
                    'status' => 'success'
                ]),
            ]);

            return response()->json(['message' => 'Successfully imported ' . count($resolvedManifests) . ' products']);
        } catch (\Exception $e) {
            AuditLog::create([
                'event_name' => 'manifest.import_error',
                'event_ts' => now(),
                'event_userid' => auth()->id(),
                'offer_id' => $offer,
                'event_ext' => json_encode([
                    'payload' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]),
            ]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Validate a list of SKUs against Shopify
     */
    public function validate(Request $request, int $shop, int $offer): JsonResponse
    {
        [, $productService] = $this->makeServices($request);

        $validated = $request->validate([
            'skus' => 'required|array',
            'skus.*' => 'required|string',
        ]);

        $skus = array_unique($validated['skus']);
        $skusToResolve = [];
        $skuToGid = [];
        $results = [];

        foreach ($skus as $sku) {
            if (str_starts_with($sku, 'gid://shopify/ProductVariant/')) {
                $skuToGid[$sku] = $sku;
            } else {
                $skusToResolve[] = $sku;
            }
        }

        // Batch resolve SKUs to GIDs
        if (!empty($skusToResolve)) {
            $resolvedSkus = $productService->getVariantIdsBySkus($skusToResolve);
            foreach ($skusToResolve as $sku) {
                if (isset($resolvedSkus[$sku])) {
                    $skuToGid[$sku] = $resolvedSkus[$sku];
                } else {
                    $results[$sku] = ['valid' => false, 'error' => 'Not found'];
                }
            }
        }

        // Batch fetch product data for all resolved GIDs
        $allGids = array_values(array_filter($skuToGid));
        $allProductData = $productService->getProductDataByVariantIds($allGids);

        // Get current manifest counts for this offer to show "current qty in offer"
        $currentCounts = \App\Models\OfferManifest::where('offer_id', $offer)
            ->whereIn('mf_variant', $allGids)
            ->selectRaw('mf_variant, COUNT(*) as count')
            ->groupBy('mf_variant')
            ->pluck('count', 'mf_variant')
            ->toArray();

        foreach ($skuToGid as $sku => $gid) {
            if (isset($allProductData[$gid])) {
                $results[$sku] = [
                    'valid' => true,
                    'variantId' => $gid,
                    'productName' => $allProductData[$gid]['title'] ?? 'Unknown Product',
                    'currentQty' => $currentCounts[$gid] ?? 0,
                ];
            } else {
                $results[$sku] = ['valid' => false, 'error' => 'Not found in Shopify'];
            }
        }

        return response()->json($results);
    }
}
