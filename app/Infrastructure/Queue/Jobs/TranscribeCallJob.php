<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallTranscript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscribeCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    /**
     * Real estate vocabulary injected as a Whisper prompt to bias transcription.
     * Whisper uses this as prior context, improving accuracy for domain terms.
     */
    private const DOMAIN_VOCABULARY = <<<VOCAB
Real estate terms: listing, property, mortgage, bond, deposit, levy, rates, sectional title,
freehold, HOA, homeowners association, body corporate, estate agent, mandate, OTP, offer to purchase,
conveyancer, transfer duty, stamp duty, occupational rent, sectional plan, cadastral, cadastre,
title deed, FICA, SARS, deeds office, valuation, CMA, comparative market analysis, leaseback,
buy-to-let, short-let, Airbnb, yield, cap rate, ROI, return on investment, pre-qualification,
bond originator, FNB, Nedbank, Standard Bank, ABSA, Ooba, BetterBond, Rawson, Pam Golding, Seeff,
RE/MAX, Chas Everitt, Harcourts, Jawitz, Engel Völkers.
VOCAB;

    public function __construct(public readonly Call $call) {}

    public function handle(): void
    {
        if (! $this->call->recording_url) {
            Log::warning("TranscribeCallJob: call {$this->call->id} has no recording URL");
            return;
        }

        $started = microtime(true);

        $audioPath = $this->downloadRecording();

        try {
            $language   = $this->detectLanguage();
            $transcript = $this->transcribeWithWhisper($audioPath, $language);
            $segments   = $this->parseSpeakerSegments($transcript);

            CallTranscript::create([
                'call_id'            => $this->call->id,
                'full_text'          => $transcript['text'],
                'speaker_segments'   => $segments,
                'word_count'         => str_word_count($transcript['text']),
                'language'           => $transcript['language'] ?? $language,
                'whisper_model'      => 'whisper-1',
                'processing_seconds' => (int) (microtime(true) - $started),
            ]);

            SummariseCallJob::dispatch($this->call)->onQueue('ai');
        } finally {
            Storage::delete($audioPath);
        }
    }

    /**
     * Look up the preferred language configured for the agent's Twilio number.
     * Falls back to 'en' if not set.
     */
    private function detectLanguage(): string
    {
        if (! $this->call->twilio_number) {
            return 'en';
        }

        return \App\Infrastructure\Persistence\Models\AgentNumber::where(
            'twilio_number', $this->call->twilio_number,
        )->value('language') ?? 'en';
    }

    private function downloadRecording(): string
    {
        $path = "call-recordings/tmp/{$this->call->id}.mp3";

        $response = Http::withBasicAuth(
            config('services.twilio.sid'),
            config('services.twilio.token'),
        )->get($this->call->recording_url);

        Storage::put($path, $response->body());

        return $path;
    }

    private function transcribeWithWhisper(string $storagePath, string $language): array
    {
        $fullPath = Storage::path($storagePath);

        $response = Http::withToken(config('services.openai.api_key'))
            ->attach('file', fopen($fullPath, 'r'), basename($fullPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model'                   => 'whisper-1',
                'language'                => $language,
                'response_format'         => 'verbose_json',
                'timestamp_granularities' => ['segment'],
                // Domain vocabulary prompt improves accuracy for real estate terms
                'prompt' => self::DOMAIN_VOCABULARY,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Whisper API error: " . $response->body());
        }

        return $response->json();
    }

    private function parseSpeakerSegments(array $whisperResponse): array
    {
        $segments = $whisperResponse['segments'] ?? [];

        // Whisper-1 does not natively diarise; alternate speakers as heuristic.
        // Deepgram (Phase 3) handles true diarisation for live calls.
        return array_map(function (array $seg, int $index) {
            return [
                'speaker' => $index % 2 === 0 ? 'Agent' : 'Contact',
                'text'    => trim($seg['text']),
                'start'   => $seg['start'],
                'end'     => $seg['end'],
            ];
        }, $segments, array_keys($segments));
    }
}
