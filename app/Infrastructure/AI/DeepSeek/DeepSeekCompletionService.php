<?php

namespace App\Infrastructure\AI\DeepSeek;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekCompletionService implements AiCompletionServiceInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('ai.providers.deepseek.key', '');
        $this->model   = config('ai.providers.deepseek.model', 'deepseek-chat');
        $this->baseUrl = rtrim(config('ai.providers.deepseek.base_url', 'https://api.deepseek.com'), '/');
    }

    public function generate(string $systemPrompt, string $userPrompt, array $context = []): string
    {
        if (empty($this->apiKey)) {
            Log::warning('DeepSeek API key not set.');
            return 'AI response unavailable — API key not configured.';
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'       => $this->model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                    'temperature' => $context['temperature'] ?? 0.7,
                ]);

            if ($response->successful()) {
                return $this->sanitize($response->json('choices.0.message.content') ?? '');
            }

            Log::error('DeepSeek generate failed', ['status' => $response->status(), 'body' => $response->body()]);
            return 'AI generation failed — please try again.';
        } catch (\Exception $e) {
            Log::error('DeepSeek generate exception', ['error' => $e->getMessage()]);
            return 'AI error: ' . $e->getMessage();
        }
    }

    public function chat(array $messages, array $tools = []): array
    {
        if (empty($this->apiKey)) {
            Log::warning('DeepSeek API key not set.');
            return [
                'content'    => 'I am unable to respond — the DeepSeek API key is not configured.',
                'tool_calls' => null,
            ];
        }

        try {
            $payload = [
                'model'       => $this->model,
                'messages'    => $messages,
                'temperature' => 0.7,
            ];

            if (!empty($tools)) {
                $payload['tools']       = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->successful()) {
                $message = $response->json('choices.0.message');
                return [
                    'content'    => $this->sanitize($message['content'] ?? ''),
                    'tool_calls' => $message['tool_calls'] ?? null,
                ];
            }

            Log::error('DeepSeek chat failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [
                'content'    => 'Chat request failed — please try again.',
                'tool_calls' => null,
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek chat exception', ['error' => $e->getMessage()]);
            return [
                'content'    => 'Chat error: ' . $e->getMessage(),
                'tool_calls' => null,
            ];
        }
    }

    private function sanitize(string $text): string
    {
        // Strip invalid UTF-8 sequences so json_encode never throws
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
}
