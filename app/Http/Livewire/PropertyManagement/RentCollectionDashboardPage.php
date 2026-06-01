<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Application\PropertyManagement\Actions\ProcessRentPaymentAction;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class RentCollectionDashboardPage extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $monthFilter = '';

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

    protected $queryString = ['statusFilter', 'monthFilter'];

    public function quickPay(ProcessRentPaymentAction $action): void
    {
        $this->validate([
            'payment_rent_id' => 'required|exists:rent_payments,id',
            'amount_paid'     => 'required|numeric|min:0.01',
            'paid_date'       => 'required|date',
            'payment_method'  => 'required|string',
        ]);

        $rentPayment = RentPayment::with('lease')->findOrFail($this->payment_rent_id);

        $action->execute(
            $rentPayment->lease,
            (float) $this->amount_paid,
            $this->paid_date,
            $this->payment_method,
        );

        $this->reset(['showPaymentForm', 'payment_rent_id', 'amount_paid', 'paid_date', 'payment_method']);
        $this->dispatch('notify', message: 'Payment recorded.', type: 'success');
    }

    public function render()
    {
        [$year, $month] = explode('-', $this->monthFilter ?: now()->format('Y-m'));

        $payments = RentPayment::with(['lease.tenant.contact', 'lease.listing.property'])
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('due_date')
            ->paginate(20);

        $collectedThisMonth = RentPayment::whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        $totalDueThisMonth = RentPayment::whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->sum('amount_due');

        $outstandingBalance = RentPayment::whereIn('status', ['pending', 'overdue', 'partial'])
            ->selectRaw('SUM(amount_due) - SUM(COALESCE(amount_paid, 0)) as balance')
            ->value('balance') ?? 0;

        $overdueCount = RentPayment::where('status', 'overdue')->count();

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
