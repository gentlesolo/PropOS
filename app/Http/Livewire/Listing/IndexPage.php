<?php

namespace App\Http\Livewire\Listing;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPage extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    // Filters
    public string $search = '';
    public string $filterStatus = '';
    public string $filterType = '';

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
        'search'       => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterType'   => ['except' => ''],
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
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
        $query = Listing::with(['property', 'agent', 'coverPhoto', 'media'])
            ->when($this->search, function ($q) {
                $q->whereHas('property', fn($p) => $p
                    ->where('address_line_1', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%")
                    ->orWhere('state_province', 'like', "%{$this->search}%")
                );
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType, function ($q) {
                if ($this->filterType === 'rental') {
                    $q->where('mandate_type', 'rental');
                } elseif ($this->filterType === 'sale') {
                    $q->whereIn('mandate_type', ['sole', 'open']);
                }
            })
            ->latest();

        $listings     = $query->paginate(12);
        $activeCount  = Listing::where('status', 'active')->count();
        $underOfferCount = Listing::where('status', 'under_offer')->count();
        $totalValue   = Listing::where('status', 'active')->sum('listing_price');
        $avgDom       = (int) Listing::whereIn('status', ['active', 'under_offer'])
            ->whereNotNull('mandate_start_date')
            ->get()
            ->avg(fn($l) => $l->mandate_start_date->diffInDays(now()));

        return view('livewire.listing.index-page', compact(
            'listings', 'activeCount', 'underOfferCount', 'totalValue', 'avgDom'
        ))->layout('layouts.app');
    }
}
