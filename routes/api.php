<?php

use App\Http\Controllers\Api\Public\PublicListingController;
use App\Http\Controllers\Api\Public\PublicLeadController;
use App\Http\Controllers\Api\Public\PublicAgentController;
use App\Http\Controllers\Api\Public\PublicBookingController;
use App\Http\Controllers\Api\Public\PublicContactController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadWebhookController;
use App\Http\Controllers\Api\Mobile\MobileInvoiceController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Api\CallWebhookController;
use App\Http\Controllers\Api\LiveKitWebhookController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\EmailWebhookController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\Mobile\AgentBenchmarkController;
use App\Http\Controllers\Api\Mobile\AgentNumberController;
use App\Http\Controllers\Api\Mobile\MobileLeaseController;
use App\Http\Controllers\Api\Mobile\MobileNotificationController;
use App\Http\Controllers\Api\Mobile\MobileTenantController;
use App\Http\Controllers\Api\Mobile\CallAnalyticsController;
use App\Http\Controllers\Api\Mobile\CallController;
use App\Http\Controllers\Api\Mobile\InCallHintsController;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileBriefController;
use App\Http\Controllers\Api\Mobile\MobileContactController;
use App\Http\Controllers\Api\Mobile\MobileMessagingController;
use App\Http\Controllers\Api\Mobile\MobileTaskController;
use App\Http\Controllers\Api\Mobile\MobileViewingController;
use App\Http\Controllers\Api\Mobile\ReplyCoachController;
use App\Http\Controllers\Api\TwilioMediaStreamController;
use App\Http\Controllers\Api\SmsWebhookController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Public API v1 (API key authenticated) ────────────────────────────────
Route::prefix('v1/public')->name('api.v1.public.')->middleware(['api.key', 'throttle:public-api'])->group(function () {

    // Listings (read-only)
    Route::get('/listings',                 [PublicListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/{id}',            [PublicListingController::class, 'show'])->name('listings.show');

    // Lead capture — stricter throttle
    Route::post('/leads',                   [PublicLeadController::class, 'store'])
        ->middleware('throttle:public-leads')
        ->name('leads.store');

    // Booking / viewing request — strictest throttle
    Route::post('/bookings',                [\App\Http\Controllers\Api\Public\PublicBookingController::class, 'store'])
        ->middleware('throttle:public-bookings')
        ->name('bookings.store');

    // Agent availability calendar
    Route::get('/agents/{id}/availability', [PublicAgentController::class, 'availability'])->name('agents.availability');

    // ── Contact API (full_access keys only) ───────────────────────────────
    Route::middleware(['api.key:full_access', 'throttle:contact-api'])->group(function () {
        Route::get('/contacts',                     [PublicContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts',                    [PublicContactController::class, 'store'])->name('contacts.store');
        Route::get('/contacts/{id}',                [PublicContactController::class, 'show'])->name('contacts.show');
        Route::patch('/contacts/{id}',              [PublicContactController::class, 'update'])->name('contacts.update');
        Route::post('/contacts/{id}/tags',          [PublicContactController::class, 'addTags'])->name('contacts.tags.add');
        Route::delete('/contacts/{id}/tags',        [PublicContactController::class, 'removeTags'])->name('contacts.tags.remove');
    });
});

// PayFast ITN webhook (public — verified by signature inside controller)
Route::post('/webhooks/payfast', [PaymentWebhookController::class, 'handlePayFastItn']);

// Paystack webhook (public — verified by HMAC-SHA512 inside controller)
Route::post('/webhooks/paystack', [PaymentWebhookController::class, 'handlePaystackWebhook']);

// WhatsApp webhook (public — verified by token, not Sanctum)
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive']);

// SMS inbound webhooks (public — provider-signed)
Route::post('/webhooks/sms/twilio', [SmsWebhookController::class, 'twilio']);
Route::post('/webhooks/sms/africastalking', [SmsWebhookController::class, 'africasTalking']);

// Email event webhooks (open/click tracking)
Route::post('/webhooks/email/mailgun', [EmailWebhookController::class, 'mailgun']);
Route::post('/webhooks/email/sendgrid', [EmailWebhookController::class, 'sendgrid']);

// Smart lead capture webhooks (unauthenticated)
Route::post('/webhooks/leads/{agency_slug}/{source}', [LeadWebhookController::class, 'receive']);

// Public auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated API (Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::get('/contacts/{contact}', [ContactController::class, 'show']);
    Route::patch('/contacts/{contact}', [ContactController::class, 'update']);
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);

    // Listings
    Route::get('/listings', [ListingController::class, 'index']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::get('/listings/{listing}', [ListingController::class, 'show']);
    Route::patch('/listings/{listing}', [ListingController::class, 'update']);

    // Deals
    Route::get('/deals', [DealController::class, 'index']);
    Route::get('/deals/{deal}', [DealController::class, 'show']);
    Route::patch('/deals/{deal}', [DealController::class, 'update']);
});

// ─── LiveKit webhooks (public — JWT-signed by LiveKit server) ──────────────
Route::post('/webhooks/livekit', [LiveKitWebhookController::class, 'handle'])
    ->name('api.livekit.webhook');

// ─── Mobile API (Sanctum token) ────────────────────────────────────────────
Route::prefix('mobile')->name('api.mobile.')->group(function () {

    // Auth
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout',  [MobileAuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/refresh', [MobileAuthController::class, 'refresh'])->name('auth.refresh');
        Route::get('/auth/me',       [MobileAuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/device',  [MobileAuthController::class, 'registerDevice'])->name('auth.device');

        // Calls
        Route::post('/calls/token', [CallController::class, 'token'])->name('calls.token');
        Route::get('/calls', [CallController::class, 'index'])->name('calls.index');
        Route::post('/calls', [CallController::class, 'store'])->name('calls.store');
        Route::get('/calls/search', [CallController::class, 'search'])->name('calls.search');
        Route::get('/calls/{call}', [CallController::class, 'show'])->name('calls.show');
        Route::get('/calls/{call}/recording', [CallController::class, 'recording'])->name('calls.recording');
        Route::patch('/calls/{call}/status', [CallController::class, 'updateStatus'])->name('calls.status');
        Route::patch('/calls/{call}/summary', [CallController::class, 'confirmSummary'])->name('calls.summary');

        // Contacts
        Route::get('/contacts', [MobileContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [MobileContactController::class, 'store'])->name('contacts.store');
        Route::get('/contacts/{contact}', [MobileContactController::class, 'show'])->name('contacts.show');
        Route::post('/contacts/{contact}/notes', [MobileContactController::class, 'addNote'])->name('contacts.notes');
        Route::get('/contacts/{contact}/calls', [MobileContactController::class, 'calls'])->name('contacts.calls');
        Route::get('/contacts/{contact}/timeline', [MobileBriefController::class, 'contactTimeline'])->name('contacts.timeline');

        // Tasks
        Route::get('/tasks', [MobileTaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [MobileTaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [MobileTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [MobileTaskController::class, 'destroy'])->name('tasks.destroy');

        // Messaging (Phase 2)
        Route::get('/messages', [MobileMessagingController::class, 'inbox'])->name('messages.inbox');
        Route::get('/messages/{contact}', [MobileMessagingController::class, 'thread'])->name('messages.thread');
        Route::post('/messages/{contact}', [MobileMessagingController::class, 'send'])->name('messages.send');

        // Viewings (Phase 2)
        Route::get('/viewings', [MobileViewingController::class, 'index'])->name('viewings.index');
        Route::get('/viewings/upcoming', [MobileViewingController::class, 'upcoming'])->name('viewings.upcoming');
        Route::get('/viewings/{viewing}', [MobileViewingController::class, 'show'])->name('viewings.show');
        Route::post('/viewings/{viewing}/check-in', [MobileViewingController::class, 'checkIn'])->name('viewings.checkin');
        Route::post('/viewings/{viewing}/complete', [MobileViewingController::class, 'complete'])->name('viewings.complete');
        Route::patch('/viewings/{viewing}/status', [MobileViewingController::class, 'updateStatus'])->name('viewings.status');

        // Daily brief (Phase 2)
        Route::get('/brief', [MobileBriefController::class, 'show'])->name('brief.show');

        // Notifications
        Route::get('/notifications',                  [MobileNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count',     [MobileNotificationController::class, 'unreadCount'])->name('notifications.unread');
        Route::patch('/notifications/read-all',       [MobileNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{id}/read',      [MobileNotificationController::class, 'markRead'])->name('notifications.read');

        // ── Phase 3: Intelligence ──────────────────────────────────────────

        // Live transcript & in-call hints
        Route::get('/calls/{call}/channel', [InCallHintsController::class, 'channel'])->name('calls.channel');
        Route::post('/calls/{call}/hints', [InCallHintsController::class, 'hints'])->name('calls.hints');
        Route::post('/calls/{call}/flag', [InCallHintsController::class, 'flag'])->name('calls.flag');
        Route::post('/calls/{call}/stream', [TwilioMediaStreamController::class, 'startStream'])->name('calls.stream.start');

        // Reply coach & AI suggestions
        Route::post('/coach/score', [ReplyCoachController::class, 'score'])->name('coach.score');
        Route::post('/coach/suggest', [ReplyCoachController::class, 'suggest'])->name('coach.suggest');

        // Analytics — personal
        Route::get('/analytics/personal', [CallAnalyticsController::class, 'personal'])->name('analytics.personal');
        Route::get('/analytics/contact/{contactId}/sentiment', [CallAnalyticsController::class, 'contactSentiment'])->name('analytics.contact.sentiment');

        // Analytics — manager only
        Route::get('/analytics/team', [CallAnalyticsController::class, 'team'])->name('analytics.team');
        Route::get('/analytics/agents/{agent}/calls', [CallAnalyticsController::class, 'agentCalls'])->name('analytics.agent.calls');
        Route::post('/analytics/calls/{call}/unflag', [CallAnalyticsController::class, 'unflag'])->name('analytics.calls.unflag');

        // ── Phase 4: Benchmarking ──────────────────────────────────────────
        Route::get('/benchmark', [AgentBenchmarkController::class, 'compare'])->name('benchmark.compare');
        Route::get('/benchmark/leaderboard', [AgentBenchmarkController::class, 'leaderboard'])->name('benchmark.leaderboard');

        // ── Phase 5: Property Management ──────────────────────────────────
        Route::get('/tenants', [MobileTenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [MobileTenantController::class, 'show'])->name('tenants.show');

        Route::get('/leases', [MobileLeaseController::class, 'index'])->name('leases.index');
        Route::get('/leases/{lease}', [MobileLeaseController::class, 'show'])->name('leases.show');
        Route::post('/leases/{lease}/payments', [MobileLeaseController::class, 'recordPayment'])->name('leases.payments.store');

        // ── Phase 6: Finance ───────────────────────────────────────────────
        Route::get('/invoices', [MobileInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [MobileInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/pay', [MobileInvoiceController::class, 'payNow'])->name('invoices.pay');

        // Agent language preference update
        Route::patch('/numbers/language', function (\Illuminate\Http\Request $request) {
            $request->validate(['language' => 'required|string|size:2']);
            \App\Infrastructure\Persistence\Models\AgentNumber::where('user_id', $request->user()->id)
                ->update(['language' => $request->input('language')]);
            return response()->json(['language' => $request->input('language')]);
        })->name('numbers.language');

        // Phone number management (BYON)
        Route::get('/numbers',                              [AgentNumberController::class, 'index'])->name('numbers.index');
        Route::post('/numbers',                             [AgentNumberController::class, 'store'])->name('numbers.store');
        Route::post('/numbers/{agentNumber}/confirm',       [AgentNumberController::class, 'confirm'])->name('numbers.confirm');
        Route::post('/numbers/{agentNumber}/resend',        [AgentNumberController::class, 'resendOtp'])->name('numbers.resend');
        Route::patch('/numbers/{agentNumber}/activate',     [AgentNumberController::class, 'activate'])->name('numbers.activate');
        Route::delete('/numbers/{agentNumber}',             [AgentNumberController::class, 'destroy'])->name('numbers.destroy');
    });
});

// Recording proxy — validates the signed URL then streams the OGG from S3 (LiveKit Egress)
Route::get('/mobile/calls/{call}/recording/stream', function (\Illuminate\Http\Request $request, \App\Infrastructure\Persistence\Models\Call $call) {
    abort_unless($request->hasValidSignature(), 403);
    abort_unless($call->recording_url, 404);

    $stream = \Illuminate\Support\Facades\Http::get($call->recording_url);

    return response($stream->body(), 200, [
        'Content-Type'  => 'audio/ogg',
        'Cache-Control' => 'private, max-age=3600',
    ]);
})->name('api.mobile.calls.recording.proxy');

// Twilio MediaStream TwiML + Deepgram callback (public — Twilio/Deepgram signed)
Route::post('/webhooks/calls/twiml', [TwilioMediaStreamController::class, 'twiml'])
    ->name('api.media.stream.ws');
Route::post('/webhooks/deepgram/callback', [TwilioMediaStreamController::class, 'deepgramCallback'])
    ->name('api.deepgram.callback');
