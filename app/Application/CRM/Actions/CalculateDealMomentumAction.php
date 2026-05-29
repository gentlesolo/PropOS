<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Deal;

class CalculateDealMomentumAction
{
    public function execute(Deal $deal): int
    {
        $score = 100;

        // Decay based on days since last activity
        $daysSinceActivity = $deal->updated_at->diffInDays(now());
        $score -= min(40, $daysSinceActivity * 2);

        // Boost for high-intent contact
        if ($deal->contact && $deal->contact->intent_score >= 70) {
            $score += 10;
        }

        // Penalty for being stuck in early stages too long
        $stageOrder = $deal->stage?->order ?? 1;
        $totalStages = \App\Infrastructure\Persistence\Models\PipelineStage::where('pipeline_type', $deal->stage?->pipeline_type ?? 'sale')->count();
        $progressRatio = $totalStages > 0 ? $stageOrder / $totalStages : 0;

        if ($daysSinceActivity > 14 && $progressRatio < 0.5) {
            $score -= 15; // Stale and early-stage
        }

        // Boost for recent activity count
        $recentActivities = $deal->activities()
            ->where('occurred_at', '>=', now()->subDays(7))
            ->count();
        $score += min(20, $recentActivities * 5);

        // Won/Lost deals get fixed scores
        if ($deal->stage?->is_won) {
            return 100;
        }
        if ($deal->stage?->is_lost) {
            return 0;
        }

        $score = max(0, min(100, $score));
        $deal->update(['momentum_score' => $score]);

        return $score;
    }
}
