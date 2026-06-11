<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 15;

    public function __construct(
        public readonly int    $subscriptionId,
        public readonly string $event,
        public readonly array  $payload,
    ) {}

    public function handle(): void
    {
        $subscription = WebhookSubscription::find($this->subscriptionId);

        if (! $subscription || ! $subscription->is_active) {
            return;
        }

        $body      = json_encode($this->payload);
        $signature = $subscription->sign($body);
        $timestamp = now()->timestamp;

        try {
            Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type'           => 'application/json',
                    'X-VillaCRM-Event'         => $this->event,
                    'X-VillaCRM-Signature-256' => "sha256={$signature}",
                    'X-VillaCRM-Timestamp'     => $timestamp,
                    'User-Agent'             => 'VillaCRM-Webhooks/1.0',
                ])
                ->post($subscription->url, $this->payload)
                ->throw();

            $subscription->update([
                'last_triggered_at' => now(),
                'failure_count'     => 0,
            ]);
        } catch (RequestException $e) {
            $subscription->increment('failure_count');

            if ($subscription->failure_count >= 10) {
                $subscription->update(['is_active' => false]);
                Log::warning("Webhook subscription #{$subscription->id} disabled after 10 consecutive failures.");
            }

            throw $e; // re-throw so the queue retries
        }
    }

    public function backoff(): array
    {
        return [30, 120, 300]; // seconds between retry attempts
    }
}
