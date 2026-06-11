<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Application\PropertyManagement\Actions\CreateLeaseAction;
use App\Application\PropertyManagement\Actions\ProcessRentPaymentAction;
use App\Application\PropertyManagement\Actions\RenewLeaseAction;
use App\Application\PropertyManagement\Actions\SendLeaseRenewalOfferAction;
use App\Application\PropertyManagement\Actions\TerminateLeaseAction;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;
use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Models\Listing;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class LeaseManagementPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateForm = false;
    public ?int $selectedLeaseId = null;
    public string $detailTab = 'overview';

    // Create form
    public string $tenant_id = '';
    public string $listing_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $rent_input = '';       // what the user types (period amount)
    public string $deposit_amount = '';
    public string $escalation_percent = '0';
    public string $payment_frequency = 'yearly';
    public string $payment_day = '1';
    public string $agency_fee = '';
    public string $legal_fee = '';
    public string $service_charge = '';
    public string $special_conditions = '';

    // Record payment form
    public bool $showPaymentForm = false;
    public string $payment_lease_id = '';
    public string $amount_paid = '';
    public string $paid_date = '';
    public string $payment_method = 'eft';
    public string $payment_notes = '';

    // Termination form
    public bool $showTerminateForm = false;
    public string $terminate_lease_id = '';
    public string $termination_reason = '';
    public string $termination_date = '';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    public function createLease(CreateLeaseAction $action): void
    {
        $this->validate([
            'tenant_id'          => 'required|exists:tenants,id',
            'listing_id'         => 'required|exists:listings,id',
            'start_date'         => 'required|date',
            'end_date'           => 'required|date|after:start_date',
            'rent_input'         => 'required|numeric|min:1',
            'deposit_amount'     => 'nullable|numeric|min:0',
            'escalation_percent' => 'integer|min:0|max:100',
            'payment_frequency'  => 'required|in:monthly,quarterly,bi_yearly,yearly',
            'payment_day'        => 'required|in:1,2,3,5,7,10,15,25,28,30',
            'agency_fee'         => 'nullable|numeric|min:0',
            'legal_fee'          => 'nullable|numeric|min:0',
            'service_charge'     => 'nullable|numeric|min:0',
        ]);

        // Convert the period-based input to the stored monthly equivalent
        $periodMonths = match($this->payment_frequency) {
            'quarterly' => 3,
            'bi_yearly' => 6,
            'yearly'    => 12,
            default     => 1,
        };
        $monthlyRent = round((float) $this->rent_input / $periodMonths, 2);

        $action->execute([
            'agency_id'          => auth()->user()->agency_id,
            'tenant_id'          => $this->tenant_id,
            'listing_id'         => $this->listing_id,
            'assigned_agent_id'  => auth()->id(),
            'status'             => 'active',
            'start_date'         => $this->start_date,
            'end_date'           => $this->end_date,
            'monthly_rent'       => $monthlyRent,
            'deposit_amount'     => $this->deposit_amount ?: null,
            'escalation_percent' => $this->escalation_percent,
            'payment_frequency'  => $this->payment_frequency,
            'payment_day'        => $this->payment_day,
            'agency_fee'         => $this->agency_fee ?: null,
            'legal_fee'          => $this->legal_fee ?: null,
            'service_charge'     => $this->service_charge ?: null,
            'special_conditions' => $this->special_conditions ?: null,
        ]);

        $this->reset(['showCreateForm', 'tenant_id', 'listing_id', 'start_date', 'end_date',
            'rent_input', 'deposit_amount', 'escalation_percent', 'payment_frequency',
            'payment_day', 'agency_fee', 'legal_fee', 'service_charge', 'special_conditions']);
        $this->dispatch('notify', message: 'Lease created with payment schedule.', type: 'success');
    }

    public function recordPayment(ProcessRentPaymentAction $action): void
    {
        $this->validate([
            'payment_lease_id' => 'required|exists:leases,id',
            'amount_paid'      => 'required|numeric|min:0.01',
            'paid_date'        => 'required|date',
            'payment_method'   => 'required|string',
        ]);

        $lease = Lease::findOrFail($this->payment_lease_id);

        $action->execute(
            $lease,
            (float) $this->amount_paid,
            $this->paid_date,
            $this->payment_method,
            $this->payment_notes ?: null,
        );

        $this->reset(['showPaymentForm', 'payment_lease_id', 'amount_paid', 'paid_date', 'payment_method', 'payment_notes']);
        $this->dispatch('notify', message: 'Payment recorded.', type: 'success');
    }

    public function renewLease(int $id, RenewLeaseAction $action): void
    {
        $lease = Lease::findOrFail($id);
        $action->execute($lease);
        $this->dispatch('notify', message: 'Lease renewed for one year.', type: 'success');
    }

    public function sendRenewalOffer(int $id, SendLeaseRenewalOfferAction $action): void
    {
        $lease = Lease::with('tenant.contact', 'listing.property', 'agent')->findOrFail($id);
        $action->execute($lease);
        $this->dispatch('notify', message: 'Renewal offer sent.', type: 'success');
    }

    public function openTerminateForm(int $id): void
    {
        $this->terminate_lease_id = (string) $id;
        $this->termination_date   = now()->toDateString();
        $this->termination_reason = '';
        $this->showTerminateForm  = true;
    }

    public function terminateLease(TerminateLeaseAction $action): void
    {
        $this->validate([
            'terminate_lease_id' => 'required|exists:leases,id',
            'termination_reason' => 'required|string|min:5',
            'termination_date'   => 'required|date',
        ]);

        $lease = Lease::findOrFail($this->terminate_lease_id);
        $action->execute($lease, $this->termination_reason, Carbon::parse($this->termination_date));

        $this->reset(['showTerminateForm', 'terminate_lease_id', 'termination_reason', 'termination_date']);
        $this->dispatch('notify', message: 'Lease terminated.', type: 'warning');
    }

    public function selectLease(int $id): void
    {
        $this->selectedLeaseId = $id;
        $this->detailTab       = 'overview';
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $leases = Lease::with('tenant.contact', 'listing.property', 'rentPayments')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q->whereHas('tenant.contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('start_date')
            ->paginate(15);

        $selectedLease = $this->selectedLeaseId
            ? Lease::with('tenant.contact', 'listing.property', 'rentPayments')->find($this->selectedLeaseId)
            : null;

        $tenants  = Tenant::with('contact:id,first_name,last_name')->where('agency_id', $agencyId)->where('status', 'active')->get();
        $listings = Listing::with('property:id,address_line_1,city')->where('agency_id', $agencyId)->latest()->get(['id', 'property_id']);

        $agencyId = auth()->user()->agency_id;
        $stats = [
            'active'          => Lease::where('agency_id', $agencyId)->where('status', 'active')->count(),
            'expiring'        => Lease::where('agency_id', $agencyId)->where('status', 'active')->where('end_date', '<=', now()->addDays(90))->count(),
            'overdue_payments'=> RentPayment::where('agency_id', $agencyId)->where('status', 'overdue')->count(),
            'total_rent_due'  => RentPayment::where('agency_id', $agencyId)->whereIn('status', ['pending', 'overdue'])->sum('amount_due'),
        ];

        return view('livewire.property-management.lease-management-page', compact('leases', 'selectedLease', 'tenants', 'listings', 'stats'))
            ->layout('layouts.app');
    }
}
