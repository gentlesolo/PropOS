<?php

namespace App\Http\Livewire\Shared;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Transaction;
use Livewire\Component;

class GlobalSearch extends Component
{
    public bool $isOpen = false;
    public string $query = '';
    
    public array $results = [];

    protected $listeners = [
        'toggleGlobalSearch' => 'toggle',
    ];

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->query = '';
            $this->results = [];
        }
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }

        $agencyId = auth()->user()->agency_id;

        // Search Contacts
        $contacts = Contact::where('agency_id', $agencyId)
            ->where(function($q) {
                $q->where('first_name', 'like', "%{$this->query}%")
                  ->orWhere('last_name', 'like', "%{$this->query}%")
                  ->orWhere('email', 'like', "%{$this->query}%")
                  ->orWhere('phone', 'like', "%{$this->query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'Contact',
                'title' => "{$item->first_name} {$item->last_name}",
                'subtitle' => $item->email ?? $item->phone ?? 'Contact',
                'url' => route('crm.contact.detail', $item->id),
                'icon' => 'user',
            ]);

        // Search Listings
        $listings = Listing::where('agency_id', $agencyId)
            ->whereHas('property', function($q) {
                $q->where('address_line_1', 'like', "%{$this->query}%")
                  ->orWhere('city', 'like', "%{$this->query}%");
            })
            ->with('property')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'Listing',
                'title' => $item->property->address_line_1,
                'subtitle' => "Price: " . number_format($item->listing_price, 2) . " · Status: " . ucfirst($item->status),
                'url' => route('listing.detail', $item->id),
                'icon' => 'home',
            ]);

        // Search Deals
        $deals = Deal::where('agency_id', $agencyId)
            ->where('title', 'like', "%{$this->query}%")
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'Deal',
                'title' => $item->title,
                'subtitle' => "Value: " . number_format($item->value, 2) . " · Stage: " . ($item->pipelineStage->name ?? 'N/A'),
                'url' => route('crm.pipeline'),
                'icon' => 'chart',
            ]);

        // Search Transactions
        $transactions = Transaction::where('agency_id', $agencyId)
            ->where('reference', 'like', "%{$this->query}%")
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'Transaction',
                'title' => "Transaction #{$item->reference}",
                'subtitle' => "Sale Price: " . number_format($item->sale_price, 2) . " · Status: " . ucfirst($item->status),
                'url' => route('compliance.transaction.detail', $item->id),
                'icon' => 'shield',
            ]);

        $this->results = collect()
            ->concat($contacts)
            ->concat($listings)
            ->concat($deals)
            ->concat($transactions)
            ->toArray();
    }

    public function render()
    {
        return view('livewire.shared.global-search');
    }
}
