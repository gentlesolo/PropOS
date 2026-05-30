<?php

namespace App\Application\CRM\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;

class GenerateFollowUpMessageAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    /**
     * Generate a personalized follow-up message for a contact.
     *
     * @return array{subject: string, body: string}
     */
    public function execute(Contact $contact, string $stepType, ?Deal $deal = null): array
    {
        $contextParts = [
            "Contact name: {$contact->full_name}",
            "Contact type: {$contact->type}",
            "Status: {$contact->status}",
            "Intent score: {$contact->intent_score}/100",
        ];

        if ($contact->company) {
            $contextParts[] = "Company: {$contact->company}";
        }

        if ($deal) {
            $contextParts[] = "Deal: {$deal->title}";
            $contextParts[] = "Deal stage: {$deal->stage?->name ?? 'unknown'}";
            $contextParts[] = "Deal value: ₦" . number_format((float) $deal->value);
        }

        $prefs = $contact->preferences ?? [];
        if (!empty($prefs['areas'])) {
            $contextParts[] = "Preferred areas: " . implode(', ', (array) $prefs['areas']);
        }
        if (!empty($prefs['max_budget'])) {
            $contextParts[] = "Budget: up to ₦" . number_format((float) $prefs['max_budget']);
        }
        if (!empty($prefs['property_types'])) {
            $contextParts[] = "Property types: " . implode(', ', (array) $prefs['property_types']);
        }

        if ($contact->last_contacted_at) {
            $daysSince = (int) now()->diffInDays($contact->last_contacted_at);
            $contextParts[] = "Last contacted: {$daysSince} day(s) ago";
        }

        $context = implode('. ', $contextParts);

        $systemPrompt = <<<PROMPT
You are an expert real estate agent writing professional, warm, and concise follow-up messages.
Return ONLY a valid JSON object with exactly two keys: "subject" (a short email subject line) and "body" (the message body).
Use {{first_name}} as the placeholder for the contact's first name.
Write naturally — do not use brackets, placeholders other than {{first_name}}, or filler phrases like "I hope this finds you well".
Keep the body under 120 words.
PROMPT;

        $channelContext = match ($stepType) {
            'email' => 'a professional email',
            'sms' => 'a brief, friendly SMS text (under 60 words)',
            'call' => 'a short call script opener',
            'task' => 'an internal task note for the agent',
            default => 'a follow-up message',
        };

        $userPrompt = "Write {$channelContext} for: {$context}";

        $raw = $this->ai->generate($systemPrompt, $userPrompt, [
            'feature' => 'follow_up_generation',
            'temperature' => 0.75,
        ]);

        // Strip markdown code fences if AI wraps output in them
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw);

        $parsed = json_decode(trim($raw), true);

        if (is_array($parsed) && isset($parsed['subject'], $parsed['body'])) {
            return [
                'subject' => (string) $parsed['subject'],
                'body' => (string) $parsed['body'],
            ];
        }

        return [
            'subject' => "Follow-up with {$contact->full_name}",
            'body' => $raw,
        ];
    }
}
