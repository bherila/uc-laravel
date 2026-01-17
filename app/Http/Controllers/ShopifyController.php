<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Shopify\ShopifyProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyController extends Controller
{
    public function __construct(
        private ShopifyProductService $productService
    ) {}

    /**
     * Get Shopify products by type (deal or manifest-item)
     */
    public function products(Request $request): JsonResponse
    {
        $type = $request->query('type');

        if (!$type || !in_array($type, ['deal', 'manifest-item'])) {
            return response()->json(['error' => 'Invalid type parameter. Must be "deal" or "manifest-item"'], 400);
        }

        try {
            $products = $this->productService->loadProducts($type);
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
            $data = $this->productService->getProductDataByVariantIds($variantIds);
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
            $this->productService->setInventoryQuantity(
                $validated['variant_id'],
                (int) $validated['quantity']
            );
            return response()->json(['message' => 'Inventory updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
