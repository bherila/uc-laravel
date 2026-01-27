<?php

declare(strict_types=1);

namespace App\Services\Offer;

use App\Models\Offer;
use App\Models\OfferManifest;
use App\Services\Shopify\ShopifyProductService;
use Illuminate\Support\Facades\DB;

class OfferManifestService
{
    public function __construct(
        private ShopifyProductService $shopifyProductService
    ) {}

    /**
     * Get the appropriate random function for the current database driver
     */
    private function getRandomFunction(): string
    {
        $driver = DB::connection()->getDriverName();
        return $driver === 'sqlite' ? 'RANDOM()' : 'RAND()';
    }

    /**
     * Reshuffle assignment ordering for all unassigned manifests in an offer
     */
    public function reshuffleUnassignedManifests(int $offerId): int
    {
        $randomFn = $this->getRandomFunction();
        return OfferManifest::where('offer_id', $offerId)
            ->whereNull('assignee_id')
            ->update(['assignment_ordering' => DB::raw($randomFn)]);
    }

    /**
     * Update offer manifests with new SKU quantities
     *
     * @param int $offerId
     * @param array<array{sku: string, qty: int}> $skuQtyToSet
     * @return Offer
     */
    public function putSkuQty(int $offerId, array $skuQtyToSet): Offer
    {
        $offer = Offer::findOrFail($offerId);
        $manifestsChanged = false;

        DB::transaction(function () use ($offerId, $skuQtyToSet, &$manifestsChanged) {
            foreach ($skuQtyToSet as $item) {
                $sku = $item['sku'];
                $qty = $item['qty'];

                // Get total count of manifests for this SKU (assigned + unassigned)
                $totalCount = OfferManifest::where('offer_id', $offerId)
                    ->where('mf_variant', $sku)
                    ->count();

                // Get unassigned count (the only ones we can safely delete)
                $unassignedCount = OfferManifest::where('offer_id', $offerId)
                    ->where('mf_variant', $sku)
                    ->whereNull('assignee_id')
                    ->count();

                if ($qty > $totalCount) {
                    // Need to add more manifests to reach target total
                    $toAdd = $qty - $totalCount;
                    $maxOrdering = OfferManifest::where('offer_id', $offerId)->max('assignment_ordering') ?? 0;

                    for ($i = 0; $i < $toAdd; $i++) {
                        OfferManifest::create([
                            'offer_id' => $offerId,
                            'mf_variant' => $sku,
                            'assignee_id' => null,
                            'assignment_ordering' => $maxOrdering + $i + 1,
                        ]);
                    }
                    $manifestsChanged = true;
                } elseif ($qty < $totalCount) {
                    // Need to remove manifests to reach target total
                    $toRemove = min($totalCount - $qty, $unassignedCount);
                    
                    if ($toRemove > 0) {
                        OfferManifest::where('offer_id', $offerId)
                            ->where('mf_variant', $sku)
                            ->whereNull('assignee_id')
                            ->orderBy('assignment_ordering', 'desc')
                            ->limit($toRemove)
                            ->delete();
                        $manifestsChanged = true;
                    }
                }
            }

            // Reshuffle assignment ordering for all unassigned manifests in this offer
            if ($manifestsChanged) {
                $this->reshuffleUnassignedManifests($offerId);
            }
        });

        return $offer->fresh();
    }

    /**
     * Get manifest summary for an offer grouped by variant
     *
     * @param int $offerId
     * @return array<array{sku: string, total: int, assigned: int, unassigned: int}>
     */
    public function getManifestSummary(int $offerId): array
    {
        return OfferManifest::where('offer_id', $offerId)
            ->selectRaw('mf_variant as sku, COUNT(*) as total, SUM(CASE WHEN assignee_id IS NOT NULL THEN 1 ELSE 0 END) as assigned')
            ->groupBy('mf_variant')
            ->get()
            ->map(function ($row) {
                return [
                    'sku' => $row->sku,
                    'total' => (int)$row->total,
                    'assigned' => (int)$row->assigned,
                    'unassigned' => (int)$row->total - (int)$row->assigned,
                ];
            })
            ->toArray();
    }

    /**
     * Get product data for manifests
     *
     * @param array<OfferManifest> $manifests
     * @return array
     */
    public function getProductDataFromManifests(array $manifests): array
    {
        $variantIds = array_unique(array_map(fn($m) => $m->mf_variant, $manifests));
        $productData = $this->shopifyProductService->getProductDataByVariantIds($variantIds);

        $result = [];
        foreach ($variantIds as $variantId) {
            $qty = count(array_filter($manifests, fn($m) => $m->mf_variant === $variantId));
            $result[$variantId] = [
                'productData' => $productData[$variantId] ?? null,
                'quantity' => $qty,
            ];
        }

        return $result;
    }
}
