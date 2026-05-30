<?php

namespace App\Http\Livewire\Contracts;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Contract;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class ContractsPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public bool $showCreateForm = false;
    public ?int $viewingContractId = null;

    // Create form
    public string $title = '';
    public string $type = 'sale_agreement';
    public string $deal_id = '';
    public string $contact_id = '';
    public string $listing_id = '';
    public string $valid_from = '';
    public string $valid_until = '';
    public string $body = '';

    protected $queryString = ['search', 'statusFilter', 'typeFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createContract(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:sale_agreement,lease_agreement,mou,mandate,offer_to_purchase,addendum,other',
            'deal_id' => 'nullable|exists:deals,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);

        Contract::create([
            'agency_id' => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'deal_id' => $this->deal_id ?: null,
            'contact_id' => $this->contact_id ?: null,
            'listing_id' => $this->listing_id ?: null,
            'title' => $this->title,
            'type' => $this->type,
            'status' => 'draft',
            'body' => $this->body ?: null,
            'valid_from' => $this->valid_from ?: null,
            'valid_until' => $this->valid_until ?: null,
        ]);

        $this->reset(['showCreateForm', 'title', 'type', 'deal_id', 'contact_id', 'listing_id', 'valid_from', 'valid_until', 'body']);
        $this->dispatch('notify', message: 'Contract created as draft.', type: 'success');
    }

    public function updateStatus(int $id, string $status): void
    {
        $contract = Contract::findOrFail($id);
        $contract->update(['status' => $status]);
        $this->dispatch('notify', message: 'Contract status updated.', type: 'success');
    }

    public function render()
    {
        $contracts = Contract::with('deal', 'contact', 'listing.property', 'createdBy')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $deals = Deal::orderBy('title')->get(['id', 'title']);
        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $listings = Listing::with('property:id,address')->latest()->get(['id', 'property_id']);

        $stats = [
            'draft' => Contract::where('status', 'draft')->count(),
            'sent' => Contract::whereIn('status', ['sent', 'viewed'])->count(),
            'signed' => Contract::where('status', 'fully_signed')->count(),
            'total' => Contract::count(),
        ];

        return view('livewire.contracts.contracts-page', compact('contracts', 'deals', 'contacts', 'listings', 'stats'))
            ->layout('layouts.app');
    }
}
