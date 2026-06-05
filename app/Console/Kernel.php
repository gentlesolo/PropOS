<?php

namespace App\Console;

use App\Infrastructure\Queue\Jobs\ApplyLateFeesJob;
use App\Infrastructure\Queue\Jobs\SendComplianceRemindersJob;
use App\Infrastructure\Queue\Jobs\SyncAllEmailAccountsJob;
use App\Infrastructure\Queue\Jobs\DetectAndNotifyStaleDealsJob;
use App\Infrastructure\Queue\Jobs\DispatchScheduledCampaignsJob;
use App\Infrastructure\Queue\Jobs\GenerateMonthlyInvoicesJob;
use App\Infrastructure\Queue\Jobs\GenerateMonthlyRentPaymentsJob;
use App\Infrastructure\Queue\Jobs\GenerateSellerReportJob;
use App\Infrastructure\Queue\Jobs\ProcessFollowUpSequenceJob;
use App\Infrastructure\Queue\Jobs\ProcessOverdueRentsJob;
use App\Infrastructure\Queue\Jobs\SendLeaseExpiryReminderJob;
use App\Infrastructure\Queue\Jobs\SendRentPaymentReminderJob;
use App\Infrastructure\Queue\Jobs\SendViewingRemindersJob;
use App\Infrastructure\Queue\Jobs\SyncPaymentMandatesJob;
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

        // Send compliance deadline reminders to agency admins — daily at 08:30
        $schedule->job(new SendComplianceRemindersJob)
                 ->dailyAt('08:30')
                 ->withoutOverlapping()
                 ->name('send-compliance-reminders');

        // Send lease expiry reminders to agents and tenants (30, 14, 7 days) — daily at 08:00
        $schedule->job(new SendLeaseExpiryReminderJob)
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->name('send-lease-expiry-reminders');

        // Send rent payment reminders to tenants — daily at 07:00
        $schedule->job(new SendRentPaymentReminderJob)
                 ->dailyAt('07:00')
                 ->withoutOverlapping()
                 ->name('send-rent-payment-reminders');

        // Mark past-due rent payments as overdue and notify agents — daily at midnight
        $schedule->job(new ProcessOverdueRentsJob)
                 ->dailyAt('00:05')
                 ->withoutOverlapping()
                 ->name('process-overdue-rents');

        // Generate missing rent payment records for active leases on the 1st of each month
        $schedule->job(new GenerateMonthlyRentPaymentsJob)
                 ->monthlyOn(1, '06:00')
                 ->withoutOverlapping()
                 ->name('generate-monthly-rent-payments');

        // Generate rent invoices for all active leases on the 1st of each month at 05:00
        $schedule->job(new GenerateMonthlyInvoicesJob)
                 ->monthlyOn(1, '05:00')
                 ->withoutOverlapping()
                 ->name('generate-monthly-invoices');

        // Apply late fees to overdue invoices past the grace period — daily at 09:00
        $schedule->job(new ApplyLateFeesJob)
                 ->dailyAt('09:00')
                 ->withoutOverlapping()
                 ->name('apply-late-fees');

        // Log payment mandate collection activity — daily at 06:00
        $schedule->job(new SyncPaymentMandatesJob)
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->name('sync-payment-mandates');

        // Sync all active IMAP email accounts — every 5 minutes
        $schedule->job(new SyncAllEmailAccountsJob)
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->name('sync-email-accounts');

        // Prune stale telescope/horizon entries and expired sessions — daily at midnight
        $schedule->command('queue:prune-failed --hours=168')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
