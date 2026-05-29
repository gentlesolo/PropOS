<?php

namespace App\Http\Livewire\Intelligence;

use App\Infrastructure\Persistence\Models\Commission;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Transaction;
use Livewire\Component;

class CommissionLedgerPage extends Component
{
    public string $filterStatus = '';
    public string $year;

    public function mount()
    {
        $this->year = (string) now()->year;
    }

    public function reconcile()
    {
        $agencyId = auth()->user()->agency_id;

        // Create Commission records for won deals that don't have one yet
        $wonDeals = Deal::with(['stage', 'agent', 'listing'])
            ->where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', true))
            ->get();

        foreach ($wonDeals as $deal) {
            $transaction = Transaction::firstOrCreate(
                ['deal_id' => $deal->id],
                [
                    'agency_id' => $agencyId,
                    'listing_id' => $deal->listing_id,
                    'contact_id' => $deal->contact_id,
                    'assigned_agent_id' => $deal->assigned_agent_id,
                    'sale_price' => $deal->value,
                    'commission_rate' => 5.00,
                    'agent_split' => 50.00,
                    'status' => 'completed',
                    'closed_at' => $deal->updated_at->toDateString(),
                ]
            );

            if (!Commission::where('deal_id', $deal->id)->exists()) {
                $gross = $deal->value * (5 / 100);
                $agentSplit = 50;
                Commission::create([
                    'agency_id' => $agencyId,
                    'transaction_id' => $transaction->id,
                    'deal_id' => $deal->id,
                    'agent_id' => $deal->assigned_agent_id,
                    'sale_price' => $deal->value,
                    'commission_rate' => 5.00,
                    'gross_commission' => $gross,
                    'agent_split_percentage' => $agentSplit,
                    'agent_commission' => $gross * ($agentSplit / 100),
                    'agency_commission' => $gross * ((100 - $agentSplit) / 100),
                    'payment_status' => 'pending',
                    'expected_payment_date' => now()->addDays(30)->toDateString(),
                ]);
            }
        }

        $this->dispatch('notify', message: 'Ledger reconciled with current won deals.', type: 'success');
    }

    public function markPaid(int $commissionId)
    {
        Commission::where('id', $commissionId)
            ->where('agency_id', auth()->user()->agency_id)
            ->update([
                'payment_status' => 'paid',
                'paid_at' => now()->toDateString(),
            ]);
        $this->dispatch('notify', message: 'Commission marked as paid.', type: 'success');
    }

    public function markProcessing(int $commissionId)
    {
        Commission::where('id', $commissionId)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['payment_status' => 'processing']);
        $this->dispatch('notify', message: 'Commission marked as processing.', type: 'success');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $ledger = Commission::with(['deal.listing.property', 'deal.contact', 'agent', 'transaction'])
            ->where('agency_id', $agencyId)
            ->when($this->filterStatus, fn($q) => $q->where('payment_status', $this->filterStatus))
            ->whereYear('created_at', $this->year)
            ->latest()
            ->get();

        $stats = [
            'gross_ytd' => Commission::where('agency_id', $agencyId)->whereYear('created_at', $this->year)->sum('gross_commission'),
            'agency_ytd' => Commission::where('agency_id', $agencyId)->whereYear('created_at', $this->year)->sum('agency_commission'),
            'agents_ytd' => Commission::where('agency_id', $agencyId)->whereYear('created_at', $this->year)->sum('agent_commission'),
            'paid' => Commission::where('agency_id', $agencyId)->where('payment_status', 'paid')->whereYear('created_at', $this->year)->sum('gross_commission'),
            'pending_count' => Commission::where('agency_id', $agencyId)->whereIn('payment_status', ['pending', 'processing'])->count(),
        ];

        return view('livewire.intelligence.commission-ledger-page', compact('ledger', 'stats'))
            ->layout('layouts.app');
    }
}
