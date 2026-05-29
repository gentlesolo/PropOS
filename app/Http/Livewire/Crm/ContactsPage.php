<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\DetectDuplicateContactsAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Infrastructure\Notifications\ContactCreatedNotification;
use Livewire\Component;
use Livewire\WithPagination;
use App\Infrastructure\Persistence\Models\Contact;

class ContactsPage extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $type = 'buyer';
    public string $source = '';

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';

    public array $duplicates = [];
    public bool $confirmDuplicate = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterStatus' => ['except' => ''],
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
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
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
        ]);

        $logAction->execute($contact, 'system', 'Contact created', 'Contact added to CRM.');
        $scorer->execute($contact);

        // Notify the assigned agent (or current user) of the new contact
        auth()->user()->notify(new ContactCreatedNotification($contact));

        $this->reset(['first_name', 'last_name', 'email', 'phone', 'source', 'showCreateModal', 'duplicates', 'confirmDuplicate']);
        $this->type = 'buyer';
    }

    public function dismissDuplicates(): void
    {
        $this->confirmDuplicate = true;
        $this->duplicates = [];
    }

    /**
     * Merge $sourceId into $targetId: move all activities, deals, viewings to target, then delete source.
     */
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

    public function render()
    {
        $contacts = Contact::with('agent')
            ->when($this->search, fn($q) => $q->where(function ($sub) {
                $sub->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(15);

        $totalActive = Contact::where('status', '!=', 'archived')->count();
        $newThisWeek = Contact::where('created_at', '>=', now()->startOfWeek())->count();
        $hotBuyers = Contact::where('type', 'buyer')->where('intent_score', '>=', 80)->count();
        $pendingSellers = Contact::where('type', 'seller')->where('status', 'new')->count();

        return view('livewire.crm.contacts-page', compact(
            'contacts', 'totalActive', 'newThisWeek', 'hotBuyers', 'pendingSellers'
        ))->layout('layouts.app');
    }
}
