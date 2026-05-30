<?php

use Illuminate\Support\Facades\Route;

// Redirect root
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// ── Public routes (no auth required) ────────────────────────────────────────
Route::get('/book/{listing}', \App\Http\Livewire\Viewing\PublicBookingPage::class)->name('viewing.book');
Route::get('/feedback/{viewing}/{token}', \App\Http\Livewire\Viewing\PublicFeedbackPage::class)->name('viewing.feedback');

// Google Calendar OAuth callback — must be public (Google redirects here; auth is still active via session)
Route::get('/integrations/google-calendar/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])
    ->middleware('auth')
    ->name('google-calendar.callback');

// Guest routes
Route::middleware(['tenant', 'guest'])->group(function () {
    Route::get('/login', \App\Http\Livewire\Auth\LoginPage::class)->name('login');
    Route::get('/register', \App\Http\Livewire\Auth\RegisterPage::class)->name('register');
});

// 2FA challenge — session-gated, no full auth yet
Route::get('/two-factor', \App\Http\Livewire\Auth\TwoFactorChallengePage::class)
    ->name('two-factor.challenge')
    ->middleware('tenant');

// Logout
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

// Authenticated Application Routes
Route::middleware(['auth', 'tenant'])->group(function () {

    // ── Dashboard (all authenticated users) ──────────────────────────────────
    Route::get('/dashboard', \App\Http\Livewire\DashboardPage::class)->name('dashboard');
    Route::get('/dashboard-v2', \App\Http\Livewire\DashboardPageV2::class)->name('dashboard.v2');

    // ── CRM — requires contacts.view_own ─────────────────────────────────────
    Route::middleware('permission:contacts.view_own')->group(function () {
        Route::get('/contacts', \App\Http\Livewire\Crm\ContactsPage::class)->name('crm.contacts');
        Route::get('/contacts/{contact}', \App\Http\Livewire\Crm\ContactDetailPage::class)->name('crm.contact.detail');
        Route::get('/pipeline', \App\Http\Livewire\Crm\PipelineBoard::class)->name('crm.pipeline');
        Route::get('/pipeline/deals/{deal}', \App\Http\Livewire\Crm\DealDetailPage::class)->name('crm.deal.detail');
    });

    // ── Listings — requires listings.view_own ─────────────────────────────────
    Route::middleware('permission:listings.view_own')->group(function () {
        Route::get('/listings', \App\Http\Livewire\Listing\IndexPage::class)->name('listing.index');
        Route::get('/listings/{listing}', \App\Http\Livewire\Listing\ListingDetailPage::class)->name('listing.detail');
    });

    // ── Marketing — requires campaigns.view_own or campaigns.view_all ────────
    Route::middleware('permission:campaigns.view_all|campaigns.view_own')->group(function () {
        Route::get('/marketing/campaigns', \App\Http\Livewire\Marketing\CampaignListPage::class)->name('marketing.campaigns');
        Route::get('/marketing/campaign/new', \App\Http\Livewire\Marketing\CampaignWizard::class)->name('marketing.campaign.new');
        Route::get('/marketing/calendar', \App\Http\Livewire\Marketing\ContentCalendarPage::class)->name('marketing.calendar');
        Route::get('/marketing/whatsapp', \App\Http\Livewire\Marketing\WhatsAppPage::class)->name('marketing.whatsapp');
        Route::get('/marketing/social', \App\Http\Livewire\Marketing\SocialPostsPage::class)->name('marketing.social');
        Route::get('/marketing/sequences', \App\Http\Livewire\Marketing\FollowUpSequencesPage::class)->name('marketing.sequences');
    });
    Route::middleware('permission:campaigns.manage')->group(function () {
        Route::get('/marketing/meta-ads', \App\Http\Livewire\Marketing\MetaAdsPage::class)->name('marketing.meta-ads');
    });

    // ── Viewings — requires dashboard.view ───────────────────────────────────
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/viewings/day', \App\Http\Livewire\Viewing\DayViewPage::class)->name('viewing.day');
        Route::get('/viewings/open-houses', \App\Http\Livewire\Viewing\OpenHousePage::class)->name('viewing.open-houses');
        Route::get('/ai-planner', \App\Http\Livewire\Ai\PlannerPage::class)->name('ai.planner');
    });

    // ── Analytics — basic (all agents) ───────────────────────────────────────
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/analytics/scorecard', \App\Http\Livewire\Intelligence\AgentScorecardPage::class)->name('analytics.scorecard');
        Route::get('/analytics/listing-health', \App\Http\Livewire\Intelligence\ListingHealthDashboard::class)->name('analytics.listing-health');
        Route::get('/analytics/market-intelligence', \App\Http\Livewire\Intelligence\MarketIntelligencePage::class)->name('analytics.market-intelligence');
        Route::get('/analytics/sentiment', \App\Http\Livewire\Intelligence\SentimentMonitoringPage::class)->name('analytics.sentiment');
        Route::get('/analytics/predictions', \App\Http\Livewire\Intelligence\PredictionDashboardPage::class)->name('analytics.predictions');
    });

    // ── Analytics — manager/principal only ────────────────────────────────────
    Route::middleware('permission:pipeline.view_team')->group(function () {
        Route::get('/analytics/forecast', \App\Http\Livewire\Intelligence\RevenueForecastPage::class)->name('analytics.forecast');
    });

    // ── Compliance — requires transactions.view_own ───────────────────────────
    Route::middleware('permission:transactions.view_own')->group(function () {
        Route::get('/compliance/transactions', \App\Http\Livewire\Compliance\TransactionCenterPage::class)->name('compliance.transactions');
        Route::get('/compliance/transactions/{transaction}', \App\Http\Livewire\Compliance\TransactionDetailPage::class)->name('compliance.transaction.detail');
        Route::get('/compliance/attorneys', \App\Http\Livewire\Compliance\AttorneyPortalPage::class)->name('compliance.attorneys');
    });

    // ── Commissions — requires commission.view_own ────────────────────────────
    Route::middleware('permission:commission.view_own')->group(function () {
        Route::get('/finance/commissions', \App\Http\Livewire\Intelligence\CommissionLedgerPage::class)->name('finance.commissions');
    });

    // ── Training — requires training.view ────────────────────────────────────
    Route::middleware('permission:training.view')->group(function () {
        Route::get('/training', \App\Http\Livewire\Training\TrainingDashboardPage::class)->name('training.dashboard');
        Route::get('/training/objections', \App\Http\Livewire\Training\ObjectionHandlerPage::class)->name('training.objections');
        Route::get('/training/skills-library', \App\Http\Livewire\Training\SkillsLibraryPage::class)->name('training.skills-library');
        Route::get('/training/skills', \App\Http\Livewire\Training\SkillsLibraryPage::class)->name('training.skills');
        Route::get('/training/role-play', \App\Http\Livewire\Training\RolePlayPage::class)->name('training.role-play');
        Route::get('/training/roleplay', \App\Http\Livewire\Training\RolePlayPage::class)->name('training.roleplay');
    });

    // ── Settings — profile & 2FA (all users) ─────────────────────────────────
    Route::get('/settings', \App\Http\Livewire\Settings\ProfilePage::class)->name('settings.profile');
    Route::get('/settings/two-factor', \App\Http\Livewire\Auth\TwoFactorSetupPage::class)->name('two-factor.setup');

    // ── Team Management — requires agency.manage ──────────────────────────────
    Route::middleware('permission:agency.manage')->group(function () {
        Route::get('/settings/team', \App\Http\Livewire\Settings\TeamPage::class)->name('settings.team');
    });

    // ── Offers ───────────────────────────────────────────────────────────────
    Route::middleware('permission:contacts.view_own')->group(function () {
        Route::get('/offers', \App\Http\Livewire\Offers\OffersPage::class)->name('offers.index');
    });

    // ── Contracts ─────────────────────────────────────────────────────────────
    Route::middleware('permission:transactions.view_own')->group(function () {
        Route::get('/contracts', \App\Http\Livewire\Contracts\ContractsPage::class)->name('contracts.index');
    });

    // ── Tasks ─────────────────────────────────────────────────────────────────
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/tasks', \App\Http\Livewire\Tasks\TaskBoardPage::class)->name('tasks.board');
    });

    // ── Property Management ───────────────────────────────────────────────────
    Route::middleware('permission:transactions.view_own')->group(function () {
        Route::get('/property-management/tenants', \App\Http\Livewire\PropertyManagement\TenantManagementPage::class)->name('pm.tenants');
        Route::get('/property-management/leases', \App\Http\Livewire\PropertyManagement\LeaseManagementPage::class)->name('pm.leases');
    });

    // ── Inspections ───────────────────────────────────────────────────────────
    Route::middleware('permission:transactions.view_own')->group(function () {
        Route::get('/compliance/inspections', \App\Http\Livewire\Compliance\InspectionsPage::class)->name('compliance.inspections');
    });

    // ── Messaging Inbox ───────────────────────────────────────────────────────
    Route::middleware('permission:campaigns.view_own|campaigns.view_all')->group(function () {
        Route::get('/marketing/inbox', \App\Http\Livewire\Marketing\MessagingInboxPage::class)->name('marketing.inbox');
        Route::get('/marketing/email-templates', \App\Http\Livewire\Marketing\EmailTemplatesPage::class)->name('marketing.email-templates');
    });

    // ── CMA Reports ───────────────────────────────────────────────────────────
    Route::middleware('permission:listings.view_own')->group(function () {
        Route::get('/analytics/cma', \App\Http\Livewire\Intelligence\CmaReportPage::class)->name('analytics.cma');
    });

    // ── Bulk Import/Export ────────────────────────────────────────────────────
    Route::middleware('permission:contacts.manage')->group(function () {
        Route::get('/crm/import', \App\Http\Livewire\Crm\BulkImportPage::class)->name('crm.import');
    });

    // ── Settings: Commission Splits & Lead Routing ────────────────────────────
    Route::middleware('permission:agency.manage')->group(function () {
        Route::get('/settings/commission-splits', \App\Http\Livewire\Settings\CommissionSplitPage::class)->name('settings.commission-splits');
        Route::get('/settings/lead-routing', \App\Http\Livewire\Settings\LeadRoutingPage::class)->name('settings.lead-routing');
    });

    // ── PDF Reports ───────────────────────────────────────────────────────────
    Route::get('/listings/{listing}/report/seller-pdf', [\App\Http\Controllers\Api\ReportController::class, 'sellerReport'])
        ->name('reports.seller-pdf')
        ->middleware('permission:listings.view_own');

    // ── Google Calendar OAuth ─────────────────────────────────────────────────
    Route::get('/integrations/google-calendar/connect', [\App\Http\Controllers\GoogleCalendarController::class, 'redirect'])
        ->name('google-calendar.redirect');
    Route::get('/integrations/google-calendar/disconnect', [\App\Http\Controllers\GoogleCalendarController::class, 'disconnect'])
        ->name('google-calendar.disconnect');

    // Team invitation accept
    Route::get('/invitations/{token}/accept', function (string $token) {
        $invitation = \App\Infrastructure\Persistence\Models\TeamInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        $user = auth()->user();

        if ($user && $user->email === $invitation->email) {
            // Scope permissions to this agency before assigning the role
            setPermissionsTeamId($invitation->agency_id);

            $invitation->update(['accepted_at' => now()]);

            // Update user's agency if they're joining a different one
            if ($user->agency_id !== $invitation->agency_id) {
                $user->update(['agency_id' => $invitation->agency_id, 'status' => 'active']);
            }

            $user->syncRoles([$invitation->role]);

            return redirect()->route('dashboard')->with('success', 'Welcome to the team!');
        }

        // Not logged in — send to register with token in session so RegisterPage can auto-accept
        return redirect()->route('register')->with('invitation_token', $token);
    })->name('invitations.accept');
});
