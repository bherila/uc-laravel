<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ShopifyShop;
use App\Models\UserShopAccess;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    //
    // Web Routes - Return Blade Views
    //

    /**
     * Show the users management page.
     */
    public function usersPage()
    {
        return view('admin.users');
    }

    /**
     * Show the stores management page.
     */
    public function storesPage()
    {
        return view('admin.stores');
    }

    /**
     * Show the user detail/edit page.
     */
    public function userDetailPage(int $id)
    {
        return view('admin.user-detail', ['userId' => $id]);
    }

    /**
     * Show the store detail/edit page.
     */
    public function storeDetailPage(int $id)
    {
        return view('admin.store-detail', ['storeId' => $id]);
    }

    //
    // API Routes - Return JSON
    //

    /**
     * List all users with their shop accesses.
     */
    public function listUsers(): JsonResponse
    {
        $users = User::with(['shopAccesses.shop:id,name,shop_domain'])
            ->select('id', 'email', 'alias', 'is_admin', 'last_login_at', 'created_at')
            ->orderBy('id')
            ->get();

        return response()->json($users);
    }

    /**
     * Get a single user with shop accesses.
     */
    public function getUser(int $id): JsonResponse
    {
        $user = User::with(['shopAccesses.shop:id,name,shop_domain'])
            ->select('id', 'email', 'alias', 'is_admin', 'last_login_at', 'created_at')
            ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Create a new user.
     */
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'alias' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'alias' => $validated['alias'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Update a user.
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'alias' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8',
            'is_admin' => 'boolean',
        ]);

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (array_key_exists('alias', $validated)) {
            $user->alias = $validated['alias'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (isset($validated['is_admin'])) {
            // Prevent removing admin from user id=1
            if ($id === 1 && !$validated['is_admin']) {
                return response()->json(['error' => 'Cannot remove admin status from user id=1'], 422);
            }
            $user->is_admin = $validated['is_admin'];
        }

        $user->save();

        return response()->json($user);
    }

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): JsonResponse
    {
        if ($id === 1) {
            return response()->json(['error' => 'Cannot delete user id=1'], 422);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Update user shop accesses.
     */
    public function updateUserShopAccesses(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'shop_accesses' => 'required|array',
            'shop_accesses.*.shopify_shop_id' => 'required|exists:shopify_shops,id',
            'shop_accesses.*.access_level' => 'required|in:read-only,read-write',
        ]);

        // Delete existing accesses and recreate
        $user->shopAccesses()->delete();

        foreach ($validated['shop_accesses'] as $access) {
            UserShopAccess::create([
                'user_id' => $id,
                'shopify_shop_id' => $access['shopify_shop_id'],
                'access_level' => $access['access_level'],
            ]);
        }

        $user->load(['shopAccesses.shop:id,name,shop_domain']);

        return response()->json($user);
    }

    /**
     * List all shops.
     */
    public function listStores(): JsonResponse
    {
        $shops = ShopifyShop::select('id', 'name', 'shop_domain', 'is_active', 'api_version', 'created_at')
            ->withCount('offers')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json($shops);
    }

    /**
     * Get a single shop with full details (for editing).
     */
    public function getStore(int $id): JsonResponse
    {
        $shop = ShopifyShop::findOrFail($id);

        // Return sensitive fields for admin editing
        return response()->json([
            'id' => $shop->id,
            'name' => $shop->name,
            'shop_domain' => $shop->shop_domain,
            'app_name' => $shop->app_name,
            'admin_api_token' => $shop->admin_api_token,
            'api_version' => $shop->api_version,
            'api_key' => $shop->api_key,
            'api_secret_key' => $shop->api_secret_key,
            'webhook_version' => $shop->webhook_version,
            'webhook_secret' => $shop->webhook_secret,
            'is_active' => $shop->is_active,
            'created_at' => $shop->created_at,
            'updated_at' => $shop->updated_at,
        ]);
    }

    /**
     * Create a new shop.
     */
    public function createStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shop_domain' => 'required|string|max:255|unique:shopify_shops,shop_domain',
            'app_name' => 'nullable|string|max:1024',
            'admin_api_token' => 'nullable|string|max:1024',
            'api_version' => 'nullable|string|max:1024',
            'api_key' => 'nullable|string|max:1024',
            'api_secret_key' => 'nullable|string|max:1024',
            'webhook_version' => 'nullable|string|max:1024',
            'webhook_secret' => 'nullable|string|max:1024',
            'is_active' => 'boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $isFirstShop = ShopifyShop::count() === 0;

            $shop = ShopifyShop::create([
                'name' => $validated['name'],
                'shop_domain' => $validated['shop_domain'],
                'app_name' => $validated['app_name'] ?? null,
                'admin_api_token' => $validated['admin_api_token'] ?? null,
                'api_version' => $validated['api_version'] ?? '2025-01',
                'api_key' => $validated['api_key'] ?? null,
                'api_secret_key' => $validated['api_secret_key'] ?? null,
                'webhook_version' => $validated['webhook_version'] ?? '2025-01',
                'webhook_secret' => $validated['webhook_secret'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // If this is the first shop, have it "own" any pre-existing offers 
            // that have shop_id set to null.
            if ($isFirstShop) {
                Offer::whereNull('shop_id')->update(['shop_id' => $shop->id]);
            }

            return response()->json($shop, 201);
        });
    }

    /**
     * Update a shop.
     */
    public function updateStore(Request $request, int $id): JsonResponse
    {
        $shop = ShopifyShop::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'shop_domain' => ['sometimes', 'string', 'max:255', Rule::unique('shopify_shops', 'shop_domain')->ignore($id)],
            'app_name' => 'nullable|string|max:1024',
            'admin_api_token' => 'nullable|string|max:1024',
            'api_version' => 'nullable|string|max:1024',
            'api_key' => 'nullable|string|max:1024',
            'api_secret_key' => 'nullable|string|max:1024',
            'webhook_version' => 'nullable|string|max:1024',
            'webhook_secret' => 'nullable|string|max:1024',
            'is_active' => 'boolean',
        ]);

        $shop->fill($validated);
        $shop->save();

        return response()->json($shop);
    }

    /**
     * Delete a shop.
     */
    public function deleteStore(int $id): JsonResponse
    {
        $shop = ShopifyShop::findOrFail($id);

        // Check if shop has offers
        if ($shop->offers()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete shop with existing offers. Reassign or delete offers first.',
            ], 422);
        }

        $shop->delete();

        return response()->json(['success' => true]);
    }
}
