<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessRentPaymentAction
{
    public function execute(
        Lease $lease,
        float $amountPaid,
        string $paidDate,
        string $paymentMethod,
        ?string $notes = null,
    ): RentPayment {
        $pending = $lease->rentPayments()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->orderBy('due_date')
            ->first();

        if ($pending) {
            $totalPaid = ((float) ($pending->amount_paid ?? 0)) + $amountPaid;
            $status    = $totalPaid >= (float) $pending->amount_due ? 'paid' : 'partial';

            $pending->update([
                'amount_paid'    => $totalPaid,
                'status'         => $status,
                'paid_date'      => $paidDate,
                'payment_method' => $paymentMethod,
                'notes'          => $notes,
            ]);

            $payment = $pending;
        } else {
            $status  = $amountPaid >= (float) $lease->monthly_rent ? 'paid' : 'partial';
            $payment = RentPayment::create([
                'agency_id'      => $lease->agency_id,
                'lease_id'       => $lease->id,
                'tenant_id'      => $lease->tenant_id,
                'amount_due'     => $lease->monthly_rent,
                'amount_paid'    => $amountPaid,
                'status'         => $status,
                'due_date'       => $paidDate,
                'paid_date'      => $paidDate,
                'payment_method' => $paymentMethod,
                'notes'          => $notes,
            ]);
        }

        if ($status === 'paid') {
            $this->sendReceipt($lease, $payment);
        }

        return $payment;
    }

    private function sendReceipt(Lease $lease, RentPayment $payment): void
    {
        $contact = $lease->tenant?->contact ?? $lease->contact;

        if (! $contact?->email) {
            return;
        }

        $property = $lease->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';

        $body = "Dear {$contact->first_name},\n\n"
            . "This is your payment receipt for {$address}.\n\n"
            . "Reference: {$payment->reference}\n"
            . "Amount Paid: R " . number_format((float) $payment->amount_paid, 2) . "\n"
            . "Date: " . \Carbon\Carbon::parse($payment->paid_date)->format('d M Y') . "\n"
            . "Method: " . ucfirst(str_replace('_', ' ', $payment->payment_method)) . "\n\n"
            . "Thank you for your payment.\n\nKind regards,\nProperty Management";

        try {
            Mail::raw($body, fn ($msg) => $msg->to($contact->email, $contact->full_name)->subject("Payment Receipt — {$payment->reference}"));
        } catch (\Exception $e) {
            Log::error('Rent payment receipt email failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
        }
    }
}
