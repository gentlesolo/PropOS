<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\MatchBuyersToListingAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Application\CRM\Actions\SuggestNextActionAction;
use App\Infrastructure\Persistence\Models\Contact;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContactDetailPage extends Component
{
    use WithFileUploads;

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
    public string $pref_timeline = '';

    // Custom Tagging
    public string $newTag = '';

    // Family Details
    public bool $showFamilyForm = false;
    public string $fam_partner_name = '';
    public string $fam_anniversary = '';
    public array $fam_children = [];

    // Property Ownership History
    public bool $showHistoryForm = false;
    public array $history_items = [];

    // Document Upload
    public $docFile;
    public string $docTitle = '';

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
        $this->pref_timeline           = (string) ($prefs['timeline'] ?? '');

        // Load family details
        $family = $prefs['family'] ?? [];
        $this->fam_partner_name        = $family['partner_name'] ?? '';
        $this->fam_anniversary         = $family['anniversary'] ?? '';
        $this->fam_children            = $family['children'] ?? [];

        // Load history items
        $this->history_items           = $prefs['ownership_history'] ?? [];
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

        $prefs = $this->contact->preferences ?? [];
        $prefs['min_budget']         = $this->pref_min_budget ? (float) $this->pref_min_budget : null;
        $prefs['max_budget']         = $this->pref_max_budget ? (float) $this->pref_max_budget : null;
        $prefs['min_bedrooms']       = $this->pref_min_bedrooms ? (int) $this->pref_min_bedrooms : null;
        $prefs['areas']              = array_filter(array_map('trim', explode(',', $this->pref_areas)));
        $prefs['property_types']     = array_filter(array_map('trim', explode(',', $this->pref_property_types)));
        $prefs['must_have_features'] = array_filter(array_map('trim', explode(',', $this->pref_must_have_features)));
        $prefs['timeline']           = $this->pref_timeline ?: null;

        $this->contact->update(['preferences' => $prefs]);

        $this->showPreferencesForm = false;
        $this->contact->refresh();
        app(ScoreLeadAction::class)->execute($this->contact);
        $this->dispatch('notify', message: 'Buyer preferences saved.', type: 'success');
    }

    // Custom Tagging Management
    public function addTag(): void
    {
        $this->validate(['newTag' => 'required|string|min:2|max:50']);
        $tags = $this->contact->tags ?? [];
        $tagNormalized = strtolower(trim($this->newTag));
        if (!in_array($tagNormalized, $tags)) {
            $tags[] = $tagNormalized;
            $this->contact->update(['tags' => $tags]);
            $this->contact->refresh();
            $this->dispatch('notify', message: 'Tag added successfully.', type: 'success');
        }
        $this->newTag = '';
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->contact->tags ?? [];
        if (($key = array_search($tag, $tags)) !== false) {
            unset($tags[$key]);
            $this->contact->update(['tags' => array_values($tags)]);
            $this->contact->refresh();
            $this->dispatch('notify', message: 'Tag removed successfully.', type: 'success');
        }
    }

    // Family Details Management
    public function addFamilyChild(): void
    {
        $this->fam_children[] = [
            'name' => '',
            'birthday' => '',
            'school' => '',
        ];
    }

    public function removeFamilyChild(int $index): void
    {
        unset($this->fam_children[$index]);
        $this->fam_children = array_values($this->fam_children);
    }

    public function saveFamily(): void
    {
        $prefs = $this->contact->preferences ?? [];
        $prefs['family'] = [
            'partner_name' => $this->fam_partner_name ?: null,
            'anniversary' => $this->fam_anniversary ?: null,
            'children' => array_filter($this->fam_children, fn($c) => !empty($c['name'])),
        ];

        $this->contact->update(['preferences' => $prefs]);
        $this->showFamilyForm = false;
        $this->contact->refresh();
        $this->dispatch('notify', message: 'Family details updated.', type: 'success');
    }

    // Property Ownership History Management
    public function addHistoryItem(): void
    {
        $this->history_items[] = [
            'address' => '',
            'price' => '',
            'year_acquired' => '',
            'year_sold' => '',
        ];
    }

    public function removeHistoryItem(int $index): void
    {
        unset($this->history_items[$index]);
        $this->history_items = array_values($this->history_items);
    }

    public function saveHistory(): void
    {
        $prefs = $this->contact->preferences ?? [];
        $prefs['ownership_history'] = array_filter($this->history_items, fn($item) => !empty($item['address']));

        $this->contact->update(['preferences' => $prefs]);
        $this->showHistoryForm = false;
        $this->contact->refresh();
        $this->dispatch('notify', message: 'Property history updated.', type: 'success');
    }

    // Contact Documents Management
    public function uploadDocument(): void
    {
        $this->validate([
            'docFile' => 'required|file|max:10240', // Max 10MB
            'docTitle' => 'required|string|max:255',
        ]);

        $filePath = $this->docFile->store('contact-documents', 'public');
        $fileName = $this->docFile->getClientOriginalName();

        $prefs = $this->contact->preferences ?? [];
        $documents = $prefs['documents'] ?? [];

        $documents[] = [
            'title' => $this->docTitle,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'uploaded_at' => now()->toIso8601String(),
        ];

        $prefs['documents'] = $documents;
        $this->contact->update(['preferences' => $prefs]);

        $this->reset(['docFile', 'docTitle']);
        $this->contact->refresh();
        $this->dispatch('notify', message: 'Document uploaded successfully.', type: 'success');
    }

    public function deleteDocument(int $index): void
    {
        $prefs = $this->contact->preferences ?? [];
        $documents = $prefs['documents'] ?? [];

        if (isset($documents[$index])) {
            unset($documents[$index]);
            $prefs['documents'] = array_values($documents);
            $this->contact->update(['preferences' => $prefs]);
            $this->contact->refresh();
            $this->dispatch('notify', message: 'Document deleted.', type: 'success');
        }
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
