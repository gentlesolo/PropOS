<?php

namespace App\Infrastructure\AI\DeepSeek;

use App\Domain\AI\Contracts\TextGenerationInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekTextGeneration implements TextGenerationInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('ai.providers.deepseek.key');
        $this->model = config('ai.providers.deepseek.model', 'deepseek-chat');
        $this->baseUrl = config('ai.providers.deepseek.base_url', 'https://api.deepseek.com');
    }

    public function generate(string $prompt, array $options = []): string
    {
        if (empty($this->apiKey)) {
            Log::warning('DeepSeek API Key is missing. Returning fallback mock response.');
            return "[Mock DeepSeek Response for prompt: '{$prompt}']";
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => $options['temperature'] ?? 0.7,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? '';
            }

            Log::error('DeepSeek generation failed: ' . $response->body());
            return "[Error: DeepSeek generation failed]";
        } catch (\Exception $e) {
            Log::error('DeepSeek generation exception: ' . $e->getMessage());
            return "[Error: DeepSeek exception]";
        }
    }

    public function generateStructured(string $prompt, array $schema, array $options = []): array
    {
        if (empty($this->apiKey)) {
            Log::warning('DeepSeek API Key is missing. Returning fallback structured mock response.');
            return ['status' => 'mock', 'prompt' => $prompt];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => $options['temperature'] ?? 0.2,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content') ?? '{}';
                return json_decode($content, true) ?: [];
            }

            Log::error('DeepSeek structured generation failed: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('DeepSeek structured generation exception: ' . $e->getMessage());
            return [];
        }
    }

    public function stream(string $prompt, array $options = []): iterable
    {
        if (empty($this->apiKey)) {
            yield "[DeepSeek API key not configured. Set DEEPSEEK_API_KEY in .env]";
            return;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->withOptions(['stream' => true])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'stream' => true,
                ]);

            $body = $response->getBody();

            while (! $body->eof()) {
                $line = $this->readStreamLine($body);

                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = ltrim($line, 'data: ');

                if ($data === '[DONE]') {
                    return;
                }

                $decoded = json_decode($data, true);
                $token = $decoded['choices'][0]['delta']['content'] ?? null;

                if ($token !== null) {
                    yield $token;
                }
            }
        } catch (\Exception $e) {
            Log::error('DeepSeek stream exception: ' . $e->getMessage());
            yield "[Stream error: {$e->getMessage()}]";
        }
    }

    private function readStreamLine($body): string
    {
        $line = '';
        while (! $body->eof()) {
            $char = $body->read(1);
            if ($char === "\n") {
                return rtrim($line);
            }
            $line .= $char;
        }
        return rtrim($line);
    }
}
