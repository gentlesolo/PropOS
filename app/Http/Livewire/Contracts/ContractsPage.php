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
    public string $selectedTemplate = '';

    public const TEMPLATES = [
        'sale_agreement' => [
            'title' => 'Standard Sale Agreement',
            'body' => "SALE AGREEMENT\n\nThis Agreement is entered into by and between:\nSeller: {seller_name}\nBuyer: {buyer_name}\n\nProperty: {property_address}\nPurchase Price: {price}\n\nTERMS AND CONDITIONS:\n1. The Buyer agrees to buy and the Seller agrees to sell the Property for the Purchase Price.\n2. Earnest money deposit is to be paid within 7 days of this agreement.\n3. Transfer of ownership will take place upon registration at the deeds office.\n\nSigned at Lagos on {date}.\n\nSeller Signature: ___________________\nBuyer Signature: ___________________",
        ],
        'lease_agreement' => [
            'title' => 'Residential Lease Agreement',
            'body' => "RESIDENTIAL LEASE AGREEMENT\n\nLandlord: {agency_name} (on behalf of Owner)\nTenant: {buyer_name}\n\nProperty: {property_address}\nMonthly Rent: {price}/month\n\nTERMS AND CONDITIONS:\n1. The Lease shall commence on {date} for a duration of 12 months.\n2. The Tenant shall pay the monthly rent on or before the 1st of each month.\n3. The Tenant agrees to maintain the property in clean and tenantable condition.\n\nSigned at Lagos on {date}.\n\nLandlord Signature: ___________________\nTenant Signature: ___________________",
        ],
        'mandate' => [
            'title' => 'Exclusive Seller Mandate',
            'body' => "EXCLUSIVE SELLER MANDATE\n\nAgency: {agency_name}\nSeller: {seller_name}\n\nProperty: {property_address}\nTarget Selling Price: {price}\n\nTERMS AND CONDITIONS:\n1. The Seller hereby grants the Agency the exclusive mandate to market and sell the Property.\n2. The Agency commission rate is agreed at 5.00% of the final sale price.\n3. This mandate shall remain in force for a period of 90 days from date hereof.\n\nSigned at Lagos on {date}.\n\nSeller Signature: ___________________\nAgent Signature: ___________________",
        ],
    ];

    public function updatedSelectedTemplate(string $val): void
    {
        if (empty($val) || !isset(self::TEMPLATES[$val])) {
            return;
        }

        $template = self::TEMPLATES[$val];
        $body = $template['body'];

        // Resolve data
        $agencyName = auth()->user()->agency->name ?? 'PropOS Agency';
        $contactName = 'Client / Signatory';
        $sellerName = 'Owner / Seller';
        $propAddress = 'The Property';
        $price = 'Market Price';

        if ($this->contact_id) {
            $contact = Contact::find($this->contact_id);
            if ($contact) {
                $contactName = $contact->first_name . ' ' . $contact->last_name;
                if ($contact->type === 'seller') {
                    $sellerName = $contactName;
                }
            }
        }

        if ($this->deal_id) {
            $deal = Deal::with(['contact', 'listing.property'])->find($this->deal_id);
            if ($deal) {
                if ($deal->contact) {
                    $contactName = $deal->contact->first_name . ' ' . $deal->contact->last_name;
                    if ($deal->contact->type === 'seller') {
                        $sellerName = $contactName;
                    }
                }
                if ($deal->listing && $deal->listing->property) {
                    $propAddress = $deal->listing->property->address_line_1;
                }
                $price = 'NGN ' . number_format($deal->value, 2);
            }
        }

        if (!$this->deal_id && $this->listing_id) {
            $listing = Listing::with('property')->find($this->listing_id);
            if ($listing) {
                if ($listing->property) {
                    $propAddress = $listing->property->address_line_1;
                }
                $price = 'NGN ' . number_format($listing->listing_price, 2);
            }
        }

        // Substitution
        $body = str_replace('{agency_name}', $agencyName, $body);
        $body = str_replace('{buyer_name}', $contactName, $body);
        $body = str_replace('{seller_name}', $sellerName, $body);
        $body = str_replace('{property_address}', $propAddress, $body);
        $body = str_replace('{price}', $price, $body);
        $body = str_replace('{date}', now()->format('d M Y'), $body);

        $this->body = $body;
        $this->title = $template['title'] . ' — ' . $propAddress;
        $this->type = $val;
    }

    public function sendForSignature(int $contractId): void
    {
        $contract = Contract::findOrFail($contractId);
        $contract->update([
            'status' => 'sent',
            'signatories' => array_merge($contract->signatories ?? [], [
                'envelope_id' => 'env_' . Str::random(16),
                'sent_at' => now()->toDateTimeString(),
            ]),
        ]);
        $this->dispatch('notify', message: 'Contract sent for eSignature via simulated portal.', type: 'success');
    }

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
