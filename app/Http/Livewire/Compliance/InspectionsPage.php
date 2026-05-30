<?php

namespace App\Http\Livewire\Compliance;

use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Inspection;
use App\Infrastructure\Persistence\Models\Listing;
use Livewire\Component;
use Livewire\WithPagination;

class InspectionsPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public bool $showCreateForm = false;

    // Create form
    public string $listing_id = '';
    public string $deal_id = '';
    public string $type = 'pre_purchase';
    public string $inspector_name = '';
    public string $inspector_company = '';
    public string $scheduled_at = '';
    public string $cost = '';
    public string $notes = '';

    protected $queryString = ['search', 'statusFilter', 'typeFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createInspection(): void
    {
        $this->validate([
            'listing_id' => 'nullable|exists:listings,id',
            'deal_id' => 'nullable|exists:deals,id',
            'type' => 'required|in:pre_purchase,pre_rental,routine,exit,appraisal,building,pest,electrical,plumbing',
            'scheduled_at' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
        ]);

        Inspection::create([
            'agency_id' => auth()->user()->agency_id,
            'listing_id' => $this->listing_id ?: null,
            'deal_id' => $this->deal_id ?: null,
            'assigned_agent_id' => auth()->id(),
            'inspector_name' => $this->inspector_name ?: null,
            'inspector_company' => $this->inspector_company ?: null,
            'type' => $this->type,
            'status' => 'scheduled',
            'result' => 'pending',
            'scheduled_at' => $this->scheduled_at,
            'cost' => $this->cost ?: null,
            'summary' => $this->notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'listing_id', 'deal_id', 'type', 'inspector_name',
            'inspector_company', 'scheduled_at', 'cost', 'notes']);
        $this->dispatch('notify', message: 'Inspection scheduled.', type: 'success');
    }

    public function markComplete(int $id, string $result): void
    {
        Inspection::findOrFail($id)->update([
            'status' => 'completed',
            'result' => $result,
            'completed_at' => now(),
        ]);
        $this->dispatch('notify', message: 'Inspection marked complete.', type: 'success');
    }

    public function render()
    {
        $inspections = Inspection::with('listing.property', 'deal', 'agent')
            ->when($this->search, fn ($q) => $q->where('inspector_name', 'like', "%{$this->search}%")
                ->orWhereHas('listing.property', fn ($sq) => $sq->where('address', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        $listings = Listing::with('property:id,address')->latest()->get(['id', 'property_id']);
        $deals = Deal::orderBy('title')->get(['id', 'title']);

        $stats = [
            'scheduled' => Inspection::where('status', 'scheduled')->count(),
            'completed' => Inspection::where('status', 'completed')->count(),
            'passed' => Inspection::where('result', 'pass')->count(),
            'failed' => Inspection::where('result', 'fail')->count(),
        ];

        return view('livewire.compliance.inspections-page', compact('inspections', 'listings', 'deals', 'stats'))
            ->layout('layouts.app');
    }
}
