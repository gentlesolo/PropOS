<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;

class GeneratePaymentScheduleAction
{
    public function execute(Lease $lease): void
    {
        $frequency = $lease->payment_frequency ?? 'monthly';

        [$intervalMonths, $periodAmount] = match($frequency) {
            'quarterly' => [3,  round((float) $lease->monthly_rent * 3,  2)],
            'bi_yearly' => [6,  round((float) $lease->monthly_rent * 6,  2)],
            'yearly'    => [12, round((float) $lease->monthly_rent * 12, 2)],
            default     => [1,  (float) $lease->monthly_rent],
        };

        // For non-monthly frequencies use the start date as anchor (anniversary-based).
        // For monthly, use the configured payment_day within the month.
        if ($frequency === 'monthly') {
            $current = $lease->start_date->copy()->day((int) $lease->payment_day);
            if ($current->lt($lease->start_date)) {
                $current->addMonth();
            }
        } else {
            $current = $lease->start_date->copy();
        }

        while ($current->lte($lease->end_date)) {
            $alreadyExists = RentPayment::where('lease_id', $lease->id)
                ->whereYear('due_date', $current->year)
                ->whereMonth('due_date', $current->month)
                ->exists();

            if (! $alreadyExists) {
                RentPayment::create([
                    'agency_id'  => $lease->agency_id,
                    'lease_id'   => $lease->id,
                    'tenant_id'  => $lease->tenant_id,
                    'amount_due' => $periodAmount,
                    'status'     => 'pending',
                    'due_date'   => $current->copy(),
                ]);
            }

            $current->addMonths($intervalMonths);
        }
    }
}
