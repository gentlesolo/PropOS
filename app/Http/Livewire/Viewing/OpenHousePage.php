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

        $openHouse = OpenHouse::find($this->checkingInId);

        if (! $openHouse) {
            return;
        }

        OpenHouseRsvp::create([
            'open_house_id' => $openHouse->id,
            'guest_name'    => $this->checkin_name,
            'guest_email'   => $this->checkin_email ?: null,
            'guest_phone'   => $this->checkin_phone ?: null,
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        $openHouse->increment('attendance_count');
        $openHouse->increment('rsvp_count');

        $this->reset(['checkingInId', 'checkin_name', 'checkin_email', 'checkin_phone']);
        $this->dispatch('notify', message: 'Guest checked in.', type: 'success');
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
