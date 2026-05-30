<?php

namespace App\Http\Livewire\Offers;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Offer;
use Livewire\Component;
use Livewire\WithPagination;

class OffersPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateForm = false;

    // Create form fields
    public string $deal_id = '';
    public string $contact_id = '';
    public string $listing_id = '';
    public string $amount = '';
    public string $type = 'sale';
    public string $expiry_date = '';
    public string $proposed_occupation_date = '';
    public string $deposit_amount = '';
    public string $conditions = '';
    public string $notes = '';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function createOffer(): void
    {
        $this->validate([
            'deal_id' => 'required|exists:deals,id',
            'contact_id' => 'required|exists:contacts,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:sale,rental',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        $deal = Deal::findOrFail($this->deal_id);

        Offer::create([
            'agency_id' => auth()->user()->agency_id,
            'deal_id' => $this->deal_id,
            'listing_id' => $this->listing_id ?: $deal->listing_id,
            'contact_id' => $this->contact_id,
            'submitted_by' => auth()->id(),
            'amount' => $this->amount,
            'type' => $this->type,
            'status' => 'pending',
            'expiry_date' => $this->expiry_date ?: null,
            'proposed_occupation_date' => $this->proposed_occupation_date ?: null,
            'deposit_amount' => $this->deposit_amount ?: null,
            'conditions' => $this->conditions ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'deal_id', 'contact_id', 'listing_id', 'amount', 'type',
            'expiry_date', 'proposed_occupation_date', 'deposit_amount', 'conditions', 'notes']);
        $this->dispatch('notify', message: 'Offer submitted.', type: 'success');
    }

    public function acceptOffer(int $id, \App\Application\Offers\Actions\ProcessAcceptedOfferAction $action): void
    {
        $offer = Offer::findOrFail($id);
        $offer->update(['status' => 'accepted', 'responded_at' => now()]);
        
        $action->execute($offer);

        $this->dispatch('notify', message: 'Offer accepted and Transaction created successfully.', type: 'success');
    }

    public function rejectOffer(int $id): void
    {
        $offer = Offer::findOrFail($id);
        $offer->update(['status' => 'rejected', 'responded_at' => now()]);
        $this->dispatch('notify', message: 'Offer rejected.', type: 'success');
    }

    public function render()
    {
        $query = Offer::with('deal', 'contact', 'listing.property', 'submittedBy')
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->whereHas('contact', function ($sq) {
                $sq->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%");
            }))
            ->orderByDesc('created_at');

        $offers = $query->paginate(15);
        $deals = Deal::orderBy('title')->get(['id', 'title', 'contact_id', 'listing_id']);
        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $listings = Listing::orderBy('id', 'desc')->with('property:id,address')->get();

        $stats = [
            'total' => Offer::count(),
            'pending' => Offer::where('status', 'pending')->count(),
            'accepted' => Offer::where('status', 'accepted')->count(),
            'countered' => Offer::where('status', 'countered')->count(),
        ];

        return view('livewire.offers.offers-page', compact('offers', 'deals', 'contacts', 'listings', 'stats'))
            ->layout('layouts.app');
    }
}
