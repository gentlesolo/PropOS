<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Tenant;
use Livewire\Component;
use Livewire\WithPagination;

class TenantManagementPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateForm = false;
    public ?int $selectedTenantId = null;

    // Create form
    public string $contact_id = '';
    public string $listing_id = '';
    public string $status = 'prospect';
    public string $id_number = '';
    public string $employer = '';
    public string $monthly_income = '';
    public string $notes = '';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createTenant(): void
    {
        $this->validate([
            'contact_id' => 'required|exists:contacts,id',
            'listing_id' => 'nullable|exists:listings,id',
            'status' => 'required|in:prospect,active,vacating,vacated,blacklisted',
            'monthly_income' => 'nullable|numeric|min:0',
        ]);

        Tenant::create([
            'agency_id' => auth()->user()->agency_id,
            'contact_id' => $this->contact_id,
            'listing_id' => $this->listing_id ?: null,
            'assigned_agent_id' => auth()->id(),
            'status' => $this->status,
            'id_number' => $this->id_number ?: null,
            'employer' => $this->employer ?: null,
            'monthly_income' => $this->monthly_income ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'contact_id', 'listing_id', 'status', 'id_number', 'employer', 'monthly_income', 'notes']);
        $this->dispatch('notify', message: 'Tenant profile created.', type: 'success');
    }

    public function selectTenant(int $id): void
    {
        $this->selectedTenantId = $id;
    }

    public function render()
    {
        $tenants = Tenant::with('contact', 'listing.property', 'agent', 'activeLease')
            ->when($this->search, fn ($q) => $q->whereHas('contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $selectedTenant = $this->selectedTenantId
            ? Tenant::with('contact', 'listing.property', 'leases.rentPayments', 'activeLease')->find($this->selectedTenantId)
            : null;

        $contacts = Contact::whereDoesntHave('tenant')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $listings = Listing::with('property:id,address')->latest()->get(['id', 'property_id']);

        $stats = [
            'active' => Tenant::where('status', 'active')->count(),
            'prospect' => Tenant::where('status', 'prospect')->count(),
            'vacating' => Tenant::where('status', 'vacating')->count(),
            'total' => Tenant::count(),
        ];

        return view('livewire.property-management.tenant-management-page', compact('tenants', 'selectedTenant', 'contacts', 'listings', 'stats'))
            ->layout('layouts.app');
    }
}
