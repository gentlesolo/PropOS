<?php

namespace App\Infrastructure\Persistence\Observers;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Queue\Jobs\GenerateListingGraphicsJob;
use App\Infrastructure\Queue\Jobs\NotifyAgentsOfListingMatchesJob;

class ListingObserver
{
    /**
     * When a listing is published (status → active), auto-generate social graphics.
     */
    public function updated(Listing $listing): void
    {
        if (
            $listing->isDirty('status') &&
            $listing->status === 'active' &&
            $listing->getOriginal('status') !== 'active'
        ) {
            GenerateListingGraphicsJob::dispatch($listing->id)
                ->onQueue('default')
                ->delay(now()->addSeconds(5));

            NotifyAgentsOfListingMatchesJob::dispatch($listing->id)
                ->onQueue('default')
                ->delay(now()->addSeconds(10));
        }
    }

    public function created(Listing $listing): void
    {
        if ($listing->status === 'active') {
            GenerateListingGraphicsJob::dispatch($listing->id)
                ->onQueue('default')
                ->delay(now()->addSeconds(5));

            NotifyAgentsOfListingMatchesJob::dispatch($listing->id)
                ->onQueue('default')
                ->delay(now()->addSeconds(10));
        }
    }
}
