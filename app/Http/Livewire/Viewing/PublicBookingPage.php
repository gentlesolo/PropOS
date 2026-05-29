<?php

namespace App\Http\Livewire\Viewing;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Viewing;
use Carbon\Carbon;
use Livewire\Component;

class PublicBookingPage extends Component
{
    public Listing $listing;

    // Slot selection
    public ?string $selectedSlot = null;
    public string $selectedDate = '';

    // Guest details
    public string $guest_name = '';
    public string $guest_email = '';
    public string $guest_phone = '';
    public string $guest_message = '';

    // State
    public bool $booked = false;
    public ?int $viewingId = null;

    // Configurable slot settings
    public int $slotDurationMinutes = 30;
    public string $availableFrom = '09:00';
    public string $availableTo = '17:00';

    public function mount(Listing $listing): void
    {
        $this->listing = $listing->load('property', 'agent', 'coverPhoto');
        $this->selectedDate = Carbon::today()->addDay()->format('Y-m-d');
    }

    public function selectSlot(string $slot): void
    {
        $this->selectedSlot = $slot;
    }

    public function previousDay(): void
    {
        $date = Carbon::parse($this->selectedDate)->subDay();
        if ($date->isToday() || $date->isFuture()) {
            $this->selectedDate = $date->format('Y-m-d');
        }
        $this->selectedSlot = null;
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->selectedSlot = null;
    }

    public function confirmBooking(): void
    {
        $this->validate([
            'selectedSlot' => 'required|string',
            'guest_name'   => 'required|string|max:255',
            'guest_email'  => 'nullable|email|max:255',
            'guest_phone'  => 'required|string|max:30',
        ]);

        $scheduledAt = Carbon::parse("{$this->selectedDate} {$this->selectedSlot}");

        // Guard against past slots
        if ($scheduledAt->isPast()) {
            $this->addError('selectedSlot', 'This slot is in the past. Please choose another.');
            return;
        }

        // Find or create a contact record for the guest
        $contact = null;
        if ($this->guest_email) {
            $contact = Contact::where('email', $this->guest_email)
                ->where('agency_id', $this->listing->agency_id)
                ->first();
        }

        if (! $contact && $this->guest_phone) {
            $contact = Contact::where('phone', $this->guest_phone)
                ->where('agency_id', $this->listing->agency_id)
                ->first();
        }

        if (! $contact) {
            $nameParts = explode(' ', trim($this->guest_name), 2);
            $contact = Contact::create([
                'agency_id'          => $this->listing->agency_id,
                'assigned_agent_id'  => $this->listing->agent_id,
                'first_name'         => $nameParts[0],
                'last_name'          => $nameParts[1] ?? '',
                'email'              => $this->guest_email ?: null,
                'phone'              => $this->guest_phone,
                'type'               => 'buyer',
                'status'             => 'new',
                'lead_source'        => 'booking_portal',
            ]);
        }

        $viewing = Viewing::create([
            'agency_id'          => $this->listing->agency_id,
            'listing_id'         => $this->listing->id,
            'contact_id'         => $contact->id,
            'assigned_agent_id'  => $this->listing->agent_id,
            'scheduled_at'       => $scheduledAt,
            'duration_minutes'   => $this->slotDurationMinutes,
            'status'             => 'scheduled',
            'notes'              => $this->guest_message ?: null,
            'booking_source'     => 'public_portal',
        ]);

        $this->viewingId = $viewing->id;
        $this->booked    = true;
    }

    public function getAvailableSlotsProperty(): array
    {
        $date = Carbon::parse($this->selectedDate);

        // No slots on weekends (configurable)
        if ($date->isWeekend()) {
            return [];
        }

        $from  = Carbon::parse("{$this->selectedDate} {$this->availableFrom}");
        $to    = Carbon::parse("{$this->selectedDate} {$this->availableTo}");
        $slots = [];

        // Build slots
        $current = $from->copy();
        while ($current->lt($to)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($this->slotDurationMinutes);
        }

        // Remove already-booked slots
        $bookedTimes = Viewing::where('listing_id', $this->listing->id)
            ->where('status', 'scheduled')
            ->whereDate('scheduled_at', $date)
            ->get()
            ->map(fn($v) => Carbon::parse($v->scheduled_at)->format('H:i'))
            ->toArray();

        return array_filter($slots, fn($slot) => ! in_array($slot, $bookedTimes));
    }

    public function render()
    {
        return view('livewire.viewing.public-booking-page', [
            'availableSlots' => $this->availableSlots,
        ])->layout('layouts.public');
    }
}
