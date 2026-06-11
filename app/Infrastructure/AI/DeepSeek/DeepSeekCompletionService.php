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
            $payload = [
                'model'       => $this->model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => $context['temperature'] ?? 0.7,
            ];

            $tempFile = tempnam(sys_get_temp_dir(), 'ds_payload');
            file_put_contents($tempFile, json_encode($payload));

            $command = sprintf(
                'curl -s -X POST %s -H "Content-Type: application/json" -H "Authorization: Bearer %s" -d "@%s"',
                escapeshellarg("{$this->baseUrl}/chat/completions"),
                escapeshellcmd($this->apiKey),
                $tempFile
            );

            $output = shell_exec($command);
            @unlink($tempFile);

            if (!$output) {
                return 'AI generation failed — no response from curl.';
            }

            $data = json_decode($output, true);
            if (isset($data['choices'][0]['message']['content'])) {
                return $this->sanitize($data['choices'][0]['message']['content']);
            }

            Log::error('DeepSeek generate failed', ['body' => $output]);
            return 'AI error: ' . ($data['error']['message'] ?? 'Unknown API error');
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

            $tempFile = tempnam(sys_get_temp_dir(), 'ds_chat_payload');
            file_put_contents($tempFile, json_encode($payload));

            $command = sprintf(
                'curl -s -X POST %s -H "Content-Type: application/json" -H "Authorization: Bearer %s" -d "@%s"',
                escapeshellarg("{$this->baseUrl}/chat/completions"),
                escapeshellcmd($this->apiKey),
                $tempFile
            );

            $output = shell_exec($command);
            @unlink($tempFile);

            if (!$output) {
                return [
                    'content'    => 'Chat request failed — no response from curl.',
                    'tool_calls' => null,
                ];
            }

            $data = json_decode($output, true);
            if (isset($data['choices'][0]['message'])) {
                $message = $data['choices'][0]['message'];
                return [
                    'content'    => $this->sanitize($message['content'] ?? ''),
                    'tool_calls' => $message['tool_calls'] ?? null,
                ];
            }

            Log::error('DeepSeek chat failed', ['body' => $output]);
            return [
                'content'    => 'Chat error: ' . ($data['error']['message'] ?? 'Unknown API error'),
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
