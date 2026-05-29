<?php

namespace App\Http\Livewire\Viewing;

use App\Infrastructure\Persistence\Models\Viewing;
use App\Infrastructure\Persistence\Models\ViewingFeedback;
use Livewire\Component;

class PublicFeedbackPage extends Component
{
    public Viewing $viewing;
    public bool $submitted = false;

    public int $overall_rating    = 3;
    public int $price_perception  = 3;
    public string $interest_level = 'maybe';
    public string $positive_notes = '';
    public string $concerns       = '';
    public bool $would_make_offer = false;
    public bool $would_revisit    = false;

    public function mount(int $viewing, string $token): void
    {
        $v = Viewing::where('id', $viewing)
            ->where('feedback_token', $token)
            ->with(['contact', 'listing.property'])
            ->firstOrFail();

        $this->viewing = $v;

        // Pre-fill if feedback already exists
        $existing = ViewingFeedback::where('viewing_id', $v->id)->first();
        if ($existing) {
            $this->submitted = true;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'overall_rating'   => 'required|integer|between:1,5',
            'price_perception' => 'required|integer|between:1,5',
            'interest_level'   => 'required|in:very_interested,interested,maybe,not_interested',
            'positive_notes'   => 'nullable|string|max:1000',
            'concerns'         => 'nullable|string|max:1000',
        ]);

        ViewingFeedback::updateOrCreate(
            ['viewing_id' => $this->viewing->id],
            [
                'agency_id'       => $this->viewing->agency_id,
                'overall_rating'  => $this->overall_rating,
                'price_perception' => $this->price_perception,
                'interest_level'  => $this->interest_level,
                'positive_notes'  => $this->positive_notes ?: null,
                'concerns'        => $this->concerns ?: null,
                'would_make_offer' => $this->would_make_offer,
            ]
        );

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.viewing.public-feedback-page')
            ->layout('layouts.public');
    }
}
