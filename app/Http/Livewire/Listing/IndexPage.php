<?php

namespace App\Http\Livewire\Listing;

use Livewire\Component;
use Livewire\WithPagination;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;

class IndexPage extends Component
{
    use WithPagination;

    public $showCreateModal = false;

    // Minimal Property Fields
    public $address_line_1;
    public $city;
    public $state_province;
    public $country = 'NG';
    public $property_type = 'apartment';

    // Minimal Listing Fields
    public $listing_price;
    public $mandate_type = 'open';

    protected $rules = [
        'address_line_1' => 'required|string|max:255',
        'city' => 'required|string|max:255',
        'state_province' => 'required|string|max:255',
        'property_type' => 'required|in:house,apartment,townhouse,penthouse,land,commercial,office,warehouse',
        'listing_price' => 'required|numeric|min:0',
        'mandate_type' => 'required|in:sole,open,rental',
    ];

    public function saveListing()
    {
        $this->validate();

        $agencyId = auth()->user()->agency_id ?? 1;

        $property = Property::create([
            'agency_id' => $agencyId,
            'address_line_1' => $this->address_line_1,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'country' => $this->country,
            'property_type' => $this->property_type,
        ]);

        Listing::create([
            'agency_id' => $agencyId,
            'agent_id' => auth()->id() ?? 1,
            'property_id' => $property->id,
            'mandate_type' => $this->mandate_type,
            'listing_price' => $this->listing_price,
            'mandate_start_date' => now(),
            'status' => 'draft',
        ]);

        $this->reset([
            'address_line_1', 'city', 'state_province', 'listing_price', 'showCreateModal'
        ]);
        $this->property_type = 'apartment';
        $this->mandate_type = 'open';
    }

    public function render()
    {
        $listings = Listing::with('property')->latest()->paginate(10);
        $activeCount = Listing::where('status', 'active')->count();
        $underOfferCount = Listing::where('status', 'under_offer')->count();
        $totalValue = Listing::where('status', 'active')->sum('listing_price');

        return view('livewire.listing.index-page', [
            'listings' => $listings,
            'activeCount' => $activeCount,
            'underOfferCount' => $underOfferCount,
            'totalValue' => $totalValue,
        ])->layout('layouts.app');
    }
}
