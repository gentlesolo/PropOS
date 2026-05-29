<?php

namespace App\Infrastructure\AI;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

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
        try {
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $context['temperature'] ?? 0.7,
            ]);

            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            Log::error('OpenAI Generation Failed', ['error' => $e->getMessage()]);
            // For Phase 2 demo purposes, return a mock if API key isn't set or fails
            return "This is a simulated AI response. (Error: " . $e->getMessage() . ")";
        }
    }

    public function chat(array $messages, array $tools = []): array
    {
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
}
