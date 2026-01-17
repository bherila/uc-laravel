<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OfferManifestController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShopifyWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Offers
Route::apiResource('offers', OfferController::class)->only(['index', 'show', 'store', 'destroy']);
Route::get('offers/{offer}/metafields', [OfferController::class, 'metafields']);
Route::get('offers/{offer}/orders', [OfferController::class, 'orders']);

// Offer Manifests
Route::get('offers/{offer}/manifests', [OfferManifestController::class, 'index']);
Route::put('offers/{offer}/manifests', [OfferManifestController::class, 'update']);

// Shopify
Route::get('shopify/products', [ShopifyController::class, 'products']);
Route::post('shopify/product-data', [ShopifyController::class, 'productData']);
Route::post('shopify/set-inventory', [ShopifyController::class, 'setInventoryQuantity']);

// Shopify Webhook (no auth - uses HMAC verification)
Route::post('shopify/webhook', [ShopifyWebhookController::class, 'handle'])
    ->withoutMiddleware(['auth:sanctum', 'throttle:api']);
