<?php

use Illuminate\Support\Facades\Route;

// Redirect root
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

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
    Route::get('/dashboard', \App\Http\Livewire\DashboardPage::class)->name('dashboard');
    Route::get('/dashboard-v2', \App\Http\Livewire\DashboardPageV2::class)->name('dashboard.v2');

    // CRM
    Route::get('/contacts', \App\Http\Livewire\Crm\ContactsPage::class)->name('crm.contacts');
    Route::get('/contacts/{contact}', \App\Http\Livewire\Crm\ContactDetailPage::class)->name('crm.contact.detail');
    Route::get('/pipeline', \App\Http\Livewire\Crm\PipelineBoard::class)->name('crm.pipeline');

    // Listings
    Route::get('/listings', \App\Http\Livewire\Listing\IndexPage::class)->name('listing.index');
    Route::get('/listings/{listing}', \App\Http\Livewire\Listing\ListingDetailPage::class)->name('listing.detail');

    // Marketing
    Route::get('/marketing/campaigns', \App\Http\Livewire\Marketing\CampaignListPage::class)->name('marketing.campaigns');
    Route::get('/marketing/campaign/new', \App\Http\Livewire\Marketing\CampaignWizard::class)->name('marketing.campaign.new');
    Route::get('/marketing/calendar', \App\Http\Livewire\Marketing\ContentCalendarPage::class)->name('marketing.calendar');

    // CRM — Phase 2
    Route::get('/pipeline/deals/{deal}', \App\Http\Livewire\Crm\DealDetailPage::class)->name('crm.deal.detail');

    // Viewings
    Route::get('/viewings/day', \App\Http\Livewire\Viewing\DayViewPage::class)->name('viewing.day');

    // Analytics / Intelligence
    Route::get('/analytics/scorecard', \App\Http\Livewire\Intelligence\AgentScorecardPage::class)->name('analytics.scorecard');
    Route::get('/analytics/listing-health', \App\Http\Livewire\Intelligence\ListingHealthDashboard::class)->name('analytics.listing-health');
    Route::get('/analytics/forecast', \App\Http\Livewire\Intelligence\RevenueForecastPage::class)->name('analytics.forecast');
    Route::get('/compliance/transactions', \App\Http\Livewire\Compliance\TransactionCenterPage::class)->name('compliance.transactions');
    Route::get('/compliance/transactions/{transaction}', \App\Http\Livewire\Compliance\TransactionDetailPage::class)->name('compliance.transaction.detail');
    Route::get('/compliance/attorneys', \App\Http\Livewire\Compliance\AttorneyPortalPage::class)->name('compliance.attorneys');
    Route::get('/finance/commissions', \App\Http\Livewire\Intelligence\CommissionLedgerPage::class)->name('finance.commissions');
    Route::get('/analytics/market-intelligence', \App\Http\Livewire\Intelligence\MarketIntelligencePage::class)->name('analytics.market-intelligence');
    Route::get('/training/skills-library', \App\Http\Livewire\Training\SkillsLibraryPage::class)->name('training.skills-library');
    Route::get('/training/role-play', \App\Http\Livewire\Training\RolePlayPage::class)->name('training.role-play');

    // Phase 4 — Training
    Route::get('/training', \App\Http\Livewire\Training\TrainingDashboardPage::class)->name('training.dashboard');
    Route::get('/training/objections', \App\Http\Livewire\Training\ObjectionHandlerPage::class)->name('training.objections');
    Route::get('/training/skills', \App\Http\Livewire\Training\SkillsLibraryPage::class)->name('training.skills');
    Route::get('/training/roleplay', \App\Http\Livewire\Training\RolePlayPage::class)->name('training.roleplay');

    // Phase 4 — Intelligence
    Route::get('/analytics/sentiment', \App\Http\Livewire\Intelligence\SentimentMonitoringPage::class)->name('analytics.sentiment');
    Route::get('/analytics/predictions', \App\Http\Livewire\Intelligence\PredictionDashboardPage::class)->name('analytics.predictions');

    // Phase 4 — Marketing
    Route::get('/marketing/meta-ads', \App\Http\Livewire\Marketing\MetaAdsPage::class)->name('marketing.meta-ads');
    Route::get('/marketing/whatsapp', \App\Http\Livewire\Marketing\WhatsAppPage::class)->name('marketing.whatsapp');
    Route::get('/ai-planner', \App\Http\Livewire\Ai\PlannerPage::class)->name('ai.planner');
    Route::get('/settings', \App\Http\Livewire\Settings\ProfilePage::class)->name('settings.profile');
    Route::get('/settings/two-factor', \App\Http\Livewire\Auth\TwoFactorSetupPage::class)->name('two-factor.setup');

    // Team invitation accept
    Route::get('/invitations/{token}/accept', function (string $token) {
        $invitation = \App\Infrastructure\Persistence\Models\TeamInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if (auth()->check() && auth()->user()->email === $invitation->email) {
            $invitation->update(['accepted_at' => now()]);
            auth()->user()->assignRole($invitation->role);
            return redirect()->route('dashboard')->with('success', 'You have joined the team!');
        }

        return redirect()->route('register')->with('invitation_token', $token);
    })->name('invitations.accept');
});
