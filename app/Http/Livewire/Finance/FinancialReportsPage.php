<?php

namespace App\Http\Livewire\Finance;

use App\Application\Finance\Actions\CalculateCashFlowAction;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\Property;
use Carbon\Carbon;
use Livewire\Component;

class FinancialReportsPage extends Component
{
    public string $activeReport  = 'pl';
    public string $periodType    = 'month';
    public string $periodMonth;
    public string $periodYear;
    public ?int   $propertyId    = null;

    public function mount(): void
    {
        $this->periodMonth = now()->format('m');
        $this->periodYear  = now()->format('Y');
    }

    public function render(CalculateCashFlowAction $cashFlow)
    {
        $agencyId = auth()->user()->agency_id;
        $month    = (int) $this->periodMonth;
        $year     = (int) $this->periodYear;

        $plData       = [];
        $agingData    = [];
        $cashFlowData = [];
        $taxData      = [];

        if ($this->activeReport === 'pl') {
            $incomeRows = [
                'Rental Income' => (float) Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->where('type', 'rent')->whereIn('status', ['paid', 'partially_paid'])->sum('amount_paid'),
                'Late Fees'     => (float) \App\Infrastructure\Persistence\Models\InvoiceLineItem::where('category', 'late_fee')->whereHas('invoice', fn ($q) => $q->where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year))->sum('amount'),
                'Other Income'  => (float) Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereNotIn('type', ['rent'])->whereIn('status', ['paid', 'partially_paid'])->sum('amount_paid'),
            ];

            $expenseRows = Expense::where('agency_id', $agencyId)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->whereIn('status', ['approved', 'paid'])
                ->when($this->propertyId, fn ($q) => $q->where('property_id', $this->propertyId))
                ->selectRaw('category, SUM(amount) as total')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->map(fn ($v) => (float) $v)
                ->toArray();

            $totalIncome  = array_sum($incomeRows);
            $totalExpenses = array_sum($expenseRows);

            $plData = compact('incomeRows', 'expenseRows', 'totalIncome', 'totalExpenses');
        }

        if ($this->activeReport === 'aging') {
            $today = now();
            $buckets = ['Current' => 0, '1-30 days' => 0, '31-60 days' => 0, '61-90 days' => 0, '90+ days' => 0];

            Invoice::where('agency_id', $agencyId)
                ->whereNotIn('status', ['paid', 'void'])
                ->with(['lease.tenant.contact'])
                ->get()
                ->each(function (Invoice $inv) use ($today, &$buckets) {
                    $days = (int) $today->diffInDays($inv->due_date, false) * -1;
                    $balance = (float) $inv->balance;
                    if ($days <= 0)      $buckets['Current'] += $balance;
                    elseif ($days <= 30) $buckets['1-30 days'] += $balance;
                    elseif ($days <= 60) $buckets['31-60 days'] += $balance;
                    elseif ($days <= 90) $buckets['61-90 days'] += $balance;
                    else                  $buckets['90+ days'] += $balance;
                });

            $agingData = ['buckets' => $buckets, 'total' => array_sum($buckets)];
        }

        if ($this->activeReport === 'cashflow') {
            for ($m = 1; $m <= 12; $m++) {
                $cashFlowData[] = $cashFlow->execute($m, $year, $this->propertyId);
            }
        }

        if ($this->activeReport === 'tax') {
            $taxData = Expense::where('agency_id', $agencyId)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->where('is_tax_deductible', true)
                ->whereIn('status', ['approved', 'paid'])
                ->when($this->propertyId, fn ($q) => $q->where('property_id', $this->propertyId))
                ->with('property')
                ->get();
        }

        $properties = Property::where('agency_id', $agencyId)->get(['id', 'address_line_1', 'city']);

        return view('livewire.finance.financial-reports-page', compact(
            'plData', 'agingData', 'cashFlowData', 'taxData', 'properties'
        ))->layout('layouts.app');
    }
}
