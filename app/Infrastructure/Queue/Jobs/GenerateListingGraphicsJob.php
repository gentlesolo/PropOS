<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Application\Marketing\Actions\GenerateListingGraphicAction;
use App\Application\Marketing\Actions\GenerateSocialPostCopyAction;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateListingGraphicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public int $listingId) {}

    public function handle(
        GenerateListingGraphicAction  $graphicAction,
        GenerateSocialPostCopyAction  $copyAction,
    ): void {
        $listing = Listing::with(['property', 'agency', 'media', 'coverPhoto'])->find($this->listingId);

        if (! $listing) {
            Log::warning("GenerateListingGraphicsJob: listing {$this->listingId} not found");
            return;
        }

        // Generate all three format graphics
        foreach (['square', 'landscape', 'story'] as $format) {
            try {
                $graphic = $graphicAction->execute($listing, $format);

                // Attach AI copy to this graphic
                $copyAction->attachToGraphic($graphic, $listing);

                Log::info("Listing graphic generated", [
                    'listing_id' => $listing->id,
                    'format'     => $format,
                    'path'       => $graphic->file_path,
                ]);
            } catch (\Exception $e) {
                Log::error("Graphic generation failed for listing {$listing->id} format {$format}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
