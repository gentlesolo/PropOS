<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Viewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendViewingFeedbackSurveyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        // Find completed viewings from the last 2 hours that haven't received a survey yet
        $viewings = Viewing::with(['contact', 'listing.property'])
            ->where('status', 'completed')
            ->where('feedback_survey_sent', false)
            ->where('updated_at', '>=', now()->subHours(2))
            ->get();

        foreach ($viewings as $viewing) {
            $this->sendSurvey($viewing);
        }
    }

    private function sendSurvey(Viewing $viewing): void
    {
        $contact  = $viewing->contact;
        $property = $viewing->listing?->property;

        if (! $contact?->email) {
            $viewing->update(['feedback_survey_sent' => true]);
            return;
        }

        $feedbackUrl = route('viewing.feedback', ['viewing' => $viewing->id, 'token' => $viewing->feedback_token]);
        $address     = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';

        $body = implode("\n\n", [
            "Hi {$contact->first_name},",
            "Thank you for viewing {$address} today.",
            "We'd love to hear your thoughts — it takes less than 2 minutes:",
            $feedbackUrl,
            "Your feedback helps us improve and keeps the seller informed.",
            "Thank you,\nThe VillaCRM Team",
        ]);

        try {
            Mail::raw($body, fn($m) => $m
                ->to($contact->email, $contact->full_name)
                ->subject("How did the viewing go? — {$address}")
            );

            $viewing->update(['feedback_survey_sent' => true]);
        } catch (\Exception $e) {
            Log::error('Feedback survey email failed', [
                'viewing_id' => $viewing->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
