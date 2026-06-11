<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;

class ProcessRentPaymentAction
{
    public function __construct(private readonly SendRentReceiptAction $receipt) {}

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

            $payment = $pending->refresh();
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

        $this->receipt->execute($payment);

        return $payment;
    }
}
