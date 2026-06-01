<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Application\Finance\Actions\ApplyLateFeeAction;
use App\Infrastructure\Persistence\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyLateFeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(ApplyLateFeeAction $applyFee): void
    {
        $graceDays = (int) config('finance.late_fee_grace_days', 3);
        $cutoff    = now()->subDays($graceDays)->toDateString();

        Invoice::with(['lineItems', 'lease'])
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->where('due_date', '<', $cutoff)
            ->each(function (Invoice $invoice) use ($applyFee) {
                try {
                    $applyFee->execute($invoice);
                } catch (\Exception $e) {
                    Log::error('ApplyLateFeesJob: failed for invoice', [
                        'invoice_id' => $invoice->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            });
    }
}
