<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;
use Carbon\Carbon;

class GeneratePaymentScheduleAction
{
    public function execute(Lease $lease): void
    {
        $current = $lease->start_date->copy()->day((int) $lease->payment_day);

        if ($current->lt($lease->start_date)) {
            $current->addMonth();
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
                    'amount_due' => $lease->monthly_rent,
                    'status'     => 'pending',
                    'due_date'   => $current->copy(),
                ]);
            }

            $current->addMonth();
        }
    }
}
