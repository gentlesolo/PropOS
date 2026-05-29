<?php

namespace App\Http\Livewire\Ai;

use App\Application\AI\Actions\GenerateDailyBriefAction;
use App\Infrastructure\Persistence\Models\DailyBrief;
use Carbon\Carbon;
use Livewire\Component;

class PlannerPage extends Component
{
    public DailyBrief $brief;
    public bool $generatingBrief = false;

    public function mount()
    {
        $this->loadBrief();
    }

    public function loadBrief()
    {
        $userId = auth()->id();
        $agencyId = auth()->user()->agency_id;
        $today = Carbon::today();

        // Try to load today's brief from DB
        $existing = DailyBrief::where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($existing) {
            $this->brief = $existing;
            return;
        }

        // Generate from real data
        $this->brief = app(GenerateDailyBriefAction::class)->execute($userId, $agencyId);
    }

    public function regenerateBrief()
    {
        $this->generatingBrief = true;

        DailyBrief::where('agency_id', auth()->user()->agency_id)
            ->where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->delete();

        $this->brief = app(GenerateDailyBriefAction::class)->execute(auth()->id(), auth()->user()->agency_id);
        $this->generatingBrief = false;

        $this->dispatch('notify', message: 'Brief regenerated from live data.', type: 'success');
    }

    public function completeAction(int $index)
    {
        $actions = $this->brief->priority_actions;
        if (isset($actions[$index])) {
            $actions[$index]['completed'] = true;
            $this->brief->update(['priority_actions' => $actions]);
            $this->dispatch('notify', message: 'Action marked complete.', type: 'success');
        }
    }

    public function draftWithCopilot(int $index)
    {
        $actions = $this->brief->priority_actions;
        $action = $actions[$index] ?? null;
        if (!$action) return;

        $context = "Draft a follow-up email for this task: {$action['title']}. Context: {$action['context']}";
        $this->dispatch('open-chat-with-context', context: $context);
    }

    public function render()
    {
        return view('livewire.ai.planner-page')->layout('layouts.app');
    }
}
