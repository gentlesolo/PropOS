<?php

namespace App\Http\Livewire\Offers;

use App\Application\Offers\Actions\ProcessAcceptedOfferAction;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Offer;
use Livewire\Component;
use Livewire\WithPagination;

class OffersPage extends Component
{
    use WithPagination;

    // ── Filters ───────────────────────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';
    public string $typeFilter   = '';

    protected $queryString = ['search', 'statusFilter', 'typeFilter'];

    // ── Detail panel ──────────────────────────────────────────────────────────
    public bool $showDetail    = false;
    public ?int $detailOfferId = null;

    // ── Create form ───────────────────────────────────────────────────────────
    public bool   $showCreateForm              = false;
    public string $deal_id                     = '';
    public string $contact_id                  = '';
    public string $listing_id                  = '';
    public string $amount                      = '';
    public string $type                        = 'sale';
    public string $expiry_date                 = '';
    public string $proposed_occupation_date    = '';
    public string $deposit_amount              = '';
    public string $conditions                  = '';
    public string $notes                       = '';

    // ── Edit form ─────────────────────────────────────────────────────────────
    public bool   $showEditForm                   = false;
    public ?int   $editOfferId                    = null;
    public string $edit_amount                    = '';
    public string $edit_expiry_date               = '';
    public string $edit_proposed_occupation_date  = '';
    public string $edit_deposit_amount            = '';
    public string $edit_conditions                = '';
    public string $edit_notes                     = '';

    // ── Counter form ──────────────────────────────────────────────────────────
    public bool   $showCounterForm  = false;
    public ?int   $counterOfferId   = null;
    public string $counter_amount   = '';
    public string $counter_notes    = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function openDetail(int $id): void
    {
        $this->detailOfferId  = $id;
        $this->showDetail     = true;
        $this->showCreateForm = false;
        $this->showEditForm   = false;
        $this->showCounterForm = false;
    }

    public function closeDetail(): void
    {
        $this->showDetail    = false;
        $this->detailOfferId = null;
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->reset(['deal_id', 'contact_id', 'listing_id', 'amount', 'expiry_date',
            'proposed_occupation_date', 'deposit_amount', 'conditions', 'notes']);
        $this->type           = 'sale';
        $this->expiry_date    = now()->addDays(7)->toDateString();
        $this->showCreateForm = true;
        $this->showDetail     = false;
        $this->showEditForm   = false;
        $this->showCounterForm = false;
    }

    public function createOffer(): void
    {
        $this->validate([
            'deal_id'    => 'required|exists:deals,id',
            'contact_id' => 'required|exists:contacts,id',
            'amount'     => 'required|numeric|min:1',
            'type'       => 'required|in:sale,rental',
            'expiry_date'=> 'nullable|date|after:today',
        ]);

        $agencyId = auth()->user()->agency_id;
        $deal     = Deal::where('id', $this->deal_id)->where('agency_id', $agencyId)->firstOrFail();

        $offer = Offer::create([
            'agency_id'                => $agencyId,
            'deal_id'                  => $deal->id,
            'listing_id'               => $this->listing_id ?: $deal->listing_id,
            'contact_id'               => $this->contact_id,
            'submitted_by'             => auth()->id(),
            'amount'                   => (float) $this->amount,
            'type'                     => $this->type,
            'status'                   => 'pending',
            'expiry_date'              => $this->expiry_date ?: null,
            'proposed_occupation_date' => $this->proposed_occupation_date ?: null,
            'deposit_amount'           => $this->deposit_amount ?: null,
            'conditions'               => $this->conditions ?: null,
            'notes'                    => $this->notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'deal_id', 'contact_id', 'listing_id', 'amount', 'type',
            'expiry_date', 'proposed_occupation_date', 'deposit_amount', 'conditions', 'notes']);
        $this->openDetail($offer->id);
        $this->dispatch('notify', message: 'Offer submitted.', type: 'success');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function openEditForm(int $id): void
    {
        $offer = $this->scopedOffer($id);

        if ($offer->status !== 'pending') {
            $this->dispatch('notify', message: 'Only pending offers can be edited.', type: 'error');
            return;
        }

        $this->editOfferId                   = $offer->id;
        $this->edit_amount                   = (string) $offer->amount;
        $this->edit_expiry_date              = $offer->expiry_date?->toDateString() ?? '';
        $this->edit_proposed_occupation_date = $offer->proposed_occupation_date?->toDateString() ?? '';
        $this->edit_deposit_amount           = (string) ($offer->deposit_amount ?? '');
        $this->edit_conditions               = $offer->conditions ?? '';
        $this->edit_notes                    = $offer->notes ?? '';
        $this->showEditForm                  = true;
        $this->showCreateForm                = false;
        $this->showCounterForm               = false;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'edit_amount'     => 'required|numeric|min:1',
            'edit_expiry_date'=> 'nullable|date',
        ]);

        $offer = $this->scopedOffer($this->editOfferId);

        if ($offer->status !== 'pending') {
            $this->dispatch('notify', message: 'Only pending offers can be edited.', type: 'error');
            return;
        }

        $offer->update([
            'amount'                   => (float) $this->edit_amount,
            'expiry_date'              => $this->edit_expiry_date ?: null,
            'proposed_occupation_date' => $this->edit_proposed_occupation_date ?: null,
            'deposit_amount'           => $this->edit_deposit_amount ?: null,
            'conditions'               => $this->edit_conditions ?: null,
            'notes'                    => $this->edit_notes ?: null,
        ]);

        $this->reset(['showEditForm', 'editOfferId', 'edit_amount', 'edit_expiry_date',
            'edit_proposed_occupation_date', 'edit_deposit_amount', 'edit_conditions', 'edit_notes']);
        $this->dispatch('notify', message: 'Offer updated.', type: 'success');
    }

    public function cancelEdit(): void
    {
        $this->reset(['showEditForm', 'editOfferId', 'edit_amount', 'edit_expiry_date',
            'edit_proposed_occupation_date', 'edit_deposit_amount', 'edit_conditions', 'edit_notes']);
    }

    // ── Counter ───────────────────────────────────────────────────────────────

    public function openCounterForm(int $id): void
    {
        $offer = $this->scopedOffer($id);

        if (! in_array($offer->status, ['pending', 'countered'])) {
            $this->dispatch('notify', message: 'Can only counter a pending or countered offer.', type: 'error');
            return;
        }

        $this->counterOfferId = $offer->id;
        $this->counter_amount = (string) ($offer->counter_amount ?? '');
        $this->counter_notes  = $offer->counter_notes ?? '';
        $this->showCounterForm = true;
        $this->showEditForm    = false;
        $this->showCreateForm  = false;
    }

    public function submitCounter(): void
    {
        $this->validate([
            'counter_amount' => 'required|numeric|min:1',
        ]);

        $this->scopedOffer($this->counterOfferId)->update([
            'status'        => 'countered',
            'counter_amount'=> (float) $this->counter_amount,
            'counter_notes' => $this->counter_notes ?: null,
            'responded_at'  => now(),
        ]);

        $this->reset(['showCounterForm', 'counterOfferId', 'counter_amount', 'counter_notes']);
        $this->dispatch('notify', message: 'Counter offer submitted.', type: 'success');
    }

    // ── Accept / Reject / Withdraw ────────────────────────────────────────────

    public function acceptOffer(int $id, ProcessAcceptedOfferAction $action): void
    {
        $offer = $this->scopedOffer($id);
        $offer->update(['status' => 'accepted', 'responded_at' => now()]);
        $action->execute($offer);
        $this->dispatch('notify', message: 'Offer accepted and transaction created.', type: 'success');
    }

    public function rejectOffer(int $id): void
    {
        $this->scopedOffer($id)->update(['status' => 'rejected', 'responded_at' => now()]);
        $this->dispatch('notify', message: 'Offer rejected.', type: 'info');
    }

    public function withdrawOffer(int $id): void
    {
        $offer = $this->scopedOffer($id);

        if (! in_array($offer->status, ['pending', 'countered'])) {
            $this->dispatch('notify', message: 'Only pending or countered offers can be withdrawn.', type: 'error');
            return;
        }

        $offer->update(['status' => 'withdrawn', 'responded_at' => now()]);
        $this->dispatch('notify', message: 'Offer withdrawn.', type: 'info');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function deleteOffer(int $id): void
    {
        $offer = $this->scopedOffer($id);

        if (! in_array($offer->status, ['pending', 'expired', 'withdrawn'])) {
            $this->dispatch('notify', message: 'Accepted, countered and rejected offers cannot be deleted.', type: 'error');
            return;
        }

        if ($this->detailOfferId === $id) {
            $this->showDetail = false;
        }

        $offer->delete();
        $this->dispatch('notify', message: 'Offer deleted.', type: 'info');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function scopedOffer(int $id): Offer
    {
        return Offer::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $offers = Offer::with(['deal', 'contact', 'listing.property', 'submittedBy', 'contract'])
            ->where('agency_id', $agencyId)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter,   fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->search, fn ($q) => $q->whereHas('contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(15);

        $deals    = Deal::where('agency_id', $agencyId)->orderBy('title')->get(['id', 'title', 'contact_id', 'listing_id']);
        $contacts = Contact::where('agency_id', $agencyId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $listings = Listing::where('agency_id', $agencyId)->with('property:id,address_line_1')->latest()->get(['id', 'property_id']);

        $stats = [
            'total'    => Offer::where('agency_id', $agencyId)->count(),
            'pending'  => Offer::where('agency_id', $agencyId)->where('status', 'pending')->count(),
            'accepted' => Offer::where('agency_id', $agencyId)->where('status', 'accepted')->count(),
            'countered'=> Offer::where('agency_id', $agencyId)->where('status', 'countered')->count(),
        ];

        $detailOffer = null;
        if ($this->showDetail && $this->detailOfferId) {
            $detailOffer = Offer::with(['deal.listing.property', 'contact', 'listing.property', 'submittedBy', 'contract'])
                ->where('agency_id', $agencyId)
                ->find($this->detailOfferId);
        }

        return view('livewire.offers.offers-page', compact('offers', 'deals', 'contacts', 'listings', 'stats', 'detailOffer'))
            ->layout('layouts.app');
    }
}
