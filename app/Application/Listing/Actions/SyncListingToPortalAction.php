<?php

namespace App\Application\Listing\Actions;

use App\Infrastructure\ExternalServices\Portals\Property24Client;
use App\Infrastructure\ExternalServices\Portals\PropertyProClient;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\ListingPortalSync;
use App\Infrastructure\Persistence\Models\Portal;

class SyncListingToPortalAction
{
    public function __construct(
        private Property24Client $property24,
        private PropertyProClient $propertyPro,
    ) {}

    public function execute(Listing $listing, Portal $portal, bool $activate = true): ListingPortalSync
    {
        $sync = ListingPortalSync::updateOrCreate(
            ['listing_id' => $listing->id, 'portal_id' => $portal->id],
            [
                'agency_id' => $listing->agency_id,
                'is_active' => $activate,
                'status'    => $activate ? 'pending' : 'unpublished',
            ]
        );

        if ($activate) {
            $this->attemptSync($sync, $listing, $portal);
        } else {
            $this->attemptUnpublish($sync, $portal);
        }

        return $sync->fresh();
    }

    private function attemptSync(ListingPortalSync $sync, Listing $listing, Portal $portal): void
    {
        $sync->update(['status' => 'syncing']);

        try {
            $externalId = match ($portal->code) {
                'property24'  => $this->property24->publishListing($listing),
                'propertypro' => $this->propertyPro->publishListing($listing),
                default       => $this->genericSync($portal->code, $listing),
            };

            $sync->update([
                'status'        => 'synced',
                'external_id'   => $externalId,
                'last_synced_at' => now(),
                'sync_errors'   => null,
            ]);
        } catch (\Exception $e) {
            $sync->update([
                'status'      => 'failed',
                'sync_errors' => ['message' => $e->getMessage(), 'at' => now()->toIsoString()],
            ]);
        }
    }

    private function attemptUnpublish(ListingPortalSync $sync, Portal $portal): void
    {
        if (! $sync->external_id) {
            return;
        }

        try {
            $result = match ($portal->code) {
                'property24'  => $this->property24->unpublishListing($sync->external_id),
                'propertypro' => $this->propertyPro->unpublishListing($sync->external_id),
                default       => true,
            };

            if ($result) {
                $sync->update(['status' => 'unpublished']);
            }
        } catch (\Exception $e) {
            $sync->update([
                'sync_errors' => ['message' => $e->getMessage(), 'at' => now()->toIsoString()],
            ]);
        }
    }

    private function genericSync(string $portalCode, Listing $listing): string
    {
        // Placeholder for portals without a dedicated client yet.
        // Returns a traceable reference ID rather than a fake timestamp-based ID.
        return strtoupper($portalCode) . '-' . $listing->id;
    }
}
