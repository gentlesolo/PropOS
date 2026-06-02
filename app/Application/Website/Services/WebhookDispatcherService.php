<?php

namespace App\Application\Website\Services;

use App\Infrastructure\Persistence\Models\WebhookSubscription;
use App\Infrastructure\Queue\Jobs\DispatchWebhookJob;

class WebhookDispatcherService
{
    /** Dispatch an event to all active subscribers for the agency. */
    public function dispatch(int $agencyId, string $event, array $payload): void
    {
        $basePayload = array_merge($payload, [
            'event'      => $event,
            'sent_at'    => now()->toISOString(),
            'api_version' => 'v1',
        ]);

        WebhookSubscription::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->each(function (WebhookSubscription $sub) use ($event, $basePayload) {
                DispatchWebhookJob::dispatch($sub->id, $event, $basePayload)
                    ->onQueue('webhooks');
            });
    }
}
