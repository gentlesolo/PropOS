<?php

namespace App\Application\Finance\Actions;

use App\Application\PropertyManagement\Actions\ProcessRentPaymentAction;
use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Invoice;

class MarkInvoicePaidAction
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ProcessRentPaymentAction $processRentPayment,
    ) {}

    public function execute(
        Invoice $invoice,
        float $amountPaid,
        string $method,
        ?string $reference = null,
    ): Invoice {
        $newAmountPaid = (float) $invoice->amount_paid + $amountPaid;
        $total         = (float) $invoice->total;

        $status = $newAmountPaid >= $total ? 'paid' : 'partially_paid';

        $invoice->update([
            'amount_paid' => $newAmountPaid,
            'status'      => $status,
            'paid_at'     => $status === 'paid' ? now() : $invoice->paid_at,
        ]);

        // Keep linked RentPayment in sync
        if ($invoice->lease_id && $invoice->type === 'rent') {
            $invoice->loadMissing('lease');
            try {
                $this->processRentPayment->execute(
                    lease: $invoice->lease,
                    amountPaid: $amountPaid,
                    paidDate: now()->toDateString(),
                    paymentMethod: $method,
                    notes: $reference ? "Gateway ref: {$reference}" : null,
                );
            } catch (\Exception) {
                // Non-fatal; invoice is already updated
            }
        }

        if ($status === 'paid' && $invoice->lease?->assigned_agent_id) {
            $this->notifications->notifyUser(
                $invoice->lease->assigned_agent_id,
                'invoice_paid',
                'Invoice Paid',
                "Invoice {$invoice->reference} — R " . number_format($newAmountPaid, 2) . " received.",
                '/finance/invoices',
                'success',
            );
        }

        return $invoice->fresh();
    }
}
