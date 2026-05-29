<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\ContactActivity;

class LogContactActivityAction
{
    public function execute(Contact $contact, string $type, ?string $subject, ?string $body, array $metadata = []): ContactActivity
    {
        return ContactActivity::create([
            'agency_id' => $contact->agency_id,
            'contact_id' => $contact->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
