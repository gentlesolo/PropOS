<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Viewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateListingHealthScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        Listing::where('status', 'active')
            ->with(['media', 'portalSyncs'])
            ->chunkById(100, function ($listings) {
                foreach ($listings as $listing) {
                    $this->updateListing($listing);
                }
            });
    }

    private function updateListing(Listing $listing): void
    {
        $startDate    = $listing->mandate_start_date ?? $listing->created_at;
        $daysOnMarket = (int) now()->diffInDays($startDate);

        $viewingsCount   = Viewing::where('listing_id', $listing->id)->count();
        $completedViews  = Viewing::where('listing_id', $listing->id)->where('status', 'completed')->count();
        $photoCount      = $listing->media->where('file_type', 'image')->count();
        $portalSyncCount = $listing->portalSyncs->where('status', 'synced')->count();

        $score = 100;
        $score -= min(40, $daysOnMarket * 1.2);   // age penalty
        $score += min(20, $viewingsCount * 4);      // viewing activity
        $score += min(10, $photoCount * 2);         // photo quality
        $score += min(10, $portalSyncCount * 3);    // portal reach
        if ($listing->headline)             $score += 5;
        if ($listing->description_standard) $score += 5;
        $score = max(0, min(100, (int) round($score)));

        $listing->update([
            'health_score'    => $score,
            'days_on_market'  => $daysOnMarket,
        ]);
    }
}
