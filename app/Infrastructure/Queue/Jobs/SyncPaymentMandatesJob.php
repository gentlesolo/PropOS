<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\PaymentMandate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPaymentMandatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        PaymentMandate::where('status', 'active')
            ->whereDate('next_collection_date', today())
            ->each(function (PaymentMandate $mandate) {
                try {
                    // PayFast subscription billing is automatic; we update our internal tracking
                    $mandate->update([
                        'last_collected_at'    => now(),
                        'next_collection_date' => now()->addMonth()->day($mandate->collection_day),
                    ]);

                    Log::info('SyncPaymentMandatesJob: mandate collection logged', [
                        'mandate_id' => $mandate->id,
                        'amount'     => $mandate->amount,
                    ]);
                } catch (\Exception $e) {
                    Log::error('SyncPaymentMandatesJob: failed', [
                        'mandate_id' => $mandate->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            });
    }
}
