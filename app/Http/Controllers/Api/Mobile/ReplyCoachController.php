<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReplyCoachController extends Controller
{
    /**
     * Score a draft message before the agent sends it.
     * Returns quality score + suggested rewrite.
     */
    public function score(Request $request): JsonResponse
    {
        $request->validate([
            'draft'      => 'required|string|max:2000',
            'contact_id' => 'nullable|exists:contacts,id',
            'channel'    => 'required|in:whatsapp,sms,email',
            'context'    => 'nullable|string|max:1000',
        ]);

        $contact = $request->contact_id
            ? Contact::find($request->contact_id)
            : null;

        $result = $this->analyseDraft(
            draft: $request->input('draft'),
            channel: $request->input('channel'),
            contact: $contact,
            context: $request->input('context'),
        );

        return response()->json($result);
    }

    /**
     * Generate a full AI-written reply suggestion given conversation context.
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'last_message' => 'required|string|max:2000',
            'contact_id'   => 'nullable|exists:contacts,id',
            'channel'      => 'required|in:whatsapp,sms,email',
        ]);

        $contact = $request->contact_id
            ? Contact::find($request->contact_id)
            : null;

        $contactContext = $contact
            ? "{$contact->first_name} {$contact->last_name} (status: {$contact->status})"
            : 'a real estate lead';

        $channelNote = match ($request->input('channel')) {
            'sms'   => 'Keep it under 160 characters.',
            'email' => 'Use a professional, structured format.',
            default => 'Keep it conversational and friendly.',
        };

        $prompt = <<<PROMPT
You are a real estate agent. Write a warm, professional reply to this message from {$contactContext}.

Their message: "{$request->input('last_message')}"

{$channelNote}
Return a JSON object with:
- suggestion: string (the full reply text)
- tone: string (one word: warm/professional/urgent/empathetic)
PROMPT;

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(12)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o',
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => 'You are a professional real estate agent writing client messages.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens'  => 200,
            ]);

        if (! $response->successful()) {
            return response()->json(['suggestion' => null, 'tone' => null]);
        }

        $result = json_decode($response->json('choices.0.message.content'), true) ?? [];

        return response()->json($result);
    }

    private function analyseDraft(
        string $draft,
        string $channel,
        ?Contact $contact,
        ?string $context,
    ): array {
        $contactContext = $contact
            ? "{$contact->first_name} {$contact->last_name} (status: {$contact->status})"
            : 'a real estate lead';

        $channelNote = match ($channel) {
            'sms'   => 'Note: SMS should be under 160 characters. Current length: ' . mb_strlen($draft),
            'email' => 'Note: Email can be longer but should be scannable.',
            default => 'Note: WhatsApp — keep it conversational.',
        };

        $prompt = <<<PROMPT
You are a real estate communication coach. Score this draft message to {$contactContext}.

{$channelNote}
Context: {$context}

Draft: "{$draft}"

Return a JSON object with:
- score: integer 0-100 (overall quality)
- tone_score: integer 0-100 (appropriate tone for channel and lead stage)
- clarity_score: integer 0-100 (clear and easy to understand)
- persuasion_score: integer 0-100 (motivates a response or action)
- issues: array of strings (specific problems, max 3)
- rewrite: string (improved version of the message)
- rewrite_reason: string (one sentence explaining the main improvement)
PROMPT;

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(12)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o',
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => 'You are a real estate communication coach. Return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens'  => 400,
            ]);

        if (! $response->successful()) {
            return ['score' => null, 'issues' => [], 'rewrite' => null];
        }

        return json_decode($response->json('choices.0.message.content'), true)
            ?? ['score' => null, 'issues' => [], 'rewrite' => null];
    }
}
