<?php

namespace App\Http\Livewire\Finance;

use App\Application\Finance\Actions\CalculateCashFlowAction;
use App\Application\Finance\Actions\GenerateFinancialForecastAction;
use App\Infrastructure\Persistence\Models\Budget;
use App\Infrastructure\Persistence\Models\Property;
use Livewire\Component;

class BudgetingPage extends Component
{
    public string $activeTab = 'setup';

    // ── Detail panel ──────────────────────────────────────────────────────────
    public bool $showDetail     = false;
    public ?int $detailBudgetId = null;

    // ── Create / Edit form ────────────────────────────────────────────────────
    public bool   $showForm       = false;
    public ?int   $editBudgetId   = null;
    public string $budgetName     = '';
    public string $budgetYear;
    public ?int   $budgetPropertyId        = null;
    public array  $monthlyIncomeTargets    = [];
    public array  $monthlyExpenseTargets   = [];
    public string $vacancyRate             = '5.00';
    public string $escalation              = '7.00';
    public string $budgetNotes             = '';

    // ── Variance tab ──────────────────────────────────────────────────────────
    public ?int   $varianceBudgetId   = null;
    public ?int   $variancePropertyId = null;

    // ── Forecast tab ──────────────────────────────────────────────────────────
    public string $forecastVacancy    = '5';
    public string $forecastEscalation = '0';
    public ?int   $forecastPropertyId = null;

    public function mount(): void
    {
        $this->budgetYear            = (string) now()->year;
        $this->monthlyIncomeTargets  = array_fill(0, 12, 0);
        $this->monthlyExpenseTargets = array_fill(0, 12, 0);
    }

    // ── Detail panel ──────────────────────────────────────────────────────────

    public function openDetail(int $id): void
    {
        $this->detailBudgetId = $id;
        $this->showDetail     = true;
        // Don't close the form if it's already open for a different budget
        if ($this->editBudgetId !== $id) {
            $this->showForm = false;
        }
    }

    public function closeDetail(): void
    {
        $this->showDetail     = false;
        $this->detailBudgetId = null;
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->reset(['editBudgetId', 'budgetName', 'budgetPropertyId', 'budgetNotes', 'vacancyRate', 'escalation']);
        $this->budgetYear            = (string) now()->year;
        $this->vacancyRate           = '5.00';
        $this->escalation            = '7.00';
        $this->monthlyIncomeTargets  = array_fill(0, 12, 0);
        $this->monthlyExpenseTargets = array_fill(0, 12, 0);
        $this->showForm              = true;
        $this->showDetail            = false;
        $this->activeTab             = 'setup';
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function editBudget(int $id): void
    {
        $budget = $this->scopedBudget($id);

        $this->editBudgetId          = $budget->id;
        $this->budgetName            = $budget->name;
        $this->budgetYear            = (string) $budget->year;
        $this->budgetPropertyId      = $budget->property_id;
        $this->vacancyRate           = number_format((float) $budget->vacancy_rate_assumption, 2, '.', '');
        $this->escalation            = number_format((float) $budget->escalation_assumption, 2, '.', '');
        $this->budgetNotes           = $budget->notes ?? '';

        // Normalise to exactly 12 elements
        $inc = $budget->monthly_income_targets ?? [];
        $exp = $budget->monthly_expense_targets ?? [];
        $this->monthlyIncomeTargets  = array_values(array_pad(array_slice($inc, 0, 12), 12, 0));
        $this->monthlyExpenseTargets = array_values(array_pad(array_slice($exp, 0, 12), 12, 0));

        $this->showForm   = true;
        $this->showDetail = false;
        $this->activeTab  = 'setup';
    }

    public function cancelForm(): void
    {
        $this->reset(['showForm', 'editBudgetId', 'budgetName', 'budgetPropertyId', 'budgetNotes']);
        $this->monthlyIncomeTargets  = array_fill(0, 12, 0);
        $this->monthlyExpenseTargets = array_fill(0, 12, 0);
        $this->vacancyRate           = '5.00';
        $this->escalation            = '7.00';
    }

    // ── Save (create or update) ───────────────────────────────────────────────

    public function saveBudget(): void
    {
        $this->validate([
            'budgetName' => 'required|string|max:255',
            'budgetYear' => 'required|integer|min:2020|max:2050',
            'vacancyRate'=> 'required|numeric|min:0|max:100',
            'escalation' => 'required|numeric|min:0|max:100',
        ]);

        $agencyId = auth()->user()->agency_id;

        $payload = [
            'agency_id'               => $agencyId,
            'name'                    => $this->budgetName,
            'year'                    => (int) $this->budgetYear,
            'property_id'             => $this->budgetPropertyId ?: null,
            'monthly_income_targets'  => array_map('floatval', $this->monthlyIncomeTargets),
            'monthly_expense_targets' => array_map('floatval', $this->monthlyExpenseTargets),
            'vacancy_rate_assumption' => (float) $this->vacancyRate,
            'escalation_assumption'   => (float) $this->escalation,
            'notes'                   => $this->budgetNotes ?: null,
        ];

        if ($this->editBudgetId) {
            $budget  = $this->scopedBudget($this->editBudgetId);
            // Preserve the existing approval status — editing doesn't demote it
            $budget->update($payload);
            $this->dispatch('notify', message: 'Budget updated.', type: 'success');
        } else {
            $payload['status'] = 'draft';
            $budget = Budget::create($payload);
            $this->dispatch('notify', message: 'Budget saved as draft.', type: 'success');
        }

        // Re-open detail panel for the saved budget
        $this->openDetail($budget->id);
        $this->cancelForm();
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicateBudget(int $id): void
    {
        $source = $this->scopedBudget($id);

        $copy = Budget::create([
            'agency_id'               => $source->agency_id,
            'property_id'             => $source->property_id,
            'year'                    => $source->year + 1,
            'name'                    => $source->name . ' (Copy)',
            'status'                  => 'draft',
            'monthly_income_targets'  => $source->monthly_income_targets,
            'monthly_expense_targets' => $source->monthly_expense_targets,
            'vacancy_rate_assumption' => $source->vacancy_rate_assumption,
            'escalation_assumption'   => $source->escalation_assumption,
            'notes'                   => $source->notes,
        ]);

        $this->openDetail($copy->id);
        $this->dispatch('notify', message: 'Budget duplicated as draft for ' . $copy->year . '.', type: 'success');
    }

    // ── Status transitions ────────────────────────────────────────────────────

    public function approveBudget(int $id): void
    {
        $this->scopedBudget($id)->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->dispatch('notify', message: 'Budget approved.', type: 'success');
    }

    public function activateBudget(int $id): void
    {
        $this->scopedBudget($id)->update(['status' => 'active']);
        $this->dispatch('notify', message: 'Budget set to active.', type: 'success');
    }

    public function closeBudget(int $id): void
    {
        $this->scopedBudget($id)->update(['status' => 'closed']);
        $this->dispatch('notify', message: 'Budget closed.', type: 'info');
    }

    // ── Delete (draft only) ───────────────────────────────────────────────────

    public function deleteBudget(int $id): void
    {
        $budget = $this->scopedBudget($id);

        if ($budget->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft budgets can be deleted.', type: 'error');
            return;
        }

        $budget->delete();

        if ($this->detailBudgetId === $id) {
            $this->showDetail = false;
        }

        $this->dispatch('notify', message: 'Budget deleted.', type: 'info');
    }

    // ── Fill from actuals ─────────────────────────────────────────────────────

    public function prefillFromActuals(CalculateCashFlowAction $cashFlow): void
    {
        $agencyId = auth()->user()->agency_id;
        $year     = (int) $this->budgetYear;

        for ($m = 1; $m <= 12; $m++) {
            $actual = $cashFlow->execute($m, $year, $this->budgetPropertyId ?: null);
            if ($actual['income'] > 0) {
                $this->monthlyIncomeTargets[$m - 1] = $actual['income'];
            }
            if ($actual['expenses'] > 0) {
                $this->monthlyExpenseTargets[$m - 1] = $actual['expenses'];
            }
        }

        $this->dispatch('notify', message: "Prefilled from {$year} actuals. Review and save.", type: 'info');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function scopedBudget(int $id): Budget
    {
        return Budget::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(CalculateCashFlowAction $cashFlow, GenerateFinancialForecastAction $forecast)
    {
        $agencyId = auth()->user()->agency_id;
        $months   = range(1, 12);

        $budgets = Budget::with('property')
            ->where('agency_id', $agencyId)
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get();

        // ── Variance data ─────────────────────────────────────────────────────
        $varianceData   = [];
        $varianceBudget = null;

        if ($this->activeTab === 'variance') {
            // Allow selecting a specific budget or default to current-year approved
            $varianceBudget = $this->varianceBudgetId
                ? $budgets->find($this->varianceBudgetId)
                : $budgets->whereIn('status', ['approved', 'active'])
                          ->where('year', now()->year)
                          ->first();

            $varYear = $varianceBudget?->year ?? now()->year;

            foreach ($months as $m) {
                $actual    = $cashFlow->execute($m, $varYear, $this->variancePropertyId ?: null);
                $incTarget = (float) ($varianceBudget?->monthly_income_targets[$m - 1] ?? 0);
                $expTarget = (float) ($varianceBudget?->monthly_expense_targets[$m - 1] ?? 0);

                $varianceData[] = [
                    'month'            => $m,
                    'label'            => \Carbon\Carbon::create($varYear, $m, 1)->format('M Y'),
                    'actual_income'    => $actual['income'],
                    'income_target'    => $incTarget,
                    'income_variance'  => $actual['income'] - $incTarget,
                    'actual_expenses'  => $actual['expenses'],
                    'expense_target'   => $expTarget,
                    'expense_variance' => $expTarget - $actual['expenses'],
                ];
            }
        }

        // ── Forecast data ─────────────────────────────────────────────────────
        $forecastData = [];
        if ($this->activeTab === 'forecast') {
            $forecastData = $forecast->execute(
                months: 12,
                propertyId: $this->forecastPropertyId ?: null,
                vacancyRateOverride: (float) $this->forecastVacancy,
                escalationOverride:  (float) $this->forecastEscalation,
            );
        }

        $properties = Property::where('agency_id', $agencyId)->get(['id', 'address_line_1', 'city']);

        // Detail panel budget
        $detailBudget = null;
        if ($this->showDetail && $this->detailBudgetId) {
            $detailBudget = Budget::with('property', 'approver')
                ->where('agency_id', $agencyId)
                ->find($this->detailBudgetId);
        }

        // Computed totals for form preview
        $formIncomeTotal  = array_sum(array_map('floatval', $this->monthlyIncomeTargets));
        $formExpenseTotal = array_sum(array_map('floatval', $this->monthlyExpenseTargets));

        return view('livewire.finance.budgeting-page', compact(
            'budgets', 'varianceData', 'varianceBudget', 'forecastData',
            'properties', 'months', 'detailBudget', 'formIncomeTotal', 'formExpenseTotal'
        ))->layout('layouts.app');
    }
}
