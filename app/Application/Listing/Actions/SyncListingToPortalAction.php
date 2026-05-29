<?php

namespace App\Application\Listing\Actions;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\ListingPortalSync;
use App\Infrastructure\Persistence\Models\Portal;

class SyncListingToPortalAction
{
    public function execute(Listing $listing, Portal $portal, bool $activate = true): ListingPortalSync
    {
        $sync = ListingPortalSync::updateOrCreate(
            ['listing_id' => $listing->id, 'portal_id' => $portal->id],
            [
                'agency_id' => $listing->agency_id,
                'is_active' => $activate,
                'status' => $activate ? 'pending' : 'unpublished',
            ]
        );

        if ($activate) {
            $this->attemptSync($sync, $listing, $portal);
        }

        return $sync->fresh();
    }

    private function attemptSync(ListingPortalSync $sync, Listing $listing, Portal $portal): void
    {
        $sync->update(['status' => 'syncing']);

        try {
            // Dispatch to the appropriate portal adapter based on portal code
            $externalId = match ($portal->code) {
                'property24' => $this->simulatePortalSync('property24', $listing),
                'propertypro' => $this->simulatePortalSync('propertypro', $listing),
                default => $this->simulatePortalSync($portal->code, $listing),
            };

            $sync->update([
                'status' => 'synced',
                'external_id' => $externalId,
                'last_synced_at' => now(),
                'sync_errors' => null,
            ]);
        } catch (\Exception $e) {
            $sync->update([
                'status' => 'failed',
                'sync_errors' => ['message' => $e->getMessage(), 'at' => now()->toIsoString()],
            ]);
        }
    }

    private function simulatePortalSync(string $portalCode, Listing $listing): string
    {
        // Real API calls go here per portal. For now: return a mock external ID.
        return strtoupper($portalCode) . '-' . $listing->id . '-' . now()->format('YmdHis');
    }
}
