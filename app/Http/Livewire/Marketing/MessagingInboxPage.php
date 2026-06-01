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
    // ── Filters ───────────────────────────────────────────────────────────────
    public string $channel       = 'all';
    public string $search        = '';
    public string $contactSearch = '';

    // ── Selected contact thread ────────────────────────────────────────────────
    public ?int $selectedContactId = null;

    // ── Compose form ──────────────────────────────────────────────────────────
    public bool   $showCompose      = false;
    public string $composeChannel   = 'email';
    public string $compose_to       = '';
    public string $compose_subject  = '';
    public string $compose_body     = '';
    public ?int   $compose_contact_id = null;

    public function selectContact(int $id): void
    {
        $this->selectedContactId = $id;
        $this->showCompose       = false;
    }

    public function closeThread(): void
    {
        $this->selectedContactId = null;
    }

    public function openCompose(?int $contactId = null): void
    {
        $this->compose_contact_id = $contactId ?? $this->selectedContactId;
        $this->showCompose        = true;
        $this->composeChannel     = 'email';
        $this->compose_to         = '';
        $this->compose_subject    = '';
        $this->compose_body       = '';

        // Pre-fill To from contact
        if ($this->compose_contact_id) {
            $contact = Contact::find($this->compose_contact_id);
            if ($contact) {
                $this->compose_to = $contact->email ?? $contact->phone ?? '';
            }
        }
    }

    public function updatedComposeChannel(): void
    {
        if ($this->compose_contact_id) {
            $contact = Contact::find($this->compose_contact_id);
            if ($contact) {
                $this->compose_to = $this->composeChannel === 'email'
                    ? ($contact->email ?? '')
                    : ($contact->phone ?? '');
            }
        }
    }

    public function sendMessage(EmailService $emailService, SmsService $smsService): void
    {
        $rules = [
            'composeChannel' => 'required|in:email,sms,whatsapp',
            'compose_to'     => 'required|string',
            'compose_body'   => 'required|string|min:1',
        ];

        if ($this->composeChannel === 'email') {
            $rules['compose_subject'] = 'required|string|max:255';
        }

        $this->validate($rules);

        $agencyId = auth()->user()->agency_id;
        $contact  = $this->compose_contact_id ? Contact::find($this->compose_contact_id) : null;

        if ($this->composeChannel === 'email') {
            $emailService->sendRaw(
                $this->compose_to,
                $this->compose_subject,
                nl2br(e($this->compose_body)),
                $contact,
                $agencyId,
            );
        } elseif ($this->composeChannel === 'sms') {
            $smsService->send($this->compose_to, $this->compose_body, $contact);
        } elseif ($this->composeChannel === 'whatsapp') {
            WhatsAppMessage::create([
                'agency_id'  => $agencyId,
                'contact_id' => $contact?->id,
                'to_number'  => $this->compose_to,
                'body'       => $this->compose_body,
                'direction'  => 'outbound',
                'status'     => 'queued',
                'sent_at'    => now(),
            ]);
        }

        $this->reset(['showCompose', 'compose_to', 'compose_subject', 'compose_body']);
        $this->dispatch('notify', message: 'Message sent.', type: 'success');
    }

    public function deleteEmailLog(int $id): void
    {
        EmailLog::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();
        $this->dispatch('notify', message: 'Email log removed.', type: 'info');
    }

    public function deleteSms(int $id): void
    {
        SmsMessage::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();
        $this->dispatch('notify', message: 'SMS removed.', type: 'info');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        // ── Global lists ──────────────────────────────────────────────────────
        $emailLogs = EmailLog::with('contact', 'sentBy')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q
                ->where('subject', 'like', "%{$this->search}%")
                ->orWhere('to_email', 'like', "%{$this->search}%"))
            ->when(! in_array($this->channel, ['all', 'email']), fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $smsMessages = SmsMessage::with('contact', 'sentBy')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q
                ->where('body', 'like', "%{$this->search}%")
                ->orWhere('to_number', 'like', "%{$this->search}%"))
            ->when(! in_array($this->channel, ['all', 'sms']), fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $whatsAppMessages = WhatsAppMessage::with('contact')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q->where('body', 'like', "%{$this->search}%"))
            ->when(! in_array($this->channel, ['all', 'whatsapp']), fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // ── Contact list with last-message preview ────────────────────────────
        $contacts = Contact::where('agency_id', $agencyId)
            ->when($this->contactSearch, fn ($q) => $q
                ->where('first_name', 'like', "%{$this->contactSearch}%")
                ->orWhere('last_name', 'like', "%{$this->contactSearch}%")
                ->orWhere('phone', 'like', "%{$this->contactSearch}%"))
            ->orderBy('first_name')
            ->limit(40)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        // ── Contact thread ────────────────────────────────────────────────────
        $selectedContact = null;
        $thread          = collect();

        if ($this->selectedContactId) {
            $selectedContact = Contact::where('agency_id', $agencyId)
                ->find($this->selectedContactId);

            if ($selectedContact) {
                // Merge all message types into a single chronological thread
                $emails = EmailLog::where('agency_id', $agencyId)
                    ->where('contact_id', $selectedContact->id)
                    ->latest()
                    ->limit(30)
                    ->get()
                    ->map(fn ($e) => [
                        'id'        => $e->id,
                        'type'      => 'email',
                        'direction' => 'outbound',
                        'body'      => $e->subject,
                        'status'    => $e->status,
                        'at'        => $e->sent_at ?? $e->created_at,
                        'model_id'  => $e->id,
                    ]);

                $smsList = SmsMessage::where('agency_id', $agencyId)
                    ->where('contact_id', $selectedContact->id)
                    ->latest()
                    ->limit(30)
                    ->get()
                    ->map(fn ($s) => [
                        'id'        => $s->id,
                        'type'      => 'sms',
                        'direction' => $s->direction,
                        'body'      => $s->body,
                        'status'    => $s->status,
                        'at'        => $s->sent_at ?? $s->created_at,
                        'model_id'  => $s->id,
                    ]);

                $waMsgs = WhatsAppMessage::where('agency_id', $agencyId)
                    ->where('contact_id', $selectedContact->id)
                    ->latest()
                    ->limit(30)
                    ->get()
                    ->map(fn ($w) => [
                        'id'        => $w->id,
                        'type'      => 'whatsapp',
                        'direction' => $w->direction,
                        'body'      => $w->body,
                        'status'    => $w->status,
                        'at'        => $w->sent_at ?? $w->created_at,
                        'model_id'  => $w->id,
                    ]);

                $thread = $emails->concat($smsList)->concat($waMsgs)
                    ->sortBy('at')
                    ->values();
            }
        }

        // ── Stats ─────────────────────────────────────────────────────────────
        $stats = [
            'emails_sent'  => EmailLog::where('agency_id', $agencyId)->count(),
            'emails_opened'=> EmailLog::where('agency_id', $agencyId)->where('status', 'opened')->count(),
            'sms_sent'     => SmsMessage::where('agency_id', $agencyId)->where('direction', 'outbound')->count(),
            'sms_inbound'  => SmsMessage::where('agency_id', $agencyId)->where('direction', 'inbound')->count(),
            'wa_total'     => WhatsAppMessage::where('agency_id', $agencyId)->count(),
            'wa_inbound'   => WhatsAppMessage::where('agency_id', $agencyId)->where('direction', 'inbound')->count(),
        ];

        return view('livewire.marketing.messaging-inbox-page', compact(
            'emailLogs', 'smsMessages', 'whatsAppMessages',
            'selectedContact', 'thread', 'contacts', 'stats'
        ))->layout('layouts.app');
    }
}
