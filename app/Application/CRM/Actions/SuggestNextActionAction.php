<?php

namespace App\Application\CRM\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;

class SuggestNextActionAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function forContact(Contact $contact): string
    {
        $lastActivity = $contact->activities()->first();
        $daysSinceContact = $contact->last_contacted_at
            ? (int) now()->diffInDays($contact->last_contacted_at)
            : null;

        $recentSentiments = $contact->activities()
            ->whereNotNull('metadata->sentiment')
            ->latest('occurred_at')
            ->limit(3)
            ->get()
            ->pluck('metadata.sentiment')
            ->implode(', ');

        $contextParts = [
            "Name: {$contact->full_name}",
            "Type: {$contact->type}",
            "Status: {$contact->status}",
            "Intent score: {$contact->intent_score}/100",
            $daysSinceContact !== null
                ? "Last contacted: {$daysSinceContact} day(s) ago"
                : "Never contacted",
            $lastActivity
                ? "Last activity: {$lastActivity->type}" . ($lastActivity->subject ? " ({$lastActivity->subject})" : '')
                : "No activities logged",
        ];

        if ($recentSentiments) {
            $contextParts[] = "Recent sentiment: {$recentSentiments}";
        }

        $prefs = $contact->preferences ?? [];
        if (!empty($prefs['areas'])) {
            $contextParts[] = "Preferred areas: " . implode(', ', (array) $prefs['areas']);
        }

        $systemPrompt = "You are a smart CRM assistant for a real estate agency. Based on the contact profile, suggest exactly ONE specific, actionable next step the agent should take to move this relationship forward. Reply in 1-2 sentences maximum. Be direct and practical.";

        $userPrompt = implode('. ', $contextParts);

        return trim($this->ai->generate($systemPrompt, $userPrompt, [
            'feature' => 'next_action_suggestion',
            'temperature' => 0.4,
        ]));
    }

    public function forDeal(Deal $deal): string
    {
        $deal->loadMissing(['stage', 'contact', 'checklistItems', 'activities']);

        $lastActivity = $deal->activities->sortByDesc('occurred_at')->first();
        $daysSinceActivity = $lastActivity
            ? (int) now()->diffInDays($lastActivity->occurred_at)
            : null;

        $checklistDone = $deal->checklistItems->where('completed', true)->count();
        $checklistTotal = $deal->checklistItems->count();

        $allDone = $checklistTotal > 0 && $checklistDone === $checklistTotal;

        $contextParts = [
            "Deal: {$deal->title}",
            "Value: ₦" . number_format((float) $deal->value),
            "Stage: {$deal->stage?->name ?? 'No stage'}",
            "Momentum: {$deal->momentum_score}/100 ({$deal->momentumLabel})",
            $daysSinceActivity !== null
                ? "Last activity: {$daysSinceActivity} day(s) ago"
                : "No activities logged",
            "Checklist: {$checklistDone}/{$checklistTotal} items complete" . ($allDone ? ' (all done)' : ''),
        ];

        if ($deal->contact) {
            $contextParts[] = "Contact intent score: {$deal->contact->intent_score}/100";
        }

        $systemPrompt = "You are a smart CRM assistant for a real estate agency. Based on this deal's status, suggest exactly ONE specific next action to progress the deal. Reply in 1-2 sentences. Be direct and concrete.";

        $userPrompt = implode('. ', $contextParts);

        return trim($this->ai->generate($systemPrompt, $userPrompt, [
            'feature' => 'next_action_suggestion',
            'temperature' => 0.4,
        ]));
    }
}
