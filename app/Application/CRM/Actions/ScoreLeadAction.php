<?php

namespace App\Application\CRM\Actions;

use App\Domain\AI\Contracts\PredictionInterface;
use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Support\Facades\Log;

class ScoreLeadAction
{
    public function __construct(private PredictionInterface $prediction) {}

    public function execute(Contact $contact): int
    {
        $ruleScore = $this->ruleBasedScore($contact);

        try {
            $activityCount = $contact->activities()->count();
            $daysSinceContact = $contact->last_contacted_at
                ? (int) now()->diffInDays($contact->last_contacted_at)
                : 999;

            $aiScore = $this->prediction->predictScore([
                'contact_type'      => $contact->type,
                'status'            => $contact->status,
                'has_email'         => $contact->email ? 'yes' : 'no',
                'has_phone'         => $contact->phone ? 'yes' : 'no',
                'has_company'       => $contact->company ? 'yes' : 'no',
                'has_preferences'   => !empty($contact->preferences) ? 'yes' : 'no',
                'activity_count'    => $activityCount,
                'days_since_contact' => $daysSinceContact,
                'current_rule_score' => $ruleScore,
            ]);

            // 60% AI weight, 40% rule-based for a balanced, stable score
            $score = (int) round(($aiScore * 0.6) + ($ruleScore * 0.4));
        } catch (\Throwable $e) {
            Log::warning('AI lead scoring failed, using rule-based score', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
            $score = $ruleScore;
        }

        $score = max(0, min(100, $score));
        $contact->update(['intent_score' => $score]);

        return $score;
    }

    private function ruleBasedScore(Contact $contact): int
    {
        $score = 0;

        if ($contact->email) $score += 10;
        if ($contact->phone) $score += 10;
        if ($contact->company) $score += 5;

        $score += match ($contact->type) {
            'buyer', 'seller' => 20,
            'investor' => 25,
            'landlord', 'tenant' => 15,
            default => 10,
        };

        $activityCount = $contact->activities()->count();
        $score += min($activityCount * 5, 25);

        if ($contact->last_contacted_at && $contact->last_contacted_at->gte(now()->subDays(7))) {
            $score += 10;
        }

        $score += match ($contact->status) {
            'qualified' => 15,
            'active' => 10,
            'nurturing' => 5,
            default => 0,
        };

        return min(100, $score);
    }
}
