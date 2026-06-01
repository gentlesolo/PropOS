<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Infrastructure\Persistence\Models\Lease;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class DepositManagementPage extends Component
{
    use WithPagination;

    public string $search = '';

    // Refund form
    public bool $showRefundForm = false;
    public string $refund_lease_id = '';
    public string $refund_date = '';
    public array $deductions = [];
    public string $deduction_description = '';
    public string $deduction_amount = '';

    public function addDeduction(): void
    {
        $this->validate([
            'deduction_description' => 'required|string|min:3',
            'deduction_amount'      => 'required|numeric|min:0.01',
        ]);

        $this->deductions[] = [
            'description' => $this->deduction_description,
            'amount'      => (float) $this->deduction_amount,
        ];

        $this->reset(['deduction_description', 'deduction_amount']);
    }

    public function removeDeduction(int $index): void
    {
        unset($this->deductions[$index]);
        $this->deductions = array_values($this->deductions);
    }

    public function processRefund(): void
    {
        $this->validate([
            'refund_lease_id' => 'required|exists:leases,id',
            'refund_date'     => 'required|date',
        ]);

        $lease = Lease::findOrFail($this->refund_lease_id);

        $lease->update([
            'deposit_refunded_at' => Carbon::parse($this->refund_date),
            'deposit_deductions'  => count($this->deductions) > 0 ? $this->deductions : null,
        ]);

        $this->reset(['showRefundForm', 'refund_lease_id', 'refund_date', 'deductions']);
        $this->dispatch('notify', message: 'Deposit refund recorded.', type: 'success');
    }

    public function openRefundForm(int $leaseId): void
    {
        $this->refund_lease_id = (string) $leaseId;
        $this->refund_date     = now()->toDateString();
        $this->deductions      = [];
        $this->showRefundForm  = true;
    }

    public function render()
    {
        $leases = Lease::with('tenant.contact', 'listing.property')
            ->where('deposit_amount', '>', 0)
            ->when($this->search, fn ($q) => $q->whereHas('tenant.contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total_held'    => Lease::where('deposit_amount', '>', 0)->whereNull('deposit_refunded_at')->sum('deposit_amount'),
            'refunded'      => Lease::whereNotNull('deposit_refunded_at')->count(),
            'pending_count' => Lease::where('deposit_amount', '>', 0)->whereNull('deposit_refunded_at')->whereIn('status', ['terminated', 'vacated'])->count(),
        ];

        return view('livewire.property-management.deposit-management-page', compact('leases', 'stats'))
            ->layout('layouts.app');
    }
}
