<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InCallHintsController extends Controller
{
    /**
     * Generate real-time AI hints based on the current live transcript.
     * The mobile app polls this every 30 seconds during an active call.
     */
    public function hints(Request $request, Call $call): JsonResponse
    {
        abort_unless(
            $call->agent_id === $request->user()->id,
            403,
        );

        $request->validate([
            'transcript_so_far' => 'required|string|max:8000',
        ]);

        $cacheKey = "call_hints_{$call->id}_" . md5($request->input('transcript_so_far'));

        $hints = Cache::remember($cacheKey, 25, function () use ($call, $request) {
            return $this->generateHints(
                $request->input('transcript_so_far'),
                $call->contact,
            );
        });

        return response()->json($hints);
    }

    /**
     * Return the Pusher channel name for a call's live transcript.
     */
    public function channel(Call $call): JsonResponse
    {
        abort_unless(
            $call->agent_id === request()->user()->id,
            403,
        );

        return response()->json([
            'channel'       => $call->live_transcript_channel ?? "call.{$call->id}",
            'stream_active' => (bool) $call->live_transcript_channel,
        ]);
    }

    /**
     * Flag a call for manager coaching review.
     */
    public function flag(Request $request, Call $call): JsonResponse
    {
        $request->validate([
            'coaching_notes' => 'nullable|string|max:1000',
        ]);

        $call->update([
            'flagged_for_coaching' => true,
            'coaching_notes'       => $request->input('coaching_notes'),
            'flagged_by'           => $request->user()->id,
        ]);

        return response()->json(['flagged' => true]);
    }

    private function generateHints(string $transcript, ?Contact $contact): array
    {
        $contactContext = $contact
            ? "Contact: {$contact->first_name} {$contact->last_name}, Status: {$contact->status}"
            : 'Contact: unknown';

        $prompt = <<<PROMPT
You are a real estate sales coach. Analyse this live call transcript and provide concise, actionable hints for the agent RIGHT NOW.

{$contactContext}

Transcript so far:
{$transcript}

Return a JSON object with:
- objection_detected: string|null (the main objection or concern the lead raised, if any)
- suggested_response: string|null (one concise response the agent can use immediately)
- talking_points: array of strings (2-3 relevant points to bring up next)
- urgency_signal: boolean (true if the lead shows strong intent to act soon)
- warning: string|null (anything the agent should avoid saying based on the conversation)

Keep all text under 20 words per item. Be direct and immediately usable.
PROMPT;

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(10)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o',
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => 'You are a real estate sales coaching AI. Return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
                'max_tokens'  => 300,
            ]);

        if (! $response->successful()) {
            return $this->emptyHints();
        }

        return json_decode($response->json('choices.0.message.content'), true)
            ?? $this->emptyHints();
    }

    private function emptyHints(): array
    {
        return [
            'objection_detected' => null,
            'suggested_response' => null,
            'talking_points'     => [],
            'urgency_signal'     => false,
            'warning'            => null,
        ];
    }
}
