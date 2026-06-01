<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\CalculateDealMomentumAction;
use App\Application\CRM\Actions\CreateFollowUpSequenceAction;
use App\Application\CRM\Actions\GenerateFollowUpMessageAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\SuggestNextActionAction;
use App\Infrastructure\Persistence\Models\ContactActivity;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\FollowUpSequence;
use App\Infrastructure\Persistence\Models\PipelineStage;
use App\Infrastructure\Persistence\Models\StageChecklistItem;
use Livewire\Component;

class DealDetailPage extends Component
{
    public Deal $deal;

    // Activity log
    public string $activityType = 'note';
    public string $activitySubject = '';
    public string $activityBody = '';

    // Edit deal
    public bool $showEditForm = false;
    public string $title = '';
    public string $value = '';
    public string $pipeline_stage_id = '';
    public string $notes = '';

    // Checklist
    public string $newChecklistItem = '';

    // Follow-up
    public bool $showFollowUpForm = false;
    public string $followUpName = '';
    public array $followUpSteps = [
        ['type' => 'email', 'subject' => '', 'message_template' => '', 'delay_days' => 1],
    ];

    // AI next best action
    public ?string $nextActionSuggestion = null;
    public bool $loadingNextAction = false;

    // AI message generation per-step (keyed by step index)
    public array $generatingMessage = [];

    public function mount(Deal $deal)
    {
        $this->deal = $deal->load('contact', 'listing.property', 'stage', 'agent', 'activities.user', 'checklistItems');
        $this->title = $deal->title;
        $this->value = (string) $deal->value;
        $this->pipeline_stage_id = (string) $deal->pipeline_stage_id;
        $this->notes = $deal->notes ?? '';
    }

    public function saveDeal(CalculateDealMomentumAction $momentum, \App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction $checklistGenerator)
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'notes' => 'nullable|string',
        ]);

        $this->deal->update([
            'title' => $this->title,
            'value' => $this->value,
            'pipeline_stage_id' => $this->pipeline_stage_id,
            'notes' => $this->notes ?: null,
        ]);

        $momentum->execute($this->deal->fresh());

        $stage = PipelineStage::find($this->pipeline_stage_id);
        if ($stage) {
            $checklistGenerator->execute($this->deal, $stage);
        }

        $this->showEditForm = false;
        $this->deal->refresh()->load('stage', 'activities.user', 'checklistItems');
        $this->nextActionSuggestion = null;
    }

    public function logActivity(LogContactActivityAction $logAction)
    {
        $this->validate(['activityBody' => 'required|string|max:2000']);

        $logAction->execute(
            $this->deal->contact,
            $this->activityType,
            $this->activitySubject ?: null,
            $this->activityBody,
            ['deal_id' => $this->deal->id]
        );

        ContactActivity::create([
            'agency_id'  => $this->deal->agency_id,
            'contact_id' => $this->deal->contact_id,
            'deal_id'    => $this->deal->id,
            'user_id'    => auth()->id(),
            'type'       => $this->activityType,
            'subject'    => $this->activitySubject ?: null,
            'body'       => $this->activityBody,
            'occurred_at' => now(),
        ]);

        app(CalculateDealMomentumAction::class)->execute($this->deal->fresh());

        $this->reset(['activityBody', 'activitySubject']);
        $this->activityType = 'note';
        $this->deal->refresh()->load('activities.user', 'checklistItems');
        $this->nextActionSuggestion = null;
    }

    public function addChecklistItem()
    {
        $this->validate(['newChecklistItem' => 'required|string|max:255']);

        StageChecklistItem::create([
            'agency_id'         => $this->deal->agency_id,
            'pipeline_stage_id' => $this->deal->pipeline_stage_id,
            'deal_id'           => $this->deal->id,
            'title'             => $this->newChecklistItem,
            'order'             => $this->deal->checklistItems()->count() + 1,
        ]);

        $this->newChecklistItem = '';
        $this->deal->refresh()->load('checklistItems');

        // Suggest advancing stage if all items now complete
        $this->checkChecklistCompletion();
    }

    public function toggleChecklistItem(int $itemId)
    {
        $item = StageChecklistItem::find($itemId);
        if ($item && $item->deal_id === $this->deal->id) {
            $item->update([
                'completed'    => !$item->completed,
                'completed_at' => !$item->completed ? now() : null,
                'completed_by' => !$item->completed ? auth()->id() : null,
            ]);
        }
        $this->deal->refresh()->load('checklistItems');
        $this->checkChecklistCompletion();
    }

    public function deleteChecklistItem(int $itemId)
    {
        StageChecklistItem::where('id', $itemId)->where('deal_id', $this->deal->id)->delete();
        $this->deal->refresh()->load('checklistItems');
    }

    public function addFollowUpStep()
    {
        $this->followUpSteps[] = ['type' => 'email', 'subject' => '', 'message_template' => '', 'delay_days' => 3];
    }

    public function removeFollowUpStep(int $index)
    {
        array_splice($this->followUpSteps, $index, 1);
        unset($this->generatingMessage[$index]);
    }

    public function generateStepMessage(int $index, GenerateFollowUpMessageAction $generator)
    {
        $this->generatingMessage[$index] = true;

        $stepType = $this->followUpSteps[$index]['type'] ?? 'email';
        $result = $generator->execute($this->deal->contact, $stepType, $this->deal);

        $this->followUpSteps[$index]['subject'] = $result['subject'];
        $this->followUpSteps[$index]['message_template'] = $result['body'];

        unset($this->generatingMessage[$index]);
        $this->dispatch('notify', message: 'AI message generated. Review and edit before saving.', type: 'info');
    }

    public function saveFollowUpSequence(CreateFollowUpSequenceAction $action)
    {
        $this->validate([
            'followUpName' => 'required|string|max:255',
            'followUpSteps' => 'required|array|min:1',
            'followUpSteps.*.type' => 'required|in:email,call,sms,task',
            'followUpSteps.*.message_template' => 'required|string',
            'followUpSteps.*.delay_days' => 'required|integer|min:0',
        ]);

        $action->execute($this->deal->contact, $this->followUpName, $this->followUpSteps);

        $this->showFollowUpForm = false;
        $this->followUpName = '';
        $this->followUpSteps = [['type' => 'email', 'subject' => '', 'message_template' => '', 'delay_days' => 1]];
        $this->generatingMessage = [];
        $this->dispatch('notify', message: 'Follow-up sequence created!', type: 'success');
    }

    public function loadNextAction(SuggestNextActionAction $suggester)
    {
        $this->loadingNextAction = true;
        $this->nextActionSuggestion = $suggester->forDeal($this->deal);
        $this->loadingNextAction = false;
    }

    public function deleteDeal(): void
    {
        $agencyId = auth()->user()->agency_id;
        Deal::where('id', $this->deal->id)
            ->where('agency_id', $agencyId)
            ->firstOrFail()
            ->delete();

        $this->redirect(route('crm.pipeline'), navigate: true);
    }

    public function dismissNextAction()
    {
        $this->nextActionSuggestion = null;
    }

    private function checkChecklistCompletion(): void
    {
        $this->deal->refresh()->load('checklistItems');
        $total = $this->deal->checklistItems->count();
        $done = $this->deal->checklistItems->where('completed', true)->count();

        if ($total > 0 && $done === $total) {
            $this->dispatch('notify', message: 'All checklist items complete — consider advancing to the next stage.', type: 'success');
        }
    }

    public function render()
    {
        $stages = PipelineStage::where('pipeline_type', $this->deal->stage?->pipeline_type ?? 'sale')
            ->orderBy('order')
            ->get();

        $followUpSequences = FollowUpSequence::where('contact_id', $this->deal->contact_id)
            ->with('steps')
            ->latest()
            ->get();

        return view('livewire.crm.deal-detail-page', compact('stages', 'followUpSequences'))
            ->layout('layouts.app');
    }
}
