<?php

namespace App\Http\Livewire\Listing;

use App\Infrastructure\Persistence\Models\Listing;
use Livewire\Component;

class PublicPocketListingPage extends Component
{
    public Listing $listing;
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->listing = Listing::where('pocket_token', $token)
            ->where('is_pocket', true)
            ->firstOrFail()
            ->load('property', 'media', 'agent');
    }

    public function render()
    {
        return view('livewire.listing.public-pocket-listing-page')
            ->layout('layouts.public');
    }
}
