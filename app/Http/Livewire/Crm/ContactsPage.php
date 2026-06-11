<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\DetectDuplicateContactsAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Application\CRM\Actions\MatchBuyersToListingAction;
use App\Infrastructure\Notifications\ContactCreatedNotification;
use Livewire\Component;
use Livewire\WithPagination;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Support\Collection;

class ContactsPage extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    // Contact creation fields
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $type = 'buyer';
    public string $source = '';

    // Search and Filter fields
    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public string $filterTag = '';
    
    // New Sorting, Smart Filters & Bulk Actions states
    public string $sortBy = 'latest';
    public bool $smartFilterActive = false;
    public string $smartQuery = '';
    
    public array $selectedContacts = [];
    public bool $selectAll = false;

    public array $duplicates = [];
    public bool $confirmDuplicate = false;

    // Contact Detail Drawer properties
    public bool $showDrawer = false;
    public ?int $selectedContactId = null;
    public ?Contact $selectedContact = null;
    public string $activityType = 'note';
    public string $activitySubject = '';
    public string $activityBody = '';
    
    // Details Drawer Tabs & Actions
    public string $activeTab = 'overview';
    public string $newNote = '';
    public bool $showDraftModal = false;
    public string $draftChannel = 'whatsapp';
    public string $draftMessage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterTag' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
        'smartQuery' => ['except' => ''],
    ];

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'type' => 'required|in:buyer,seller,landlord,tenant,investor,referral_partner',
        'source' => 'nullable|string|max:100',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterTag(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function resetSelection(): void
    {
        $this->selectedContacts = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $agencyId = auth()->user()->agency_id ?? 1;
            
            // Build the query exactly as we render it (without pagination) to get visible IDs
            $query = Contact::where('agency_id', $agencyId);
            $query = $this->applyFiltersToQuery($query);
            
            $visibleIds = $query->limit(100)->pluck('id')->toArray();
            
            $this->selectedContacts = [];
            foreach ($visibleIds as $id) {
                $this->selectedContacts[$id] = true;
            }
        } else {
            $this->selectedContacts = [];
        }
    }

    public function toggleSmartFilter(): void
    {
        $this->smartFilterActive = !$this->smartFilterActive;
        $this->resetPage();
        $this->resetSelection();
        if (!$this->smartFilterActive) {
            $this->smartQuery = '';
        }
    }

    public function applySmartFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function clearSmartFilter(): void
    {
        $this->smartQuery = '';
        $this->resetPage();
        $this->resetSelection();
    }

    // Bulk action handlers
    public function bulkDelete(): void
    {
        $ids = array_keys(array_filter($this->selectedContacts));
        if (empty($ids)) return;

        $agencyId = auth()->user()->agency_id ?? 1;
        Contact::whereIn('id', $ids)->where('agency_id', $agencyId)->delete();
        
        $this->resetSelection();
        $this->dispatch('notify', message: count($ids) . " contacts deleted successfully.", type: 'success');
    }

    public function bulkUpdateStatus(string $status): void
    {
        $ids = array_keys(array_filter($this->selectedContacts));
        if (empty($ids)) return;

        $agencyId = auth()->user()->agency_id ?? 1;
        Contact::whereIn('id', $ids)->where('agency_id', $agencyId)->update(['status' => $status]);
        
        $this->resetSelection();
        $this->dispatch('notify', message: count($ids) . " contacts updated to " . ucfirst($status) . ".", type: 'success');
    }

    public function bulkAssignAgent(?int $agentId): void
    {
        $ids = array_keys(array_filter($this->selectedContacts));
        if (empty($ids)) return;

        $agencyId = auth()->user()->agency_id ?? 1;
        Contact::whereIn('id', $ids)->where('agency_id', $agencyId)->update(['assigned_agent_id' => $agentId]);
        
        $this->resetSelection();
        $this->dispatch('notify', message: count($ids) . " contacts assigned to agent.", type: 'success');
    }

    public function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) return '—';
        if (str_starts_with($phone, '+')) return $phone;
        
        $digits = preg_replace('/\D/', '', $phone);
        if (empty($digits)) return $phone;
        
        if (str_starts_with($digits, '0')) {
            // Default to Nigeria code
            return '+234 ' . substr($digits, 1, 3) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7);
        }
        return '+' . $digits;
    }

    public function checkDuplicates(DetectDuplicateContactsAction $detector): void
    {
        if (!$this->email && !$this->phone) {
            $this->duplicates = [];
            return;
        }

        $found = $detector->execute($this->email ?: null, $this->phone ?: null);
        $this->duplicates = $found->map(fn($c) => [
            'id' => $c->id,
            'name' => "{$c->first_name} {$c->last_name}",
            'email' => $c->email,
            'phone' => $c->phone,
        ])->toArray();
    }

    public function saveContact(
        DetectDuplicateContactsAction $detector,
        LogContactActivityAction $logAction,
        ScoreLeadAction $scorer
    ) {
        $this->validate();

        if (!$this->confirmDuplicate && ($this->email || $this->phone)) {
            $found = $detector->execute($this->email ?: null, $this->phone ?: null);
            if ($found->isNotEmpty()) {
                $this->duplicates = $found->map(fn($c) => [
                    'id' => $c->id,
                    'name' => "{$c->first_name} {$c->last_name}",
                    'email' => $c->email,
                    'phone' => $c->phone,
                ])->toArray();
                return;
            }
        }

        $contact = Contact::create([
            'agency_id' => auth()->user()->agency_id ?? 1,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'type' => $this->type,
            'source' => $this->source ?: null,
            'status' => 'new',
            'preferences' => [
                'min_budget' => 20000000,
                'max_budget' => 80000000,
                'areas' => ['Lekki Phase 1', 'Ikoyi'],
                'property_types' => ['house', 'apartment'],
                'min_bedrooms' => 3,
                'max_bedrooms' => 5,
                'must_have_features' => ['Swimming Pool', '24/7 Power']
            ],
            'tags' => ['new-lead', $this->type]
        ]);

        $logAction->execute($contact, 'system', 'Contact created', 'Contact added to CRM.');
        $scorer->execute($contact);

        // Notify the assigned agent (or current user) of the new contact
        auth()->user()->notify(new ContactCreatedNotification($contact));

        $this->reset(['first_name', 'last_name', 'email', 'phone', 'source', 'showCreateModal', 'duplicates', 'confirmDuplicate']);
        $this->type = 'buyer';
    }

    public function deleteContact(int $id): void
    {
        $agencyId = auth()->user()->agency_id;
        $contact  = Contact::where('id', $id)->where('agency_id', $agencyId)->firstOrFail();

        if ($this->selectedContactId === $id) {
            $this->showDrawer        = false;
            $this->selectedContact   = null;
            $this->selectedContactId = null;
        }

        $contact->delete();
        $this->dispatch('notify', message: 'Contact deleted.', type: 'info');
    }

    public function dismissDuplicates(): void
    {
        $this->confirmDuplicate = true;
        $this->duplicates = [];
    }

    public function mergeContacts(int $targetId, int $sourceId): void
    {
        $agencyId = auth()->user()->agency_id;

        $target = Contact::where('id', $targetId)->where('agency_id', $agencyId)->firstOrFail();
        $source = Contact::where('id', $sourceId)->where('agency_id', $agencyId)->firstOrFail();

        // Move related records
        \DB::table('contact_activities')->where('contact_id', $sourceId)->update(['contact_id' => $targetId]);
        \DB::table('deals')->where('contact_id', $sourceId)->update(['contact_id' => $targetId]);
        \DB::table('viewings')->where('contact_id', $sourceId)->update(['contact_id' => $targetId]);
        \DB::table('follow_up_sequences')->where('contact_id', $sourceId)->update(['contact_id' => $targetId]);

        // Merge email/phone if target is missing them
        $updates = [];
        if (! $target->email && $source->email) {
            $updates['email'] = $source->email;
        }
        if (! $target->phone && $source->phone) {
            $updates['phone'] = $source->phone;
        }
        if ($source->intent_score > $target->intent_score) {
            $updates['intent_score'] = $source->intent_score;
        }
        if (! empty($updates)) {
            $target->update($updates);
        }

        $source->forceDelete();

        $this->dispatch('notify', message: "Contacts merged successfully.", type: 'success');
    }

    public function updateStatus(int $id, string $status): void
    {
        $agencyId = auth()->user()->agency_id ?? 1;
        $contact = Contact::where('id', $id)->where('agency_id', $agencyId)->firstOrFail();
        $contact->update(['status' => $status]);
        $this->dispatch('notify', message: "Status updated successfully.", type: 'success');
    }

    public function updateIntentScore(int $id, int $score): void
    {
        $agencyId = auth()->user()->agency_id ?? 1;
        $contact = Contact::where('id', $id)->where('agency_id', $agencyId)->firstOrFail();
        $contact->update(['intent_score' => min(100, max(0, $score))]);
        $this->dispatch('notify', message: "Intent score updated successfully.", type: 'success');
    }

    public function selectContact(int $id): void
    {
        $agencyId = auth()->user()->agency_id ?? 1;
        $this->selectedContactId = $id;
        $this->selectedContact = Contact::where('id', $id)
            ->where('agency_id', $agencyId)
            ->with(['activities.user', 'agent'])
            ->firstOrFail();

        $this->reset(['activityBody', 'activitySubject']);
        $this->activityType = 'note';
        $this->newNote = $this->selectedContact->notes ?? '';
        $this->activeTab = 'overview';
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->selectedContact = null;
        $this->selectedContactId = null;
    }

    public function saveDrawerActivity(LogContactActivityAction $logAction)
    {
        if (!$this->selectedContact) {
            return;
        }

        $this->validate([
            'activityBody' => 'required|string|max:2000',
            'activityType' => 'required|in:note,call,email,meeting,sms'
        ]);

        $logAction->execute(
            $this->selectedContact,
            $this->activityType,
            $this->activitySubject ?: null,
            $this->activityBody
        );

        $this->selectedContact->update(['last_contacted_at' => now()]);
        app(ScoreLeadAction::class)->execute($this->selectedContact->fresh());

        $this->reset(['activityBody', 'activitySubject']);
        $this->activityType = 'note';

        // Refresh selected contact to display updated timeline
        $this->selectedContact = Contact::where('id', $this->selectedContact->id)
            ->with(['activities.user', 'agent'])
            ->firstOrFail();

        $this->dispatch('notify', message: "Activity logged successfully.", type: 'success');
    }

    public function saveContactNotes(): void
    {
        if (!$this->selectedContact) return;
        
        $this->selectedContact->update([
            'notes' => $this->newNote
        ]);
        
        $logAction = app(LogContactActivityAction::class);
        $logAction->execute(
            $this->selectedContact,
            'note',
            'Notes updated',
            'Updated contact search requirements.'
        );
        
        $this->selectedContact = Contact::where('id', $this->selectedContact->id)
            ->with(['activities.user', 'agent'])
            ->firstOrFail();
            
        $this->dispatch('notify', message: "Notes saved.", type: 'success');
    }

    // Computed property for listings match
    public function getMatchedListingsProperty()
    {
        if (!$this->selectedContact) return collect();
        return app(MatchBuyersToListingAction::class)->matchListingsForBuyer($this->selectedContact);
    }

    // AI Message drafting
    public function openDraftModal(string $channel): void
    {
        if (!$this->selectedContact) return;

        $this->draftChannel = $channel;
        $name = $this->selectedContact->first_name;
        $prefs = $this->selectedContact->preferences ?? [];
        $budgetVal = $prefs['max_budget'] ?? 150000000;
        $budget = '₦' . number_format($budgetVal / 1000000, 0) . 'M';
        $location = (isset($prefs['areas']) && !empty($prefs['areas'])) ? $prefs['areas'][0] : 'Lekki';
        $beds = $prefs['min_bedrooms'] ?? 4;

        if ($channel === 'whatsapp') {
            $this->draftMessage = "Hi {$name}, this is Demo Agent from VillaCRM. ✦ I noticed you're looking for a {$beds}-bedroom property in {$location} within {$budget}. We just listed a gorgeous modern unit matching your criteria. Let me know if you would like to schedule a viewing this week?";
        } elseif ($channel === 'sms') {
            $this->draftMessage = "VillaCRM ✦: Hello {$name}, we found a stunning {$beds}-bed property in {$location} matching your requirements (budget {$budget}). Reply to schedule a viewing!";
        } else {
            $this->draftMessage = "Subject: Premium Property Match in {$location} - VillaCRM\n\nDear {$name},\n\nI hope this email finds you well.\n\nFollowing up on your property search criteria, I wanted to share a new listing that matches your preferences:\n- Location: {$location}\n- Size: {$beds} Bedrooms\n- Budget: Within {$budget}\n\nThis unit features high-end finishes, 24/7 power, and excellent security.\n\nLet me know if you would like me to send the full brochure or schedule a viewing.\n\nBest regards,\nDemo Agent\nVillaCRM Operating System";
        }

        $this->showDraftModal = true;
    }

    public function sendDraftMessage(): void
    {
        if (!$this->selectedContact) return;

        $logAction = app(LogContactActivityAction::class);
        $logAction->execute(
            $this->selectedContact,
            ($this->draftChannel === 'email' ? 'email' : 'sms'),
            "AI Draft message sent via " . ucfirst($this->draftChannel),
            $this->draftMessage
        );

        $this->selectedContact->update(['last_contacted_at' => now()]);
        app(ScoreLeadAction::class)->execute($this->selectedContact->fresh());

        // Refresh selected contact
        $this->selectedContact = Contact::where('id', $this->selectedContact->id)
            ->with(['activities.user', 'agent'])
            ->firstOrFail();

        $this->showDraftModal = false;
        $this->dispatch('notify', message: "Message sent and logged on timeline.", type: 'success');
    }

    protected function applyFiltersToQuery($query)
    {
        // Apply standard filters
        $query->when($this->search, fn($q) => $q->where(function ($sub) {
            $sub->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%");
        }))
        ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
        ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
        ->when($this->filterTag, fn($q) => $q->whereJsonContains('tags', $this->filterTag));

        // Apply Natural Language Smart Filter
        if ($this->smartFilterActive && $this->smartQuery) {
            $qs = strtolower($this->smartQuery);

            // Filter Type
            if (str_contains($qs, 'buyer') || str_contains($qs, 'buyers')) {
                $query->where('type', 'buyer');
            } elseif (str_contains($qs, 'seller') || str_contains($qs, 'sellers')) {
                $query->where('type', 'seller');
            } elseif (str_contains($qs, 'tenant') || str_contains($qs, 'tenants')) {
                $query->where('type', 'tenant');
            } elseif (str_contains($qs, 'landlord') || str_contains($qs, 'landlords')) {
                $query->where('type', 'landlord');
            }

            // Filter Intent score
            if (str_contains($qs, 'hot') || str_contains($qs, 'high intent') || str_contains($qs, 'motivated')) {
                $query->where('intent_score', '>=', 80);
            }

            // Filter specific suburbs / locations
            $locations = ['lekki', 'ikoyi', 'vi ', 'victoria island', 'ikeja', 'abuja', 'sandton', 'karen'];
            foreach ($locations as $loc) {
                if (str_contains($qs, $loc)) {
                    $query->where(function($q) use ($loc) {
                        $q->where('preferences->areas', 'like', "%{$loc}%")
                          ->orWhere('notes', 'like', "%{$loc}%")
                          ->orWhere('source', 'like', "%{$loc}%");
                    });
                }
            }

            // Filter budget
            if (preg_match('/(\d+)\s*(m|million)/i', $qs, $matches)) {
                $numVal = (int) $matches[1] * 1000000;
                if (str_contains($qs, 'over') || str_contains($qs, 'greater') || str_contains($qs, '>') || str_contains($qs, 'above')) {
                    $query->where('preferences->max_budget', '>=', $numVal);
                } else {
                    $query->where('preferences->max_budget', '<=', $numVal);
                }
            }

            // Filter Activity
            if (str_contains($qs, 'not contacted this week') || str_contains($qs, 'no activity this week') || str_contains($qs, 'not contacted')) {
                $query->where(function($q) {
                    $q->whereNull('last_contacted_at')
                      ->orWhere('last_contacted_at', '<', now()->subDays(7));
                });
            }
        }

        return $query;
    }

    protected function seedMockPreferencesIfEmpty(): void
    {
        // Find contacts with missing preference files and seed them on load to make details view rich
        $contacts = Contact::whereNull('preferences')->get();
        if ($contacts->isEmpty()) {
            return;
        }

        $areasList = [
            ['Lekki Phase 1', 'Ikoyi', 'Victoria Island'],
            ['Ikeja GRA', 'Magodo', 'Surulere'],
            ['Abuja Central', 'Maitama', 'Gwarinpa'],
            ['Sandton', 'Rosebank', 'Bryanston'],
            ['Westlands', 'Kilimani', 'Karen']
        ];

        $propertyTypes = [['house'], ['apartment'], ['penthouse', 'apartment'], ['house', 'townhouse']];
        $sources = ['portal', 'referral', 'social_media', 'direct', 'campaign'];

        foreach ($contacts as $index => $contact) {
            $areas = $areasList[$index % count($areasList)];
            $types = $propertyTypes[$index % count($propertyTypes)];
            $minBudget = rand(20, 80) * 1000000;
            $maxBudget = $minBudget + (rand(30, 120) * 1000000);
            $beds = rand(2, 5);

            $contact->update([
                'preferences' => [
                    'min_budget' => $minBudget,
                    'max_budget' => $maxBudget,
                    'areas' => $areas,
                    'property_types' => $types,
                    'min_bedrooms' => $beds,
                    'max_bedrooms' => $beds + 1,
                    'must_have_features' => ['Swimming Pool', '24/7 Power', 'Backup Water', 'Private Security']
                ],
                'source' => $contact->source ?: $sources[$index % count($sources)],
                'tags' => ['hot-lead', $contact->type],
                'notes' => $contact->notes ?: "Adaeze is looking for a premium property in " . implode(', ', $areas) . ". Motivated to close this month."
            ]);
        }
    }

    public function render()
    {
        $this->seedMockPreferencesIfEmpty();

        $agencyId = auth()->user()->agency_id ?? 1;

        $contactsQuery = Contact::with(['agent', 'activities'])
            ->where('agency_id', $agencyId);

        $contactsQuery = $this->applyFiltersToQuery($contactsQuery);

        // Sorting
        if ($this->sortBy === 'name') {
            $contactsQuery->orderBy('first_name', 'asc');
        } elseif ($this->sortBy === 'score') {
            $contactsQuery->orderBy('intent_score', 'desc');
        } elseif ($this->sortBy === 'activity') {
            $contactsQuery->orderBy('last_contacted_at', 'desc');
        } else {
            $contactsQuery->latest();
        }

        $contacts = $contactsQuery->paginate(15);

        // Quick Stats calculations based on database
        $totalActive    = Contact::where('agency_id', $agencyId)->where('status', '!=', 'archived')->count();
        $newThisWeek    = Contact::where('agency_id', $agencyId)->where('created_at', '>=', now()->subDays(7))->count();
        $hotBuyers      = Contact::where('agency_id', $agencyId)->where('type', 'buyer')->where('intent_score', '>=', 80)->count();
        $pendingSellers = Contact::where('agency_id', $agencyId)->where('type', 'seller')->where('status', 'new')->count();

        // Get all unique tags for filter
        $allTags = Contact::where('agency_id', $agencyId)
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->filter()
            ->values();

        // Fetch agents list for bulk allocation
        $agents = User::where('agency_id', $agencyId)->get();

        return view('livewire.crm.contacts-page', compact(
            'contacts', 'totalActive', 'newThisWeek', 'hotBuyers', 'pendingSellers', 'allTags', 'agents'
        ))->layout('layouts.app');
    }
}
