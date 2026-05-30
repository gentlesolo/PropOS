<?php

namespace App\Infrastructure\AI;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OpenAiCompletionService implements AiCompletionServiceInterface
{
    private string $model;

    public function __construct()
    {
        // Default to gpt-4o, configurable in .env
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    public function generate(string $systemPrompt, string $userPrompt, array $context = []): string
    {
        $user = auth()->user();
        
        // 1. Check Rate/Token Limits
        if ($limitError = $this->checkLimits($user)) {
            return $limitError;
        }

        if (empty(config('openai.api_key'))) {
            // Log simulated usage
            $this->logUsage($user, $context['feature'] ?? 'unknown', $this->model, 100, 200, 10);
            return "This is a simulated AI response. (API Key not configured)";
        }

        $startTime = microtime(true);

        try {
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $context['temperature'] ?? 0.7,
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            
            $promptTokens = $response->usage->promptTokens ?? 0;
            $completionTokens = $response->usage->completionTokens ?? 0;

            // 2. Log AI Usage
            $this->logUsage($user, $context['feature'] ?? 'unknown', $this->model, $promptTokens, $completionTokens, $durationMs);

            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            Log::error('OpenAI Generation Failed', ['error' => $e->getMessage()]);
            return "This is a simulated AI response. (Error: " . $e->getMessage() . ")";
        }
    }

    public function chat(array $messages, array $tools = []): array
    {
        $user = auth()->user();
        
        // 1. Check Rate/Token Limits
        if ($limitError = $this->checkLimits($user)) {
            return [
                'content' => $limitError,
                'tool_calls' => null,
            ];
        }

        if (empty(config('openai.api_key'))) {
            // Log simulated usage
            $this->logUsage($user, 'chat', $this->model, 150, 250, 12);
            return [
                'content' => "I am operating in simulation mode. (API Key not configured)",
                'tool_calls' => null,
            ];
        }

        $startTime = microtime(true);

        try {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
            ];

            if (!empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $response = OpenAI::chat()->create($payload);
            $message = $response->choices[0]->message;

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            
            $promptTokens = $response->usage->promptTokens ?? 0;
            $completionTokens = $response->usage->completionTokens ?? 0;

            // 2. Log AI Usage
            $this->logUsage($user, 'chat', $this->model, $promptTokens, $completionTokens, $durationMs);

            return [
                'content' => $message->content,
                'tool_calls' => $message->toolCalls ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Chat Failed', ['error' => $e->getMessage()]);
            return [
                'content' => "I am operating in simulation mode. I received your message but cannot reach OpenAI.",
                'tool_calls' => null,
            ];
        }
    }

    private function checkLimits(?\App\Infrastructure\Persistence\Models\User $user): ?string
    {
        if (!$user) {
            return null;
        }

        $agency = $user->agency;
        if (!$agency) {
            return null;
        }

        $settings = $agency->settings ?? [];
        $dailyLimit = $settings['daily_token_limit'] ?? 100000; // default 100k tokens
        $monthlyLimit = $settings['monthly_token_limit'] ?? 1000000; // default 1M tokens

        // Calculate today's usage
        $todayUsage = \App\Infrastructure\Persistence\Models\AiUsageLog::where('agency_id', $agency->id)
            ->whereDate('created_at', Carbon::today())
            ->sum('total_tokens');

        if ($todayUsage >= $dailyLimit) {
            return "AI request blocked: Daily token limit reached for your agency ({$dailyLimit} tokens).";
        }

        // Calculate monthly usage
        $monthlyUsage = \App\Infrastructure\Persistence\Models\AiUsageLog::where('agency_id', $agency->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_tokens');

        if ($monthlyUsage >= $monthlyLimit) {
            return "AI request blocked: Monthly token limit reached for your agency ({$monthlyLimit} tokens).";
        }

        return null;
    }

    private function logUsage(
        ?\App\Infrastructure\Persistence\Models\User $user,
        string $feature,
        string $model,
        int $promptTokens,
        int $completionTokens,
        int $durationMs
    ): void {
        if (!$user || !$user->agency_id) {
            return;
        }

        $totalTokens = $promptTokens + $completionTokens;
        
        // gpt-4o pricing (rough estimate)
        $costEstimate = ($promptTokens * 0.000005) + ($completionTokens * 0.000015);

        try {
            \App\Infrastructure\Persistence\Models\AiUsageLog::create([
                'agency_id' => $user->agency_id,
                'user_id' => $user->id,
                'feature' => $feature,
                'provider' => 'openai',
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost_estimate' => $costEstimate,
                'duration_ms' => $durationMs,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log AI usage', ['error' => $e->getMessage()]);
        }
    }
}

