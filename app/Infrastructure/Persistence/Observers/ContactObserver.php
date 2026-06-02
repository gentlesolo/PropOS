<?php

namespace App\Infrastructure\Persistence\Observers;

use App\Application\Website\Services\WebhookDispatcherService;
use App\Infrastructure\Persistence\Models\Contact;

class ContactObserver
{
    public function __construct(private readonly WebhookDispatcherService $webhooks) {}

    public function created(Contact $contact): void
    {
        $this->webhooks->dispatch($contact->agency_id, 'contact.created', $this->payload($contact));
    }

    public function updated(Contact $contact): void
    {
        $tagsChanged = $contact->isDirty('tags');

        // Always fire contact.updated for any field change
        $this->webhooks->dispatch($contact->agency_id, 'contact.updated', array_merge(
            $this->payload($contact),
            ['changed_fields' => array_keys($contact->getDirty())],
        ));

        // Also fire the focused contact.tagged event when tags specifically changed
        if ($tagsChanged) {
            $this->webhooks->dispatch($contact->agency_id, 'contact.tagged', array_merge(
                $this->payload($contact),
                [
                    'tags_added'   => array_values(array_diff(
                        $contact->tags ?? [],
                        $contact->getOriginal('tags') ?? [],
                    )),
                    'tags_removed' => array_values(array_diff(
                        $contact->getOriginal('tags') ?? [],
                        $contact->tags ?? [],
                    )),
                ],
            ));
        }
    }

    private function payload(Contact $contact): array
    {
        return [
            'contact_id'  => $contact->id,
            'first_name'  => $contact->first_name,
            'last_name'   => $contact->last_name,
            'email'       => $contact->email,
            'phone'       => $contact->phone,
            'type'        => $contact->type,
            'status'      => $contact->status,
            'intent_score'=> $contact->intent_score,
            'tags'        => $contact->tags ?? [],
            'source'      => $contact->source,
        ];
    }
}
