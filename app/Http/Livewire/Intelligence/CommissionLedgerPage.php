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
    public ?int $selectedCommissionId = null;

    public function mount()
    {
        $this->year = (string) now()->year;
    }

    public function selectCommission(int $id): void
    {
        $this->selectedCommissionId = $id;
    }

    public function reconcile()
    {
        $agencyId = auth()->user()->agency_id;

        // Fetch all active commission split configurations for the agency
        $splitConfigs = \App\Infrastructure\Persistence\Models\CommissionSplitConfig::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->get();

        // Create Commission records for won deals that don't have one yet
        $wonDeals = Deal::with(['stage', 'agent.roles', 'listing'])
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
                // Find matching split config based on priority:
                // 1. Specific Agent
                // 2. Role
                // 3. Agency Default
                $agent = $deal->agent;
                $roleNames = $agent ? $agent->roles->pluck('name')->toArray() : [];

                $matchingConfig = null;
                if ($agent) {
                    $matchingConfig = $splitConfigs->first(fn($c) => $c->applies_to === 'agent' && (int)$c->user_id === (int)$agent->id);
                    if (!$matchingConfig) {
                        $matchingConfig = $splitConfigs->first(fn($c) => $c->applies_to === 'role' && in_array($c->role, $roleNames));
                    }
                }
                if (!$matchingConfig) {
                    $matchingConfig = $splitConfigs->first(fn($c) => $c->applies_to === 'agency_default');
                }

                $commRate = $matchingConfig ? (float)$matchingConfig->commission_rate : 5.00;
                $agentSplit = $matchingConfig ? (float)$matchingConfig->agent_split : 50.00;

                $gross = $deal->value * ($commRate / 100);

                Commission::create([
                    'agency_id' => $agencyId,
                    'transaction_id' => $transaction->id,
                    'deal_id' => $deal->id,
                    'agent_id' => $deal->assigned_agent_id,
                    'sale_price' => $deal->value,
                    'commission_rate' => $commRate,
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

        $this->dispatch('notify', message: 'Commission payment status updated.', type: 'success');
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

        $commissions = Commission::with(['deal', 'agent', 'transaction.listing.property'])
            ->where('agency_id', $agencyId)
            ->when($this->filterStatus, fn($q) => $q->where('payment_status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->get();

        // Metrics
        $totalBrokerageRevenue = $commissions->where('payment_status', 'paid')->sum('agency_commission');
        $totalAgentPayouts = $commissions->where('payment_status', 'paid')->sum('agent_commission');
        $ytdVolume = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', true))
            ->whereYear('updated_at', $this->year)
            ->sum('value');

        $activeCommission = $this->selectedCommissionId 
            ? Commission::with(['deal.contact', 'agent', 'transaction.listing.property', 'agency'])->find($this->selectedCommissionId)
            : null;

        return view('livewire.intelligence.commission-ledger-page', [
            'commissions' => $commissions,
            'totalBrokerageRevenue' => $totalBrokerageRevenue,
            'totalAgentPayouts' => $totalAgentPayouts,
            'ytdVolume' => $ytdVolume,
            'activeCommission' => $activeCommission,
        ])->layout('layouts.app');
    }
}
