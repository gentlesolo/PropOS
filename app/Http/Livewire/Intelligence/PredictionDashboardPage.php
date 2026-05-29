<?php

namespace App\Http\Livewire\Intelligence;

use App\Domain\AI\Contracts\PredictionInterface;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\PipelineStage;
use Livewire\Component;

class PredictionDashboardPage extends Component
{
    public string $scoringTab = 'contacts';
    public bool $scoring = false;
    public ?int $scoringId = null;
    public array $scores = [];

    public function scoreContact(int $contactId, PredictionInterface $predictor): void
    {
        $this->scoring = true;
        $this->scoringId = $contactId;

        $contact = Contact::with('activities')->findOrFail($contactId);

        $activityCount = $contact->activities->count();
        $recentActivity = $contact->activities
            ->where('occurred_at', '>=', now()->subDays(14))
            ->count();
        $daysSinceContact = $contact->last_contacted_at
            ? now()->diffInDays($contact->last_contacted_at)
            : 90;

        $features = [
            'contact_type' => $contact->type,
            'status' => $contact->status,
            'total_activities' => $activityCount,
            'recent_activities_14d' => $recentActivity,
            'days_since_last_contact' => $daysSinceContact,
            'has_budget' => $contact->budget_max ? 'yes' : 'no',
            'has_preferences' => !empty($contact->preferences) ? 'yes' : 'no',
            'existing_intent_score' => $contact->intent_score ?? 0,
        ];

        $score = $predictor->predictScore($features);

        $this->scores["contact_{$contactId}"] = $score;

        Contact::where('id', $contactId)->update(['intent_score' => $score]);

        $this->scoring = false;
        $this->scoringId = null;

        $this->dispatch('notify', message: "Lead score updated: {$score}/100", type: 'success');
    }

    public function scoreDeal(int $dealId, PredictionInterface $predictor): void
    {
        $this->scoring = true;
        $this->scoringId = $dealId;

        $deal = Deal::with(['stage', 'contact', 'activities'])->findOrFail($dealId);

        $activityCount = $deal->activities->count();
        $recentActivity = $deal->activities
            ->where('occurred_at', '>=', now()->subDays(7))
            ->count();
        $daysInStage = $deal->updated_at ? now()->diffInDays($deal->updated_at) : 0;

        $features = [
            'stage' => $deal->stage?->name ?? 'unknown',
            'deal_value' => (int) ($deal->value ?? 0),
            'total_activities' => $activityCount,
            'recent_activities_7d' => $recentActivity,
            'days_in_current_stage' => $daysInStage,
            'has_listing' => $deal->listing_id ? 'yes' : 'no',
            'contact_type' => $deal->contact?->type ?? 'unknown',
            'existing_momentum' => $deal->momentum_score ?? 0,
        ];

        $score = $predictor->predictScore($features);

        $this->scores["deal_{$dealId}"] = $score;

        Deal::where('id', $dealId)->update(['momentum_score' => $score]);

        $this->scoring = false;
        $this->scoringId = null;

        $this->dispatch('notify', message: "Deal momentum updated: {$score}/100", type: 'success');
    }

    public function scoreAllContacts(PredictionInterface $predictor): void
    {
        $contacts = Contact::with('activities')
            ->where('status', '!=', 'closed')
            ->where('status', '!=', 'archived')
            ->limit(20)
            ->get();

        foreach ($contacts as $contact) {
            $features = [
                'contact_type' => $contact->type,
                'status' => $contact->status,
                'total_activities' => $contact->activities->count(),
                'days_since_last_contact' => $contact->last_contacted_at
                    ? now()->diffInDays($contact->last_contacted_at)
                    : 90,
                'has_budget' => $contact->budget_max ? 'yes' : 'no',
                'existing_intent_score' => $contact->intent_score ?? 0,
            ];

            $score = $predictor->predictScore($features);
            $this->scores["contact_{$contact->id}"] = $score;
            Contact::where('id', $contact->id)->update(['intent_score' => $score]);
        }

        $this->dispatch('notify', message: 'Batch scoring complete for ' . $contacts->count() . ' contacts.', type: 'success');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $contacts = Contact::with('activities')
            ->where('status', '!=', 'archived')
            ->orderByDesc('intent_score')
            ->limit(30)
            ->get();

        $deals = Deal::with(['stage', 'contact', 'activities'])
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->orderByDesc('momentum_score')
            ->limit(20)
            ->get();

        $stages = PipelineStage::where('agency_id', $agencyId)->orderBy('order')->get();

        $contactStats = [
            'hot' => $contacts->where('intent_score', '>=', 70)->count(),
            'warm' => $contacts->whereBetween('intent_score', [40, 69])->count(),
            'cold' => $contacts->where('intent_score', '<', 40)->where('intent_score', '>', 0)->count(),
            'unscored' => $contacts->where('intent_score', 0)->orWhere('intent_score', null)->count(),
        ];

        return view('livewire.intelligence.prediction-dashboard-page', compact(
            'contacts', 'deals', 'stages', 'contactStats'
        ))->layout('layouts.app');
    }
}
