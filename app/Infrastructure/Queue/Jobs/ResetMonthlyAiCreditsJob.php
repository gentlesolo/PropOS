<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResetMonthlyAiCreditsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $resetCount = 0;

        Agency::where('subscription_status', 'active')
            ->whereNotNull('subscription_plan')
            ->each(function (Agency $agency) use (&$resetCount) {
                $monthly = config("pricing.plans.{$agency->subscription_plan}.ai_credits_monthly");

                // Enterprise (-1) means unlimited — skip
                if ($monthly === null || $monthly === -1) {
                    return;
                }

                $agency->update(['ai_credits_balance' => $monthly]);
                $resetCount++;
            });

        Log::info('ResetMonthlyAiCreditsJob: credits reset', ['agencies_updated' => $resetCount]);
    }
}
