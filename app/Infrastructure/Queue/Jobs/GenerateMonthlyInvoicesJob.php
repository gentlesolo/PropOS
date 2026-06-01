<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Application\Finance\Actions\GenerateRentInvoiceAction;
use App\Application\Finance\Actions\SendInvoiceAction;
use App\Infrastructure\Persistence\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(GenerateRentInvoiceAction $generate, SendInvoiceAction $send): void
    {
        $month = (int) now()->format('m');
        $year  = (int) now()->format('Y');

        Lease::where('status', 'active')->each(function (Lease $lease) use ($month, $year, $generate, $send) {
            try {
                $invoice = $generate->execute($lease, $month, $year);

                if ($invoice && $invoice->wasRecentlyCreated) {
                    $send->execute($invoice);
                }
            } catch (\Exception $e) {
                Log::error('GenerateMonthlyInvoicesJob: failed for lease', [
                    'lease_id' => $lease->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        });
    }
}
