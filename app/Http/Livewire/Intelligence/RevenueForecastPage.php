<?php

namespace App\Http\Livewire\Intelligence;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\PipelineStage;
use Carbon\Carbon;
use Livewire\Component;

class RevenueForecastPage extends Component
{
    public string $timeframe = '90_days';

    // Scenario planner
    public bool $showScenario = false;
    public int $scenario_extra_deals = 2;
    public int $scenario_avg_value = 0;

    // AI insights (lazy-loaded)
    public array $aiInsights = [];
    public bool $generatingInsights = false;

    public function mount(): void
    {
        $this->scenario_avg_value = (int) (Deal::where('agency_id', auth()->user()->agency_id)->avg('value') ?? 50000000);
    }

    public function generateInsights(AiCompletionServiceInterface $ai): void
    {
        $this->generatingInsights = true;
        $data = $this->forecastData;

        $prompt = implode("\n", [
            "Revenue forecast data:",
            "- Active pipeline: ₦" . number_format($data['total_pipeline']),
            "- Weighted forecast: ₦" . number_format($data['weighted_forecast']),
            "- Open deals: {$data['deals_count']}",
            "- Target: ₦" . number_format($data['target']),
            "- Gap to target: ₦" . number_format($data['gap']),
            "- Confidence: {$data['confidence']}%",
            "- Timeframe: {$this->timeframe}",
            "Provide 3 specific, actionable insights for closing the gap. Return as a JSON array of strings.",
        ]);

        $raw = $ai->generate(
            "You are a real estate revenue forecasting expert. Give concise, actionable pipeline acceleration insights.",
            $prompt
        );

        $parsed = json_decode($raw, true);
        $this->aiInsights = is_array($parsed) ? $parsed : [
            "Focus on the highest-weighted deals in your pipeline first.",
            "Schedule follow-ups for all deals stagnant for 7+ days.",
            "Consider a price review for listings that haven't generated viewings in 14 days.",
        ];
        $this->generatingInsights = false;
    }

    public function getForecastDataProperty(): array
    {
        $agencyId = auth()->user()->agency_id;
        $days = (int) str_replace('_days', '', $this->timeframe);

        $deals = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->with('stage')
            ->get();

        $totalPipeline = 0;
        $weightedForecast = 0;
        $dealStages = [];

        foreach ($deals as $deal) {
            $probability = min(95, max(5, $deal->momentum_score));
            $totalPipeline += $deal->value;
            $weightedForecast += $deal->value * ($probability / 100);

            $name = $deal->stage?->name ?? 'Unknown';
            $dealStages[$name] ??= ['count' => 0, 'value' => 0, 'weighted' => 0, 'order' => $deal->stage?->order ?? 99];
            $dealStages[$name]['count']++;
            $dealStages[$name]['value'] += $deal->value;
            $dealStages[$name]['weighted'] += $deal->value * ($probability / 100);
        }

        // Historical won revenue as target baseline
        $wonThisYear = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', true))
            ->whereYear('updated_at', now()->year)
            ->sum('value');

        $target = max(500_000_000, $wonThisYear * 2);
        $gap = max(0, $target - $weightedForecast);

        // Scenario: what if we close N more deals?
        $scenarioUpside = $this->showScenario
            ? $this->scenario_extra_deals * $this->scenario_avg_value
            : 0;

        return [
            'total_pipeline' => $totalPipeline,
            'weighted_forecast' => $weightedForecast,
            'deals_count' => $deals->count(),
            'stages' => collect($dealStages)->sortBy('order')->toArray(),
            'target' => $target,
            'gap' => $gap,
            'confidence' => $deals->count() > 0 ? min(98, round(($weightedForecast / max(1, $totalPipeline)) * 100)) : 0,
            'scenario_upside' => $scenarioUpside,
            'scenario_forecast' => $weightedForecast + $scenarioUpside,
        ];
    }

    public function render()
    {
        return view('livewire.intelligence.revenue-forecast-page', [
            'data' => $this->forecastData,
        ])->layout('layouts.app');
    }
}
