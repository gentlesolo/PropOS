<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\CalculateDealMomentumAction;
use App\Application\CRM\Actions\DetectStaleDealsAction;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\PipelineStage;
use Livewire\Component;

class PipelineBoard extends Component
{
    public string $pipelineType = 'sale';

    public bool $showNewDealModal = false;
    public string $title = '';
    public string $contact_id = '';
    public string $listing_id = '';
    public string $value = '';
    public string $pipeline_stage_id = '';
    public string $notes = '';

    public function updateDealStage(int $dealId, int $newStageId, CalculateDealMomentumAction $momentum, \App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction $checklistGenerator)
    {
        $deal = Deal::find($dealId);
        $stage = PipelineStage::find($newStageId);
        if ($deal && $stage) {
            $deal->update(['pipeline_stage_id' => $newStageId]);
            $momentum->execute($deal->fresh());
            $checklistGenerator->execute($deal, $stage);
            $this->dispatch('notify', message: 'Deal moved successfully.', type: 'success');
        }
    }

    public function saveDeal(CalculateDealMomentumAction $momentum, \App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction $checklistGenerator)
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'contact_id' => 'required|exists:contacts,id',
            'value' => 'required|numeric|min:0',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'notes' => 'nullable|string',
        ]);

        $deal = Deal::create([
            'agency_id' => auth()->user()->agency_id,
            'assigned_agent_id' => auth()->id(),
            'pipeline_stage_id' => $this->pipeline_stage_id,
            'contact_id' => $this->contact_id,
            'listing_id' => $this->listing_id ?: null,
            'title' => $this->title,
            'type' => $this->pipelineType,
            'value' => $this->value,
            'notes' => $this->notes ?: null,
            'momentum_score' => 80,
        ]);

        $momentum->execute($deal->fresh());
        
        $stage = PipelineStage::find($this->pipeline_stage_id);
        if ($stage) {
            $checklistGenerator->execute($deal, $stage);
        }

        $this->reset(['title', 'contact_id', 'listing_id', 'value', 'notes', 'showNewDealModal']);
        $this->pipeline_stage_id = '';
        $this->dispatch('notify', message: 'Deal created successfully.', type: 'success');
    }

    public function getStagesProperty()
    {
        return PipelineStage::where('pipeline_type', $this->pipelineType)
            ->with(['deals' => fn($q) => $q->with(['contact', 'listing.property', 'agent'])])
            ->orderBy('order')
            ->get();
    }

    public function render(DetectStaleDealsAction $staleDetector)
    {
        $staleDeals = $staleDetector->execute(auth()->user()->agency_id ?? 1);
        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $listings = Listing::with('property')->where('status', 'active')->get();
        $firstStage = PipelineStage::where('pipeline_type', $this->pipelineType)->orderBy('order')->first();
        if ($firstStage && !$this->pipeline_stage_id) {
            $this->pipeline_stage_id = (string) $firstStage->id;
        }

        return view('livewire.crm.pipeline-board', [
            'stages' => $this->stages,
            'staleDeals' => $staleDeals,
            'contacts' => $contacts,
            'listings' => $listings,
        ])->layout('layouts.app');
    }
}
