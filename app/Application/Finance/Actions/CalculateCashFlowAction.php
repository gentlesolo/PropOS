<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\InvoiceLineItem;

class CalculateCashFlowAction
{
    public function execute(int $month, int $year, ?int $propertyId = null): array
    {
        $invoiceQuery = Invoice::where('period_month', $month)
            ->where('period_year', $year);

        if ($propertyId) {
            $invoiceQuery->whereHas('lease.listing', fn ($q) => $q->where('property_id', $propertyId));
        }

        $income = (float) $invoiceQuery->clone()
            ->whereIn('status', ['paid', 'partially_paid'])
            ->sum('amount_paid');

        $outstandingAr = (float) $invoiceQuery->clone()
            ->whereNotIn('status', ['paid', 'void'])
            ->selectRaw('SUM(total) - SUM(amount_paid) as balance')
            ->value('balance') ?? 0;

        $lateFeesCollected = (float) InvoiceLineItem::where('category', 'late_fee')
            ->whereHas('invoice', function ($q) use ($month, $year, $propertyId) {
                $q->where('period_month', $month)
                  ->where('period_year', $year)
                  ->whereIn('status', ['paid', 'partially_paid']);
                if ($propertyId) {
                    $q->whereHas('lease.listing', fn ($q2) => $q2->where('property_id', $propertyId));
                }
            })
            ->sum('amount');

        $expenseQuery = Expense::where('period_month', $month)
            ->where('period_year', $year)
            ->whereIn('status', ['approved', 'paid']);

        if ($propertyId) {
            $expenseQuery->where('property_id', $propertyId);
        }

        $expenses = (float) $expenseQuery->sum('amount');

        return [
            'month'          => $month,
            'year'           => $year,
            'income'         => $income,
            'expenses'       => $expenses,
            'net'            => $income - $expenses,
            'outstanding_ar' => $outstandingAr,
            'late_fees'      => $lateFeesCollected,
        ];
    }
}
