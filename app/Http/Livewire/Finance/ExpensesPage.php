<?php

namespace App\Http\Livewire\Finance;

use App\Application\Finance\Actions\ApproveExpenseAction;
use App\Application\Finance\Actions\CreateExpenseAction;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Property;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpensesPage extends Component
{
    use WithPagination, WithFileUploads;

    public string $categoryFilter = '';
    public string $statusFilter   = '';
    public string $periodMonth;
    public string $periodYear;

    public bool    $showForm    = false;
    public ?int    $selectedId  = null;

    // Form fields
    public string  $vendor_name   = '';
    public string  $category      = 'maintenance';
    public string  $description   = '';
    public string  $amount        = '';
    public string  $expense_date  = '';
    public bool    $is_tax_deductible = true;
    public ?int    $property_id   = null;
    public string  $notes         = '';
    public $receipt = null;

    protected $queryString = ['categoryFilter', 'statusFilter'];

    public function mount(): void
    {
        $this->periodMonth  = now()->format('m');
        $this->periodYear   = now()->format('Y');
        $this->expense_date = now()->toDateString();
    }

    public function createExpense(CreateExpenseAction $action): void
    {
        $this->validate([
            'vendor_name'  => 'required|string|max:255',
            'category'     => 'required|string',
            'description'  => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
        ]);

        $action->execute([
            'agency_id'        => auth()->user()->agency_id,
            'vendor_name'      => $this->vendor_name,
            'category'         => $this->category,
            'description'      => $this->description,
            'amount'           => (float) $this->amount,
            'expense_date'     => $this->expense_date,
            'is_tax_deductible'=> $this->is_tax_deductible,
            'property_id'      => $this->property_id ?: null,
            'notes'            => $this->notes ?: null,
            'period_month'     => (int) $this->periodMonth,
            'period_year'      => (int) $this->periodYear,
        ], $this->receipt);

        $this->reset(['showForm', 'vendor_name', 'category', 'description', 'amount', 'expense_date', 'is_tax_deductible', 'property_id', 'notes', 'receipt']);
        $this->dispatch('notify', message: 'Expense submitted for approval.', type: 'success');
    }

    public function approveExpense(int $id, ApproveExpenseAction $action): void
    {
        $expense = Expense::where('agency_id', auth()->user()->agency_id)->findOrFail($id);
        $action->execute($expense, auth()->user(), true);
        $this->dispatch('notify', message: 'Expense approved.', type: 'success');
    }

    public function rejectExpense(int $id, ApproveExpenseAction $action): void
    {
        $expense = Expense::where('agency_id', auth()->user()->agency_id)->findOrFail($id);
        $action->execute($expense, auth()->user(), false);
        $this->dispatch('notify', message: 'Expense rejected.', type: 'info');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;
        $month    = (int) $this->periodMonth;
        $year     = (int) $this->periodYear;

        $expenses = Expense::with(['property', 'approver'])
            ->where('agency_id', $agencyId)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('expense_date')
            ->paginate(20);

        $totalThisMonth   = Expense::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereIn('status', ['approved', 'paid'])->sum('amount');
        $pendingApproval  = Expense::where('agency_id', $agencyId)->where('status', 'pending')->count();
        $deductibleTotal  = Expense::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->where('is_tax_deductible', true)->whereIn('status', ['approved', 'paid'])->sum('amount');

        $properties = Property::where('agency_id', $agencyId)->get(['id', 'address_line_1', 'city']);

        $stats = [
            'total_this_month'  => $totalThisMonth,
            'pending_approval'  => $pendingApproval,
            'deductible_total'  => $deductibleTotal,
        ];

        return view('livewire.finance.expenses-page', compact('expenses', 'stats', 'properties'))
            ->layout('layouts.app');
    }
}
