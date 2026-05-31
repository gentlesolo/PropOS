<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallSummary;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Services\MobileNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummariseCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(public readonly Call $call) {}

    public function handle(MobileNotificationService $notifier): void
    {
        $call = $this->call->load(['transcript', 'contact', 'agent']);

        if (! $call->transcript) {
            Log::warning("SummariseCallJob: call {$call->id} has no transcript yet");
            return;
        }

        $result = $this->callGpt($call->transcript->full_text, $call);

        $summary = CallSummary::create([
            'call_id'            => $call->id,
            'summary_text'       => $result['summary'],
            'key_points'         => $result['key_points'],
            'sentiment'          => $result['sentiment'],
            'sentiment_score'    => $result['sentiment_score'],
            'action_items'       => $result['action_items'],
            'suggested_next_step' => $result['suggested_next_step'],
            'gpt_model'          => 'gpt-4o',
        ]);

        $notifier->sendCallSummaryReady($call->agent, $call, $summary);

        // Auto-create a nurture sequence for warm/hot leads
        AutoNurtureFromCallJob::dispatch($this->call)->onQueue('default');
    }

    private function callGpt(string $transcript, Call $call): array
    {
        $contactName = $call->contact
            ? $call->contact->first_name . ' ' . $call->contact->last_name
            : 'the lead';

        $prompt = <<<PROMPT
You are an AI assistant for a real estate agency. Analyse this call transcript between an agent and {$contactName}.

Return a JSON object with exactly these fields:
- summary: string (3-5 sentences summarising the call)
- key_points: array of strings (bullet points, max 6)
- sentiment: one of "hot", "warm", "cold", "neutral" (lead's interest level)
- sentiment_score: integer 0-100 (0 = very cold, 100 = very hot)
- action_items: array of strings (concrete follow-up tasks the agent should do)
- suggested_next_step: string (one recommended next action)

Transcript:
{$transcript}
PROMPT;

        $response = Http::withToken(config('services.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o',
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => 'You are a real estate call analysis assistant. Always return valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("GPT API error: " . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        return json_decode($content, true) ?? $this->fallbackResult();
    }

    private function fallbackResult(): array
    {
        return [
            'summary'            => 'Call summary unavailable.',
            'key_points'         => [],
            'sentiment'          => 'neutral',
            'sentiment_score'    => 50,
            'action_items'       => [],
            'suggested_next_step' => 'Follow up with the lead.',
        ];
    }
}
