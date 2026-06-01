<?php

namespace App\Http\Livewire\Viewing;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\OpenHouse;
use App\Infrastructure\Persistence\Models\OpenHouseRsvp;
use Livewire\Component;

class OpenHousePage extends Component
{
    // Create form
    public bool $showCreateForm = false;
    public string $listing_id = '';
    public string $starts_at = '';
    public string $ends_at = '';
    public string $notes = '';

    // Check-in
    public ?int $checkingInId = null;
    public string $checkin_name = '';
    public string $checkin_email = '';
    public string $checkin_phone = '';

    protected function rules(): array
    {
        return [
            'listing_id' => 'required|exists:listings,id',
            'starts_at'  => 'required|date|after:now',
            'ends_at'    => 'required|date|after:starts_at',
            'notes'      => 'nullable|string|max:1000',
        ];
    }

    public function createOpenHouse(): void
    {
        $this->validate();

        OpenHouse::create([
            'agency_id'  => auth()->user()->agency_id,
            'listing_id' => $this->listing_id,
            'agent_id'   => auth()->id(),
            'starts_at'  => $this->starts_at,
            'ends_at'    => $this->ends_at,
            'notes'      => $this->notes ?: null,
            'status'     => 'scheduled',
        ]);

        $this->reset(['listing_id', 'starts_at', 'ends_at', 'notes', 'showCreateForm']);
        $this->dispatch('notify', message: 'Open house scheduled.', type: 'success');
    }

    public function cancelOpenHouse(int $id): void
    {
        OpenHouse::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['status' => 'cancelled']);

        $this->dispatch('notify', message: 'Open house cancelled.', type: 'info');
    }

    public function markLive(int $id): void
    {
        OpenHouse::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['status' => 'live']);
    }

    public function markCompleted(int $id): void
    {
        $openHouse = OpenHouse::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->first();

        if ($openHouse) {
            $openHouse->update([
                'status'           => 'completed',
                'attendance_count' => $openHouse->rsvps()->where('checked_in', true)->count(),
            ]);
        }
    }

    public function startCheckin(int $id): void
    {
        $this->checkingInId  = $id;
        $this->checkin_name  = '';
        $this->checkin_email = '';
        $this->checkin_phone = '';
    }

    public function checkIn(): void
    {
        $this->validate([
            'checkin_name'  => 'required|string|max:255',
            'checkin_email' => 'nullable|email|max:255',
            'checkin_phone' => 'nullable|string|max:30',
        ]);

        $openHouse = OpenHouse::with('listing.property')->find($this->checkingInId);

        if (! $openHouse) {
            return;
        }

        // Find or create Contact
        $contact = null;
        if ($this->checkin_email) {
            $contact = \App\Infrastructure\Persistence\Models\Contact::where('email', $this->checkin_email)
                ->where('agency_id', $openHouse->agency_id)
                ->first();
        }
        if (!$contact && $this->checkin_phone) {
            $contact = \App\Infrastructure\Persistence\Models\Contact::where('phone', $this->checkin_phone)
                ->where('agency_id', $openHouse->agency_id)
                ->first();
        }
        if (!$contact) {
            $nameParts = explode(' ', trim($this->checkin_name), 2);
            $contact = \App\Infrastructure\Persistence\Models\Contact::create([
                'agency_id'          => $openHouse->agency_id,
                'assigned_agent_id'  => $openHouse->agent_id,
                'first_name'         => $nameParts[0],
                'last_name'          => $nameParts[1] ?? '',
                'email'              => $this->checkin_email ?: null,
                'phone'              => $this->checkin_phone ?: null,
                'type'               => 'buyer',
                'status'             => 'new',
                'lead_source'        => 'open_house',
            ]);
        }

        OpenHouseRsvp::create([
            'open_house_id' => $openHouse->id,
            'contact_id'    => $contact->id,
            'guest_name'    => $this->checkin_name,
            'guest_email'   => $this->checkin_email ?: null,
            'guest_phone'   => $this->checkin_phone ?: null,
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        $openHouse->increment('attendance_count');
        $openHouse->increment('rsvp_count');

        // Enroll in automated nurturing sequence
        $steps = [
            [
                'type' => 'email',
                'subject' => 'Thank you for attending the Open House!',
                'message_template' => 'Hi {{first_name}}, thank you for visiting the open house today. I wanted to see if you have any questions about the property, or if you\'d like to schedule a private viewing.',
                'delay_days' => 1,
            ],
            [
                'type' => 'sms',
                'subject' => '',
                'message_template' => 'Hi {{first_name}}, just checking in. Let me know if you would like me to send you the property prospectus or disclosure docs. - PropOS Agency',
                'delay_days' => 2,
            ]
        ];

        $action = new \App\Application\CRM\Actions\CreateFollowUpSequenceAction();
        $action->execute($contact, "Open House Follow-up (" . ($openHouse->listing->property->address_line_1 ?? 'Property') . ")", $steps);

        $this->reset(['checkingInId', 'checkin_name', 'checkin_email', 'checkin_phone']);
        $this->dispatch('notify', message: 'Guest checked in and follow-up sequence initialized.', type: 'success');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $upcomingOpenHouses = OpenHouse::with(['listing.property', 'agent', 'rsvps'])
            ->where('agency_id', $agencyId)
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('starts_at')
            ->get();

        $pastOpenHouses = OpenHouse::with(['listing.property', 'rsvps'])
            ->where('agency_id', $agencyId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();

        $listings = Listing::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->with('property')
            ->get();

        return view('livewire.viewing.open-house-page', compact('upcomingOpenHouses', 'pastOpenHouses', 'listings'))
            ->layout('layouts.app');
    }
}
