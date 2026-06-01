<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\RentPayment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMonthlyRentPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $activeLeases = Lease::where('status', 'active')
            ->where('end_date', '>=', today())
            ->get();

        foreach ($activeLeases as $lease) {
            $dueDate = Carbon::create(now()->year, now()->month, (int) $lease->payment_day);

            $exists = RentPayment::where('lease_id', $lease->id)
                ->whereYear('due_date', $dueDate->year)
                ->whereMonth('due_date', $dueDate->month)
                ->exists();

            if (! $exists) {
                RentPayment::create([
                    'agency_id'  => $lease->agency_id,
                    'lease_id'   => $lease->id,
                    'tenant_id'  => $lease->tenant_id,
                    'amount_due' => $lease->monthly_rent,
                    'status'     => 'pending',
                    'due_date'   => $dueDate,
                ]);
            }
        }
    }
}
