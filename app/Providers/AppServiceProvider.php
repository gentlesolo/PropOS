<?php

namespace App\Providers;

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

        $this->app->bind(
            \App\Domain\AI\Contracts\AiCompletionServiceInterface::class,
            \App\Infrastructure\AI\OpenAiCompletionService::class
        );

        $this->app->bind(
            \App\Domain\AI\Contracts\PredictionInterface::class,
            \App\Infrastructure\AI\OpenAiPredictionService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
