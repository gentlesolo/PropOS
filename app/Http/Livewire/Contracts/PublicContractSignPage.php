<?php

namespace App\Http\Livewire\Contracts;

use App\Infrastructure\Persistence\Models\Contract;
use Livewire\Component;

class PublicContractSignPage extends Component
{
    public string $reference;
    public ?Contract $contract = null;

    public string $fullName = '';
    public string $initials = '';
    public string $signatureStyle = 'dancing';
    public bool $agreed = false;
    public bool $signed = false;

    public function mount(string $reference): void
    {
        $this->reference = $reference;
        $this->contract = Contract::where('reference', $reference)->firstOrFail();

        if ($this->contract->status === 'fully_signed') {
            $this->signed = true;
        }
    }

    public function submitSignature(): void
    {
        $this->validate([
            'fullName' => 'required|string|max:255',
            'initials' => 'required|string|max:10',
            'agreed' => 'accepted',
        ]);

        $signatures = $this->contract->signed_at ?? [];
        $signatures[] = [
            'name' => $this->fullName,
            'initials' => $this->initials,
            'signed_at' => now()->toDateTimeString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $this->contract->update([
            'status' => 'fully_signed',
            'signed_at' => $signatures,
        ]);

        // Auto-update deal stage if the contract is linked to a deal
        if ($this->contract->deal_id) {
            $deal = $this->contract->deal;
            if ($deal) {
                // Find or update the stage to "Closed" or "Offer Accepted"
                $stage = \App\Infrastructure\Persistence\Models\PipelineStage::where('agency_id', $deal->agency_id)
                    ->where('is_won', true)
                    ->first();
                if ($stage) {
                    $deal->update(['pipeline_stage_id' => $stage->id]);
                    // Run checklist items automatically
                    app(\App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction::class)->execute($deal, $stage);
                }
            }
        }

        $this->signed = true;
        $this->dispatch('notify', message: 'Contract successfully signed electronically!', type: 'success');
    }

    public function render()
    {
        return view('livewire.contracts.public-contract-sign-page')
            ->layout('layouts.guest'); // Guest layout for public pages
    }
}
