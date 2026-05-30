<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\ContactActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeActivitySentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $activityId) {}

    public function handle(AiCompletionServiceInterface $ai): void
    {
        $activity = ContactActivity::find($this->activityId);

        if (!$activity || empty($activity->body)) {
            return;
        }

        // Skip types where sentiment isn't meaningful
        if (in_array($activity->type, ['status_change', 'system'])) {
            return;
        }

        $systemPrompt = <<<PROMPT
You are a sentiment analysis tool for a real estate CRM.
Analyze the text and return ONLY one of these labels (no punctuation, no explanation):
positive
neutral
negative
urgent
PROMPT;

        $userPrompt = "Activity type: {$activity->type}\nText: {$activity->body}";

        $raw = trim($ai->generate($systemPrompt, $userPrompt, [
            'feature' => 'sentiment_analysis',
            'temperature' => 0.1,
        ]));

        $sentiment = strtolower(preg_replace('/[^a-z]/', '', $raw));
        $validLabels = ['positive', 'neutral', 'negative', 'urgent'];

        if (!in_array($sentiment, $validLabels)) {
            $sentiment = 'neutral';
        }

        $metadata = $activity->metadata ?? [];
        $metadata['sentiment'] = $sentiment;
        $activity->update(['metadata' => $metadata]);

        // Alert the agent if consecutive negative or urgent sentiments found
        $this->checkForSentimentAlert($activity, $sentiment);
    }

    private function checkForSentimentAlert(ContactActivity $activity, string $sentiment): void
    {
        if (!in_array($sentiment, ['negative', 'urgent'])) {
            return;
        }

        $recentNegativeCount = ContactActivity::where('contact_id', $activity->contact_id)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->get()
            ->filter(fn($a) => in_array($a->metadata['sentiment'] ?? '', ['negative', 'urgent']))
            ->count();

        if ($recentNegativeCount >= 2) {
            Log::warning('Contact showing repeated negative sentiment', [
                'contact_id' => $activity->contact_id,
                'count' => $recentNegativeCount,
            ]);
        }
    }
}
