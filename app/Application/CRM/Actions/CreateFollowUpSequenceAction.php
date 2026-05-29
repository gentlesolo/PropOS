<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\FollowUpSequence;
use App\Infrastructure\Persistence\Models\FollowUpStep;

class CreateFollowUpSequenceAction
{
    /** @param array<array{type: string, subject: string, message_template: string, delay_days: int}> $steps */
    public function execute(Contact $contact, string $name, array $steps): FollowUpSequence
    {
        $sequence = FollowUpSequence::create([
            'agency_id' => $contact->agency_id,
            'contact_id' => $contact->id,
            'assigned_agent_id' => auth()->id(),
            'name' => $name,
            'status' => 'active',
            'current_step' => 0,
            'next_action_at' => now()->addDays($steps[0]['delay_days'] ?? 1),
        ]);

        foreach ($steps as $i => $step) {
            FollowUpStep::create([
                'sequence_id' => $sequence->id,
                'step_number' => $i + 1,
                'type' => $step['type'] ?? 'email',
                'subject' => $step['subject'] ?? null,
                'message_template' => $step['message_template'],
                'delay_days' => $step['delay_days'] ?? 1,
                'status' => 'pending',
            ]);
        }

        return $sequence->load('steps');
    }
}
