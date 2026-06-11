<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\RentPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRentReceiptAction
{
    public function execute(RentPayment $payment): void
    {
        $payment->loadMissing(['lease.tenant.contact', 'lease.listing.property']);

        $this->syncInvoice($payment);
        $this->email($payment);
    }

    private function syncInvoice(RentPayment $payment): void
    {
        if (! $payment->lease_id || ! $payment->due_date) {
            return;
        }

        $invoice = Invoice::where('lease_id', $payment->lease_id)
            ->where('period_month', $payment->due_date->month)
            ->where('period_year', $payment->due_date->year)
            ->first();

        if (! $invoice) {
            return;
        }

        $amountPaid = (float) ($payment->amount_paid ?? 0);
        $total      = (float) $invoice->total;

        $status = match (true) {
            $amountPaid >= $total && $total > 0 => 'paid',
            $amountPaid > 0                     => 'partially_paid',
            default                             => $invoice->status,
        };

        $invoice->update([
            'amount_paid' => $amountPaid,
            'status'      => $status,
            'paid_at'     => $status === 'paid' && ! $invoice->paid_at ? now() : $invoice->paid_at,
        ]);
    }

    private function email(RentPayment $payment): void
    {
        $lease   = $payment->lease;
        $contact = $lease?->tenant?->contact ?? $lease?->contact;

        if (! $contact?->email) {
            return;
        }

        $property = $lease?->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $isPartial = $payment->status === 'partial';

        $amountDue  = (float) $payment->amount_due;
        $amountPaid = (float) $payment->amount_paid;
        $balance    = round($amountDue - $amountPaid, 2);

        $paidDate = $payment->paid_date
            ? \Carbon\Carbon::parse($payment->paid_date)->format('d M Y')
            : now()->format('d M Y');

        $subject = $isPartial
            ? "Partial Payment Received — {$payment->reference}"
            : "Payment Receipt — {$payment->reference}";

        $body = "Dear {$contact->first_name},\n\n"
            . ($isPartial
                ? "We have received a partial payment for {$address}.\n\n"
                : "Thank you for your payment for {$address}.\n\n")
            . "RECEIPT\n"
            . str_repeat('-', 40) . "\n"
            . "Reference:    {$payment->reference}\n"
            . "Period:       {$payment->due_date->format('F Y')}\n"
            . "Amount Due:   R " . number_format($amountDue, 2) . "\n"
            . "Amount Paid:  R " . number_format($amountPaid, 2) . "\n"
            . "Date:         {$paidDate}\n"
            . "Method:       " . ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'EFT')) . "\n"
            . ($isPartial ? "Balance Due:  R " . number_format($balance, 2) . "\n" : '')
            . str_repeat('-', 40) . "\n\n"
            . ($isPartial
                ? "Please arrange payment of the outstanding balance to avoid penalties.\n\n"
                : "Your account is up to date for this period.\n\n")
            . "Kind regards,\nProperty Management";

        try {
            Mail::raw(
                $body,
                fn ($msg) => $msg
                    ->to($contact->email, $contact->full_name)
                    ->subject($subject)
            );
        } catch (\Exception $e) {
            Log::error('Rent receipt email failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
