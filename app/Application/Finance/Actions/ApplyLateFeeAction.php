<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\InvoiceLineItem;

class ApplyLateFeeAction
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function execute(Invoice $invoice): void
    {
        // Idempotent — do not double-charge
        $alreadyApplied = $invoice->lineItems()
            ->where('category', 'late_fee')
            ->exists();

        if ($alreadyApplied) {
            return;
        }

        $minFixed   = (float) config('finance.late_fee_min_fixed', 250.00);
        $rate       = (float) config('finance.late_fee_rate', 0.10);
        $calculated = round((float) $invoice->subtotal * $rate, 2);
        $feeAmount  = max($minFixed, $calculated);

        InvoiceLineItem::create([
            'invoice_id'  => $invoice->id,
            'description' => 'Late Payment Fee',
            'category'    => 'late_fee',
            'quantity'    => 1,
            'unit_price'  => $feeAmount,
            'amount'      => $feeAmount,
            'is_taxable'  => false,
        ]);

        $newSubtotal = (float) $invoice->subtotal + $feeAmount;
        $newTotal    = $newSubtotal + (float) $invoice->tax_amount;

        $invoice->update([
            'subtotal' => $newSubtotal,
            'total'    => $newTotal,
            'status'   => 'overdue',
        ]);

        if ($invoice->lease?->assigned_agent_id) {
            $this->notifications->notifyUser(
                $invoice->lease->assigned_agent_id,
                'late_fee_applied',
                'Late Fee Applied',
                "Late fee of R " . number_format($feeAmount, 2) . " applied to invoice {$invoice->reference}.",
                '/finance/invoices',
                'warning',
            );
        }
    }
}
