<?php

namespace App\Application\Listing\Services;

use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Support\Facades\Log;

class MlsSyncService
{
    /**
     * Simulate synchronization of all listings that are linked to MLS.
     */
    public function syncAllListings(): array
    {
        $listings = Listing::whereNotNull('mls_id')->get();
        $results = [];

        foreach ($listings as $listing) {
            $results[] = $this->syncListing($listing);
        }

        return $results;
    }

    /**
     * Sync a single listing with the MLS/IDX feed.
     */
    public function syncListing(Listing $listing): array
    {
        // Mock data fetch for MLS listing
        $mlsData = $this->fetchSimulatedMlsData($listing->mls_id);

        if (!$mlsData) {
            return [
                'success' => false,
                'listing_id' => $listing->id,
                'mls_id' => $listing->mls_id,
                'message' => 'MLS listing not found in external feed.',
            ];
        }

        $oldStatus = $listing->status;
        $newStatus = $mlsData['status'];
        $oldPrice = (float) $listing->listing_price;
        $newPrice = (float) $mlsData['price'];

        $updated = false;

        // Auto-update status if changed on MLS (Two-Way Sync)
        if ($oldStatus !== $newStatus) {
            $listing->status = $newStatus;
            $updated = true;
        }

        // Auto-update price if changed on MLS
        if ($oldPrice !== $newPrice) {
            $listing->original_price = $oldPrice;
            $listing->listing_price = $newPrice;
            $updated = true;
        }

        $listing->mls_last_synced_at = now();
        $listing->save();

        // Log the sync event
        if ($updated) {
            Log::info("MLS Sync: Listing {$listing->id} updated.", [
                'mls_id' => $listing->mls_id,
                'status_change' => "{$oldStatus} -> {$newStatus}",
                'price_change' => "{$oldPrice} -> {$newPrice}",
            ]);
        }

        return [
            'success' => true,
            'listing_id' => $listing->id,
            'mls_id' => $listing->mls_id,
            'updated' => $updated,
            'changes' => $updated ? [
                'status' => ['old' => $oldStatus, 'new' => $newStatus],
                'price' => ['old' => $oldPrice, 'new' => $newPrice],
            ] : [],
        ];
    }

    /**
     * Fetch simulated MLS payload from IDX server.
     */
    private function fetchSimulatedMlsData(string $mlsId): ?array
    {
        // Deterministic simulation based on the MLS ID to facilitate testing
        if (str_contains(strtolower($mlsId), 'notfound')) {
            return null;
        }

        // Default simulated feed payload
        $status = 'active';
        $priceOffset = 0;

        if (str_contains(strtolower($mlsId), 'sold')) {
            $status = 'sold';
        } elseif (str_contains(strtolower($mlsId), 'offer')) {
            $status = 'under_offer';
        }

        if (str_contains(strtolower($mlsId), 'price_drop')) {
            $priceOffset = -50000;
        }

        return [
            'mls_id' => $mlsId,
            'status' => $status,
            'price' => 750000 + $priceOffset,
            'updated_at' => now()->toIsoString(),
        ];
    }
}
