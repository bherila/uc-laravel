<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Offer\OfferManifestService;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferManifestController extends Controller
{
    public function __construct(
        private OfferManifestService $manifestService,
        private ShopifyProductService $productService
    ) {}

    /**
     * Get manifest summary for an offer
     */
    public function index(int $offer): JsonResponse
    {
        $summary = $this->manifestService->getManifestSummary($offer);
        return response()->json($summary);
    }

    /**
     * Update SKU quantities for an offer
     */
    public function update(Request $request, int $offer): JsonResponse
    {
        $payloadKey = $request->has('manifests') ? 'manifests' : 'sku_qty';

        $validated = $request->validate([
            $payloadKey => 'required|array',
            "$payloadKey.*.sku" => 'required|string',
            "$payloadKey.*.qty" => 'required|integer|min:0',
        ]);

        try {
            $this->manifestService->putSkuQty($offer, $validated[$payloadKey]);
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
            $variantId = $this->productService->getVariantIdBySku($sku);
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
            $this->manifestService->putSkuQty($offer, $resolvedManifests);
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
                $variantId = $this->productService->getVariantIdBySku($sku);
            }

            if ($variantId) {
                $productData = $this->productService->getProductDataByVariantId($variantId);
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
