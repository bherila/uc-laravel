<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShopifyShop;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyController extends Controller
{
    /**
     * Get the shop from the request (set by shop.access middleware).
     */
    private function getShop(Request $request): ShopifyShop
    {
        return $request->attributes->get('shop');
    }

    /**
     * Create a ShopifyProductService for the current shop.
     */
    private function makeProductService(Request $request): ShopifyProductService
    {
        $shop = $this->getShop($request);
        $client = new ShopifyClient($shop);
        return new ShopifyProductService($client);
    }

    /**
     * Get Shopify products by type (deal or manifest-item)
     */
    public function products(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $excludeExisting = $request->query('exclude_existing_offers') === '1';

        if (!$type || !in_array($type, ['deal', 'manifest-item'])) {
            return response()->json(['error' => 'Invalid type parameter. Must be "deal" or "manifest-item"'], 400);
        }

        try {
            $productService = $this->makeProductService($request);
            $products = $productService->loadProducts($type);

            if ($excludeExisting) {
                $existingVariantIds = \App\Models\Offer::pluck('offer_variant_id')->toArray();
                $products = array_filter($products, function($p) use ($existingVariantIds) {
                    return !in_array($p['variantId'], $existingVariantIds);
                });
                // Re-index array after filtering
                $products = array_values($products);
            }

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get product data for specific variant IDs
     */
    public function productData(Request $request): JsonResponse
    {
        $variantIds = $request->input('variant_ids', []);

        if (empty($variantIds)) {
            return response()->json(['error' => 'variant_ids required'], 400);
        }

        try {
            $productService = $this->makeProductService($request);
            $data = $productService->getProductDataByVariantIds($variantIds);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Set inventory quantity for a variant
     */
    public function setInventoryQuantity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        try {
            $productService = $this->makeProductService($request);
            $productService->setInventoryQuantity(
                $validated['variant_id'],
                (int) $validated['quantity']
            );
            return response()->json(['message' => 'Inventory updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
