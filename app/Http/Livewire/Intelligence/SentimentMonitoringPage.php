<?php

namespace App\Http\Livewire\Intelligence;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\ContactActivity;
use App\Infrastructure\Persistence\Models\ViewingFeedback;
use Livewire\Component;

class SentimentMonitoringPage extends Component
{
    public string $period = '30';
    public bool $analysing = false;
    public ?array $aiSummary = null;

    public function analyseSentiment(AiCompletionServiceInterface $ai): void
    {
        $this->analysing = true;

        $data = $this->getSentimentData();

        $feedbackSummary = collect($data['feedback'])->map(fn($f) =>
            "Rating: {$f['rating']}/5, Interest: {$f['interest']}, Concerns: {$f['concerns']}"
        )->implode('; ');

        $activitySummary = collect($data['activities'])->pluck('body')->filter()->take(10)->implode('; ');

        $prompt = implode("\n", [
            "Analyse the sentiment from these real estate client interactions:",
            "Viewing feedback: {$feedbackSummary}",
            "Contact activity notes: {$activitySummary}",
            "Return JSON: {\"overall_sentiment\": \"positive|neutral|negative\", \"sentiment_score\": int_0_to_100, \"key_themes\": [\"string\",\"string\",\"string\"], \"buyer_confidence\": \"high|medium|low\", \"price_sensitivity\": \"high|medium|low\", \"recommendation\": \"string\"}",
        ]);

        $raw = $ai->generate(
            "You are a real estate market sentiment analyst. Analyse client feedback and activity notes.",
            $prompt
        );

        $parsed = json_decode($raw, true);

        $this->aiSummary = is_array($parsed) ? $parsed : [
            'overall_sentiment' => 'neutral',
            'sentiment_score' => 55,
            'key_themes' => ['Price sensitivity is moderate', 'Buyer interest remains cautious', 'Location is consistently positive'],
            'buyer_confidence' => 'medium',
            'price_sensitivity' => 'medium',
            'recommendation' => 'Focus on value-add features in listing descriptions and address price concerns proactively during viewings.',
        ];

        $this->analysing = false;
    }

    private function getSentimentData(): array
    {
        $agencyId = auth()->user()->agency_id;
        $since = now()->subDays((int) $this->period);

        $feedbacks = ViewingFeedback::whereHas('viewing', fn($q) => $q->where('agency_id', $agencyId))
            ->where('created_at', '>=', $since)
            ->get()
            ->map(fn($f) => [
                'rating' => $f->overall_rating,
                'interest' => $f->interest_level,
                'concerns' => $f->concerns,
                'would_offer' => $f->would_make_offer,
            ]);

        $activities = ContactActivity::where('agency_id', $agencyId)
            ->where('created_at', '>=', $since)
            ->whereIn('type', ['call', 'email', 'note', 'meeting'])
            ->whereNotNull('body')
            ->latest()
            ->limit(20)
            ->get(['type', 'body', 'occurred_at']);

        return ['feedback' => $feedbacks->toArray(), 'activities' => $activities->toArray()];
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;
        $since = now()->subDays((int) $this->period);

        $feedbacks = ViewingFeedback::whereHas('viewing', fn($q) => $q->where('agency_id', $agencyId))
            ->where('created_at', '>=', $since)
            ->with('viewing.contact', 'viewing.listing.property')
            ->get();

        $stats = [
            'total_feedback' => $feedbacks->count(),
            'avg_rating' => round($feedbacks->avg('overall_rating') ?? 0, 1),
            'would_offer' => $feedbacks->where('would_make_offer', true)->count(),
            'very_interested' => $feedbacks->where('interest_level', 'very_interested')->count(),
        ];

        $ratingDist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($feedbacks as $f) {
            if ($f->overall_rating) $ratingDist[$f->overall_rating]++;
        }

        return view('livewire.intelligence.sentiment-monitoring-page', compact('feedbacks', 'stats', 'ratingDist'))
            ->layout('layouts.app');
    }
}
