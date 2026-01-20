<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OfferManifestController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShopifyWebhookController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\PasswordResetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Password Reset Routes
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('reset-password', [PasswordResetController::class, 'reset']);

// Shops accessible to current user
Route::middleware('auth')->get('shops', [ShopController::class, 'listAccessible']);

// Shop-scoped routes
Route::middleware(['auth', 'shop.access'])->prefix('shops/{shop}')->group(function () {
    // Offers
    Route::get('offers', [OfferController::class, 'index']);
    Route::get('offers/cleanup-count', [OfferController::class, 'cleanupCount']);
    Route::post('offers/cleanup', [OfferController::class, 'cleanup'])->middleware('shop.access:write');
    Route::post('offers', [OfferController::class, 'store'])->middleware('shop.access:write');
    Route::get('offers/{offer}', [OfferController::class, 'show']);
    Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->middleware('shop.access:write');
    Route::post('offers/{offer}/archive', [OfferController::class, 'archive'])->middleware('shop.access:write');
    Route::post('offers/{offer}/unarchive', [OfferController::class, 'unarchive'])->middleware('shop.access:write');
    Route::post('offers/{offer}/force-reload', [OfferController::class, 'forceReload'])->middleware('shop.access:write');
    Route::get('offers/{offer}/metafields', [OfferController::class, 'metafields']);
    Route::get('offers/{offer}/orders', [OfferController::class, 'orders']);

    // Offer Manifests
    Route::get('offers/{offer}/manifests', [OfferManifestController::class, 'index']);
    Route::put('offers/{offer}/manifests', [OfferManifestController::class, 'update'])->middleware('shop.access:write');

    // Shopify (shop-specific)
    Route::get('shopify/products', [ShopifyController::class, 'products']);
    Route::post('shopify/product-data', [ShopifyController::class, 'productData']);
    Route::post('shopify/set-inventory', [ShopifyController::class, 'setInventoryQuantity'])->middleware('shop.access:write');
});

// Admin API routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Users
    Route::get('users', [AdminController::class, 'listUsers']);
    Route::post('users', [AdminController::class, 'createUser']);
    Route::get('users/{id}', [AdminController::class, 'getUser']);
    Route::put('users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
    Route::put('users/{id}/shop-accesses', [AdminController::class, 'updateUserShopAccesses']);

    // Stores
    Route::get('stores', [AdminController::class, 'listStores']);
    Route::post('stores', [AdminController::class, 'createStore']);
    Route::get('stores/{id}', [AdminController::class, 'getStore']);
    Route::put('stores/{id}', [AdminController::class, 'updateStore']);
    Route::delete('stores/{id}', [AdminController::class, 'deleteStore']);

    // Webhooks
    Route::get('webhooks', [WebhookController::class, 'list']);
    Route::get('webhooks/{id}', [WebhookController::class, 'get']);
    Route::post('webhooks/{id}/rerun', [WebhookController::class, 'rerun']);

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'list']);
});

// Shopify Webhook (no auth - uses HMAC verification and shop domain lookup)
Route::post('shopify/webhook', [ShopifyWebhookController::class, 'handle'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ]);
