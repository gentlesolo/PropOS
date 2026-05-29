<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Contact;

class ScoreLeadAction
{
    public function execute(Contact $contact): int
    {
        $score = 0;

        // Contact completeness
        if ($contact->email) $score += 10;
        if ($contact->phone) $score += 10;
        if ($contact->company) $score += 5;

        // Type-based base score
        $score += match ($contact->type) {
            'buyer', 'seller' => 20,
            'investor' => 25,
            'landlord', 'tenant' => 15,
            default => 10,
        };

        // Engagement signals
        $activityCount = $contact->activities()->count();
        $score += min($activityCount * 5, 25);

        // Recent contact bonus
        if ($contact->last_contacted_at && $contact->last_contacted_at->gte(now()->subDays(7))) {
            $score += 10;
        }

        // Status-based score
        $score += match ($contact->status) {
            'qualified' => 15,
            'active' => 10,
            'nurturing' => 5,
            default => 0,
        };

        $score = min(100, $score);

        $contact->update(['intent_score' => $score]);

        return $score;
    }
}
