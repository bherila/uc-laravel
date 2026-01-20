<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShopifyShop;
use App\Services\Offer\OfferService;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyProductService;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * Get the shop from the request (set by shop.access middleware).
     */
    private function getShop(Request $request): ShopifyShop
    {
        return $request->attributes->get('shop');
    }

    /**
     * Create an OfferService for the current shop.
     */
    private function makeOfferService(Request $request): OfferService
    {
        $shop = $this->getShop($request);
        $client = new ShopifyClient($shop);
        $productService = new ShopifyProductService($client);
        $orderService = new ShopifyOrderService($client);
        return new OfferService($productService, $orderService);
    }

    /**
     * List all offers with Shopify product data (shop-scoped)
     */
    public function index(Request $request, int $shop): JsonResponse
    {
        $status = $request->query('status', 'active');
        $offerService = $this->makeOfferService($request);
        $result = $offerService->loadOfferList($shop, $status);
        return response()->json($result);
    }

    /**
     * Get count of offers that can be archived (ended > 30d ago)
     */
    public function cleanupCount(Request $request, int $shop): JsonResponse
    {
        $offerService = $this->makeOfferService($request);
        $count = $offerService->getCleanupOffers($shop)->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Bulk archive offers that ended > 30d ago
     */
    public function cleanup(Request $request, int $shop): JsonResponse
    {
        $offerService = $this->makeOfferService($request);
        $count = $offerService->cleanupOffers($shop);
        return response()->json([
            'message' => "Successfully archived {$count} offers",
            'count' => $count
        ]);
    }

    /**
     * Archive an offer
     */
    public function archive(Request $request, int $shop, int $offer): JsonResponse
    {
        $offerService = $this->makeOfferService($request);
        $offerService->setArchived($offer, true);
        return response()->json(['message' => 'Offer archived']);
    }

    /**
     * Unarchive an offer
     */
    public function unarchive(Request $request, int $shop, int $offer): JsonResponse
    {
        $offerService = $this->makeOfferService($request);
        $offerService->setArchived($offer, false);
        return response()->json(['message' => 'Offer unarchived']);
    }

    /**
     * Get a single offer with optional detail mode
     */
    public function show(Request $request, int $shop, int $offer): JsonResponse
    {
        $offerService = $this->makeOfferService($request);

        // Use detailed view if 'detail' query param is set
        if ($request->query('detail')) {
            $result = $offerService->getOfferDetail($offer);
        } else {
            $result = $offerService->getOffer($offer);
        }

        if (!$result) {
            return response()->json(['error' => 'Offer not found'], 404);
        }

        return response()->json($result);
    }

    /**
     * Create a new offer
     */
    public function store(Request $request, int $shop): JsonResponse
    {
        $validated = $request->validate([
            'offer_name' => 'required|string|max:100',
            'offer_variant_id' => 'required|string|max:100',
            'offer_product_name' => 'required|string|max:200',
        ]);

        try {
            $offerService = $this->makeOfferService($request);
            $offer = $offerService->createOffer(
                $validated['offer_name'],
                $validated['offer_variant_id'],
                $validated['offer_product_name'],
                $shop
            );

            return response()->json([
                'offer_id' => $offer->offer_id,
                'message' => 'Offer created successfully',
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete an offer
     */
    public function destroy(Request $request, int $shop, int $offer): JsonResponse
    {
        try {
            $offerService = $this->makeOfferService($request);
            $offerService->deleteOffer($offer);
            return response()->json(['message' => 'Offer deleted']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get and update offer metafields
     */
    public function metafields(Request $request, int $shop, int $offer): JsonResponse
    {
        try {
            $offerService = $this->makeOfferService($request);
            $metafields = $offerService->updateOfferMetafields($offer);

            if (!$metafields) {
                return response()->json(['error' => 'Offer not found or missing product data'], 404);
            }

            return response()->json($metafields);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get order manifests for an offer
     */
    public function orders(Request $request, int $shop, int $offer): JsonResponse
    {
        try {
            $offerService = $this->makeOfferService($request);
            $orders = $offerService->getOfferOrders($offer);

            if (!$orders) {
                return response()->json(['error' => 'Offer not found'], 404);
            }

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Force reload Shopify data for an offer
     */
    public function forceReload(Request $request, int $shop, int $offer): JsonResponse
    {
        try {
            $offerService = $this->makeOfferService($request);
            $offerService->forceReload($offer);
            return response()->json(['message' => 'Cache flushed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
