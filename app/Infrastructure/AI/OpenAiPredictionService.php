<?php

namespace App\Infrastructure\AI;

use App\Domain\AI\Contracts\PredictionInterface;
use App\Domain\AI\Contracts\AiCompletionServiceInterface;

class OpenAiPredictionService implements PredictionInterface
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function predictScore(array $features): int
    {
        $featureStr = collect($features)
            ->map(fn($v, $k) => "{$k}: {$v}")
            ->implode(', ');

        $raw = $this->ai->generate(
            "You are a predictive scoring model for a real estate CRM. Return only a single integer between 0 and 100. No explanation.",
            "Based on these features, predict the lead conversion probability score (0-100): {$featureStr}"
        );

        $score = (int) preg_replace('/\D/', '', trim($raw));
        return max(0, min(100, $score ?: 50));
    }

    public function predictTimeSeries(array $history, int $steps): array
    {
        $historyStr = implode(', ', array_map(fn($v, $i) => "Month {$i}: ₦{$v}", $history, array_keys($history)));

        $raw = $this->ai->generate(
            "You are a time-series forecasting model. Return only a JSON array of {$steps} predicted numeric values. No explanation, no text — just the array.",
            "Predict the next {$steps} months of revenue based on this history: {$historyStr}. Return as JSON array of numbers."
        );

        $parsed = json_decode(trim($raw), true);
        if (is_array($parsed) && count($parsed) === $steps) {
            return array_map('intval', $parsed);
        }

        // Fallback: linear extrapolation
        $n = count($history);
        $last = end($history) ?: 0;
        $avg_growth = $n > 1 ? ($last - reset($history)) / ($n - 1) : 0;
        $result = [];
        for ($i = 1; $i <= $steps; $i++) {
            $result[] = max(0, (int) ($last + $avg_growth * $i));
        }
        return $result;
    }
}
