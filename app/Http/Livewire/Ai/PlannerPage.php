<?php

namespace App\Http\Livewire\Ai;

use App\Application\AI\Actions\GenerateDailyBriefAction;
use App\Infrastructure\Persistence\Models\DailyBrief;
use App\Infrastructure\Persistence\Models\Task;
use Carbon\Carbon;
use Livewire\Component;

class PlannerPage extends Component
{
    public ?DailyBrief $brief = null;
    public bool $generatingBrief = false;
    public bool $generating = false;
    public string $selectedDate = '';

    public bool $showGoalModal = false;
    public string $newGoalTitle = '';
    public int $newGoalTarget = 1;
    public string $newGoalUnit = 'calls';

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
        $this->loadBrief();
    }

    public function loadBrief(): void
    {
        $userId   = auth()->id();
        $agencyId = auth()->user()->agency_id;
        $date     = Carbon::parse($this->selectedDate);

        $existing = DailyBrief::where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->where('date', $date->toDateString())
            ->first();

        if ($existing) {
            $this->brief      = $existing;
            $this->generating = false;
            return;
        }

        $this->brief      = null;
        $this->generating = $date->isToday();
    }

    public function generateBrief(): void
    {
        if (!$this->generating || !Carbon::parse($this->selectedDate)->isToday()) {
            return;
        }

        $this->brief      = app(GenerateDailyBriefAction::class)->execute(auth()->id(), auth()->user()->agency_id);
        $this->generating = false;
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->toDateString();
        $this->loadBrief();
    }

    public function nextDay(): void
    {
        $next = Carbon::parse($this->selectedDate)->addDay();
        if ($next->isFuture() && !$next->isToday()) {
            return;
        }
        $this->selectedDate = $next->toDateString();
        $this->loadBrief();
    }

    public function regenerateBrief(): void
    {
        if (!Carbon::parse($this->selectedDate)->isToday()) {
            return;
        }

        DailyBrief::where('agency_id', auth()->user()->agency_id)
            ->where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->delete();

        $this->brief      = null;
        $this->generating = true;
        $this->generateBrief();
        $this->dispatch('notify', message: 'Brief regenerated from live data.', type: 'success');
    }

    public function completeAction(int $index): void
    {
        $actions = $this->brief->priority_actions;
        if (isset($actions[$index])) {
            $actions[$index]['completed'] = true;
            $this->brief->update(['priority_actions' => $actions]);
            $this->dispatch('notify', message: 'Action marked complete.', type: 'success');
        }
    }

    public function snoozeAction(int $index): void
    {
        $actions = $this->brief->priority_actions;
        if (isset($actions[$index])) {
            $actions[$index]['snoozed']       = true;
            $actions[$index]['snoozed_until'] = Carbon::tomorrow()->toDateString();
            $this->brief->update(['priority_actions' => $actions]);
            $this->dispatch('notify', message: 'Action snoozed to tomorrow.', type: 'info');
        }
    }

    public function createTaskFromAction(int $index): void
    {
        $actions = $this->brief->priority_actions;
        $action  = $actions[$index] ?? null;

        if (!$action || ($action['task_created'] ?? false) || isset($action['task_id'])) {
            return;
        }

        $typeMap = ['call' => 'call', 'email' => 'email', 'meeting' => 'meeting', 'follow_up' => 'follow_up'];

        Task::create([
            'agency_id'   => auth()->user()->agency_id,
            'assigned_to' => auth()->id(),
            'created_by'  => auth()->id(),
            'contact_id'  => $action['contact_id'] ?? null,
            'deal_id'     => $action['deal_id'] ?? null,
            'title'       => $action['title'],
            'description' => $action['context'] ?? '',
            'type'        => $typeMap[$action['type']] ?? 'other',
            'priority'    => $action['priority'] ?? 'medium',
            'status'      => 'pending',
            'due_at'      => $action['due_at'] ?? now()->endOfDay(),
        ]);

        $actions[$index]['task_created'] = true;
        $this->brief->update(['priority_actions' => $actions]);
        $this->dispatch('notify', message: 'Task created and added to Task Board.', type: 'success');
    }

    public function draftWithCopilot(int $index): void
    {
        $actions = $this->brief->priority_actions;
        $action  = $actions[$index] ?? null;
        if (!$action) {
            return;
        }

        $context = "Draft a follow-up for this task: {$action['title']}. Context: {$action['context']}";
        $this->dispatch('open-chat-with-context', context: $context);
    }

    public function dismissAlert(int $index): void
    {
        $alerts = $this->brief->deal_alerts ?? [];
        array_splice($alerts, $index, 1);
        $this->brief->update(['deal_alerts' => $alerts]);
    }

    // ── Goals ─────────────────────────────────────────────────────────────

    public function addGoal(): void
    {
        if (empty(trim($this->newGoalTitle))) {
            return;
        }

        $goals   = $this->brief->goals ?? [];
        $goals[] = [
            'title'     => $this->newGoalTitle,
            'target'    => max(1, $this->newGoalTarget),
            'unit'      => $this->newGoalUnit ?: 'items',
            'current'   => 0,
            'completed' => false,
        ];

        $this->brief->update(['goals' => $goals]);
        $this->newGoalTitle  = '';
        $this->newGoalTarget = 1;
        $this->newGoalUnit   = 'calls';
        $this->showGoalModal = false;
    }

    public function incrementGoal(int $index): void
    {
        $goals = $this->brief->goals ?? [];
        if (!isset($goals[$index])) {
            return;
        }

        $goals[$index]['current'] = min(
            ($goals[$index]['current'] ?? 0) + 1,
            $goals[$index]['target']
        );

        if ($goals[$index]['current'] >= $goals[$index]['target']) {
            $goals[$index]['completed'] = true;
        }

        $this->brief->update(['goals' => $goals]);
    }

    public function toggleGoal(int $index): void
    {
        $goals = $this->brief->goals ?? [];
        if (!isset($goals[$index])) {
            return;
        }

        $goals[$index]['completed'] = !$goals[$index]['completed'];
        if ($goals[$index]['completed']) {
            $goals[$index]['current'] = $goals[$index]['target'];
        }

        $this->brief->update(['goals' => $goals]);
    }

    public function removeGoal(int $index): void
    {
        $goals = $this->brief->goals ?? [];
        array_splice($goals, $index, 1);
        $this->brief->update(['goals' => $goals]);
    }

    public function render()
    {
        return view('livewire.ai.planner-page')->layout('layouts.app');
    }
}
