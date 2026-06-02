<?php

namespace App\Infrastructure\Persistence\Observers;

use App\Application\Website\Services\WebhookDispatcherService;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Queue\Jobs\GenerateListingGraphicsJob;
use App\Infrastructure\Queue\Jobs\NotifyAgentsOfListingMatchesJob;

class ListingObserver
{
    public function __construct(private readonly WebhookDispatcherService $webhooks) {}

    public function updated(Listing $listing): void
    {
        if ($listing->isDirty('status') && $listing->status === 'active' && $listing->getOriginal('status') !== 'active') {
            GenerateListingGraphicsJob::dispatch($listing->id)->onQueue('default')->delay(now()->addSeconds(5));
            NotifyAgentsOfListingMatchesJob::dispatch($listing->id)->onQueue('default')->delay(now()->addSeconds(10));

            $this->webhooks->dispatch($listing->agency_id, 'listing.published', $this->listingPayload($listing));

        } elseif ($listing->isDirty('status') && in_array($listing->status, ['sold', 'let', 'withdrawn', 'expired'])) {
            $this->webhooks->dispatch($listing->agency_id, 'listing.deleted', $this->listingPayload($listing));

        } elseif ($listing->isDirty('listing_price') && $listing->listing_price < $listing->getOriginal('listing_price')) {
            $this->webhooks->dispatch($listing->agency_id, 'listing.price_reduced', array_merge(
                $this->listingPayload($listing),
                ['previous_price' => (float) $listing->getOriginal('listing_price')],
            ));

        } elseif ($listing->isDirty()) {
            $this->webhooks->dispatch($listing->agency_id, 'listing.updated', $this->listingPayload($listing));
        }
    }

    public function created(Listing $listing): void
    {
        if ($listing->status === 'active') {
            GenerateListingGraphicsJob::dispatch($listing->id)->onQueue('default')->delay(now()->addSeconds(5));
            NotifyAgentsOfListingMatchesJob::dispatch($listing->id)->onQueue('default')->delay(now()->addSeconds(10));

            $this->webhooks->dispatch($listing->agency_id, 'listing.published', $this->listingPayload($listing));
        }
    }

    private function listingPayload(Listing $listing): array
    {
        return [
            'listing_id'    => $listing->id,
            'status'        => $listing->status,
            'mandate_type'  => $listing->mandate_type,
            'listing_price' => (float) $listing->listing_price,
            'headline'      => $listing->headline,
            'published_at'  => $listing->published_at?->toISOString(),
        ];
    }
}
