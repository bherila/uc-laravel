<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Offer;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\OfferManifestController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CombineOperationController;

// Home page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('shops');
    }
    return view('welcome');
})->name('home');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Shops list
    Route::get('/shops', [ShopController::class, 'index'])->name('shops');

    // Shop-scoped routes
    Route::middleware('shop.access')->prefix('/shop/{shop}')->group(function () {
        // Shop dashboard
        Route::get('/', [ShopController::class, 'show'])->name('shop.dashboard');

        // Offers
        Route::get('/offers', function (Request $request, $shop) {
            $user = auth()->user();
            $canWrite = $user->isAdmin() || $user->hasShopWriteAccess((int)$shop);
            $shopObj = $request->attributes->get('shop');
            return view('offers', [
                'shopId' => $shop,
                'shopName' => $shopObj?->name,
                'canWrite' => $canWrite
            ]);
        })->name('shop.offers');

        Route::get('/offers/new', function (Request $request, $shop) {
            $shopObj = $request->attributes->get('shop');
            return view('offer-new', [
                'shopId' => $shop,
                'shopName' => $shopObj?->name,
            ]);
        })->name('shop.offers.new');

        Route::get('/offers/{offerId}', function (Request $request, $shop, $offerId) {
            $user = auth()->user();
            $canWrite = $user->isAdmin() || $user->hasShopWriteAccess((int)$shop);
            $offer = Offer::findOrFail($offerId);
            return view('offer-detail', [
                'shopId' => $shop,
                'offerId' => $offerId,
                'offerName' => $offer->offer_name,
                'canWrite' => $canWrite
            ]);
        })->name('shop.offers.show');

        Route::get('/offers/{offerId}/add-manifest', function (Request $request, $shop, $offerId) {
            $user = auth()->user();
            $canWrite = $user->isAdmin() || $user->hasShopWriteAccess((int)$shop);
            $offer = Offer::findOrFail($offerId);
            return view('offer-add-manifest', [
                'shopId' => $shop, 
                'offerId' => $offerId,
                'offerName' => $offer->offer_name,
                'canWrite' => $canWrite
            ]);
        })->name('shop.offers.add-manifest');

        Route::put('/offers/{offer}/add-manifest', [OfferManifestController::class, 'update']);
        Route::put('/offers/{offer}/manifests', [OfferManifestController::class, 'update']);

        Route::get('/offers/{offerId}/profitability', function (Request $request, $shop, $offerId) {
            $offer = Offer::findOrFail($offerId);
            return view('offer-profitability', [
                'shopId' => $shop, 
                'offerId' => $offerId,
                'offerName' => $offer->offer_name,
            ]);
        })->name('shop.offers.profitability');

        Route::get('/offers/{offerId}/metafields', function (Request $request, $shop, $offerId) {
            $offer = Offer::findOrFail($offerId);
            return view('offer-metafields', [
                'shopId' => $shop, 
                'offerId' => $offerId,
                'offerName' => $offer->offer_name,
            ]);
        })->name('shop.offers.metafields');

        Route::get('/offers/{offerId}/shopify_manifests', function (Request $request, $shop, $offerId) {
            $offer = Offer::findOrFail($offerId);
            return view('offer-manifests', [
                'shopId' => $shop, 
                'offerId' => $offerId,
                'offerName' => $offer->offer_name,
            ]);
        })->name('shop.offers.manifests');
    });

    // Admin routes
    Route::middleware('admin')->prefix('/admin')->group(function () {
        Route::get('/users', [AdminController::class, 'usersPage'])->name('admin.users');
        Route::get('/users/{id}', [AdminController::class, 'userDetailPage'])->name('admin.users.detail');

        Route::get('/webhooks', [WebhookController::class, 'indexPage'])->name('admin.webhooks');
        Route::get('/webhooks/{id}', [WebhookController::class, 'showPage'])->name('admin.webhooks.detail');

        Route::get('/audit-logs', [AuditLogController::class, 'indexPage'])->name('admin.audit-logs');

        Route::get('/combine-operations', [CombineOperationController::class, 'indexPage'])->name('admin.combine-operations');
        Route::get('/combine-operations/{id}', [CombineOperationController::class, 'detailPage'])->name('admin.combine-operations.detail');
    });

    // Legacy offer routes (redirect to shops)
    Route::get('/offers', function () {
        return redirect()->route('shops');
    })->name('offers');
});
