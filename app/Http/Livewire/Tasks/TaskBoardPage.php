<?php

namespace App\Http\Livewire\Tasks;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class TaskBoardPage extends Component
{
    // ── Filters ────────────────────────────────────────────────────────────────
    public string $search         = '';
    public string $priorityFilter = '';
    public string $assigneeFilter = '';
    public string $typeFilter     = '';
    public bool   $showMyTasksOnly = false;

    // ── Modal state ────────────────────────────────────────────────────────────
    public bool $showForm    = false;
    public bool $showDetail  = false;
    public ?int $editingId   = null;
    public ?int $detailId    = null;

    // ── Form fields ────────────────────────────────────────────────────────────
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

    // ── Create ─────────────────────────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId  = null;
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

    // ── Edit ───────────────────────────────────────────────────────────────────
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

    // ── Delete ─────────────────────────────────────────────────────────────────
    public function deleteTask(int $id): void
    {
        Task::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Task deleted.', type: 'success');

        if ($this->detailId === $id) {
            $this->showDetail = false;
            $this->detailId   = null;
        }
    }

    // ── Status transitions ─────────────────────────────────────────────────────
    public function startTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'in_progress']);
        $this->dispatch('notify', message: 'Task started.', type: 'success');
    }

    public function completeTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'completed', 'completed_at' => now()]);
        $this->dispatch('notify', message: 'Task marked complete.', type: 'success');
    }

    public function reopenTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'pending', 'completed_at' => null]);
        $this->dispatch('notify', message: 'Task reopened.', type: 'info');
    }

    public function cancelTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'cancelled']);
        $this->dispatch('notify', message: 'Task cancelled.', type: 'warning');
    }

    // ── Detail view ────────────────────────────────────────────────────────────
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

    // ── Helpers ────────────────────────────────────────────────────────────────
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
        $priorityOrder = "CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END";

        $base = Task::with('assignedTo', 'contact', 'deal', 'createdBy')
            ->when($this->search,         fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->typeFilter,     fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->assigneeFilter, fn ($q) => $q->where('assigned_to', $this->assigneeFilter))
            ->when($this->showMyTasksOnly, fn ($q) => $q->where('assigned_to', auth()->id()))
            ->orderByRaw($priorityOrder)
            ->orderBy('due_at');

        $pending    = (clone $base)->where('status', 'pending')->get();
        $inProgress = (clone $base)->where('status', 'in_progress')->get();
        $completed  = (clone $base)->where('status', 'completed')->latest('completed_at')->limit(20)->get();
        $cancelled  = (clone $base)->where('status', 'cancelled')->latest()->limit(10)->get();
        $overdue    = Task::overdue()
            ->with('assignedTo', 'contact')
            ->when($this->showMyTasksOnly, fn ($q) => $q->where('assigned_to', auth()->id()))
            ->get();

        $detailTask = $this->detailId ? Task::with('assignedTo', 'contact', 'deal', 'listing', 'transaction', 'createdBy')->find($this->detailId) : null;

        $agents   = User::where('agency_id', auth()->user()->agency_id)->get(['id', 'first_name', 'last_name']);
        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $deals    = Deal::orderBy('title')->get(['id', 'title']);

        $stats = [
            'pending'        => Task::where('assigned_to', auth()->id())->where('status', 'pending')->count(),
            'overdue'        => Task::overdue()->where('assigned_to', auth()->id())->count(),
            'due_today'      => Task::where('assigned_to', auth()->id())->whereDate('due_at', today())->where('status', 'pending')->count(),
            'completed_week' => Task::where('assigned_to', auth()->id())->where('status', 'completed')
                                    ->whereBetween('completed_at', [now()->startOfWeek(), now()])->count(),
        ];

        return view('livewire.tasks.task-board-page', compact(
            'pending', 'inProgress', 'completed', 'cancelled', 'overdue',
            'detailTask', 'agents', 'contacts', 'deals', 'stats'
        ))->layout('layouts.app');
    }
}
