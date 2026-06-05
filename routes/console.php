<?php

use App\Infrastructure\ExternalServices\WhatsApp\WhatsAppApiClient;
use App\Infrastructure\Queue\Jobs\CheckMandateExpiryJob;
use App\Infrastructure\Queue\Jobs\DetectAndNotifyStaleDealsJob;
use App\Infrastructure\Queue\Jobs\DispatchScheduledCampaignsJob;
use App\Infrastructure\Queue\Jobs\GeneratePerformanceNudgesJob;
use App\Infrastructure\Queue\Jobs\GenerateSellerReportJob;
use App\Infrastructure\Queue\Jobs\ProcessFollowUpSequenceJob;
use App\Infrastructure\Queue\Jobs\SendViewingFeedbackSurveyJob;
use App\Infrastructure\Queue\Jobs\SendViewingRemindersJob;
use App\Infrastructure\Queue\Jobs\SyncAllEmailAccountsJob;
use App\Infrastructure\Queue\Jobs\SyncMetaAdsInsightsJob;
use App\Infrastructure\Queue\Jobs\UpdateListingHealthScoresJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process queued follow-up sequence steps — every hour
Schedule::job(new ProcessFollowUpSequenceJob)->hourly()->withoutOverlapping()->name('process-follow-up-sequences');

// Dispatch campaigns whose send time has passed — every 15 minutes
Schedule::job(new DispatchScheduledCampaignsJob)->everyFifteenMinutes()->withoutOverlapping()->name('dispatch-campaigns');

// Send automated viewing reminders (48h, morning-of, 1h) — every 30 minutes
Schedule::job(new SendViewingRemindersJob)->everyThirtyMinutes()->withoutOverlapping()->name('viewing-reminders');

// Detect stale deals and notify assigned agents — weekdays at 08:00
Schedule::job(new DetectAndNotifyStaleDealsJob)->weekdays()->at('08:00')->withoutOverlapping()->name('stale-deals');

// Generate and email weekly seller reports — Mondays at 09:00
Schedule::job(new GenerateSellerReportJob)->weekly()->mondays()->at('09:00')->withoutOverlapping()->name('seller-reports');

// Flush queued outbound WhatsApp messages — every 5 minutes
Schedule::call(fn () => app(WhatsAppApiClient::class)->flushQueue())->everyFiveMinutes()->name('flush-whatsapp-queue');

// Prune failed jobs older than 7 days — daily
Schedule::command('queue:prune-failed --hours=168')->daily();

// Update listing health scores + days-on-market — nightly at 02:00
Schedule::job(new UpdateListingHealthScoresJob)->dailyAt('02:00')->withoutOverlapping()->name('update-listing-health');

// Check mandate expiry dates and notify agents — daily at 08:30
Schedule::job(new CheckMandateExpiryJob)->dailyAt('08:30')->withoutOverlapping()->name('check-mandate-expiry');

// Send post-viewing feedback surveys — every hour
Schedule::job(new SendViewingFeedbackSurveyJob)->hourly()->withoutOverlapping()->name('send-feedback-surveys');

// Generate proactive performance nudges for agents — weekdays at 09:00
Schedule::job(new GeneratePerformanceNudgesJob)->weekdays()->at('09:00')->withoutOverlapping()->name('performance-nudges');

// Pull Meta Ads spend/impressions/leads metrics — every hour
Schedule::job(new SyncMetaAdsInsightsJob)->hourly()->withoutOverlapping()->name('sync-meta-ads-insights');

// Sync all active IMAP email accounts — every 5 minutes
Schedule::job(new SyncAllEmailAccountsJob)->everyFiveMinutes()->withoutOverlapping()->name('sync-email-accounts');
