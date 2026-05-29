<?php

namespace App\Http\Livewire\Compliance;

use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Transaction;
use Livewire\Component;

class TransactionCenterPage extends Component
{
    public string $activeTab = 'pending';

    public function initiateTransaction(int $dealId)
    {
        $deal = Deal::with(['contact', 'listing', 'stage', 'agent'])->findOrFail($dealId);

        $existing = Transaction::where('deal_id', $dealId)->first();
        if ($existing) {
            return redirect()->route('compliance.transaction.detail', $existing);
        }

        $transaction = Transaction::create([
            'agency_id' => $deal->agency_id,
            'deal_id' => $deal->id,
            'listing_id' => $deal->listing_id,
            'contact_id' => $deal->contact_id,
            'assigned_agent_id' => $deal->assigned_agent_id,
            'sale_price' => $deal->value,
            'commission_rate' => 5.00,
            'agent_split' => 50.00,
            'status' => 'fica_pending',
            'deadline' => now()->addDays(30)->toDateString(),
            'estimated_close_date' => now()->addDays(60)->toDateString(),
        ]);

        // Seed default FICA checklist documents
        $ficaDocs = [
            ['title' => 'Proof of Identity', 'document_type' => 'proof_of_identity', 'is_fica_required' => true],
            ['title' => 'Proof of Address', 'document_type' => 'proof_of_address', 'is_fica_required' => true],
            ['title' => 'Bank Statements (3 months)', 'document_type' => 'bank_statement', 'is_fica_required' => true],
            ['title' => 'Signed Mandate', 'document_type' => 'signed_mandate', 'is_fica_required' => false],
            ['title' => 'Offer to Purchase', 'document_type' => 'offer_to_purchase', 'is_fica_required' => false],
        ];

        foreach ($ficaDocs as $doc) {
            $transaction->documents()->create([
                'agency_id' => $deal->agency_id,
                'document_type' => $doc['document_type'],
                'title' => $doc['title'],
                'status' => 'required',
                'is_fica_required' => $doc['is_fica_required'],
            ]);
        }

        return redirect()->route('compliance.transaction.detail', $transaction);
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        // Real transactions
        $transactions = Transaction::with(['deal', 'contact', 'listing.property', 'documents', 'agent'])
            ->where('agency_id', $agencyId)
            ->when($this->activeTab === 'pending', fn($q) => $q->whereNotIn('status', ['completed', 'cancelled', 'fica_verified']))
            ->when($this->activeTab === 'approved', fn($q) => $q->where('status', 'fica_verified'))
            ->when($this->activeTab === 'completed', fn($q) => $q->whereIn('status', ['completed', 'cancelled']))
            ->orderBy('deadline')
            ->get();

        // Deals eligible for transaction initiation (won/offer-accepted stages, no transaction yet)
        $eligibleDeals = Deal::with(['contact', 'stage', 'listing.property'])
            ->where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', true)->orWhere('name', 'like', '%Offer%')->orWhere('name', 'like', '%Negotiation%'))
            ->whereDoesntHave('transaction')
            ->get();

        $stats = [
            'action_required' => Transaction::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled', 'fica_verified'])->count(),
            'overdue' => Transaction::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where('deadline', '<', now())->count(),
            'compliant' => Transaction::where('agency_id', $agencyId)
                ->where('status', 'fica_verified')->count(),
            'completed' => Transaction::where('agency_id', $agencyId)
                ->where('status', 'completed')->count(),
        ];

        return view('livewire.compliance.transaction-center-page', compact('transactions', 'eligibleDeals', 'stats'))
            ->layout('layouts.app');
    }
}
