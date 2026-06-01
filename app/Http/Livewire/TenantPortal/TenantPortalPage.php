<?php

namespace App\Http\Livewire\TenantPortal;

use App\Infrastructure\Persistence\Models\MaintenanceRequest;
use App\Infrastructure\Persistence\Models\Tenant;
use Livewire\Component;

class TenantPortalPage extends Component
{
    public string $token;
    public string $activeTab = 'lease';

    // Maintenance form
    public bool $showMaintenanceForm = false;
    public string $maintenance_title = '';
    public string $maintenance_description = '';
    public string $maintenance_priority = 'medium';

    public function mount(string $token): void
    {
        $this->token = $token;

        abort_unless(
            Tenant::where('portal_token', $token)->exists(),
            404,
        );
    }

    public function submitMaintenance(): void
    {
        $this->validate([
            'maintenance_title'       => 'required|string|min:3|max:255',
            'maintenance_description' => 'required|string|min:10',
            'maintenance_priority'    => 'required|in:low,medium,high,urgent',
        ]);

        $tenant = Tenant::where('portal_token', $this->token)->firstOrFail();

        MaintenanceRequest::create([
            'agency_id'   => $tenant->agency_id,
            'tenant_id'   => $tenant->id,
            'lease_id'    => $tenant->activeLease?->id,
            'title'       => $this->maintenance_title,
            'description' => $this->maintenance_description,
            'priority'    => $this->maintenance_priority,
            'status'      => 'open',
        ]);

        $this->reset(['showMaintenanceForm', 'maintenance_title', 'maintenance_description', 'maintenance_priority']);
        $this->dispatch('notify', message: 'Maintenance request submitted.', type: 'success');
    }

    public function render()
    {
        $tenant = Tenant::with([
            'contact',
            'listing.property',
            'activeLease.rentPayments',
            'activeLease.contract',
        ])->where('portal_token', $this->token)->firstOrFail();

        $maintenanceRequests = MaintenanceRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        return view('livewire.tenant-portal.tenant-portal-page', compact('tenant', 'maintenanceRequests'))
            ->layout('layouts.portal');
    }
}
