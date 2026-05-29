<?php

namespace App\Console;

use App\Infrastructure\Queue\Jobs\DetectAndNotifyStaleDealsJob;
use App\Infrastructure\Queue\Jobs\DispatchScheduledCampaignsJob;
use App\Infrastructure\Queue\Jobs\GenerateSellerReportJob;
use App\Infrastructure\Queue\Jobs\ProcessFollowUpSequenceJob;
use App\Infrastructure\Queue\Jobs\SendViewingRemindersJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Process queued follow-up sequence steps — every hour
        $schedule->job(new ProcessFollowUpSequenceJob)
                 ->hourly()
                 ->withoutOverlapping()
                 ->name('process-follow-up-sequences');

        // Dispatch any scheduled campaigns whose send time has passed — every 15 minutes
        $schedule->job(new DispatchScheduledCampaignsJob)
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->name('dispatch-scheduled-campaigns');

        // Send viewing reminder emails (48h, morning-of, 1h) — every 30 minutes
        $schedule->job(new SendViewingRemindersJob)
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->name('send-viewing-reminders');

        // Detect stale deals and notify agents — every weekday at 08:00
        $schedule->job(new DetectAndNotifyStaleDealsJob)
                 ->weekdays()
                 ->at('08:00')
                 ->withoutOverlapping()
                 ->name('detect-stale-deals');

        // Generate and email weekly seller reports — every Monday at 09:00
        $schedule->job(new GenerateSellerReportJob)
                 ->weekly()
                 ->mondays()
                 ->at('09:00')
                 ->withoutOverlapping()
                 ->name('generate-seller-reports');

        // Flush queued WhatsApp outbound messages — every 5 minutes
        $schedule->call(function () {
            app(\App\Infrastructure\ExternalServices\WhatsApp\WhatsAppApiClient::class)->flushQueue();
        })
        ->everyFiveMinutes()
        ->name('flush-whatsapp-queue');

        // Prune stale telescope/horizon entries and expired sessions — daily at midnight
        $schedule->command('queue:prune-failed --hours=168')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
