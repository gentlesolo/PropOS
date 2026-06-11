<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Infrastructure\Tenancy\TenantResolver::class, function ($app) {
            return new \App\Infrastructure\Tenancy\TenantResolver();
        });

        $this->app->singleton(\App\Infrastructure\AI\AiServiceManager::class, function ($app) {
            return new \App\Infrastructure\AI\AiServiceManager();
        });

        $this->app->bind(\App\Domain\AI\Contracts\TextGenerationInterface::class, function ($app) {
            return $app->make(\App\Infrastructure\AI\AiServiceManager::class)->textGeneration();
        });

        $this->app->bind(\App\Domain\AI\Contracts\AiCompletionServiceInterface::class, function () {
            return match (config('ai.default', 'openai')) {
                'deepseek' => new \App\Infrastructure\AI\DeepSeek\DeepSeekCompletionService(),
                default    => new \App\Infrastructure\AI\OpenAiCompletionService(),
            };
        });

        $this->app->bind(
            \App\Domain\AI\Contracts\PredictionInterface::class,
            \App\Infrastructure\AI\OpenAiPredictionService::class
        );

        $this->app->bind(
            \App\Infrastructure\Payment\Contracts\PaymentGatewayInterface::class,
            \App\Infrastructure\Payment\PayFastGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Public API rate limits (keyed by API token, fallback to IP)
        RateLimiter::for('public-api', function (Request $request) {
            $key = $request->bearerToken() ?? $request->query('api_key') ?? $request->ip();
            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('public-leads', function (Request $request) {
            // Tighter limit for lead submission to prevent form spam
            $key = $request->bearerToken() ?? $request->ip();
            return Limit::perMinute(10)->by($key)->response(function () {
                return response()->json(['error' => 'Too many submissions. Please wait a moment.'], 429);
            });
        });

        RateLimiter::for('public-bookings', function (Request $request) {
            $key = $request->bearerToken() ?? $request->ip();
            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json(['error' => 'Too many booking requests. Please wait a moment.'], 429);
            });
        });

        RateLimiter::for('contact-api', function (Request $request) {
            $key = $request->bearerToken() ?? $request->ip();
            return Limit::perMinute(120)->by($key);
        });

        \App\Infrastructure\Persistence\Models\Listing::observe(
            \App\Infrastructure\Persistence\Observers\ListingObserver::class
        );

        \App\Infrastructure\Persistence\Models\Viewing::observe(
            \App\Infrastructure\Persistence\Observers\ViewingObserver::class
        );

        \App\Infrastructure\Persistence\Models\Contact::observe(
            \App\Infrastructure\Persistence\Observers\ContactObserver::class
        );

        View::composer('*', function ($view) {
            $symbol = '₦';
            if (auth()->check()) {
                $symbol = auth()->user()->agency?->currency_symbol ?? '₦';
            }
            $view->with('currencySymbol', $symbol);
        });
    }
}
