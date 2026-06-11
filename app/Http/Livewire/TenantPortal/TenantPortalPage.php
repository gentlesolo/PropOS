<?php

namespace App\Http\Livewire\TenantPortal;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\MaintenanceRequest;
use App\Infrastructure\Persistence\Models\RentPayment;
use App\Infrastructure\Persistence\Models\Tenant;
use Livewire\Component;
use Livewire\WithFileUploads;

class TenantPortalPage extends Component
{
    use WithFileUploads;

    public string $token;

    // Maintenance form — still wire:model so validation errors render correctly
    public string $maintenance_title          = '';
    public string $maintenance_description    = '';
    public string $maintenance_priority       = 'medium';

    // Proof of payment upload
    public ?int   $uploadingPaymentId = null;
    public $proofFile = null;

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

        $this->reset(['maintenance_title', 'maintenance_description', 'maintenance_priority']);
        $this->dispatch('maintenance-submitted');
        $this->dispatch('notify', message: 'Maintenance request submitted. We will be in touch shortly.', type: 'success');
    }

    public function openProofUpload(int $paymentId): void
    {
        $this->uploadingPaymentId = $paymentId;
        $this->proofFile = null;
    }

    public function cancelProofUpload(): void
    {
        $this->uploadingPaymentId = null;
        $this->proofFile = null;
    }

    public function submitProof(): void
    {
        $this->validate([
            'proofFile' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $tenant = Tenant::where('portal_token', $this->token)->firstOrFail();

        $payment = RentPayment::where('id', $this->uploadingPaymentId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $path = $this->proofFile->store("proofs/{$tenant->id}", 'public');

        $payment->update([
            'proof_of_payment' => $path,
            'status'           => $payment->status === 'overdue' ? 'partial' : $payment->status,
        ]);

        $payment->loadMissing('lease.agent');
        $agent = $payment->lease?->agent;
        if ($agent) {
            app(NotificationService::class)->notifyUser(
                $agent,
                'proof_of_payment_received',
                'Proof of Payment Received',
                ($tenant->contact?->full_name ?? 'A tenant') . ' uploaded proof of payment for the ' . $payment->due_date->format('F Y') . ' invoice.',
                route('pm.rent-collection'),
                'info',
            );
        }

        $this->uploadingPaymentId = null;
        $this->proofFile = null;
        $this->dispatch('notify', message: 'Proof of payment uploaded successfully.', type: 'success');
    }

    public function render()
    {
        $tenant = Tenant::with([
            'contact',
            'listing.property',
            'activeLease.rentPayments',
            'activeLease.contract',
            'agency',
        ])->where('portal_token', $this->token)->firstOrFail();

        $lease = $tenant->activeLease;

        $outstandingBalance = $lease?->outstandingBalance ?? 0;

        $nextPayment = $lease
            ? $lease->rentPayments()
                ->whereIn('status', ['pending', 'overdue', 'partial'])
                ->orderBy('due_date')
                ->first()
            : null;

        $openMaintenanceCount = MaintenanceRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $maintenanceRequests = MaintenanceRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        return view('livewire.tenant-portal.tenant-portal-page', compact(
            'tenant',
            'lease',
            'outstandingBalance',
            'nextPayment',
            'openMaintenanceCount',
            'maintenanceRequests',
        ))->layout('layouts.portal');
    }
}
