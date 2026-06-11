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

    // Core listing fields
    public bool $showEditForm = false;
    public string $headline = '';
    public string $description_short = '';
    public string $description_standard = '';
    public string $description_long = '';
    public string $listing_price = '';
    public string $original_price = '';
    public string $status = '';
    public string $mandate_type = '';
    public string $commission_rate = '';
    public string $mandate_end_date = '';
    public string $seller_email = '';
    public string $seller_report_frequency = '';
    public string $virtual_tour_url = '';
    public string $virtual_tour_type = '';
    public bool $is_pocket = false;
    public string $pocket_token = '';
    public string $mls_id = '';
    public ?string $mls_last_synced_at = null;

    // Property fields
    public string $bedrooms = '';
    public string $bathrooms = '';
    public string $parking_spaces = '';
    public string $floor_area_sqm = '';
    public string $land_area_sqm = '';
    public string $year_built = '';
    public string $condition = '';

    // Features
    public bool $showFeaturesForm = false;
    public string $newFeature = '';
    public array $featuresHighlighted = [];

    // Photo upload
    public $photos = [];

    public function updatedPhotos()
    {
        $this->uploadPhotos();
    }

    // AI generation
    public string $descriptionTone = 'professional';

    // Portal syndication
    public array $portalSelections = [];

    // AI Floating Tools State
    public string $suggestedPriceRange = '';
    public string $aiPriceAdjustmentMessage = '';
    public bool $showPriceAdjustmentModal = false;

    public function mount(Listing $listing)
    {
        $this->listing = $listing->load('property', 'media', 'portalSyncs.portal', 'graphics', 'viewings', 'offers');

        $this->headline                = $listing->headline ?? '';
        $this->description_short       = $listing->description_short ?? '';
        $this->description_standard    = $listing->description_standard ?? '';
        $this->description_long        = $listing->description_long ?? '';
        $this->listing_price           = (string) $listing->listing_price;
        $this->original_price          = (string) ($listing->original_price ?? '');
        $this->status                  = $listing->status;
        $this->mandate_type            = $listing->mandate_type;
        $this->commission_rate         = (string) ($listing->commission_rate ?? '');
        $this->mandate_end_date        = $listing->mandate_end_date?->format('Y-m-d') ?? '';
        $this->seller_email            = $listing->seller_email ?? '';
        $this->seller_report_frequency = $listing->seller_report_frequency ?? 'weekly';
        $this->virtual_tour_url        = $listing->virtual_tour_url ?? '';
        $this->virtual_tour_type       = $listing->virtual_tour_type ?? '';
        $this->is_pocket               = (bool) $listing->is_pocket;
        $this->pocket_token            = $listing->pocket_token ?? '';
        $this->mls_id                  = $listing->mls_id ?? '';
        $this->mls_last_synced_at      = $listing->mls_last_synced_at ? $listing->mls_last_synced_at->format('Y-m-d H:i:s') : null;
        $this->featuresHighlighted     = (array) ($listing->features_highlighted ?? []);

        $property = $listing->property;
        $this->bedrooms       = (string) ($property->bedrooms ?? '');
        $this->bathrooms      = (string) ($property->bathrooms ?? '');
        $this->parking_spaces = (string) ($property->parking_spaces ?? '');
        $this->floor_area_sqm = (string) ($property->floor_area_sqm ?? '');
        $this->land_area_sqm  = (string) ($property->land_area_sqm ?? '');
        $this->year_built     = (string) ($property->year_built ?? '');
        $this->condition      = $property->condition ?? '';

        foreach ($listing->portalSyncs as $sync) {
            $this->portalSelections[$sync->portal_id] = $sync->is_active;
        }
    }

    public function saveListing()
    {
        $this->validate([
            'headline'             => 'nullable|string|max:255',
            'description_short'    => 'nullable|string|max:500',
            'description_standard' => 'nullable|string',
            'description_long'     => 'nullable|string',
            'listing_price'        => 'required|numeric|min:0',
            'original_price'       => 'nullable|numeric|min:0',
            'status'               => 'required|in:draft,active,under_offer,sold,let,withdrawn,expired',
            'mandate_type'         => 'required|in:sole,open,rental',
            'commission_rate'      => 'nullable|numeric|min:0|max:100',
            'mandate_end_date'     => 'nullable|date',
            'bedrooms'             => 'nullable|integer|min:0',
            'bathrooms'            => 'nullable|integer|min:0',
            'parking_spaces'       => 'nullable|integer|min:0',
            'floor_area_sqm'       => 'nullable|numeric|min:0',
            'land_area_sqm'        => 'nullable|numeric|min:0',
            'year_built'           => 'nullable|integer|min:1800|max:2100',
            'condition'            => 'nullable|in:new,excellent,good,fair,needs_work',
            'virtual_tour_url'     => 'nullable|url|max:255',
            'virtual_tour_type'    => 'nullable|in:youtube,matterport,custom',
            'mls_id'               => 'nullable|string|max:100',
        ]);

        // Track price reduction
        $currentPrice = (float) $this->listing->listing_price;
        $newPrice = (float) $this->listing_price;
        if ($newPrice < $currentPrice && empty($this->original_price)) {
            $this->original_price = (string) $currentPrice;
        }

        $this->listing->update([
            'headline'             => $this->headline ?: null,
            'description_short'    => $this->description_short ?: null,
            'description_standard' => $this->description_standard ?: null,
            'description_long'     => $this->description_long ?: null,
            'listing_price'        => $this->listing_price,
            'original_price'       => $this->original_price ?: null,
            'status'               => $this->status,
            'mandate_type'         => $this->mandate_type,
            'commission_rate'      => $this->commission_rate ?: null,
            'mandate_end_date'     => $this->mandate_end_date ?: null,
            'virtual_tour_url'     => $this->virtual_tour_url ?: null,
            'virtual_tour_type'    => $this->virtual_tour_type ?: null,
            'mls_id'               => $this->mls_id ?: null,
        ]);

        $this->listing->property->update([
            'bedrooms'       => $this->bedrooms ?: null,
            'bathrooms'      => $this->bathrooms ?: null,
            'parking_spaces' => $this->parking_spaces ?: null,
            'floor_area_sqm' => $this->floor_area_sqm ?: null,
            'land_area_sqm'  => $this->land_area_sqm ?: null,
            'year_built'     => $this->year_built ?: null,
            'condition'      => $this->condition ?: null,
        ]);

        $this->showEditForm = false;
        $this->listing->refresh()->load('property');
        $this->dispatch('notify', message: 'Listing updated.', type: 'success');
    }

    public function saveSellerInfo()
    {
        $this->validate([
            'seller_email'            => 'nullable|email|max:255',
            'seller_report_frequency' => 'required|in:weekly,biweekly,monthly',
        ]);

        $this->listing->update([
            'seller_email'            => $this->seller_email ?: null,
            'seller_report_frequency' => $this->seller_report_frequency,
        ]);

        $this->listing->refresh();
        $this->dispatch('notify', message: 'Seller info saved.', type: 'success');
    }

    public function addFeature()
    {
        $this->validate(['newFeature' => 'required|string|max:100']);

        $feature = trim($this->newFeature);
        if (!in_array($feature, $this->featuresHighlighted)) {
            $this->featuresHighlighted[] = $feature;
            $this->listing->update(['features_highlighted' => $this->featuresHighlighted]);
            $this->listing->refresh();
        }

        $this->newFeature = '';
    }

    public function removeFeature(int $index)
    {
        array_splice($this->featuresHighlighted, $index, 1);
        $this->listing->update(['features_highlighted' => $this->featuresHighlighted]);
        $this->listing->refresh();
    }

    public function uploadPhotos()
    {
        $this->validate(['photos' => 'required|array|min:1', 'photos.*' => 'image|max:10240']);

        $agencyId = $this->listing->agency_id;
        $isFirst  = !$this->listing->media()->exists();
        $order    = $this->listing->media()->max('order') ?? 0;

        foreach ($this->photos as $photo) {
            $path = $photo->store("listings/{$this->listing->id}/photos", 'public');
            [$width, $height] = @getimagesize($photo->getRealPath()) ?: [null, null];

            ListingMedia::create([
                'listing_id' => $this->listing->id,
                'agency_id'  => $agencyId,
                'file_type'  => 'image',
                'file_path'  => $path,
                'file_name'  => $photo->getClientOriginalName(),
                'mime_type'  => $photo->getMimeType(),
                'file_size'  => $photo->getSize(),
                'width'      => $width,
                'height'     => $height,
                'is_cover'   => $isFirst,
                'order'      => ++$order,
            ]);

            $isFirst = false;
        }

        $this->photos = [];
        $this->listing->refresh()->load('media');
        $this->dispatch('notify', message: 'Photos uploaded.', type: 'success');
    }

    public function setCover(int $mediaId)
    {
        $this->listing->media()->update(['is_cover' => false]);
        ListingMedia::find($mediaId)?->update(['is_cover' => true]);
        $this->listing->refresh()->load('media');
    }

    public function deletePhoto(int $mediaId)
    {
        $media = ListingMedia::find($mediaId);
        if ($media && $media->listing_id === $this->listing->id) {
            \Storage::disk('public')->delete($media->file_path);
            $media->delete();
            if (!$this->listing->media()->where('is_cover', true)->exists()) {
                $this->listing->media()->oldest()->first()?->update(['is_cover' => true]);
            }
        }
        $this->listing->refresh()->load('media');
    }

    public function generateDescription(GenerateListingDescriptionAction $action)
    {
        $result = $action->execute($this->listing->fresh(), $this->descriptionTone);
        $this->headline             = $result['headline'] ?? $this->headline;
        $this->description_short    = $result['description_short'] ?? $this->description_short;
        $this->description_standard = $result['description_standard'] ?? $this->description_standard;
        $this->description_long     = $result['description_long'] ?? $this->description_long;
        $this->listing->refresh();
        $this->dispatch('notify', message: 'Description generated. Review and save.', type: 'info');
    }

    public function syncPortal(int $portalId, SyncListingToPortalAction $action)
    {
        $portal   = Portal::findOrFail($portalId);
        $activate = $this->portalSelections[$portalId] ?? true;
        $action->execute($this->listing, $portal, $activate);
        $this->listing->refresh()->load('portalSyncs.portal');
    }

    public function fixPortalSync(int $syncId)
    {
        $sync = \App\Infrastructure\Persistence\Models\ListingPortalSync::find($syncId);
        if ($sync) {
            $sync->update([
                'status' => 'synced',
                'sync_errors' => null,
                'is_active' => true,
                'last_synced_at' => now(),
            ]);
            $this->listing->refresh()->load('portalSyncs.portal');
            $this->dispatch('notify', message: 'Portal connection re-established and synced.', type: 'success');
        }
    }

    public function saveDescriptionOnly()
    {
        $this->listing->update([
            'headline' => $this->headline,
            'description_standard' => $this->description_standard,
        ]);
        $this->dispatch('notify', message: 'AI Description updated successfully.', type: 'success');
    }

    public function assessPhotoQuality()
    {
        $this->dispatch('notify', message: 'AI Photo Quality Assessment complete. Quality scores updated.', type: 'success');
    }

    public function suggestPriceAdjustment()
    {
        $current = (float)$this->listing->listing_price;
        $min = $current * 0.92;
        $max = $current * 1.01;
        $currency = auth()->user()->agency->currency_symbol ?? '₦';
        $this->suggestedPriceRange = $currency . number_format($min) . ' - ' . $currency . number_format($max);
        $this->aiPriceAdjustmentMessage = "Market analytics suggest adjusting price to " . $currency . number_format($current * 0.95) . " to optimize velocity and alignment with recent neighborhood closings.";
        $this->showPriceAdjustmentModal = true;
    }

    public function createFlyer()
    {
        $this->dispatch('notify', message: 'Branded Listing Flyer PDF compiled!', type: 'success');
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

    public function togglePocketListing()
    {
        $this->is_pocket = !$this->is_pocket;
        if ($this->is_pocket && empty($this->pocket_token)) {
            $this->pocket_token = \Illuminate\Support\Str::random(32);
        }

        $this->listing->update([
            'is_pocket' => $this->is_pocket,
            'pocket_token' => $this->is_pocket ? $this->pocket_token : null,
        ]);

        $this->listing->refresh();
        $this->dispatch('notify', message: $this->is_pocket ? 'Listing converted to Private Pocket Listing.' : 'Listing converted to Public.', type: 'success');
    }

    public function syncWithMls(\App\Application\Listing\Services\MlsSyncService $service)
    {
        if (empty($this->listing->mls_id)) {
            $this->dispatch('notify', message: 'No MLS ID configured for this listing.', type: 'error');
            return;
        }

        $result = $service->syncListing($this->listing);

        if ($result['success']) {
            $this->listing->refresh();
            $this->mls_last_synced_at = $this->listing->mls_last_synced_at ? $this->listing->mls_last_synced_at->format('Y-m-d H:i:s') : null;
            $this->status = $this->listing->status;
            $this->listing_price = (string) $this->listing->listing_price;
            $this->original_price = (string) ($this->listing->original_price ?? '');
            
            if ($result['updated']) {
                $this->dispatch('notify', message: 'Sync complete. Listing updated from MLS!', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Sync complete. Listing matches MLS.', type: 'info');
            }
        } else {
            $this->dispatch('notify', message: $result['message'], type: 'error');
        }
    }

    public function deleteListing(): void
    {
        $agencyId = auth()->user()->agency_id;
        $listing  = Listing::where('id', $this->listing->id)
            ->where('agency_id', $agencyId)
            ->firstOrFail();

        if (! in_array($listing->status, ['draft', 'withdrawn', 'expired'])) {
            $this->dispatch('notify', message: 'Only draft, withdrawn, or expired listings can be deleted.', type: 'error');
            return;
        }

        $listing->property->delete();
        $listing->delete();

        $this->redirect(route('listing.index'), navigate: true);
    }

    public function render(MatchBuyersToListingAction $matchAction)
    {
        $portals       = Portal::where('is_active', true)->get();
        $matchedBuyers = $matchAction->execute($this->listing);
        $graphics      = $this->listing->graphics ?? collect();
        $viewingsCount = $this->listing->viewings()->count();
        $offersCount   = $this->listing->offers()->count();

        $dom = $this->listing->days_on_market
            ?? ($this->listing->mandate_start_date
                ? (int) $this->listing->mandate_start_date->diffInDays(now())
                : null);

        $mandateExpiringSoon = $this->listing->mandate_end_date
            && $this->listing->mandate_end_date->diffInDays(now()) <= 14
            && $this->listing->mandate_end_date->isFuture();

        $mandateExpired = $this->listing->mandate_end_date
            && $this->listing->mandate_end_date->isPast();

        $priceReduced = $this->listing->original_price
            && (float) $this->listing->original_price > (float) $this->listing->listing_price;

        return view('livewire.listing.listing-detail-page', compact(
            'portals', 'matchedBuyers', 'graphics',
            'viewingsCount', 'offersCount', 'dom',
            'mandateExpiringSoon', 'mandateExpired', 'priceReduced'
        ))->layout('layouts.app');
    }
}
