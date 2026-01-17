<?php

namespace App\Http\Controllers;

use App\Models\ShopifyShop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShopController extends Controller
{
    /**
     * Show the shops list page.
     */
    public function index()
    {
        return view('shops');
    }

    /**
     * Show the shop dashboard page.
     */
    public function show(int $shop)
    {
        return view('shop.dashboard', ['shopId' => $shop]);
    }

    /**
     * List shops accessible to the current user.
     */
    public function listAccessible(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            // Admins see all active shops
            $shops = ShopifyShop::where('is_active', true)
                ->select('id', 'name', 'shop_domain')
                ->withCount('offers')
                ->orderBy('name')
                ->get()
                ->map(function ($shop) {
                    return [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'shop_domain' => $shop->shop_domain,
                        'offers_count' => $shop->offers_count,
                        'access_level' => 'read-write',
                    ];
                });
        } else {
            // Regular users see shops they have access to
            $shops = $user->shops()
                ->where('is_active', true)
                ->select('shopify_shops.id', 'shopify_shops.name', 'shopify_shops.shop_domain')
                ->withCount('offers')
                ->orderBy('shopify_shops.name')
                ->get()
                ->map(function ($shop) {
                    return [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'shop_domain' => $shop->shop_domain,
                        'offers_count' => $shop->offers_count,
                        'access_level' => $shop->pivot->access_level,
                    ];
                });
        }

        return response()->json($shops);
    }
}
