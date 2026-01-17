<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Offer\OfferManifestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferManifestController extends Controller
{
    public function __construct(
        private OfferManifestService $manifestService
    ) {}

    /**
     * Get manifest summary for an offer
     */
    public function index(int $offer): JsonResponse
    {
        $summary = $this->manifestService->getManifestSummary($offer);
        return response()->json($summary);
    }

    /**
     * Update SKU quantities for an offer
     */
    public function update(Request $request, int $offer): JsonResponse
    {
        $payloadKey = $request->has('manifests') ? 'manifests' : 'sku_qty';

        $validated = $request->validate([
            $payloadKey => 'required|array',
            "$payloadKey.*.sku" => 'required|string',
            "$payloadKey.*.qty" => 'required|integer|min:0',
        ]);

        try {
            $this->manifestService->putSkuQty($offer, $validated[$payloadKey]);
            return response()->json(['message' => 'Manifest quantities updated']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
