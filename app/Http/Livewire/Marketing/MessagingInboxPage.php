<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\SmsMessage;
use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\SmsService;
use Livewire\Component;

class MessagingInboxPage extends Component
{
    public string $channel = 'all';
    public string $search = '';
    public ?int $selectedContactId = null;
    public string $composeChannel = 'email';
    public bool $showCompose = false;

    // Compose form
    public string $compose_to = '';
    public string $compose_subject = '';
    public string $compose_body = '';

    public function selectContact(int $id): void
    {
        $this->selectedContactId = $id;
    }

    public function sendMessage(EmailService $emailService, SmsService $smsService): void
    {
        $this->validate([
            'compose_to' => 'required',
            'compose_body' => 'required|string',
        ]);

        $contact = Contact::find($this->selectedContactId);

        if ($this->composeChannel === 'email') {
            $this->validate(['compose_subject' => 'required|string|max:255']);
            $emailService->sendRaw(
                $this->compose_to,
                $this->compose_subject,
                nl2br(e($this->compose_body)),
                $contact,
                auth()->user()->agency_id
            );
        } elseif ($this->composeChannel === 'sms') {
            $smsService->send($this->compose_to, $this->compose_body, $contact);
        }

        $this->reset(['showCompose', 'compose_to', 'compose_subject', 'compose_body']);
        $this->dispatch('notify', message: 'Message sent.', type: 'success');
    }

    public function render()
    {
        $emailLogs = EmailLog::with('contact', 'sentBy')
            ->when($this->search, fn ($q) => $q->where('to_email', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"))
            ->when($this->channel === 'email', fn ($q) => $q)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $smsMessages = SmsMessage::with('contact', 'sentBy')
            ->when($this->search, fn ($q) => $q->where('to_number', 'like', "%{$this->search}%")
                ->orWhere('body', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $whatsAppMessages = WhatsAppMessage::with('contact')
            ->when($this->search, fn ($q) => $q->where('body', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $selectedContact = $this->selectedContactId
            ? Contact::with(['emailLogs' => fn ($q) => $q->latest()->limit(20),
                'smsMessages' => fn ($q) => $q->latest()->limit(20)])
                ->find($this->selectedContactId)
            : null;

        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $stats = [
            'emails_sent' => EmailLog::where('status', 'sent')->count(),
            'emails_opened' => EmailLog::where('status', 'opened')->count(),
            'sms_sent' => SmsMessage::where('direction', 'outbound')->count(),
            'sms_inbound' => SmsMessage::where('direction', 'inbound')->count(),
        ];

        return view('livewire.marketing.messaging-inbox-page', compact(
            'emailLogs', 'smsMessages', 'whatsAppMessages', 'selectedContact', 'contacts', 'stats'
        ))->layout('layouts.app');
    }
}
