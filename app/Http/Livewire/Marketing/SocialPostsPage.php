<?php

namespace App\Http\Livewire\Marketing;

use App\Application\Marketing\Actions\GenerateListingGraphicAction;
use App\Application\Marketing\Actions\GenerateSocialPostCopyAction;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\ListingGraphic;
use App\Infrastructure\Queue\Jobs\GenerateListingGraphicsJob;
use Livewire\Component;

class SocialPostsPage extends Component
{
    public ?int $selectedListingId = null;
    public bool $generating        = false;
    public string $activeFormat    = 'square';
    public string $activeChannel   = 'instagram';

    // Regenerate copy only for the active channel
    public bool $regeneratingCopy = false;

    public function selectListing(int $id): void
    {
        $this->selectedListingId = $id;
        $this->activeFormat      = 'square';
        $this->activeChannel     = 'instagram';
    }

    public function generateAll(): void
    {
        if (! $this->selectedListingId) {
            return;
        }

        $this->generating = true;

        // Dispatch to queue — UI will refresh when component re-renders
        GenerateListingGraphicsJob::dispatch($this->selectedListingId);

        $this->dispatch('notify', message: 'Graphics generation queued. Refresh in a few seconds.', type: 'info');
        $this->generating = false;
    }

    /**
     * Regenerate all three formats synchronously (for on-demand refresh from UI).
     * Uses the actions directly so the user gets immediate feedback.
     */
    public function regenerateNow(
        GenerateListingGraphicAction $graphicAction,
        GenerateSocialPostCopyAction $copyAction,
    ): void {
        $listing = Listing::with(['property', 'agency', 'media', 'coverPhoto'])
            ->find($this->selectedListingId);

        if (! $listing) {
            return;
        }

        $this->generating = true;

        foreach (['square', 'landscape', 'story'] as $format) {
            try {
                $graphic = $graphicAction->execute($listing, $format);
                $copyAction->attachToGraphic($graphic, $listing);
            } catch (\Exception $e) {
                $this->dispatch('notify', message: "Failed to generate {$format}: " . $e->getMessage(), type: 'error');
            }
        }

        $this->generating = false;
        $this->dispatch('notify', message: 'Graphics regenerated successfully!', type: 'success');
    }

    /**
     * Regenerate AI copy for a single graphic without redoing the image.
     */
    public function regenerateCopy(int $graphicId, GenerateSocialPostCopyAction $copyAction): void
    {
        $graphic = ListingGraphic::find($graphicId);
        if (! $graphic) {
            return;
        }

        $listing = Listing::with(['property', 'agency'])->find($graphic->listing_id);
        if (! $listing) {
            return;
        }

        $this->regeneratingCopy = true;
        $copyAction->attachToGraphic($graphic, $listing);
        $this->regeneratingCopy = false;

        $this->dispatch('notify', message: 'Caption regenerated.', type: 'success');
    }

    public function deleteGraphic(int $graphicId): void
    {
        $graphic = ListingGraphic::where('id', $graphicId)
            ->where('agency_id', auth()->user()->agency_id)
            ->first();

        if ($graphic) {
            \Storage::disk('public')->delete($graphic->file_path);
            $graphic->delete();
        }
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $listings = Listing::with(['property', 'graphics'])
            ->where('agency_id', $agencyId)
            ->whereIn('status', ['active', 'draft', 'under_offer'])
            ->orderByDesc('created_at')
            ->get();

        $selectedListing  = null;
        $graphics         = collect();
        $allChannelCopy   = [];

        if ($this->selectedListingId) {
            $selectedListing = $listings->firstWhere('id', $this->selectedListingId);
            if ($selectedListing) {
                $graphics = ListingGraphic::where('listing_id', $this->selectedListingId)
                    ->orderByRaw("CASE format WHEN 'square' THEN 1 WHEN 'landscape' THEN 2 WHEN 'story' THEN 3 END")
                    ->get();

                // Collect all post copy across all graphics for the copy panel
                foreach ($graphics as $g) {
                    if ($g->post_copy) {
                        $allChannelCopy[$g->channel] = $g->post_copy;
                    }
                }
            }
        }

        return view('livewire.marketing.social-posts-page', compact(
            'listings', 'selectedListing', 'graphics', 'allChannelCopy'
        ))->layout('layouts.app');
    }
}
