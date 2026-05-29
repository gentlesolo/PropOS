<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
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
            'last_name' => $contact->last_name,
            'email' => $contact->email ?? '',
            'phone' => $contact->phone ?? '',
            'status' => $contact->status,
            'notes' => $contact->notes ?? '',
        ]);
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
    }

    public function saveContact()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:new,active,qualified,nurturing,closed,archived',
            'notes' => 'nullable|string',
        ]);

        $this->contact->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'status' => $this->status,
            'notes' => $this->notes ?: null,
        ]);

        $this->showEditForm = false;
        $this->contact->refresh();
    }

    public function render()
    {
        $activities = $this->contact->activities()->with('user')->get();

        return view('livewire.crm.contact-detail-page', [
            'activities' => $activities,
        ])->layout('layouts.app');
    }
}
