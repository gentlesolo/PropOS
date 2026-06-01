<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\InvoiceLineItem;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\TaxConfig;
use Carbon\Carbon;

class GenerateRentInvoiceAction
{
    public function execute(Lease $lease, int $month, int $year): ?Invoice
    {
        // Idempotent — skip if invoice already exists for this lease + period
        $existing = Invoice::where('lease_id', $lease->id)
            ->where('type', 'rent')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->first();

        if ($existing) {
            return $existing;
        }

        $dueDate = Carbon::create($year, $month, $lease->payment_day ?? 1)->startOfDay();

        // Residential rent is VAT-exempt in most SA contexts; tax rate = 0 unless configured
        $taxRate = TaxConfig::getApplicableRate($lease->agency_id, 'residential');

        $rentAmount = (float) $lease->monthly_rent;
        $subtotal   = $rentAmount;
        $taxAmount  = round($subtotal * ($taxRate / 100), 2);
        $total      = $subtotal + $taxAmount;

        $invoice = Invoice::create([
            'agency_id'    => $lease->agency_id,
            'lease_id'     => $lease->id,
            'tenant_id'    => $lease->tenant_id,
            'type'         => 'rent',
            'status'       => 'draft',
            'subtotal'     => $subtotal,
            'tax_amount'   => $taxAmount,
            'total'        => $total,
            'amount_paid'  => 0,
            'due_date'     => $dueDate,
            'period_month' => $month,
            'period_year'  => $year,
        ]);

        $monthName = Carbon::create($year, $month, 1)->format('F Y');

        InvoiceLineItem::create([
            'invoice_id'  => $invoice->id,
            'description' => "Monthly Rent — {$monthName}",
            'category'    => 'rent',
            'quantity'    => 1,
            'unit_price'  => $rentAmount,
            'amount'      => $rentAmount,
            'is_taxable'  => false,
        ]);

        return $invoice;
    }
}
