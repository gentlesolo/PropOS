<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;
use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Models\Listing;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class LeaseManagementPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateForm = false;
    public ?int $selectedLeaseId = null;

    // Create form
    public string $tenant_id = '';
    public string $listing_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $monthly_rent = '';
    public string $deposit_amount = '';
    public string $escalation_percent = '0';
    public string $payment_day = '1';
    public string $special_conditions = '';

    // Record payment form
    public bool $showPaymentForm = false;
    public string $payment_lease_id = '';
    public string $amount_paid = '';
    public string $paid_date = '';
    public string $payment_method = 'eft';
    public string $payment_notes = '';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createLease(): void
    {
        $this->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'listing_id' => 'required|exists:listings,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:1',
            'deposit_amount' => 'nullable|numeric|min:0',
            'escalation_percent' => 'integer|min:0|max:100',
            'payment_day' => 'required|in:1,2,3,5,7,10,15,25,28,30',
        ]);

        $lease = Lease::create([
            'agency_id' => auth()->user()->agency_id,
            'tenant_id' => $this->tenant_id,
            'listing_id' => $this->listing_id,
            'assigned_agent_id' => auth()->id(),
            'status' => 'active',
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'monthly_rent' => $this->monthly_rent,
            'deposit_amount' => $this->deposit_amount ?: null,
            'escalation_percent' => $this->escalation_percent,
            'payment_day' => $this->payment_day,
            'special_conditions' => $this->special_conditions ?: null,
        ]);

        Tenant::find($this->tenant_id)?->update(['status' => 'active', 'listing_id' => $this->listing_id]);

        $this->reset(['showCreateForm', 'tenant_id', 'listing_id', 'start_date', 'end_date',
            'monthly_rent', 'deposit_amount', 'escalation_percent', 'payment_day', 'special_conditions']);
        $this->dispatch('notify', message: 'Lease created.', type: 'success');
    }

    public function recordPayment(): void
    {
        $this->validate([
            'payment_lease_id' => 'required|exists:leases,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'paid_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $lease = Lease::findOrFail($this->payment_lease_id);

        $pending = $lease->rentPayments()->whereIn('status', ['pending', 'overdue', 'partial'])
            ->orderBy('due_date')->first();

        if ($pending) {
            $newPaid = ($pending->amount_paid ?? 0) + (float) $this->amount_paid;
            $status = $newPaid >= $pending->amount_due ? 'paid' : 'partial';
            $pending->update([
                'amount_paid' => $newPaid,
                'status' => $status,
                'paid_date' => $this->paid_date,
                'payment_method' => $this->payment_method,
                'notes' => $this->payment_notes ?: null,
            ]);
        } else {
            RentPayment::create([
                'agency_id' => auth()->user()->agency_id,
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'amount_due' => $lease->monthly_rent,
                'amount_paid' => $this->amount_paid,
                'status' => (float) $this->amount_paid >= (float) $lease->monthly_rent ? 'paid' : 'partial',
                'due_date' => $this->paid_date,
                'paid_date' => $this->paid_date,
                'payment_method' => $this->payment_method,
                'notes' => $this->payment_notes ?: null,
            ]);
        }

        $this->reset(['showPaymentForm', 'payment_lease_id', 'amount_paid', 'paid_date', 'payment_method', 'payment_notes']);
        $this->dispatch('notify', message: 'Payment recorded.', type: 'success');
    }

    public function renewLease(int $id): void
    {
        $lease = Lease::findOrFail($id);
        $newEnd = $lease->end_date->addYear();
        $lease->update(['status' => 'renewed', 'renewed_until' => $newEnd, 'end_date' => $newEnd]);
        $this->dispatch('notify', message: 'Lease renewed for one year.', type: 'success');
    }

    public function render()
    {
        $leases = Lease::with('tenant.contact', 'listing.property', 'rentPayments')
            ->when($this->search, fn ($q) => $q->whereHas('tenant.contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('start_date')
            ->paginate(15);

        $selectedLease = $this->selectedLeaseId
            ? Lease::with('tenant.contact', 'listing.property', 'rentPayments')->find($this->selectedLeaseId)
            : null;

        $tenants = Tenant::with('contact:id,first_name,last_name')->where('status', 'active')->get();
        $listings = Listing::with('property:id,address')->latest()->get(['id', 'property_id']);

        $stats = [
            'active' => Lease::where('status', 'active')->count(),
            'expiring' => Lease::where('status', 'active')->where('end_date', '<=', now()->addDays(60))->count(),
            'overdue_payments' => RentPayment::where('status', 'overdue')->count(),
            'total_rent_due' => RentPayment::whereIn('status', ['pending', 'overdue'])->sum('amount_due'),
        ];

        return view('livewire.property-management.lease-management-page', compact('leases', 'selectedLease', 'tenants', 'listings', 'stats'))
            ->layout('layouts.app');
    }
}
