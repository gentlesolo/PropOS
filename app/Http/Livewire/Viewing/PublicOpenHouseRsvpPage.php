<?php

namespace App\Http\Livewire\Viewing;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\OpenHouse;
use App\Infrastructure\Persistence\Models\OpenHouseRsvp;
use App\Application\CRM\Actions\CreateFollowUpSequenceAction;
use Livewire\Component;

class PublicOpenHouseRsvpPage extends Component
{
    public OpenHouse $openHouse;
    public string $rsvp_slug;

    // RSVP form fields
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $notes = '';

    public bool $registered = false;

    public function mount(string $rsvp_slug): void
    {
        $this->rsvp_slug = $rsvp_slug;
        $this->openHouse = OpenHouse::where('rsvp_slug', $rsvp_slug)
            ->firstOrFail()
            ->load('listing.property', 'agent');
    }

    public function submitRsvp(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Find or create Contact
        $contact = null;
        if ($this->email) {
            $contact = Contact::where('email', $this->email)
                ->where('agency_id', $this->openHouse->agency_id)
                ->first();
        }
        if (!$contact && $this->phone) {
            $contact = Contact::where('phone', $this->phone)
                ->where('agency_id', $this->openHouse->agency_id)
                ->first();
        }
        if (!$contact) {
            $nameParts = explode(' ', trim($this->name), 2);
            $contact = Contact::create([
                'agency_id'          => $this->openHouse->agency_id,
                'assigned_agent_id'  => $this->openHouse->agent_id,
                'first_name'         => $nameParts[0],
                'last_name'          => $nameParts[1] ?? '',
                'email'              => $this->email,
                'phone'              => $this->phone ?: null,
                'type'               => 'buyer',
                'status'             => 'new',
                'lead_source'        => 'open_house_rsvp_page',
            ]);
        }

        OpenHouseRsvp::create([
            'open_house_id' => $this->openHouse->id,
            'contact_id'    => $contact->id,
            'guest_name'    => $this->name,
            'guest_email'   => $this->email,
            'guest_phone'   => $this->phone ?: null,
            'checked_in'    => false,
        ]);

        $this->openHouse->increment('rsvp_count');

        // Trigger nurturing sequence
        $steps = [
            [
                'type' => 'email',
                'subject' => 'Open House Registration Confirmed!',
                'message_template' => 'Hi {{first_name}}, thank you for RSVPing to our upcoming open house. We look forward to seeing you there! If you need any directions, feel free to reach out.',
                'delay_days' => 0,
            ],
            [
                'type' => 'sms',
                'subject' => '',
                'message_template' => 'Hi {{first_name}}, this is a quick reminder about the open house starting tomorrow. See you soon!',
                'delay_days' => 1,
            ]
        ];

        $action = new CreateFollowUpSequenceAction();
        $action->execute($contact, "Open House RSVP (" . ($this->openHouse->listing->property->address_line_1 ?? 'Property') . ")", $steps);

        $this->registered = true;
    }

    public function render()
    {
        return view('livewire.viewing.public-open-house-rsvp-page')
            ->layout('layouts.public');
    }
}
