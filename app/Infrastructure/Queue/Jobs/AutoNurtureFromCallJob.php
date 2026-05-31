<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallSummary;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\FollowUpSequence;
use App\Infrastructure\Persistence\Models\FollowUpStep;
use App\Infrastructure\Services\MobileNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoNurtureFromCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly Call $call,
    ) {}

    public function handle(MobileNotificationService $notifier): void
    {
        $call = $this->call->load(['summary', 'contact', 'agent']);

        if (! $call->summary || ! $call->contact) {
            return;
        }

        $summary = $call->summary;
        $contact = $call->contact;

        // Only auto-nurture warm/hot leads where there are concrete action items
        if (
            ! in_array($summary->sentiment, ['hot', 'warm'])
            || empty($summary->action_items)
        ) {
            return;
        }

        // Do not create a duplicate sequence if one is already active for this contact
        $existing = FollowUpSequence::where('contact_id', $contact->id)
            ->where('status', 'active')
            ->exists();

        if ($existing) {
            Log::info("AutoNurtureFromCallJob: active sequence already exists for contact {$contact->id}");
            return;
        }

        $steps   = $this->buildSteps($summary, $contact);
        $seqName = "Post-call nurture — {$contact->full_name}";

        $sequence = FollowUpSequence::create([
            'agency_id'         => $call->agency_id,
            'contact_id'        => $contact->id,
            'assigned_agent_id' => $call->agent_id,
            'name'              => $seqName,
            'status'            => 'active',
            'current_step'      => 0,
            'next_action_at'    => now()->addDay(),
        ]);

        foreach ($steps as $i => $step) {
            FollowUpStep::create([
                'sequence_id'       => $sequence->id,
                'step_number'       => $i + 1,
                'type'              => $step['type'],
                'subject'           => $step['subject'],
                'message_template'  => $step['message'],
                'delay_days'        => $step['delay_days'],
                'status'            => 'pending',
            ]);
        }

        // Notify agent that a nurture sequence was auto-created
        if ($call->agent) {
            $notifier->sendNewLeadAssigned(
                $call->agent,
                $contact->id,
                $contact->full_name . ' (nurture started)',
            );
        }

        Log::info("AutoNurtureFromCallJob: created sequence '{$seqName}' for contact {$contact->id}");
    }

    /**
     * Build a 5-step nurture sequence from the call summary's action items and sentiment.
     */
    private function buildSteps(CallSummary $summary, Contact $contact): array
    {
        $firstName = $contact->first_name;
        $items     = $summary->action_items ?? [];
        $nextStep  = $summary->suggested_next_step ?? 'Follow up on your interest';

        $steps = [];

        // Step 1 (day 1): WhatsApp/SMS — immediate warm follow-up
        $steps[] = [
            'type'       => 'sms',
            'subject'    => null,
            'delay_days' => 1,
            'message'    => "Hi {$firstName}, great speaking with you today! {$nextStep}. Let me know if you have any questions. — {{agent_name}}",
        ];

        // Step 2 (day 2): Email — relevant property info or document
        $firstItem = $items[0] ?? 'Send relevant property information';
        $steps[] = [
            'type'       => 'email',
            'subject'    => "Following up — {{listing_title}}",
            'delay_days' => 2,
            'message'    => "Hi {$firstName},\n\nAs promised, I'm sending over the details we discussed.\n\n{$firstItem}\n\nFeel free to reach out anytime.\n\nBest,\n{{agent_name}}",
        ];

        // Step 3 (day 4): Call reminder task for agent
        $steps[] = [
            'type'       => 'task',
            'subject'    => "Call {$firstName} — check in on interest level",
            'delay_days' => 4,
            'message'    => "Follow up call with {$firstName} {$contact->last_name}. Ref call summary: {$summary->summary_text}",
        ];

        // Step 4 (day 7): SMS — social proof or market update
        $steps[] = [
            'type'       => 'sms',
            'subject'    => null,
            'delay_days' => 7,
            'message'    => "Hi {$firstName}, just wanted to share — we had a lot of interest in similar properties this week. Would love to schedule a viewing for you. {{agent_name}}",
        ];

        // Step 5 (day 14): Email — final check-in
        $steps[] = [
            'type'       => 'email',
            'subject'    => "Still interested? Let's talk — {{agent_name}}",
            'delay_days' => 14,
            'message'    => "Hi {$firstName},\n\nI haven't heard back from you and wanted to check in. The market is moving fast right now and I'd hate for you to miss the right property.\n\nAre you still looking? Happy to arrange a viewing at your convenience.\n\n{{agent_name}}",
        ];

        return $steps;
    }
}
