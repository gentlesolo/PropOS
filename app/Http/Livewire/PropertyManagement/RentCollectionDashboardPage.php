<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Application\PropertyManagement\Actions\ProcessRentPaymentAction;
use App\Application\PropertyManagement\Actions\SendRentReceiptAction;
use App\Infrastructure\Persistence\Models\RentPayment;
use Livewire\Component;
use Livewire\WithPagination;

class RentCollectionDashboardPage extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $monthFilter = '';
    public bool $proofFilter = false;

    // Quick payment form
    public bool $showPaymentForm = false;
    public string $payment_rent_id = '';
    public string $amount_paid = '';
    public string $paid_date = '';
    public string $payment_method = 'eft';

    public function mount(): void
    {
        $this->monthFilter = now()->format('Y-m');
    }

    protected $queryString = ['statusFilter', 'monthFilter', 'proofFilter'];

    public function quickPay(ProcessRentPaymentAction $action): void
    {
        $agencyId = auth()->user()->agency_id;

        $this->validate([
            'payment_rent_id' => ['required', \Illuminate\Validation\Rule::exists('rent_payments', 'id')->where('agency_id', $agencyId)],
            'amount_paid'     => 'required|numeric|min:0.01',
            'paid_date'       => 'required|date',
            'payment_method'  => 'required|string',
        ]);

        $rentPayment = RentPayment::with('lease')->where('agency_id', $agencyId)->findOrFail($this->payment_rent_id);

        $action->execute(
            $rentPayment->lease,
            (float) $this->amount_paid,
            $this->paid_date,
            $this->payment_method,
        );

        $this->reset(['showPaymentForm', 'payment_rent_id', 'amount_paid', 'paid_date', 'payment_method']);
        $this->dispatch('notify', message: 'Payment recorded.', type: 'success');
    }

    public function confirmProof(int $paymentId, SendRentReceiptAction $receipt): void
    {
        $payment = RentPayment::where('agency_id', auth()->user()->agency_id)->findOrFail($paymentId);

        $payment->update([
            'amount_paid'    => $payment->amount_due,
            'status'         => 'paid',
            'paid_date'      => today()->toDateString(),
            'payment_method' => $payment->payment_method ?? 'eft',
        ]);

        $receipt->execute($payment->refresh());

        $this->dispatch('notify', message: 'Payment confirmed as paid.', type: 'success');
    }

    public function rejectProof(int $paymentId): void
    {
        $payment = RentPayment::where('agency_id', auth()->user()->agency_id)->findOrFail($paymentId);
        $payment->update(['proof_of_payment' => null]);
        $this->dispatch('notify', message: 'Proof rejected. Tenant will need to resubmit.', type: 'warning');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;
        [$year, $month] = explode('-', $this->monthFilter ?: now()->format('Y-m'));

        $payments = RentPayment::with(['lease.tenant.contact', 'lease.listing.property'])
            ->where('agency_id', $agencyId)
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->proofFilter, fn ($q) => $q->whereNotNull('proof_of_payment'))
            ->orderBy('due_date')
            ->paginate(20);

        $collectedThisMonth = RentPayment::where('agency_id', $agencyId)
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        $totalDueThisMonth = RentPayment::where('agency_id', $agencyId)
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->sum('amount_due');

        $outstandingBalance = RentPayment::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->selectRaw('SUM(amount_due) - SUM(COALESCE(amount_paid, 0)) as balance')
            ->value('balance') ?? 0;

        $overdueCount = RentPayment::where('agency_id', $agencyId)->where('status', 'overdue')->count();

        $collectionRate = $totalDueThisMonth > 0
            ? round(($collectedThisMonth / $totalDueThisMonth) * 100, 1)
            : 0;

        $stats = [
            'collected'       => $collectedThisMonth,
            'total_due'       => $totalDueThisMonth,
            'outstanding'     => $outstandingBalance,
            'overdue_count'   => $overdueCount,
            'collection_rate' => $collectionRate,
        ];

        return view('livewire.property-management.rent-collection-dashboard-page', compact('payments', 'stats'))
            ->layout('layouts.app');
    }
}
