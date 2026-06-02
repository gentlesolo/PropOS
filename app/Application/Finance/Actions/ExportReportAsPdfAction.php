<?php

namespace App\Application\Finance\Actions;

use App\Application\Finance\Actions\CalculateCashFlowAction;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\InvoiceLineItem;
use App\Infrastructure\Persistence\Models\Property;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ExportReportAsPdfAction
{
    public function __construct(private CalculateCashFlowAction $cashFlow) {}

    public function execute(string $report, int $month, int $year, ?int $propertyId, int $agencyId): Response
    {
        $data = $this->buildReportData($report, $month, $year, $propertyId, $agencyId);

        $pdf = Pdf::loadView("reports.{$report}-pdf", $data)
            ->setPaper('a4', 'portrait');

        $filename = implode('-', array_filter([$report, $year, str_pad($month, 2, '0', STR_PAD_LEFT)])) . '.pdf';

        return $pdf->download($filename);
    }

    private function buildReportData(string $report, int $month, int $year, ?int $propertyId, int $agencyId): array
    {
        $properties = Property::where('agency_id', $agencyId)->get(['id', 'address_line_1', 'city']);
        $property   = $propertyId ? Property::find($propertyId) : null;
        $base       = compact('month', 'year', 'property', 'properties');

        return match ($report) {
            'pl'       => $base + $this->buildPlData($month, $year, $propertyId, $agencyId),
            'aging'    => $base + $this->buildAgingData($agencyId),
            'cashflow' => $base + $this->buildCashFlowData($year, $propertyId),
            'tax'      => $base + $this->buildTaxData($month, $year, $propertyId, $agencyId),
            default    => $base,
        };
    }

    private function buildPlData(int $month, int $year, ?int $propertyId, int $agencyId): array
    {
        $incomeRows = [
            'Rental Income' => (float) Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->where('type', 'rent')->whereIn('status', ['paid', 'partially_paid'])->sum('amount_paid'),
            'Late Fees'     => (float) InvoiceLineItem::where('category', 'late_fee')->whereHas('invoice', fn ($q) => $q->where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year))->sum('amount'),
            'Other Income'  => (float) Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereNotIn('type', ['rent'])->whereIn('status', ['paid', 'partially_paid'])->sum('amount_paid'),
        ];

        $expenseRows = Expense::where('agency_id', $agencyId)
            ->where('period_month', $month)->where('period_year', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->selectRaw('category, SUM(amount) as total')->groupBy('category')
            ->pluck('total', 'category')->map(fn ($v) => (float) $v)->toArray();

        $totalIncome   = array_sum($incomeRows);
        $totalExpenses = array_sum($expenseRows);

        return compact('incomeRows', 'expenseRows', 'totalIncome', 'totalExpenses');
    }

    private function buildAgingData(int $agencyId): array
    {
        $today   = now();
        $buckets = ['Current' => 0, '1-30 days' => 0, '31-60 days' => 0, '61-90 days' => 0, '90+ days' => 0];

        Invoice::where('agency_id', $agencyId)->whereNotIn('status', ['paid', 'void'])
            ->with(['lease.tenant.contact'])->get()
            ->each(function (Invoice $inv) use ($today, &$buckets) {
                $days    = (int) $today->diffInDays($inv->due_date, false) * -1;
                $balance = (float) $inv->balance;
                if ($days <= 0)      $buckets['Current'] += $balance;
                elseif ($days <= 30) $buckets['1-30 days'] += $balance;
                elseif ($days <= 60) $buckets['31-60 days'] += $balance;
                elseif ($days <= 90) $buckets['61-90 days'] += $balance;
                else                 $buckets['90+ days'] += $balance;
            });

        return ['agingBuckets' => $buckets, 'agingTotal' => array_sum($buckets)];
    }

    private function buildCashFlowData(int $year, ?int $propertyId): array
    {
        $cashFlowData = [];
        for ($m = 1; $m <= 12; $m++) {
            $cashFlowData[] = $this->cashFlow->execute($m, $year, $propertyId);
        }
        return compact('cashFlowData');
    }

    private function buildTaxData(int $month, int $year, ?int $propertyId, int $agencyId): array
    {
        $taxItems = Expense::where('agency_id', $agencyId)
            ->where('period_month', $month)->where('period_year', $year)
            ->where('is_tax_deductible', true)->whereIn('status', ['approved', 'paid'])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->with('property')->get();

        return ['taxItems' => $taxItems, 'taxTotal' => $taxItems->sum('amount')];
    }
}
