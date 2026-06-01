<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Application\PropertyManagement\Actions\GenerateTenantPortalTokenAction;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\MaintenanceRequest;
use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TenantManagementPage extends Component
{
    use WithPagination, WithFileUploads;

    // ── Filters ───────────────────────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';

    protected $queryString = ['search', 'statusFilter'];

    // ── Detail panel ──────────────────────────────────────────────────────────
    public ?int   $selectedTenantId = null;
    public string $detailTab        = 'overview';

    // ── Create form ───────────────────────────────────────────────────────────
    public bool   $showCreateForm   = false;
    public string $contact_id       = '';
    public string $listing_id       = '';
    public string $status           = 'prospect';
    public string $employer         = '';
    public string $monthly_income   = '';
    public string $create_notes     = '';

    // ── Edit form ─────────────────────────────────────────────────────────────
    public bool   $showEditForm          = false;
    public ?int   $editTenantId          = null;
    public string $edit_status           = '';
    public string $edit_employer         = '';
    public string $edit_monthly_income   = '';
    public ?int   $edit_assigned_agent   = null;
    public string $edit_notes            = '';

    // ── FICA upload ───────────────────────────────────────────────────────────
    public $ficaFile = null;

    // ── Maintenance request form ──────────────────────────────────────────────
    public bool   $showMaintenanceForm      = false;
    public string $maintenance_title        = '';
    public string $maintenance_description  = '';
    public string $maintenance_priority     = 'medium';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    // ── Select / detail ───────────────────────────────────────────────────────

    public function selectTenant(int $id): void
    {
        $this->selectedTenantId = $id;
        $this->detailTab        = 'overview';
        $this->showCreateForm   = false;
        $this->showEditForm     = false;
    }

    public function closeTenant(): void
    {
        $this->selectedTenantId = null;
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->reset(['contact_id', 'listing_id', 'employer', 'monthly_income', 'create_notes']);
        $this->status         = 'prospect';
        $this->showCreateForm = true;
        $this->showEditForm   = false;
        $this->selectedTenantId = null;
    }

    public function createTenant(): void
    {
        $this->validate([
            'contact_id'     => 'required|exists:contacts,id',
            'listing_id'     => 'nullable|exists:listings,id',
            'status'         => 'required|in:prospect,active,vacating,vacated,blacklisted',
            'monthly_income' => 'nullable|numeric|min:0',
        ]);

        $tenant = Tenant::create([
            'agency_id'         => auth()->user()->agency_id,
            'contact_id'        => $this->contact_id,
            'listing_id'        => $this->listing_id ?: null,
            'assigned_agent_id' => auth()->id(),
            'status'            => $this->status,
            'employer'          => $this->employer ?: null,
            'monthly_income'    => $this->monthly_income ?: null,
            'notes'             => $this->create_notes ?: null,
        ]);

        $this->reset(['showCreateForm', 'contact_id', 'listing_id', 'status', 'employer', 'monthly_income', 'create_notes']);
        $this->selectTenant($tenant->id);
        $this->dispatch('notify', message: 'Tenant profile created.', type: 'success');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function openEditForm(?int $id = null): void
    {
        $tenantId = $id ?? $this->selectedTenantId;
        $tenant   = $this->scopedTenant($tenantId);

        $this->editTenantId         = $tenant->id;
        $this->edit_status          = $tenant->status;
        $this->edit_employer        = $tenant->employer ?? '';
        $this->edit_monthly_income  = (string) ($tenant->monthly_income ?? '');
        $this->edit_assigned_agent  = $tenant->assigned_agent_id;
        $this->edit_notes           = $tenant->notes ?? '';
        $this->showEditForm         = true;
        $this->showCreateForm       = false;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'edit_status'         => 'required|in:prospect,active,vacating,vacated,blacklisted',
            'edit_monthly_income' => 'nullable|numeric|min:0',
        ]);

        $tenant = $this->scopedTenant($this->editTenantId);

        $tenant->update([
            'status'            => $this->edit_status,
            'employer'          => $this->edit_employer ?: null,
            'monthly_income'    => $this->edit_monthly_income ?: null,
            'assigned_agent_id' => $this->edit_assigned_agent ?? $tenant->assigned_agent_id,
            'notes'             => $this->edit_notes ?: null,
        ]);

        $this->reset(['showEditForm', 'editTenantId', 'edit_status', 'edit_employer',
            'edit_monthly_income', 'edit_assigned_agent', 'edit_notes']);
        $this->dispatch('notify', message: 'Tenant profile updated.', type: 'success');
    }

    public function cancelEdit(): void
    {
        $this->reset(['showEditForm', 'editTenantId', 'edit_status', 'edit_employer',
            'edit_monthly_income', 'edit_assigned_agent', 'edit_notes']);
    }

    // ── Blacklist ─────────────────────────────────────────────────────────────

    public function blacklistTenant(int $id): void
    {
        $tenant = $this->scopedTenant($id);
        $tenant->update(['status' => 'blacklisted']);
        $this->dispatch('notify', message: 'Tenant has been blacklisted.', type: 'warning');
    }

    // ── Delete (soft) ─────────────────────────────────────────────────────────

    public function deleteTenant(int $id): void
    {
        $tenant = $this->scopedTenant($id);

        if ($tenant->activeLease) {
            $this->dispatch('notify', message: 'Cannot delete a tenant with an active lease. Terminate the lease first.', type: 'error');
            return;
        }

        if ($this->selectedTenantId === $id) {
            $this->selectedTenantId = null;
        }

        $tenant->delete();
        $this->dispatch('notify', message: 'Tenant record deleted.', type: 'info');
    }

    // ── FICA upload ───────────────────────────────────────────────────────────

    public function uploadFica(): void
    {
        $this->validate(['ficaFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);

        $tenant = $this->scopedTenant($this->selectedTenantId);
        $path   = $this->ficaFile->store("fica/{$tenant->id}", 'local');

        $docs   = $tenant->fica_documents ?? [];
        $docs[] = [
            'path'        => $path,
            'name'        => $this->ficaFile->getClientOriginalName(),
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $tenant->update(['fica_documents' => $docs]);
        $this->ficaFile = null;
        $this->dispatch('notify', message: 'FICA document uploaded.', type: 'success');
    }

    // ── Portal link ───────────────────────────────────────────────────────────

    public function sendPortalLink(GenerateTenantPortalTokenAction $action): void
    {
        $tenant = $this->scopedTenant($this->selectedTenantId);
        $tenant->load('contact', 'listing.property');
        $action->execute($tenant);
        $this->dispatch('notify', message: 'Portal link sent to tenant.', type: 'success');
    }

    // ── Maintenance request ───────────────────────────────────────────────────

    public function submitMaintenance(): void
    {
        $this->validate([
            'maintenance_title'       => 'required|string|min:3|max:255',
            'maintenance_description' => 'required|string|min:10',
            'maintenance_priority'    => 'required|in:low,medium,high,urgent',
        ]);

        $tenant = $this->scopedTenant($this->selectedTenantId);

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

    public function updateMaintenanceStatus(int $requestId, string $status): void
    {
        MaintenanceRequest::where('id', $requestId)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['status' => $status, 'resolved_at' => in_array($status, ['resolved', 'closed']) ? now() : null]);

        $this->dispatch('notify', message: 'Maintenance request updated.', type: 'success');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function scopedTenant(int $id): Tenant
    {
        return Tenant::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $tenants = Tenant::with('contact', 'listing.property', 'agent', 'activeLease')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q->whereHas('contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")
                   ->orWhere('phone', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $selectedTenant = $this->selectedTenantId
            ? Tenant::with([
                'contact',
                'listing.property',
                'agent',
                'leases.rentPayments',
                'activeLease.rentPayments',
            ])->where('agency_id', $agencyId)->find($this->selectedTenantId)
            : null;

        $maintenanceRequests = $this->selectedTenantId
            ? MaintenanceRequest::where('tenant_id', $this->selectedTenantId)
                ->where('agency_id', $agencyId)
                ->latest()
                ->get()
            : collect();

        $contacts = Contact::where('agency_id', $agencyId)
            ->whereDoesntHave('tenant', fn ($q) => $q->where('agency_id', $agencyId))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $listings = Listing::with('property:id,address_line_1,city')
            ->where('agency_id', $agencyId)
            ->where('mandate_type', 'rental')
            ->where('status', 'active')
            ->get(['id', 'property_id']);

        $agents = User::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $stats = [
            'active'   => Tenant::where('agency_id', $agencyId)->where('status', 'active')->count(),
            'prospect' => Tenant::where('agency_id', $agencyId)->where('status', 'prospect')->count(),
            'vacating' => Tenant::where('agency_id', $agencyId)->where('status', 'vacating')->count(),
            'total'    => Tenant::where('agency_id', $agencyId)->count(),
        ];

        return view('livewire.property-management.tenant-management-page', compact(
            'tenants', 'selectedTenant', 'contacts', 'listings', 'agents', 'stats', 'maintenanceRequests'
        ))->layout('layouts.app');
    }
}
