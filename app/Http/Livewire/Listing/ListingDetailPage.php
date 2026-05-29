<?php

namespace App\Http\Livewire\Listing;

use App\Application\CRM\Actions\MatchBuyersToListingAction;
use App\Application\Listing\Actions\GenerateListingDescriptionAction;
use App\Application\Listing\Actions\SyncListingToPortalAction;
use App\Application\Marketing\Actions\GenerateListingGraphicAction;
use App\Application\Marketing\Actions\GenerateSocialPostCopyAction;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\ListingGraphic;
use App\Infrastructure\Persistence\Models\ListingMedia;
use App\Infrastructure\Persistence\Models\Portal;
use Livewire\Component;
use Livewire\WithFileUploads;

class ListingDetailPage extends Component
{
    use WithFileUploads;

    public Listing $listing;

    // Edit fields
    public bool $showEditForm = false;
    public string $headline = '';
    public string $description_short = '';
    public string $description_standard = '';
    public string $listing_price = '';
    public string $status = '';
    public string $mandate_type = '';

    // Property fields
    public string $bedrooms = '';
    public string $bathrooms = '';
    public string $floor_area_sqm = '';
    public string $land_area_sqm = '';

    // Photo upload
    public $photos = [];
    public bool $uploadingPhotos = false;

    // AI generation
    public bool $generatingDescription = false;
    public string $descriptionTone = 'professional';

    // Portal syndication
    public array $portalSelections = [];

    public function mount(Listing $listing)
    {
        $this->listing = $listing->load('property', 'media', 'portalSyncs.portal', 'graphics');

        $this->headline = $listing->headline ?? '';
        $this->description_short = $listing->description_short ?? '';
        $this->description_standard = $listing->description_standard ?? '';
        $this->listing_price = (string) $listing->listing_price;
        $this->status = $listing->status;
        $this->mandate_type = $listing->mandate_type;

        $property = $listing->property;
        $this->bedrooms = (string) ($property->bedrooms ?? '');
        $this->bathrooms = (string) ($property->bathrooms ?? '');
        $this->floor_area_sqm = (string) ($property->floor_area_sqm ?? '');
        $this->land_area_sqm = (string) ($property->land_area_sqm ?? '');

        // Pre-populate portal selections from existing syncs
        foreach ($listing->portalSyncs as $sync) {
            $this->portalSelections[$sync->portal_id] = $sync->is_active;
        }
    }

    public function saveListing()
    {
        $this->validate([
            'headline' => 'nullable|string|max:255',
            'description_short' => 'nullable|string|max:500',
            'description_standard' => 'nullable|string',
            'listing_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,active,under_offer,sold,let,withdrawn,expired',
            'mandate_type' => 'required|in:sole,open,rental',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'floor_area_sqm' => 'nullable|numeric|min:0',
            'land_area_sqm' => 'nullable|numeric|min:0',
        ]);

        $this->listing->update([
            'headline' => $this->headline ?: null,
            'description_short' => $this->description_short ?: null,
            'description_standard' => $this->description_standard ?: null,
            'listing_price' => $this->listing_price,
            'status' => $this->status,
            'mandate_type' => $this->mandate_type,
        ]);

        $this->listing->property->update([
            'bedrooms' => $this->bedrooms ?: null,
            'bathrooms' => $this->bathrooms ?: null,
            'floor_area_sqm' => $this->floor_area_sqm ?: null,
            'land_area_sqm' => $this->land_area_sqm ?: null,
        ]);

        $this->showEditForm = false;
        $this->listing->refresh();
    }

    public function uploadPhotos()
    {
        $this->validate(['photos' => 'required|array|min:1', 'photos.*' => 'image|max:10240']);

        $agencyId = $this->listing->agency_id;
        $isFirst = !$this->listing->media()->exists();
        $order = $this->listing->media()->max('order') ?? 0;

        foreach ($this->photos as $photo) {
            $path = $photo->store("listings/{$this->listing->id}/photos", 'public');
            [$width, $height] = @getimagesize($photo->getRealPath()) ?: [null, null];

            ListingMedia::create([
                'listing_id' => $this->listing->id,
                'agency_id' => $agencyId,
                'file_type' => 'image',
                'file_path' => $path,
                'file_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'file_size' => $photo->getSize(),
                'width' => $width,
                'height' => $height,
                'is_cover' => $isFirst,
                'order' => ++$order,
            ]);

            $isFirst = false;
        }

        $this->photos = [];
        $this->listing->refresh();
    }

    public function setCover(int $mediaId)
    {
        $this->listing->media()->update(['is_cover' => false]);
        ListingMedia::find($mediaId)?->update(['is_cover' => true]);
        $this->listing->refresh();
    }

    public function deletePhoto(int $mediaId)
    {
        $media = ListingMedia::find($mediaId);
        if ($media && $media->listing_id === $this->listing->id) {
            \Storage::disk('public')->delete($media->file_path);
            $media->delete();
            // If deleted photo was cover, promote next
            if (!$this->listing->media()->where('is_cover', true)->exists()) {
                $this->listing->media()->oldest()->first()?->update(['is_cover' => true]);
            }
        }
        $this->listing->refresh();
    }

    public function generateDescription(GenerateListingDescriptionAction $action)
    {
        $this->generatingDescription = true;
        $result = $action->execute($this->listing->fresh(), $this->descriptionTone);
        $this->headline = $result['headline'] ?? $this->headline;
        $this->description_short = $result['description_short'] ?? $this->description_short;
        $this->description_standard = $result['description_standard'] ?? $this->description_standard;
        $this->generatingDescription = false;
        $this->listing->refresh();
    }

    public function syncPortal(int $portalId, SyncListingToPortalAction $action)
    {
        $portal = Portal::findOrFail($portalId);
        $activate = $this->portalSelections[$portalId] ?? true;
        $action->execute($this->listing, $portal, $activate);
        $this->listing->refresh();
        $this->listing->load('portalSyncs.portal');
    }

    public function generateSocialGraphics(
        GenerateListingGraphicAction $graphicAction,
        GenerateSocialPostCopyAction $copyAction,
    ): void {
        $this->listing->refresh()->load('property', 'agency', 'media', 'coverPhoto');

        foreach (['square', 'landscape', 'story'] as $format) {
            try {
                $graphic = $graphicAction->execute($this->listing, $format);
                $copyAction->attachToGraphic($graphic, $this->listing);
            } catch (\Exception $e) {
                $this->dispatch('notify', message: "Could not generate {$format}: " . $e->getMessage(), type: 'error');
            }
        }

        $this->listing->refresh()->load('graphics');
        $this->dispatch('notify', message: 'Social media graphics generated!', type: 'success');
    }

    public function deleteSocialGraphic(int $graphicId): void
    {
        $graphic = ListingGraphic::where('id', $graphicId)
            ->where('listing_id', $this->listing->id)
            ->first();

        if ($graphic) {
            \Storage::disk('public')->delete($graphic->file_path);
            $graphic->delete();
            $this->listing->refresh()->load('graphics');
        }
    }

    public function render(MatchBuyersToListingAction $matchAction)
    {
        $portals       = Portal::where('is_active', true)->get();
        $matchedBuyers = $matchAction->execute($this->listing);
        $graphics      = $this->listing->graphics ?? collect();

        return view('livewire.listing.listing-detail-page', [
            'portals'       => $portals,
            'matchedBuyers' => $matchedBuyers,
            'graphics'      => $graphics,
        ])->layout('layouts.app');
    }
}
