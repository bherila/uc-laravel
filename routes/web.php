<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\OfferManifestController;

// Home page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Shops list
    Route::get('/shops', [ShopController::class, 'index'])->name('shops');

    // Shop-scoped routes
    Route::middleware('shop.access')->prefix('/shop/{shop}')->group(function () {
        // Shop dashboard
        Route::get('/', [ShopController::class, 'show'])->name('shop.dashboard');

        // Offers
        Route::get('/offers', function ($shop) {
            return view('offers', ['shopId' => $shop]);
        })->name('shop.offers');

        Route::get('/offers/new', function ($shop) {
            return view('offer-new', ['shopId' => $shop]);
        })->name('shop.offers.new');

        Route::get('/offers/{offerId}', function ($shop, $offerId) {
            return view('offer-detail', ['shopId' => $shop, 'offerId' => $offerId]);
        })->name('shop.offers.show');

        Route::get('/offers/{offerId}/add-manifest', function ($shop, $offerId) {
            return view('offer-add-manifest', ['shopId' => $shop, 'offerId' => $offerId]);
        })->name('shop.offers.add-manifest');

        Route::put('/offers/{offer}/add-manifest', [OfferManifestController::class, 'update']);
        Route::put('/offers/{offer}/manifests', [OfferManifestController::class, 'update']);

        Route::get('/offers/{offerId}/profitability', function ($shop, $offerId) {
            return view('offer-profitability', ['shopId' => $shop, 'offerId' => $offerId]);
        })->name('shop.offers.profitability');

        Route::get('/offers/{offerId}/metafields', function ($shop, $offerId) {
            return view('offer-metafields', ['shopId' => $shop, 'offerId' => $offerId]);
        })->name('shop.offers.metafields');

        Route::get('/offers/{offerId}/shopify_manifests', function ($shop, $offerId) {
            return view('offer-manifests', ['shopId' => $shop, 'offerId' => $offerId]);
        })->name('shop.offers.manifests');
    });

    // Admin routes
    Route::middleware('admin')->prefix('/admin')->group(function () {
        Route::get('/users', [AdminController::class, 'usersPage'])->name('admin.users');
        Route::get('/users/{id}', [AdminController::class, 'userDetailPage'])->name('admin.users.detail');
        Route::get('/stores/{id}', [AdminController::class, 'storeDetailPage'])->name('admin.stores.detail');
    });

    // Legacy offer routes (redirect to shops)
    Route::get('/offers', function () {
        return redirect()->route('shops');
    })->name('offers');
});
