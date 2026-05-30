<?php

namespace App\Http\Livewire\Tasks;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class TaskBoardPage extends Component
{
    public string $search = '';
    public string $priorityFilter = '';
    public string $assigneeFilter = '';
    public bool $showMyTasksOnly = false;
    public bool $showCreateForm = false;

    // Create form
    public string $title = '';
    public string $description = '';
    public string $type = 'other';
    public string $priority = 'medium';
    public string $assigned_to = '';
    public string $contact_id = '';
    public string $deal_id = '';
    public string $due_at = '';

    public function createTask(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:call,email,meeting,document,follow_up,viewing,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'deal_id' => 'nullable|exists:deals,id',
            'due_at' => 'nullable|date',
        ]);

        Task::create([
            'agency_id' => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'assigned_to' => $this->assigned_to ?: auth()->id(),
            'contact_id' => $this->contact_id ?: null,
            'deal_id' => $this->deal_id ?: null,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => 'pending',
            'due_at' => $this->due_at ?: null,
        ]);

        $this->reset(['showCreateForm', 'title', 'description', 'type', 'priority', 'assigned_to', 'contact_id', 'deal_id', 'due_at']);
        $this->dispatch('notify', message: 'Task created.', type: 'success');
    }

    public function completeTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'completed', 'completed_at' => now()]);
        $this->dispatch('notify', message: 'Task marked complete.', type: 'success');
    }

    public function cancelTask(int $id): void
    {
        Task::findOrFail($id)->update(['status' => 'cancelled']);
    }

    public function render()
    {
        $base = Task::with('assignedTo', 'contact', 'deal')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->assigneeFilter, fn ($q) => $q->where('assigned_to', $this->assigneeFilter))
            ->when($this->showMyTasksOnly, fn ($q) => $q->where('assigned_to', auth()->id()))
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('due_at');

        $pending = (clone $base)->where('status', 'pending')->get();
        $inProgress = (clone $base)->where('status', 'in_progress')->get();
        $completed = (clone $base)->where('status', 'completed')->latest('completed_at')->limit(20)->get();
        $overdue = Task::overdue()->with('assignedTo', 'contact')->get();

        $agents = User::where('agency_id', auth()->user()->agency_id)->get(['id', 'first_name', 'last_name']);
        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $deals = Deal::orderBy('title')->get(['id', 'title']);

        $stats = [
            'pending' => Task::where('assigned_to', auth()->id())->where('status', 'pending')->count(),
            'overdue' => Task::overdue()->where('assigned_to', auth()->id())->count(),
            'due_today' => Task::where('assigned_to', auth()->id())->whereDate('due_at', today())->where('status', 'pending')->count(),
            'completed_week' => Task::where('assigned_to', auth()->id())->where('status', 'completed')->whereBetween('completed_at', [now()->startOfWeek(), now()])->count(),
        ];

        return view('livewire.tasks.task-board-page', compact('pending', 'inProgress', 'completed', 'overdue', 'agents', 'contacts', 'deals', 'stats'))
            ->layout('layouts.app');
    }
}
