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
            $transcript = $this->transcribeWithDeepgram($audioPath, $language);
            $segments   = $this->parseSpeakerSegments($transcript);

            $fullText = $transcript['results']['channels'][0]['alternatives'][0]['transcript'] ?? '';

            CallTranscript::create([
                'call_id'            => $this->call->id,
                'full_text'          => $fullText,
                'speaker_segments'   => $segments,
                'word_count'         => str_word_count($fullText),
                'language'           => $language,
                'whisper_model'      => 'deepgram-nova-2',
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

    private function transcribeWithDeepgram(string $storagePath, string $language): array
    {
        $fullPath = Storage::path($storagePath);

        $url = "https://api.deepgram.com/v1/listen?" . http_build_query([
            'model'        => 'nova-2',
            'smart_format' => 'true',
            'diarize'      => 'true',
            'utterances'   => 'true',
            'language'     => $language,
        ]);

        $response = Http::withToken(config('services.deepgram.api_key'))
            ->withBody(file_get_contents($fullPath), 'audio/mpeg')
            ->post($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Deepgram API error: " . $response->body());
        }

        return $response->json();
    }

    private function parseSpeakerSegments(array $deepgramResponse): array
    {
        $utterances = $deepgramResponse['results']['utterances'] ?? [];

        return array_map(function (array $utterance) {
            $speakerId = $utterance['speaker'] ?? 0;
            return [
                'speaker' => $speakerId === 0 ? 'Agent' : 'Contact',
                'text'    => trim($utterance['transcript'] ?? ''),
                'start'   => $utterance['start'] ?? 0,
                'end'     => $utterance['end'] ?? 0,
            ];
        }, $utterances);
    }
}
