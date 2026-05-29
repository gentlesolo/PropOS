<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckMandateExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(NotificationService $notify): void
    {
        // 30-day warning
        $this->notifyExpiring($notify, 30);
        // 7-day warning
        $this->notifyExpiring($notify, 7);
        // 1-day warning
        $this->notifyExpiring($notify, 1);
    }

    private function notifyExpiring(NotificationService $notify, int $daysAhead): void
    {
        $listings = Listing::with(['agent', 'property'])
            ->whereIn('status', ['active', 'draft'])
            ->whereDate('mandate_end_date', now()->addDays($daysAhead)->toDateString())
            ->get();

        foreach ($listings as $listing) {
            $agent = $listing->agent;
            if (! $agent) {
                continue;
            }

            $address   = $listing->property->address_line_1 ?? 'Property';
            $label     = $daysAhead === 1 ? 'tomorrow' : "in {$daysAhead} days";
            $actionUrl = route('listing.detail', $listing);

            $notify->notifyUser(
                $agent,
                'mandate_expiry',
                "Mandate expiring {$label}",
                "The mandate for {$address} expires {$label}. Contact the seller to discuss renewal.",
                $actionUrl,
                $daysAhead <= 7 ? 'warning' : 'info',
            );
        }
    }
}
