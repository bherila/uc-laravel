<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OfferManifestController;

// Home page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Offers (protected routes)
Route::middleware('auth')->group(function () {
    Route::get('/offers', function () {
        return view('offers');
    })->name('offers');

    Route::get('/offers/new', function () {
        return view('offer-new');
    })->name('offers.new');

    Route::get('/offers/{offerId}', function ($offerId) {
        return view('offer-detail', ['offerId' => $offerId]);
    })->name('offers.show');

    Route::get('/offers/{offerId}/add-manifest', function ($offerId) {
        return view('offer-add-manifest', ['offerId' => $offerId]);
    })->name('offers.add-manifest');

    Route::put('/offers/{offer}/add-manifest', [OfferManifestController::class, 'update']);
    Route::put('/offers/{offer}/manifests', [OfferManifestController::class, 'update']);

    Route::get('/offers/{offerId}/profitability', function ($offerId) {
        return view('offer-profitability', ['offerId' => $offerId]);
    })->name('offers.profitability');

    Route::get('/offers/{offerId}/metafields', function ($offerId) {
        return view('offer-metafields', ['offerId' => $offerId]);
    })->name('offers.metafields');

    Route::get('/offers/{offerId}/shopify_manifests', function ($offerId) {
        return view('offer-manifests', ['offerId' => $offerId]);
    })->name('offers.manifests');
});
