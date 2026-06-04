<?php

namespace App\Http\Livewire\Listing;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPage extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    // View Mode
    public string $viewMode = 'grid'; // grid, list, map

    // Filters
    public string $search = '';
    public string $filterBar = 'all'; // all, sale, rental, sold, off_market
    public string $suburb = '';
    public string $minPrice = '';
    public string $maxPrice = '';
    public string $bedroomsFilter = '';
    public string $agentFilter = '';
    public string $portalStatusFilter = '';

    // Create form — Property
    public string $address_line_1 = '';
    public string $city = '';
    public string $state_province = '';
    public string $country = 'NG';
    public string $property_type = 'apartment';
    public string $bedrooms = '';
    public string $bathrooms = '';

    // Create form — Listing
    public string $listing_price = '';
    public string $mandate_type = 'open';

    protected $queryString = [
        'search'             => ['except' => ''],
        'filterBar'          => ['except' => 'all'],
        'suburb'             => ['except' => ''],
        'minPrice'           => ['except' => ''],
        'maxPrice'           => ['except' => ''],
        'bedroomsFilter'     => ['except' => ''],
        'agentFilter'        => ['except' => ''],
        'portalStatusFilter' => ['except' => ''],
        'viewMode'           => ['except' => 'grid'],
    ];

    protected $rules = [
        'address_line_1' => 'required|string|max:255',
        'city'           => 'required|string|max:255',
        'state_province' => 'required|string|max:255',
        'property_type'  => 'required|in:house,apartment,townhouse,penthouse,land,commercial,office,warehouse',
        'listing_price'  => 'required|numeric|min:0',
        'mandate_type'   => 'required|in:sole,open,rental',
        'bedrooms'       => 'nullable|integer|min:0|max:50',
        'bathrooms'      => 'nullable|integer|min:0|max:50',
    ];

    public function openListing(int $id): mixed
    {
        return redirect()->route('listing.detail', $id);
    }

    public function deleteListing(int $id): void
    {
        $agencyId = auth()->user()->agency_id;
        $listing  = Listing::where('id', $id)->where('agency_id', $agencyId)->firstOrFail();

        if (! in_array($listing->status, ['draft', 'withdrawn', 'expired'])) {
            $this->dispatch('notify', message: 'Only draft, withdrawn, or expired listings can be deleted.', type: 'error');
            return;
        }

        $listing->property->delete();
        $listing->delete();
        $this->dispatch('notify', message: 'Listing deleted.', type: 'info');
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'filterBar', 'suburb', 'minPrice', 'maxPrice',
            'bedroomsFilter', 'agentFilter', 'portalStatusFilter'
        ]);
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'list', 'map'])) {
            $this->viewMode = $mode;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterBar(): void
    {
        $this->resetPage();
    }

    public function updatingSuburb(): void
    {
        $this->resetPage();
    }

    public function updatingMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatingMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatingBedroomsFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAgentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPortalStatusFilter(): void
    {
        $this->resetPage();
    }

    public function saveListing()
    {
        $this->validate();

        $agencyId = auth()->user()->agency_id ?? 1;

        $property = Property::create([
            'agency_id'      => $agencyId,
            'address_line_1' => $this->address_line_1,
            'city'           => $this->city,
            'state_province' => $this->state_province,
            'country'        => $this->country,
            'property_type'  => $this->property_type,
            'bedrooms'       => $this->bedrooms ?: null,
            'bathrooms'      => $this->bathrooms ?: null,
        ]);

        $listing = Listing::create([
            'agency_id'          => $agencyId,
            'agent_id'           => auth()->id(),
            'property_id'        => $property->id,
            'mandate_type'       => $this->mandate_type,
            'listing_price'      => $this->listing_price,
            'mandate_start_date' => now(),
            'status'             => 'draft',
        ]);

        $this->reset([
            'address_line_1', 'city', 'state_province', 'listing_price',
            'bedrooms', 'bathrooms', 'showCreateModal',
        ]);
        $this->property_type = 'apartment';
        $this->mandate_type = 'open';

        return redirect()->route('listing.detail', $listing);
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $query = Listing::with(['property', 'agent', 'coverPhoto', 'media', 'portalSyncs.portal'])
            ->where('agency_id', $agencyId)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('property', fn($p) => $p
                        ->where('address_line_1', 'like', "%{$this->search}%")
                        ->orWhere('city', 'like', "%{$this->search}%")
                        ->orWhere('state_province', 'like', "%{$this->search}%")
                    )->orWhere('id', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterBar, function ($q) {
                switch ($this->filterBar) {
                    case 'sale':
                        $q->whereIn('mandate_type', ['sole', 'open'])->whereNotIn('status', ['sold', 'let', 'withdrawn', 'expired']);
                        break;
                    case 'rental':
                        $q->where('mandate_type', 'rental')->whereNotIn('status', ['sold', 'let', 'withdrawn', 'expired']);
                        break;
                    case 'sold':
                        $q->whereIn('status', ['sold', 'let']);
                        break;
                    case 'off_market':
                        $q->whereIn('status', ['withdrawn', 'expired', 'draft']);
                        break;
                }
            })
            ->when($this->suburb, function ($q) {
                $q->whereHas('property', fn($p) => $p->where('city', $this->suburb));
            })
            ->when($this->minPrice, function ($q) {
                $q->where('listing_price', '>=', (float) $this->minPrice);
            })
            ->when($this->maxPrice, function ($q) {
                $q->where('listing_price', '<=', (float) $this->maxPrice);
            })
            ->when($this->bedroomsFilter !== '', function ($q) {
                $q->whereHas('property', fn($p) => $p->where('bedrooms', $this->bedroomsFilter));
            })
            ->when($this->agentFilter, function ($q) {
                $q->where('agent_id', $this->agentFilter);
            })
            ->when($this->portalStatusFilter, function ($q) {
                if ($this->portalStatusFilter === 'synced') {
                    $q->whereHas('portalSyncs', fn($s) => $s->where('status', 'synced'));
                } elseif ($this->portalStatusFilter === 'error') {
                    $q->whereHas('portalSyncs', fn($s) => $s->where('status', 'failed'));
                } elseif ($this->portalStatusFilter === 'not_synced') {
                    $q->whereDoesntHave('portalSyncs');
                }
            })
            ->latest();

        $listings = $query->paginate(12);

        // Sidebar / Dropdown Options
        $agents = User::where('agency_id', $agencyId)->get();
        $suburbs = Property::where('agency_id', $agencyId)
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city');

        $activeCount     = Listing::where('agency_id', $agencyId)->where('status', 'active')->count();
        $underOfferCount = Listing::where('agency_id', $agencyId)->where('status', 'under_offer')->count();
        $totalValue      = Listing::where('agency_id', $agencyId)->where('status', 'active')->sum('listing_price');
        $avgDom          = (int) Listing::where('agency_id', $agencyId)->whereIn('status', ['active', 'under_offer'])
            ->whereNotNull('mandate_start_date')
            ->get()
            ->avg(fn($l) => $l->mandate_start_date->diffInDays(now()));

        return view('livewire.listing.index-page', compact(
            'listings', 'activeCount', 'underOfferCount', 'totalValue', 'avgDom', 'agents', 'suburbs'
        ))->layout('layouts.app');
    }
}
