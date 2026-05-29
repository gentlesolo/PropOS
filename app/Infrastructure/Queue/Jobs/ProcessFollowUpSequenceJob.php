<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\FollowUpSequence;
use App\Infrastructure\Persistence\Models\FollowUpStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessFollowUpSequenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300;

    public function handle(): void
    {
        $sequences = FollowUpSequence::with(['steps', 'contact'])
            ->where('status', 'active')
            ->where('next_action_at', '<=', now())
            ->get();

        foreach ($sequences as $sequence) {
            $this->processSequence($sequence);
        }
    }

    private function processSequence(FollowUpSequence $sequence): void
    {
        $nextStep = $sequence->steps()
            ->where('status', 'pending')
            ->orderBy('step_number')
            ->first();

        if (! $nextStep) {
            $sequence->update(['status' => 'completed']);
            return;
        }

        try {
            $this->sendStep($sequence, $nextStep);

            $nextStep->update(['status' => 'sent', 'sent_at' => now()]);

            $followingStep = $sequence->steps()
                ->where('status', 'pending')
                ->where('step_number', '>', $nextStep->step_number)
                ->orderBy('step_number')
                ->first();

            if ($followingStep) {
                $sequence->update([
                    'current_step'    => $nextStep->step_number,
                    'next_action_at'  => now()->addDays($followingStep->delay_days),
                ]);
            } else {
                $sequence->update([
                    'current_step'   => $nextStep->step_number,
                    'status'         => 'completed',
                    'next_action_at' => null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Follow-up step failed', [
                'sequence_id' => $sequence->id,
                'step_id'     => $nextStep->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function sendStep(FollowUpSequence $sequence, FollowUpStep $step): void
    {
        $contact = $sequence->contact;
        $body    = $this->interpolate($step->message_template, $contact);

        match ($step->type) {
            'email' => $this->sendEmail($contact, $step->subject ?? 'Message from us', $body),
            'sms'   => $this->sendSms($contact, $body),
            default => Log::info("Follow-up step type '{$step->type}' logged (no sender configured)", [
                'contact' => $contact?->id,
                'body'    => $body,
            ]),
        };
    }

    private function sendEmail($contact, string $subject, string $body): void
    {
        if (! $contact?->email) {
            return;
        }

        Mail::raw($body, function ($message) use ($contact, $subject) {
            $message->to($contact->email, $contact->full_name)
                    ->subject($subject);
        });
    }

    private function sendSms($contact, string $body): void
    {
        // SMS sending via Twilio or configured SMS provider goes here.
        // Logs the intent when no provider is configured.
        Log::info('SMS follow-up queued', ['to' => $contact?->phone, 'body' => $body]);
    }

    private function interpolate(string $template, $contact): string
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{full_name}}'],
            [$contact?->first_name ?? 'there', $contact?->last_name ?? '', $contact?->full_name ?? 'there'],
            $template
        );
    }
}
