<?php

namespace App\Infrastructure\Persistence\Observers;

use App\Application\Website\Services\WebhookDispatcherService;
use App\Infrastructure\Persistence\Models\Viewing;

class ViewingObserver
{
    public function __construct(private readonly WebhookDispatcherService $webhooks) {}

    public function created(Viewing $viewing): void
    {
        $this->webhooks->dispatch($viewing->agency_id, 'viewing.scheduled', [
            'viewing_id'  => $viewing->id,
            'listing_id'  => $viewing->listing_id,
            'agent_id'    => $viewing->assigned_agent_id,
            'scheduled_at' => $viewing->scheduled_at?->toISOString(),
        ]);
    }
}
