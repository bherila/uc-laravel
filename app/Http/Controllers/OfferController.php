<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Offer\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(
        private OfferService $offerService
    ) {}

    /**
     * List all offers with Shopify product data
     */
    public function index(): JsonResponse
    {
        $result = $this->offerService->loadOfferList();
        return response()->json($result['offerListItems']);
    }

    /**
     * Get a single offer with optional detail mode
     */
    public function show(Request $request, int $offer): JsonResponse
    {
        // Use detailed view if 'detail' query param is set
        if ($request->query('detail')) {
            $result = $this->offerService->getOfferDetail($offer);
        } else {
            $result = $this->offerService->getOffer($offer);
        }

        if (!$result) {
            return response()->json(['error' => 'Offer not found'], 404);
        }

        return response()->json($result);
    }

    /**
     * Create a new offer
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'offer_name' => 'required|string|max:100',
            'offer_variant_id' => 'required|string|max:100',
            'offer_product_name' => 'required|string|max:200',
        ]);

        try {
            $offer = $this->offerService->createOffer(
                $validated['offer_name'],
                $validated['offer_variant_id'],
                $validated['offer_product_name']
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
    public function destroy(int $offer): JsonResponse
    {
        try {
            $this->offerService->deleteOffer($offer);
            return response()->json(['message' => 'Offer deleted']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get and update offer metafields
     */
    public function metafields(int $offer): JsonResponse
    {
        try {
            $metafields = $this->offerService->updateOfferMetafields($offer);

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
    public function orders(int $offer): JsonResponse
    {
        try {
            $orders = $this->offerService->getOfferOrders($offer);

            if (!$orders) {
                return response()->json(['error' => 'Offer not found'], 404);
            }

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
