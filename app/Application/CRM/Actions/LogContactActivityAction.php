<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\ContactActivity;
use App\Infrastructure\Queue\Jobs\AnalyzeActivitySentimentJob;

class LogContactActivityAction
{
    public function execute(Contact $contact, string $type, ?string $subject, ?string $body, array $metadata = []): ContactActivity
    {
        $activity = ContactActivity::create([
            'agency_id'  => $contact->agency_id,
            'contact_id' => $contact->id,
            'user_id'    => auth()->id(),
            'type'       => $type,
            'subject'    => $subject,
            'body'       => $body,
            'metadata'   => $metadata,
            'occurred_at' => now(),
        ]);

        // Analyze sentiment asynchronously — skip types with no meaningful text
        if (!empty($body) && !in_array($type, ['status_change', 'system'])) {
            AnalyzeActivitySentimentJob::dispatch($activity->id)->onQueue('default');
        }

        return $activity;
    }
}
