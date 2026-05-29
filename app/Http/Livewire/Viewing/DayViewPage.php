<?php

namespace App\Http\Livewire\Viewing;

use App\Infrastructure\Persistence\Models\Viewing;
use App\Infrastructure\Persistence\Models\ViewingFeedback;
use Carbon\Carbon;
use Livewire\Component;

class DayViewPage extends Component
{
    public string $date;

    // Reschedule
    public ?int $reschedulingId = null;
    public string $newDate = '';
    public string $newTime = '';

    // Feedback
    public ?int $feedbackViewingId = null;
    public int $overall_rating = 3;
    public int $price_perception = 3;
    public string $interest_level = 'maybe';
    public string $positive_notes = '';
    public string $concerns = '';
    public bool $would_make_offer = false;
    public string $agent_notes = '';

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $this->newDate = $this->date;
    }

    public function previousDay()
    {
        $this->date = Carbon::parse($this->date)->subDay()->format('Y-m-d');
    }

    public function nextDay()
    {
        $this->date = Carbon::parse($this->date)->addDay()->format('Y-m-d');
    }

    public function completeViewing(int $viewingId)
    {
        $viewing = Viewing::find($viewingId);
        if ($viewing && $viewing->assigned_agent_id === auth()->id()) {
            $viewing->update(['status' => 'completed']);
            $this->feedbackViewingId = $viewingId;
            $this->dispatch('notify', message: 'Viewing marked complete. Please log feedback.', type: 'success');
        }
    }

    public function startReschedule(int $viewingId)
    {
        $this->reschedulingId = $viewingId;
        $viewing = Viewing::find($viewingId);
        if ($viewing) {
            $this->newDate = $viewing->scheduled_at->format('Y-m-d');
            $this->newTime = $viewing->scheduled_at->format('H:i');
        }
    }

    public function saveReschedule()
    {
        $this->validate([
            'newDate' => 'required|date',
            'newTime' => 'required|date_format:H:i',
        ]);

        $viewing = Viewing::find($this->reschedulingId);
        if ($viewing && $viewing->assigned_agent_id === auth()->id()) {
            $newDatetime = Carbon::parse("{$this->newDate} {$this->newTime}");
            $viewing->update([
                'scheduled_at' => $newDatetime,
                'status' => 'scheduled',
            ]);
            $this->date = $this->newDate;
        }

        $this->reschedulingId = null;
        $this->dispatch('notify', message: 'Viewing rescheduled.', type: 'success');
    }

    public function saveFeedback()
    {
        $this->validate([
            'overall_rating' => 'required|integer|between:1,5',
            'price_perception' => 'required|integer|between:1,5',
            'interest_level' => 'required|in:very_interested,interested,maybe,not_interested',
            'agent_notes' => 'nullable|string|max:1000',
        ]);

        ViewingFeedback::updateOrCreate(
            ['viewing_id' => $this->feedbackViewingId],
            [
                'agency_id' => auth()->user()->agency_id,
                'overall_rating' => $this->overall_rating,
                'price_perception' => $this->price_perception,
                'interest_level' => $this->interest_level,
                'positive_notes' => $this->positive_notes ?: null,
                'concerns' => $this->concerns ?: null,
                'would_make_offer' => $this->would_make_offer,
                'agent_notes' => $this->agent_notes ?: null,
            ]
        );

        $this->feedbackViewingId = null;
        $this->reset(['overall_rating', 'price_perception', 'interest_level', 'positive_notes', 'concerns', 'would_make_offer', 'agent_notes']);
        $this->overall_rating = 3;
        $this->price_perception = 3;
        $this->interest_level = 'maybe';
        $this->dispatch('notify', message: 'Feedback saved.', type: 'success');
    }

    public function getViewingsProperty()
    {
        return Viewing::with(['contact', 'listing.property', 'feedback'])
            ->where('assigned_agent_id', auth()->id())
            ->whereDate('scheduled_at', $this->date)
            ->orderBy('scheduled_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.viewing.day-view-page', [
            'viewings' => $this->viewings,
        ])->layout('layouts.app');
    }
}
