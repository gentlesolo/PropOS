<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Application\PropertyManagement\Actions\GenerateTenantPortalTokenAction;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\MaintenanceRequest;
use App\Infrastructure\Persistence\Models\Tenant;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TenantManagementPage extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateForm = false;
    public ?int $selectedTenantId = null;
    public string $detailTab = 'overview';

    // Create form
    public string $contact_id = '';
    public string $listing_id = '';
    public string $status = 'prospect';
    public string $id_number = '';
    public string $employer = '';
    public string $monthly_income = '';
    public string $notes = '';

    // FICA upload
    public $ficaFile = null;

    // Maintenance request form
    public bool $showMaintenanceForm = false;
    public string $maintenance_title = '';
    public string $maintenance_description = '';
    public string $maintenance_priority = 'medium';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createTenant(): void
    {
        $this->validate([
            'contact_id'     => 'required|exists:contacts,id',
            'listing_id'     => 'nullable|exists:listings,id',
            'status'         => 'required|in:prospect,active,vacating,vacated,blacklisted',
            'monthly_income' => 'nullable|numeric|min:0',
        ]);

        Tenant::create([
            'agency_id'       => auth()->user()->agency_id,
            'contact_id'      => $this->contact_id,
            'listing_id'      => $this->listing_id ?: null,
            'assigned_agent_id' => auth()->id(),
            'status'          => $this->status,
            'id_number'       => $this->id_number ?: null,
            'employer'        => $this->employer ?: null,
            'monthly_income'  => $this->monthly_income ?: null,
            'notes'           => $this->notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'contact_id', 'listing_id', 'status', 'id_number', 'employer', 'monthly_income', 'notes']);
        $this->dispatch('notify', message: 'Tenant profile created.', type: 'success');
    }

    public function uploadFica(): void
    {
        $this->validate(['ficaFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);

        $tenant = Tenant::findOrFail($this->selectedTenantId);

        $path = $this->ficaFile->store("fica/{$tenant->id}", 'local');

        $docs   = $tenant->fica_documents ?? [];
        $docs[] = ['path' => $path, 'name' => $this->ficaFile->getClientOriginalName(), 'uploaded_at' => now()->toDateTimeString()];

        $tenant->update(['fica_documents' => $docs]);

        $this->ficaFile = null;
        $this->dispatch('notify', message: 'FICA document uploaded.', type: 'success');
    }

    public function sendPortalLink(GenerateTenantPortalTokenAction $action): void
    {
        $tenant = Tenant::with('contact', 'listing.property')->findOrFail($this->selectedTenantId);
        $action->execute($tenant);
        $this->dispatch('notify', message: 'Portal link sent to tenant.', type: 'success');
    }

    public function submitMaintenance(): void
    {
        $this->validate([
            'maintenance_title'       => 'required|string|min:3|max:255',
            'maintenance_description' => 'required|string|min:10',
            'maintenance_priority'    => 'required|in:low,medium,high,urgent',
        ]);

        $tenant = Tenant::findOrFail($this->selectedTenantId);

        MaintenanceRequest::create([
            'agency_id'   => auth()->user()->agency_id,
            'tenant_id'   => $tenant->id,
            'lease_id'    => $tenant->activeLease?->id,
            'title'       => $this->maintenance_title,
            'description' => $this->maintenance_description,
            'priority'    => $this->maintenance_priority,
            'status'      => 'open',
        ]);

        $this->reset(['showMaintenanceForm', 'maintenance_title', 'maintenance_description', 'maintenance_priority']);
        $this->dispatch('notify', message: 'Maintenance request logged.', type: 'success');
    }

    public function selectTenant(int $id): void
    {
        $this->selectedTenantId = $id;
        $this->detailTab        = 'overview';
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

        $maintenanceRequests = $this->selectedTenantId
            ? MaintenanceRequest::where('tenant_id', $this->selectedTenantId)->latest()->get()
            : collect();

        $contacts = Contact::whereDoesntHave('tenant')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $listings  = Listing::with('property:id,address')->latest()->get(['id', 'property_id']);

        $stats = [
            'active'   => Tenant::where('status', 'active')->count(),
            'prospect' => Tenant::where('status', 'prospect')->count(),
            'vacating' => Tenant::where('status', 'vacating')->count(),
            'total'    => Tenant::count(),
        ];

        return view('livewire.property-management.tenant-management-page', compact(
            'tenants', 'selectedTenant', 'contacts', 'listings', 'stats', 'maintenanceRequests'
        ))->layout('layouts.app');
    }
}
