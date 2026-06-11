<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\FollowUpSequence;
use App\Infrastructure\Persistence\Models\FollowUpStep;
use App\Application\CRM\Actions\CreateFollowUpSequenceAction;
use Livewire\Component;
use Livewire\WithPagination;

class FollowUpSequencesPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateForm = false;

    // Create form fields
    public string $contact_id = '';
    public string $name = '';
    public array $steps = [];

    // Pre-populate templates dropdown
    public string $selectedTemplate = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->addEmptyStep();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function addEmptyStep(): void
    {
        $this->steps[] = [
            'type' => 'email',
            'subject' => '',
            'message_template' => '',
            'delay_days' => 1,
        ];
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
        if (empty($this->steps)) {
            $this->addEmptyStep();
        }
    }

    public function applyTemplate(): void
    {
        if ($this->selectedTemplate === 'new_lead') {
            $this->name = 'New Lead Nurture Sequence';
            $this->steps = [
                [
                    'type' => 'email',
                    'subject' => 'Welcome to VillaCRM Agency',
                    'message_template' => 'Hi {first_name}, thank you for contacting us. How can we assist you with your property search?',
                    'delay_days' => 0,
                ],
                [
                    'type' => 'sms',
                    'subject' => '',
                    'message_template' => 'Hi {first_name}, just checking in to see if you received our email with the curated property list.',
                    'delay_days' => 2,
                ],
                [
                    'type' => 'call',
                    'subject' => 'Follow Up Phone Call',
                    'message_template' => 'Call lead to discuss budget, preferred areas, and timing.',
                    'delay_days' => 3,
                ],
            ];
        } elseif ($this->selectedTemplate === 'post_viewing') {
            $this->name = 'Post-Viewing Feedback Loop';
            $this->steps = [
                [
                    'type' => 'sms',
                    'subject' => '',
                    'message_template' => 'Hi {first_name}, thank you for viewing the property today. What were your initial thoughts?',
                    'delay_days' => 1,
                ],
                [
                    'type' => 'email',
                    'subject' => 'Property viewing feedback & next steps',
                    'message_template' => 'Hi {first_name}, we appreciate your time today. Let us know if you would like to make an offer or view other properties.',
                    'delay_days' => 2,
                ],
            ];
        }
    }

    public function createSequence(CreateFollowUpSequenceAction $action): void
    {
        $this->validate([
            'contact_id' => 'required|exists:contacts,id',
            'name' => 'required|string|max:255',
            'steps' => 'required|array|min:1',
            'steps.*.type' => 'required|in:email,sms,call,task',
            'steps.*.message_template' => 'required|string|max:2000',
            'steps.*.delay_days' => 'required|integer|min:0',
        ]);

        $contact = Contact::findOrFail($this->contact_id);
        $action->execute($contact, $this->name, $this->steps);

        $this->reset(['showCreateForm', 'contact_id', 'name', 'selectedTemplate']);
        $this->steps = [];
        $this->addEmptyStep();

        $this->dispatch('notify', message: 'Follow-up sequence created successfully.', type: 'success');
    }

    public function pauseSequence(int $id): void
    {
        $seq = FollowUpSequence::findOrFail($id);
        $seq->update(['status' => 'paused']);
        $this->dispatch('notify', message: 'Sequence paused.', type: 'success');
    }

    public function resumeSequence(int $id): void
    {
        $seq = FollowUpSequence::findOrFail($id);
        $seq->update(['status' => 'active', 'next_action_at' => now()->addDay()]);
        $this->dispatch('notify', message: 'Sequence resumed.', type: 'success');
    }

    public function cancelSequence(int $id): void
    {
        $seq = FollowUpSequence::findOrFail($id);
        $seq->update(['status' => 'cancelled']);
        $this->dispatch('notify', message: 'Sequence cancelled.', type: 'success');
    }

    public function render()
    {
        $sequences = FollowUpSequence::with('contact', 'assignedAgent', 'steps')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhereHas('contact', function ($sq) {
                        $sq->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%");
                    });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        $stats = [
            'total' => FollowUpSequence::count(),
            'active' => FollowUpSequence::where('status', 'active')->count(),
            'paused' => FollowUpSequence::where('status', 'paused')->count(),
            'completed' => FollowUpSequence::where('status', 'completed')->count(),
        ];

        return view('livewire.marketing.follow-up-sequences-page', compact('sequences', 'contacts', 'stats'))
            ->layout('layouts.app');
    }
}
