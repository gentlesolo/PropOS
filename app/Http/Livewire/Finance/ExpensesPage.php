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

    // ── Filters ───────────────────────────────────────────────────────────────
    public string $search         = '';
    public string $categoryFilter = '';
    public string $statusFilter   = '';
    public string $periodMonth;
    public string $periodYear;

    protected $queryString = ['categoryFilter', 'statusFilter', 'search'];

    // ── Detail panel ──────────────────────────────────────────────────────────
    public bool $showDetail     = false;
    public ?int $detailExpenseId = null;

    // ── Create form ───────────────────────────────────────────────────────────
    public bool   $showCreateForm     = false;
    public string $vendor_name        = '';
    public string $category           = 'maintenance';
    public string $description        = '';
    public string $amount             = '';
    public string $expense_date       = '';
    public bool   $is_tax_deductible  = true;
    public ?int   $property_id        = null;
    public string $notes              = '';
    public $receipt = null;

    // ── Edit form ─────────────────────────────────────────────────────────────
    public bool   $showEditForm          = false;
    public ?int   $editExpenseId         = null;
    public string $edit_vendor_name      = '';
    public string $edit_category         = '';
    public string $edit_description      = '';
    public string $edit_amount           = '';
    public string $edit_expense_date     = '';
    public bool   $edit_tax_deductible   = true;
    public ?int   $edit_property_id      = null;
    public string $edit_notes            = '';

    public function mount(): void
    {
        $this->periodMonth  = now()->format('m');
        $this->periodYear   = now()->format('Y');
        $this->expense_date = now()->toDateString();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function openDetail(int $id): void
    {
        $this->detailExpenseId = $id;
        $this->showDetail      = true;
        $this->showCreateForm  = false;
        $this->showEditForm    = false;
    }

    public function closeDetail(): void
    {
        $this->showDetail      = false;
        $this->detailExpenseId = null;
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->reset(['vendor_name', 'category', 'description', 'amount', 'is_tax_deductible', 'property_id', 'notes', 'receipt']);
        $this->category     = 'maintenance';
        $this->expense_date = now()->toDateString();
        $this->is_tax_deductible = true;
        $this->showCreateForm = true;
        $this->showEditForm   = false;
        $this->showDetail     = false;
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

        $expense = $action->execute([
            'agency_id'         => auth()->user()->agency_id,
            'vendor_name'       => $this->vendor_name,
            'category'          => $this->category,
            'description'       => $this->description,
            'amount'            => (float) $this->amount,
            'expense_date'      => $this->expense_date,
            'is_tax_deductible' => $this->is_tax_deductible,
            'property_id'       => $this->property_id ?: null,
            'notes'             => $this->notes ?: null,
            'period_month'      => (int) $this->periodMonth,
            'period_year'       => (int) $this->periodYear,
        ], $this->receipt);

        $this->reset(['showCreateForm', 'vendor_name', 'description', 'amount', 'notes', 'receipt']);
        $this->openDetail($expense->id);
        $this->dispatch('notify', message: 'Expense submitted for approval.', type: 'success');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function openEditForm(int $id): void
    {
        $expense = $this->scopedExpense($id);

        if (! in_array($expense->status, ['pending', 'rejected'])) {
            $this->dispatch('notify', message: 'Only pending or rejected expenses can be edited.', type: 'error');
            return;
        }

        $this->editExpenseId      = $expense->id;
        $this->edit_vendor_name   = $expense->vendor_name;
        $this->edit_category      = $expense->category;
        $this->edit_description   = $expense->description;
        $this->edit_amount        = (string) $expense->amount;
        $this->edit_expense_date  = $expense->expense_date->toDateString();
        $this->edit_tax_deductible= (bool) $expense->is_tax_deductible;
        $this->edit_property_id   = $expense->property_id;
        $this->edit_notes         = $expense->notes ?? '';
        $this->showEditForm       = true;
        $this->showCreateForm     = false;
        $this->showDetail         = false;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'edit_vendor_name'  => 'required|string|max:255',
            'edit_category'     => 'required|string',
            'edit_description'  => 'required|string',
            'edit_amount'       => 'required|numeric|min:0.01',
            'edit_expense_date' => 'required|date',
        ]);

        $expense = $this->scopedExpense($this->editExpenseId);

        if (! in_array($expense->status, ['pending', 'rejected'])) {
            $this->dispatch('notify', message: 'Only pending or rejected expenses can be edited.', type: 'error');
            return;
        }

        $expense->update([
            'vendor_name'       => $this->edit_vendor_name,
            'category'          => $this->edit_category,
            'description'       => $this->edit_description,
            'amount'            => (float) $this->edit_amount,
            'expense_date'      => $this->edit_expense_date,
            'is_tax_deductible' => $this->edit_tax_deductible,
            'property_id'       => $this->edit_property_id ?: null,
            'notes'             => $this->edit_notes ?: null,
            'period_month'      => (int) \Carbon\Carbon::parse($this->edit_expense_date)->format('m'),
            'period_year'       => (int) \Carbon\Carbon::parse($this->edit_expense_date)->format('Y'),
            'status'            => 'pending', // re-submit for approval
            'approved_by'       => null,
            'approved_at'       => null,
        ]);

        $this->reset(['showEditForm', 'editExpenseId', 'edit_vendor_name', 'edit_category', 'edit_description',
            'edit_amount', 'edit_expense_date', 'edit_notes', 'edit_property_id']);
        $this->openDetail($expense->id);
        $this->dispatch('notify', message: 'Expense updated and re-submitted for approval.', type: 'success');
    }

    public function cancelEdit(): void
    {
        $this->reset(['showEditForm', 'editExpenseId', 'edit_vendor_name', 'edit_category', 'edit_description',
            'edit_amount', 'edit_expense_date', 'edit_notes', 'edit_property_id']);
    }

    // ── Approve / Reject ──────────────────────────────────────────────────────

    public function approveExpense(int $id, ApproveExpenseAction $action): void
    {
        $action->execute($this->scopedExpense($id), auth()->user(), true);
        $this->dispatch('notify', message: 'Expense approved.', type: 'success');
    }

    public function rejectExpense(int $id, ApproveExpenseAction $action): void
    {
        $action->execute($this->scopedExpense($id), auth()->user(), false);
        $this->dispatch('notify', message: 'Expense rejected.', type: 'info');
    }

    // ── Mark Paid ─────────────────────────────────────────────────────────────

    public function markPaid(int $id): void
    {
        $expense = $this->scopedExpense($id);

        if ($expense->status !== 'approved') {
            $this->dispatch('notify', message: 'Only approved expenses can be marked as paid.', type: 'error');
            return;
        }

        $expense->update(['status' => 'paid']);
        $this->dispatch('notify', message: 'Expense marked as paid.', type: 'success');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function deleteExpense(int $id): void
    {
        $expense = $this->scopedExpense($id);

        if (! in_array($expense->status, ['pending', 'rejected'])) {
            $this->dispatch('notify', message: 'Only pending or rejected expenses can be deleted.', type: 'error');
            return;
        }

        $expense->delete();

        if ($this->detailExpenseId === $id) {
            $this->showDetail = false;
        }

        $this->dispatch('notify', message: 'Expense deleted.', type: 'info');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function scopedExpense(int $id): Expense
    {
        return Expense::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $agencyId = auth()->user()->agency_id;
        $month    = (int) $this->periodMonth;
        $year     = (int) $this->periodYear;

        $expenses = Expense::with(['property', 'approver'])
            ->where('agency_id', $agencyId)
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('vendor_name', 'like', "%{$this->search}%")
                       ->orWhere('description', 'like', "%{$this->search}%")
                       ->orWhere('reference', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter,   fn ($q) => $q->where('status', $this->statusFilter))
            ->when(! $this->search, fn ($q) => $q->where('period_month', $month)->where('period_year', $year))
            ->orderByDesc('expense_date')
            ->paginate(20);

        $totalThisMonth  = Expense::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereIn('status', ['approved', 'paid'])->sum('amount');
        $pendingApproval = Expense::where('agency_id', $agencyId)->where('status', 'pending')->count();
        $deductibleTotal = Expense::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->where('is_tax_deductible', true)->whereIn('status', ['approved', 'paid'])->sum('amount');

        $properties = Property::where('agency_id', $agencyId)->get(['id', 'address_line_1', 'city']);

        $stats = compact('totalThisMonth', 'pendingApproval', 'deductibleTotal');

        $detailExpense = null;
        if ($this->showDetail && $this->detailExpenseId) {
            $detailExpense = Expense::with(['property', 'approver'])
                ->where('agency_id', $agencyId)
                ->find($this->detailExpenseId);
        }

        return view('livewire.finance.expenses-page', compact('expenses', 'stats', 'properties', 'detailExpense'))
            ->layout('layouts.app');
    }
}
