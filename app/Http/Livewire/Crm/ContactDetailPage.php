<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\MatchBuyersToListingAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Application\CRM\Actions\SuggestNextActionAction;
use App\Infrastructure\Persistence\Models\Contact;
use Livewire\Component;

class ContactDetailPage extends Component
{
    public Contact $contact;

    // Activity log form
    public string $activityType = 'note';
    public string $activitySubject = '';
    public string $activityBody = '';
    public bool $showActivityForm = false;

    // Edit form
    public bool $showEditForm = false;
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $status = '';
    public string $notes = '';

    // Buyer preferences
    public bool $showPreferencesForm = false;
    public string $pref_min_budget = '';
    public string $pref_max_budget = '';
    public string $pref_min_bedrooms = '';
    public string $pref_areas = '';
    public string $pref_property_types = '';
    public string $pref_must_have_features = '';

    // AI next best action
    public ?string $nextActionSuggestion = null;
    public bool $loadingNextAction = false;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'status' => 'required|in:new,active,qualified,nurturing,closed,archived',
        'notes' => 'nullable|string',
        'activityType' => 'required|in:note,call,email,meeting,sms',
        'activityBody' => 'required_if:showActivityForm,true|string|max:2000',
    ];

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
        $this->fill([
            'first_name' => $contact->first_name,
            'last_name'  => $contact->last_name,
            'email'      => $contact->email ?? '',
            'phone'      => $contact->phone ?? '',
            'status'     => $contact->status,
            'notes'      => $contact->notes ?? '',
        ]);

        $prefs = $contact->preferences ?? [];
        $this->pref_min_budget         = (string) ($prefs['min_budget'] ?? '');
        $this->pref_max_budget         = (string) ($prefs['max_budget'] ?? '');
        $this->pref_min_bedrooms       = (string) ($prefs['min_bedrooms'] ?? '');
        $this->pref_areas              = implode(', ', (array) ($prefs['areas'] ?? []));
        $this->pref_property_types     = implode(', ', (array) ($prefs['property_types'] ?? []));
        $this->pref_must_have_features = implode(', ', (array) ($prefs['must_have_features'] ?? []));
    }

    public function saveActivity(LogContactActivityAction $logAction)
    {
        $this->validateOnly('activityType');
        $this->validate(['activityBody' => 'required|string|max:2000']);

        $logAction->execute(
            $this->contact,
            $this->activityType,
            $this->activitySubject ?: null,
            $this->activityBody
        );

        $this->contact->update(['last_contacted_at' => now()]);
        app(ScoreLeadAction::class)->execute($this->contact->fresh());

        $this->reset(['activityBody', 'activitySubject', 'showActivityForm']);
        $this->activityType = 'note';
        $this->contact->refresh();

        // Clear stale suggestion after new activity
        $this->nextActionSuggestion = null;
    }

    public function saveContact()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'status'     => 'required|in:new,active,qualified,nurturing,closed,archived',
            'notes'      => 'nullable|string',
        ]);

        $this->contact->update([
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'email'      => $this->email ?: null,
            'phone'      => $this->phone ?: null,
            'status'     => $this->status,
            'notes'      => $this->notes ?: null,
        ]);

        $this->showEditForm = false;
        $this->contact->refresh();
        $this->nextActionSuggestion = null;
    }

    public function savePreferences(): void
    {
        $this->validate([
            'pref_max_budget'   => 'nullable|numeric|min:0',
            'pref_min_budget'   => 'nullable|numeric|min:0',
            'pref_min_bedrooms' => 'nullable|integer|min:0',
        ]);

        $this->contact->update([
            'preferences' => [
                'min_budget'         => $this->pref_min_budget ? (float) $this->pref_min_budget : null,
                'max_budget'         => $this->pref_max_budget ? (float) $this->pref_max_budget : null,
                'min_bedrooms'       => $this->pref_min_bedrooms ? (int) $this->pref_min_bedrooms : null,
                'areas'              => array_filter(array_map('trim', explode(',', $this->pref_areas))),
                'property_types'     => array_filter(array_map('trim', explode(',', $this->pref_property_types))),
                'must_have_features' => array_filter(array_map('trim', explode(',', $this->pref_must_have_features))),
            ],
        ]);

        $this->showPreferencesForm = false;
        $this->contact->refresh();
        $this->dispatch('notify', message: 'Buyer preferences saved.', type: 'success');
    }

    public function loadNextAction(SuggestNextActionAction $suggester)
    {
        $this->loadingNextAction = true;
        $this->nextActionSuggestion = $suggester->forContact($this->contact);
        $this->loadingNextAction = false;
    }

    public function dismissNextAction()
    {
        $this->nextActionSuggestion = null;
    }

    public function render(MatchBuyersToListingAction $matchAction)
    {
        $activities = $this->contact->activities()->with('user')->get();

        $matchedListings = in_array($this->contact->type, ['buyer', 'investor', 'tenant'])
            ? $matchAction->matchListingsForBuyer($this->contact)
            : collect();

        return view('livewire.crm.contact-detail-page', [
            'activities'     => $activities,
            'matchedListings' => $matchedListings,
        ])->layout('layouts.app');
    }
}
