<?php

namespace App\Http\Controllers\Finance;

use App\Application\Finance\Actions\ExportReportAsPdfAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportExportController extends Controller
{
    public function pdf(Request $request, ExportReportAsPdfAction $action): Response
    {
        $request->validate([
            'report' => 'required|in:pl,aging,cashflow,tax',
            'month'  => 'nullable|integer|between:1,12',
            'year'   => 'required|integer|min:2020|max:2030',
        ]);

        $agencyId = auth()->user()->agency_id;

        return $action->execute(
            report:     $request->input('report'),
            month:      (int) ($request->input('month', now()->month)),
            year:       (int) $request->input('year'),
            propertyId: $request->filled('property_id') ? (int) $request->input('property_id') : null,
            agencyId:   $agencyId,
        );
    }

    public function csv(Request $request): StreamedResponse
    {
        $request->validate([
            'report' => 'required|in:pl,aging,cashflow,tax',
            'month'  => 'nullable|integer|between:1,12',
            'year'   => 'required|integer|min:2020|max:2030',
        ]);

        $agencyId   = auth()->user()->agency_id;
        $report     = $request->input('report');
        $month      = (int) ($request->input('month', now()->month));
        $year       = (int) $request->input('year');
        $propertyId = $request->filled('property_id') ? (int) $request->input('property_id') : null;
        $filename   = "{$report}-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';

        return response()->streamDownload(function () use ($report, $month, $year, $propertyId, $agencyId) {
            $out = fopen('php://output', 'w');

            if ($report === 'pl') {
                fputcsv($out, ['Category', 'Type', 'Amount']);
                $income = Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereIn('status', ['paid', 'partially_paid'])->selectRaw('type, SUM(amount_paid) as total')->groupBy('type')->get();
                foreach ($income as $row) {
                    fputcsv($out, [$row->type, 'Income', $row->total]);
                }
                $expenses = Expense::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereIn('status', ['approved', 'paid'])->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))->selectRaw('category, SUM(amount) as total')->groupBy('category')->get();
                foreach ($expenses as $row) {
                    fputcsv($out, [$row->category, 'Expense', $row->total]);
                }
            } elseif ($report === 'tax') {
                fputcsv($out, ['Reference', 'Category', 'Property', 'Date', 'Amount', 'Status']);
                Expense::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->where('is_tax_deductible', true)->whereIn('status', ['approved', 'paid'])->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))->with('property')->get()->each(function ($e) use ($out) {
                    fputcsv($out, [$e->reference, $e->category, $e->property?->address_line_1, $e->expense_date, $e->amount, $e->status]);
                });
            } elseif ($report === 'aging') {
                fputcsv($out, ['Invoice', 'Tenant', 'Due Date', 'Balance', 'Days Overdue']);
                Invoice::where('agency_id', $agencyId)->whereNotIn('status', ['paid', 'void'])->with(['lease.tenant.contact'])->get()->each(function ($inv) use ($out) {
                    fputcsv($out, [$inv->reference, $inv->lease?->tenant?->contact?->full_name, $inv->due_date, $inv->balance, max(0, now()->diffInDays($inv->due_date, false) * -1)]);
                });
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
