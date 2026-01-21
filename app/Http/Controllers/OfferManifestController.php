<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Offer\OfferManifestService;
use App\Services\Shopify\ShopifyProductService;
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
        $productService = new \App\Services\Shopify\ShopifyProductService($client);
        $manifestService = new \App\Services\Offer\OfferManifestService($productService);

        return [$manifestService, $productService];
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
        [$manifestService] = $this->makeServices($request);
        $payloadKey = $request->has('manifests') ? 'manifests' : 'sku_qty';

        $validated = $request->validate([
            $payloadKey => 'required|array',
            "$payloadKey.*.sku" => 'required|string',
            "$payloadKey.*.qty" => 'required|integer|min:0',
        ]);

        try {
            $manifestService->putSkuQty($offer, $validated[$payloadKey]);
            return response()->json(['message' => 'Manifest quantities updated']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Import manifest items by SKU and quantity
     */
    public function import(Request $request, int $shop, int $offer): JsonResponse
    {
        [$manifestService, $productService] = $this->makeServices($request);
        
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|string',
            'items.*.qty' => 'required|integer|min:0',
        ]);

        $items = $validated['items'];
        $resolvedManifests = [];
        $errors = [];

        foreach ($items as $item) {
            $sku = $item['sku'];
            $qty = $item['qty'];

            // If it's already a GID, use it directly
            if (str_starts_with($sku, 'gid://shopify/ProductVariant/')) {
                $resolvedManifests[] = ['sku' => $sku, 'qty' => $qty];
                continue;
            }

            // Resolve SKU to Variant ID
            $variantId = $productService->getVariantIdBySku($sku);
            if ($variantId) {
                $resolvedManifests[] = ['sku' => $variantId, 'qty' => $qty];
            } else {
                $errors[] = "Could not resolve SKU: {$sku}";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'error' => 'Some SKUs could not be resolved',
                'details' => $errors
            ], 422);
        }

        try {
            $manifestService->putSkuQty($offer, $resolvedManifests);
            return response()->json(['message' => 'Successfully imported ' . count($resolvedManifests) . ' products']);
        } catch (\Exception $e) {
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
        $results = [];

        foreach ($skus as $sku) {
            $variantId = null;
            
            // If it's already a GID, use it directly
            if (str_starts_with($sku, 'gid://shopify/ProductVariant/')) {
                $variantId = $sku;
            } else {
                $variantId = $productService->getVariantIdBySku($sku);
            }

            if ($variantId) {
                $productData = $productService->getProductDataByVariantId($variantId);
                $results[$sku] = [
                    'valid' => true,
                    'variantId' => $variantId,
                    'productName' => $productData['title'] ?? 'Unknown Product',
                ];
            } else {
                $results[$sku] = [
                    'valid' => false,
                    'error' => 'Not found',
                ];
            }
        }

        return response()->json($results);
    }
}
