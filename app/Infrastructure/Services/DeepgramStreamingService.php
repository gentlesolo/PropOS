<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallTranscript;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;

class DeepgramStreamingService
{
    private string $apiKey;
    private string $wsUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepgram.api_key', '');
        $this->wsUrl  = 'wss://api.deepgram.com/v1/listen';
    }

    /**
     * Build the Deepgram streaming WebSocket URL with parameters.
     */
    public function buildStreamUrl(string $encoding = 'mulaw', int $sampleRate = 8000): string
    {
        $params = http_build_query([
            'model'               => 'nova-2',
            'encoding'            => $encoding,
            'sample_rate'         => $sampleRate,
            'channels'            => 2,
            'multichannel'        => 'true',
            'diarize'             => 'true',
            'smart_format'        => 'true',
            'interim_results'     => 'true',
            'utterance_end_ms'    => 1000,
            'language'            => 'en',
        ]);

        return "{$this->wsUrl}?{$params}";
    }

    /**
     * Return the Authorization header value for Deepgram WebSocket upgrade.
     */
    public function authHeader(): string
    {
        return "Token {$this->apiKey}";
    }

    /**
     * Parse a Deepgram transcript event and extract the useful segment.
     * Returns null for interim/empty results.
     */
    public function parseTranscriptEvent(array $event): ?array
    {
        $type = $event['type'] ?? null;

        if ($type !== 'Results') {
            return null;
        }

        $channel   = $event['channel'] ?? [];
        $alt       = $channel['alternatives'][0] ?? [];
        $transcript = trim($alt['transcript'] ?? '');

        if (empty($transcript)) {
            return null;
        }

        $isFinal   = ($event['is_final'] ?? false) === true;
        $channelIndex = $event['channel_index'][0] ?? 0;

        return [
            'text'       => $transcript,
            'speaker'    => $channelIndex === 0 ? 'Agent' : 'Contact',
            'is_final'   => $isFinal,
            'confidence' => $alt['confidence'] ?? 0,
            'words'      => $alt['words'] ?? [],
            'start'      => $event['start'] ?? 0,
            'duration'   => $event['duration'] ?? 0,
        ];
    }

    /**
     * Persist the final accumulated transcript after the call ends.
     */
    public function persistTranscript(Call $call, array $finalSegments): void
    {
        if (empty($finalSegments)) {
            return;
        }

        $fullText = implode(' ', array_column($finalSegments, 'text'));

        CallTranscript::updateOrCreate(
            ['call_id' => $call->id],
            [
                'full_text'        => $fullText,
                'speaker_segments' => $finalSegments,
                'word_count'       => str_word_count($fullText),
                'language'         => 'en',
                'whisper_model'    => 'deepgram-nova-2',
            ],
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }
}
