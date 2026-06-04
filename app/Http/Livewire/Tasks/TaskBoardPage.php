<?php

namespace App\Http\Livewire\Tasks;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class TaskBoardPage extends Component
{
    // ── Navigation & Filters ───────────────────────────────────────────────────
    public string $activeNav   = 'all';
    public string $quickFilter = '';
    public string $search      = '';
    public string $priorityFilter = '';
    public string $assigneeFilter = '';
    public string $typeFilter     = '';
    public bool   $showMyTasksOnly = false;

    // ── Modal & Detail state ───────────────────────────────────────────────────
    public bool $showDetail        = false;
    public ?int $detailId          = null;
    public bool $showAiSweepModal = false;

    // ── Inputs / Interactions ──────────────────────────────────────────────────
    public string $quickAddText      = '';
    public string $newSubtaskTitle   = '';
    public string $newCommentText    = '';

    // ── AI suggestions ─────────────────────────────────────────────────────────
    public array $suggestedTasks      = [];
    public array $selectedSuggestions = [];

    // ── Form fields (legacy/fallback edit support) ─────────────────────────────
    public bool   $showForm    = false;
    public ?int   $editingId   = null;
    public string $title       = '';
    public string $description = '';
    public string $type        = 'other';
    public string $priority    = 'medium';
    public string $status      = 'pending';
    public string $assigned_to = '';
    public string $contact_id  = '';
    public string $deal_id     = '';
    public string $due_at      = '';

    protected function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type'        => 'required|in:call,email,meeting,document,follow_up,viewing,other',
            'priority'    => 'required|in:low,medium,high,urgent',
            'status'      => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id'  => 'nullable|exists:contacts,id',
            'deal_id'     => 'nullable|exists:deals,id',
            'due_at'      => 'nullable|date',
        ];
    }

    // ── Navigation toggles ─────────────────────────────────────────────────────
    public function setNav(string $nav): void
    {
        $this->activeNav = $nav;
        $this->quickFilter = '';
    }

    public function setQuickFilter(string $filter): void
    {
        $this->quickFilter = $this->quickFilter === $filter ? '' : $filter;
    }

    // ── Checkbox Toggle ────────────────────────────────────────────────────────
    public function toggleTaskStatus(int $id): void
    {
        $task = Task::findOrFail($id);
        if ($task->status === 'completed') {
            $this->reopenTask($id);
        } else {
            $this->completeTask($id);
        }
    }

    // ── Quick Add Parser ───────────────────────────────────────────────────────
    public function submitQuickAdd(): void
    {
        if (!trim($this->quickAddText)) return;

        $text = $this->quickAddText;
        $priority = 'medium';
        $due_at = null;

        // Parse priority
        if (preg_match('/\/urgent/i', $text)) {
            $priority = 'urgent';
            $text = preg_replace('/\/urgent/i', '', $text);
        } elseif (preg_match('/\/high/i', $text)) {
            $priority = 'high';
            $text = preg_replace('/\/high/i', '', $text);
        } elseif (preg_match('/\/low/i', $text)) {
            $priority = 'low';
            $text = preg_replace('/\/low/i', '', $text);
        } elseif (preg_match('/\/medium/i', $text)) {
            $priority = 'medium';
            $text = preg_replace('/\/medium/i', '', $text);
        }

        // Parse due dates
        if (preg_match('/\/tomorrow/i', $text)) {
            $due_at = now()->addDay()->setTime(9, 0);
            $text = preg_replace('/\/tomorrow/i', '', $text);
        } elseif (preg_match('/\/today/i', $text)) {
            $due_at = now()->setTime(17, 0);
            $text = preg_replace('/\/today/i', '', $text);
        } elseif (preg_match('/\/monday/i', $text)) {
            $due_at = now()->next(\Carbon\CarbonInterface::MONDAY)->setTime(9, 0);
            $text = preg_replace('/\/monday/i', '', $text);
        }

        $title = trim(preg_replace('/\s+/', ' ', $text));

        if ($title) {
            Task::create([
                'agency_id' => auth()->user()->agency_id,
                'created_by' => auth()->id(),
                'assigned_to' => auth()->id(),
                'title' => $title,
                'priority' => $priority,
                'due_at' => $due_at,
                'status' => 'pending',
                'activity_log' => [[
                    'type' => 'status',
                    'user' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    'message' => 'created this task via inline Quick Add',
                    'time' => now()->toIso8601String()
                ]]
            ]);
            $this->dispatch('notify', message: 'Task added.', type: 'success');
        }

        $this->quickAddText = '';
    }

    // ── Snooze Action ──────────────────────────────────────────────────────────
    public function snoozeTask(int $id, string $timeframe): void
    {
        $task = Task::findOrFail($id);
        $due = match ($timeframe) {
            '1_hour' => now()->addHour(),
            'tomorrow' => now()->addDay()->setTime(9, 0),
            'next_week' => now()->next(\Carbon\CarbonInterface::MONDAY)->setTime(9, 0),
            default => now()->addDay(),
        };
        $task->update(['due_at' => $due]);
        $this->logActivityFor($id, "snoozed this task to " . $due->format('d M Y H:i'));
        $this->dispatch('notify', message: 'Task snoozed.', type: 'success');
    }

    // ── Status transitions ─────────────────────────────────────────────────────
    public function startTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'in_progress']);
        $this->logActivityFor($id, 'started this task');
        $this->dispatch('notify', message: 'Task started.', type: 'success');
    }

    public function completeTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'completed', 'completed_at' => now()]);
        $this->logActivityFor($id, 'marked this task as completed');
        $this->dispatch('notify', message: 'Task marked complete.', type: 'success');
    }

    public function reopenTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'pending', 'completed_at' => null]);
        $this->logActivityFor($id, 'reopened this task');
        $this->dispatch('notify', message: 'Task reopened.', type: 'info');
    }

    public function cancelTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'cancelled']);
        $this->logActivityFor($id, 'cancelled this task');
        $this->dispatch('notify', message: 'Task cancelled.', type: 'warning');
    }

    // ── AI suggestions Sweep ───────────────────────────────────────────────────
    public function triggerAiSweep(): void
    {
        $agencyId = auth()->user()->agency_id;
        $deals = Deal::where('agency_id', $agencyId)->with('contact')->latest()->take(5)->get();

        $this->suggestedTasks = [];
        $id = 1;
        foreach ($deals as $deal) {
            $contactName = $deal->contact ? $deal->contact->full_name : 'Client';
            $this->suggestedTasks[] = [
                'temp_id' => $id++,
                'title' => "✦ Follow up with {$contactName} on deal \"{$deal->title}\"",
                'priority' => $deal->momentum_score < 50 ? 'high' : 'medium',
                'type' => 'call',
                'deal_id' => $deal->id,
                'contact_id' => $deal->contact_id,
            ];
        }

        $this->suggestedTasks[] = [
            'temp_id' => $id++,
            'title' => '✦ Verify compliance document uploads for this week\'s closed transactions',
            'priority' => 'urgent',
            'type' => 'document',
            'deal_id' => null,
            'contact_id' => null,
        ];

        $this->selectedSuggestions = array_column($this->suggestedTasks, 'temp_id');
        $this->showAiSweepModal = true;
    }

    public function addSuggestedTasks(): void
    {
        foreach ($this->suggestedTasks as $st) {
            if (in_array($st['temp_id'], $this->selectedSuggestions)) {
                Task::create([
                    'agency_id' => auth()->user()->agency_id,
                    'created_by' => auth()->id(),
                    'assigned_to' => auth()->id(),
                    'contact_id' => $st['contact_id'],
                    'deal_id' => $st['deal_id'],
                    'title' => $st['title'],
                    'priority' => $st['priority'],
                    'type' => $st['type'],
                    'status' => 'pending',
                    'due_at' => now()->addDay()->setTime(9, 0),
                    'activity_log' => [[
                        'type' => 'status',
                        'user' => 'AI System',
                        'message' => 'created this task via Pipeline AI Sweep',
                        'time' => now()->toIso8601String()
                    ]]
                ]);
            }
        }
        $this->showAiSweepModal = false;
        $this->dispatch('notify', message: 'Suggested tasks added.', type: 'success');
    }

    // ── Metadata Chip Updates (Inline Detail Edit) ─────────────────────────────
    public function updatePriority(string $priority): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $old = $task->priority;
            $task->update(['priority' => $priority]);
            $this->logActivity("changed priority from {$old} to {$priority}");
        }
    }

    public function updateAssignee(?int $userId): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $task->update(['assigned_to' => $userId]);
            $name = $userId ? User::find($userId)->first_name : 'Unassigned';
            $this->logActivity("reassigned task to {$name}");
        }
    }

    public function updateContact(?int $contactId): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $task->update(['contact_id' => $contactId]);
            $name = $contactId ? Contact::find($contactId)->first_name : 'None';
            $this->logActivity("linked contact {$name}");
        }
    }

    public function updateListing(?int $listingId): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $task->update(['listing_id' => $listingId]);
            $addr = $listingId ? (\App\Infrastructure\Persistence\Models\Listing::find($listingId)->property->address_line_1 ?? 'Property') : 'None';
            $this->logActivity("linked listing {$addr}");
        }
    }

    public function updateDueAt(?string $date): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $task->update(['due_at' => $date ?: null]);
            $d = $date ? Carbon\Carbon::parse($date)->format('d M Y H:i') : 'None';
            $this->logActivity("updated due date to {$d}");
        }
    }

    public function updateDescription(string $desc): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $task->update(['description' => $desc]);
        }
    }

    public function updateTitle(string $title): void
    {
        if ($this->detailId) {
            $task = Task::findOrFail($this->detailId);
            $task->update(['title' => $title]);
        }
    }

    // ── Subtask Management ─────────────────────────────────────────────────────
    public function addSubtask(): void
    {
        if (!trim($this->newSubtaskTitle)) return;
        $task = Task::findOrFail($this->detailId);
        $subtasks = $task->subtasks ?? [];
        $subtasks[] = [
            'title' => $this->newSubtaskTitle,
            'completed' => false,
        ];
        $task->update(['subtasks' => $subtasks]);
        $this->logActivity("added subtask: {$this->newSubtaskTitle}");
        $this->newSubtaskTitle = '';
    }

    public function toggleSubtask(int $index): void
    {
        $task = Task::findOrFail($this->detailId);
        $subtasks = $task->subtasks ?? [];
        if (isset($subtasks[$index])) {
            $subtasks[$index]['completed'] = !$subtasks[$index]['completed'];
            $status = $subtasks[$index]['completed'] ? 'completed' : 'reopened';
            $this->logActivity("{$status} subtask: " . $subtasks[$index]['title']);
            $task->update(['subtasks' => $subtasks]);
        }
    }

    public function deleteSubtask(int $index): void
    {
        $task = Task::findOrFail($this->detailId);
        $subtasks = $task->subtasks ?? [];
        if (isset($subtasks[$index])) {
            $title = $subtasks[$index]['title'];
            unset($subtasks[$index]);
            $task->update(['subtasks' => array_values($subtasks)]);
            $this->logActivity("deleted subtask: {$title}");
        }
    }

    // ── Activity log & comments ───────────────────────────────────────────────
    public function addComment(): void
    {
        if (!trim($this->newCommentText)) return;
        $task = Task::findOrFail($this->detailId);
        $log = $task->activity_log ?? [];
        $log[] = [
            'type' => 'comment',
            'user' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'message' => $this->newCommentText,
            'time' => now()->toIso8601String(),
        ];
        $task->update(['activity_log' => $log]);
        $this->newCommentText = '';
    }

    private function logActivity(string $message): void
    {
        if ($this->detailId) {
            $this->logActivityFor($this->detailId, $message);
        }
    }

    private function logActivityFor(int $id, string $message): void
    {
        $task = Task::findOrFail($id);
        $log = $task->activity_log ?? [];
        $log[] = [
            'type' => 'status',
            'user' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'message' => $message,
            'time' => now()->toIso8601String(),
        ];
        $task->update(['activity_log' => $log]);
    }

    // ── Legacy/Fallback Actions ────────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId  = null;
        $this->showForm   = true;
        $this->showDetail = false;
    }

    public function openEdit(int $id): void
    {
        $task = Task::findOrFail($id);
        $this->editingId   = $id;
        $this->title       = $task->title;
        $this->description = $task->description ?? '';
        $this->type        = $task->type;
        $this->priority    = $task->priority;
        $this->status      = $task->status;
        $this->assigned_to = (string) ($task->assigned_to ?? '');
        $this->contact_id  = (string) ($task->contact_id ?? '');
        $this->deal_id     = (string) ($task->deal_id ?? '');
        $this->due_at      = $task->due_at ? $task->due_at->format('Y-m-d\TH:i') : '';

        $this->showForm   = true;
        $this->showDetail = false;
    }

    public function saveTask(): void
    {
        $data = $this->validate();
        $payload = [
            'agency_id'   => auth()->user()->agency_id,
            'created_by'  => auth()->id(),
            'assigned_to' => $data['assigned_to'] ?: auth()->id(),
            'contact_id'  => $data['contact_id'] ?: null,
            'deal_id'     => $data['deal_id'] ?: null,
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'type'        => $data['type'],
            'priority'    => $data['priority'],
            'status'      => $data['status'],
            'due_at'      => $data['due_at'] ?: null,
        ];

        if ($data['status'] === 'completed') {
            $payload['completed_at'] = now();
        }

        if ($this->editingId) {
            Task::findOrFail($this->editingId)->update($payload);
            $msg = 'Task updated.';
        } else {
            Task::create($payload);
            $msg = 'Task created.';
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function deleteTask(int $id): void
    {
        Task::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Task deleted.', type: 'success');

        if ($this->detailId === $id) {
            $this->showDetail = false;
            $this->detailId   = null;
        }
    }

    public function openDetail(int $id): void
    {
        $this->detailId   = $id;
        $this->showDetail = true;
        $this->showForm   = false;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailId   = null;
    }

    private function resetForm(): void
    {
        $this->reset(['title', 'description', 'type', 'priority', 'status',
                      'assigned_to', 'contact_id', 'deal_id', 'due_at', 'editingId']);
        $this->type     = 'other';
        $this->priority = 'medium';
        $this->status   = 'pending';
        $this->resetValidation();
    }

    // ── Render ─────────────────────────────────────────────────────────────────
    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        // Base query for counts and navs
        $tasksQuery = Task::with('assignedTo', 'contact', 'deal.listing.property', 'listing.property')
            ->where('agency_id', $agencyId);

        // Apply active navigation filter
        if ($this->activeNav === 'my_day') {
            $tasksQuery->where(function ($q) {
                $q->whereDate('due_at', today())
                  ->orWhere(function ($sq) {
                      $sq->where('due_at', '<', today())->whereNotIn('status', ['completed', 'cancelled']);
                  });
            })->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($this->activeNav === 'upcoming') {
            $tasksQuery->where('due_at', '>', now())->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($this->activeNav === 'all') {
            $tasksQuery->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($this->activeNav === 'pipeline') {
            $tasksQuery->whereNotNull('deal_id')->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($this->activeNav === 'ai_generated') {
            $tasksQuery->where('title', 'like', '✦%')->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($this->activeNav === 'completed') {
            $tasksQuery->where('status', 'completed');
        }

        // Apply search input
        if ($this->search) {
            $tasksQuery->where('title', 'like', "%{$this->search}%");
        }

        // Apply quick filters
        if ($this->quickFilter === 'due_today') {
            $tasksQuery->whereDate('due_at', today());
        } elseif ($this->quickFilter === 'overdue') {
            $tasksQuery->where('due_at', '<', now())->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($this->quickFilter === 'unassigned') {
            $tasksQuery->whereNull('assigned_to');
        } elseif ($this->quickFilter === 'high_priority') {
            $tasksQuery->whereIn('priority', ['high', 'urgent']);
        }

        // Apply legacy filters if set
        if ($this->priorityFilter) {
            $tasksQuery->where('priority', $this->priorityFilter);
        }
        if ($this->typeFilter) {
            $tasksQuery->where('type', $this->typeFilter);
        }
        if ($this->assigneeFilter) {
            $tasksQuery->where('assigned_to', $this->assigneeFilter);
        }
        if ($this->showMyTasksOnly) {
            $tasksQuery->where('assigned_to', auth()->id());
        }

        // Sorting: Completed at the bottom, then by priority (Urgent -> High -> Medium -> Low), then due date
        $priorityOrder = "CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END";
        $tasks = $tasksQuery
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END ASC")
            ->orderByRaw($priorityOrder)
            ->orderBy('due_at')
            ->get();

        // Calculate count badges for left sidebar nav items
        $navCounts = [
            'my_day' => Task::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where(function ($q) {
                    $q->whereDate('due_at', today())
                      ->orWhere('due_at', '<', today());
                })->count(),
            'upcoming' => Task::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where('due_at', '>', now())
                ->count(),
            'all' => Task::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
            'pipeline' => Task::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('deal_id')
                ->count(),
            'ai_generated' => Task::where('agency_id', $agencyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where('title', 'like', '✦%')
                ->count(),
            'completed' => Task::where('agency_id', $agencyId)
                ->where('status', 'completed')
                ->count(),
        ];

        // Calculate count badges for quick filters
        $filterCounts = [
            'due_today' => Task::where('agency_id', $agencyId)->whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_at', today())->count(),
            'overdue' => Task::where('agency_id', $agencyId)->overdue()->count(),
            'unassigned' => Task::where('agency_id', $agencyId)->whereNotIn('status', ['completed', 'cancelled'])->whereNull('assigned_to')->count(),
            'high_priority' => Task::where('agency_id', $agencyId)->whereNotIn('status', ['completed', 'cancelled'])->whereIn('priority', ['high', 'urgent'])->count(),
        ];

        // Detailed panel task info
        $detailTask = null;
        if ($this->showDetail && $this->detailId) {
            $detailTask = Task::with('assignedTo', 'contact', 'deal.listing.property', 'listing.property', 'createdBy')
                ->where('agency_id', $agencyId)
                ->find($this->detailId);
        }

        // Auto select first task if detail is shown but none selected
        if ($this->showDetail && !$detailTask && $tasks->count() > 0) {
            $this->detailId = $tasks->first()->id;
            $detailTask = Task::with('assignedTo', 'contact', 'deal.listing.property', 'listing.property', 'createdBy')
                ->where('agency_id', $agencyId)
                ->find($this->detailId);
        }

        $agents = User::where('agency_id', $agencyId)->get(['id', 'first_name', 'last_name']);
        $contacts = Contact::where('agency_id', $agencyId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $deals = Deal::where('agency_id', $agencyId)->with('listing.property')->orderBy('title')->get(['id', 'title', 'listing_id']);

        return view('livewire.tasks.task-board-page', compact('tasks', 'navCounts', 'filterCounts', 'detailTask', 'agents', 'contacts', 'deals'))
            ->layout('layouts.app');
    }
}
