<?php

namespace App\Http\Livewire\Compliance;

use App\Infrastructure\Persistence\Models\ComplianceReminder;
use Livewire\Component;
use Livewire\WithPagination;

class ComplianceCalendarPage extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $typeFilter   = '';
    public string $statusFilter = '';
    public bool   $showCreateForm = false;

    // Create form
    public string $title         = '';
    public string $reminder_type = 'inspection';
    public string $due_date      = '';
    public string $notes         = '';

    protected $queryString = ['search', 'typeFilter', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createReminder(): void
    {
        $this->validate([
            'title'         => 'required|string|max:255',
            'reminder_type' => 'required|in:inspection,certification,fica,audit,lease_renewal,maintenance,other',
            'due_date'      => 'required|date',
        ]);

        ComplianceReminder::create([
            'agency_id'     => auth()->user()->agency_id,
            'created_by'    => auth()->id(),
            'title'         => $this->title,
            'reminder_type' => $this->reminder_type,
            'due_date'      => $this->due_date,
            'status'        => 'pending',
            'notes'         => $this->notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'title', 'reminder_type', 'due_date', 'notes']);
        $this->dispatch('notify', message: 'Compliance reminder created.', type: 'success');
    }

    public function acknowledge(int $id): void
    {
        ComplianceReminder::where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id)
            ->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);
    }

    public function markComplete(int $id): void
    {
        ComplianceReminder::where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id)
            ->update(['status' => 'completed', 'completed_at' => now()]);
    }

    public function delete(int $id): void
    {
        ComplianceReminder::where('agency_id', auth()->user()->agency_id)->findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Reminder deleted.', type: 'success');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        // Auto-mark overdue items
        ComplianceReminder::where('agency_id', $agencyId)
            ->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $reminders = ComplianceReminder::with('createdBy')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->typeFilter, fn ($q) => $q->where('reminder_type', $this->typeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 WHEN 'pending' THEN 1 WHEN 'acknowledged' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->paginate(20);

        $stats = [
            'overdue'      => ComplianceReminder::where('agency_id', $agencyId)->where('status', 'overdue')->count(),
            'due_this_week'=> ComplianceReminder::where('agency_id', $agencyId)->dueSoon(7)->count(),
            'due_this_month'=> ComplianceReminder::where('agency_id', $agencyId)->dueSoon(30)->count(),
            'completed'    => ComplianceReminder::where('agency_id', $agencyId)->where('status', 'completed')->count(),
        ];

        return view('livewire.compliance.compliance-calendar-page', compact('reminders', 'stats'))
            ->layout('layouts.app');
    }
}
