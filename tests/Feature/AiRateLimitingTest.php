<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\AiUsageLog;
use App\Infrastructure\AI\OpenAiCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ai rate limiting blocks request when daily or monthly limits are exceeded', function () {
    $agency = Agency::factory()->create([
        'settings' => [
            'daily_token_limit' => 1000,
            'monthly_token_limit' => 5000,
        ]
    ]);

    $user = User::factory()->create(['agency_id' => $agency->id]);
    $this->actingAs($user);

    $service = app(OpenAiCompletionService::class);

    // Call under budget
    $result1 = $service->generate('System', 'User', ['feature' => 'test']);
    expect($result1)->not->toContain('blocked');

    // Create log that exceeds daily limit (1000 tokens)
    AiUsageLog::create([
        'agency_id' => $agency->id,
        'user_id' => $user->id,
        'feature' => 'test',
        'provider' => 'openai',
        'model' => 'gpt-4o',
        'prompt_tokens' => 500,
        'completion_tokens' => 600,
        'total_tokens' => 1100,
    ]);

    // Next call should be blocked
    $result2 = $service->generate('System', 'User', ['feature' => 'test']);
    expect($result2)->toContain('AI request blocked: Daily token limit reached');
});
