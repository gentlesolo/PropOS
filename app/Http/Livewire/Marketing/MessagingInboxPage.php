<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\EmailThread;
use App\Infrastructure\Persistence\Models\SmsMessage;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use App\Infrastructure\Services\SmsService;
use Livewire\Component;

class MessagingInboxPage extends Component
{
    // ── Filters ───────────────────────────────────────────────────────────────
    public string $channel       = 'all';
    public string $search        = '';
    public string $contactSearch = '';
    public string $threadFilter  = 'all'; // all | mine | unassigned | unread

    // ── Email thread view ─────────────────────────────────────────────────────
    public ?int $selectedThreadId = null;

    // ── Legacy contact thread (SMS/WhatsApp) ─────────────────────────────────
    public ?int $selectedContactId = null;

    // ── Compose form ──────────────────────────────────────────────────────────
    public bool   $showCompose      = false;
    public string $composeChannel   = 'email';
    public string $compose_to       = '';
    public string $compose_subject  = '';
    public string $compose_body     = '';
    public ?int   $compose_contact_id = null;

    // ── Email reply panel ─────────────────────────────────────────────────────
    public string $replyBody        = '';

    protected $listeners = [
        'emailSent' => '$refresh',
    ];

    // ── Thread actions ────────────────────────────────────────────────────────

    public function selectThread(int $id): void
    {
        $this->selectedThreadId  = $id;
        $this->selectedContactId = null;

        $thread = EmailThread::find($id);
        if ($thread) {
            $thread->markRead();
        }
    }

    public function closeThread(): void
    {
        $this->selectedThreadId = null;
    }

    public function archiveThread(int $id): void
    {
        EmailThread::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['is_archived' => true]);

        if ($this->selectedThreadId === $id) {
            $this->selectedThreadId = null;
        }

        $this->dispatch('notify', message: 'Thread archived.', type: 'info');
    }

    public function assignThread(int $threadId, int $userId): void
    {
        EmailThread::where('id', $threadId)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['assigned_to' => $userId]);
    }

    public function replyToThread(): void
    {
        $this->validate(['replyBody' => 'required|string|min:1']);

        $thread = EmailThread::where('id', $this->selectedThreadId)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();

        $this->dispatch('openEmailComposer', [
            'thread_id'   => $thread->id,
            'to_email'    => collect($thread->participants)->first(fn ($p) => $p !== auth()->user()->email) ?? '',
            'subject'     => 'Re: ' . $thread->subject,
            'contact_id'  => $thread->contact_id,
        ]);

        $this->replyBody = '';
    }

    public function openComposeForThread(): void
    {
        if (! $this->selectedThreadId) return;

        $thread = EmailThread::find($this->selectedThreadId);
        if (! $thread) return;

        $otherParticipant = collect($thread->participants)
            ->first(fn ($p) => $p !== auth()->user()->email) ?? '';

        $this->dispatch('openEmailComposer', [
            'thread_id'  => $thread->id,
            'to_email'   => $otherParticipant,
            'subject'    => 'Re: ' . $thread->subject,
            'contact_id' => $thread->contact_id,
        ]);
    }

    // ── Legacy compose (SMS/WhatsApp) ─────────────────────────────────────────

    public function selectContact(int $id): void
    {
        $this->selectedContactId = $id;
        $this->selectedThreadId  = null;
        $this->showCompose       = false;
    }

    public function openCompose(?int $contactId = null): void
    {
        // Email compose opens via the global EmailComposer component
        if ($this->composeChannel === 'email') {
            $contact = $contactId ? Contact::find($contactId) : null;
            $this->dispatch('openEmailComposer', [
                'to_email'   => $contact?->email ?? '',
                'to_name'    => $contact?->full_name ?? '',
                'contact_id' => $contactId,
            ]);
            return;
        }

        $this->compose_contact_id = $contactId ?? $this->selectedContactId;
        $this->showCompose        = true;
        $this->compose_to         = '';
        $this->compose_subject    = '';
        $this->compose_body       = '';

        if ($this->compose_contact_id) {
            $contact = Contact::find($this->compose_contact_id);
            if ($contact) {
                $this->compose_to = $this->composeChannel === 'email'
                    ? ($contact->email ?? '')
                    : ($contact->phone ?? '');
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

    public function sendMessage(SmsService $smsService): void
    {
        $rules = [
            'composeChannel' => 'required|in:sms,whatsapp',
            'compose_to'     => 'required|string',
            'compose_body'   => 'required|string|min:1',
        ];

        $this->validate($rules);

        $agencyId = auth()->user()->agency_id;
        $contact  = $this->compose_contact_id ? Contact::find($this->compose_contact_id) : null;

        if ($this->composeChannel === 'sms') {
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
        $userId   = auth()->id();

        // ── Email threads ──────────────────────────────────────────────────────
        $emailThreads = EmailThread::with(['contact', 'assignedAgent'])
            ->where('agency_id', $agencyId)
            ->where('is_archived', false)
            ->when($this->search, fn ($q) => $q->where('subject', 'like', "%{$this->search}%"))
            ->when($this->threadFilter === 'mine', fn ($q) => $q->where('assigned_to', $userId))
            ->when($this->threadFilter === 'unassigned', fn ($q) => $q->whereNull('assigned_to'))
            ->when($this->threadFilter === 'unread', fn ($q) => $q->where('unread_count', '>', 0))
            ->when(! in_array($this->channel, ['all', 'email']), fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('last_message_at')
            ->limit(60)
            ->get();

        // ── Selected thread messages ───────────────────────────────────────────
        $selectedThread  = null;
        $threadMessages  = collect();
        $teamMembers     = collect();

        if ($this->selectedThreadId) {
            $selectedThread = EmailThread::with('contact', 'assignedAgent')
                ->where('agency_id', $agencyId)
                ->find($this->selectedThreadId);

            if ($selectedThread) {
                $threadMessages = EmailLog::where('thread_id', $selectedThread->id)
                    ->orderBy('created_at')
                    ->get();

                $teamMembers = User::where('agency_id', $agencyId)
                    ->where('status', 'active')
                    ->get(['id', 'first_name', 'last_name']);
            }
        }

        // ── SMS messages ──────────────────────────────────────────────────────
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

        // ── Contact list (SMS/WhatsApp panel) ─────────────────────────────────
        $contacts = Contact::where('agency_id', $agencyId)
            ->when($this->contactSearch, fn ($q) => $q
                ->where('first_name', 'like', "%{$this->contactSearch}%")
                ->orWhere('last_name', 'like', "%{$this->contactSearch}%")
                ->orWhere('phone', 'like', "%{$this->contactSearch}%"))
            ->orderBy('first_name')
            ->limit(40)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        // ── Legacy contact thread (SMS/WhatsApp) ─────────────────────────────
        $selectedContact = null;
        $thread          = collect();

        if ($this->selectedContactId) {
            $selectedContact = Contact::where('agency_id', $agencyId)->find($this->selectedContactId);

            if ($selectedContact) {
                $smsList = SmsMessage::where('agency_id', $agencyId)
                    ->where('contact_id', $selectedContact->id)
                    ->latest()->limit(30)->get()
                    ->map(fn ($s) => [
                        'type' => 'sms', 'direction' => $s->direction,
                        'body' => $s->body, 'status' => $s->status,
                        'at'   => $s->sent_at ?? $s->created_at,
                    ]);

                $waMsgs = WhatsAppMessage::where('agency_id', $agencyId)
                    ->where('contact_id', $selectedContact->id)
                    ->latest()->limit(30)->get()
                    ->map(fn ($w) => [
                        'type' => 'whatsapp', 'direction' => $w->direction,
                        'body' => $w->body, 'status' => $w->status,
                        'at'   => $w->sent_at ?? $w->created_at,
                    ]);

                $thread = $smsList->concat($waMsgs)->sortBy('at')->values();
            }
        }

        // ── Stats ─────────────────────────────────────────────────────────────
        $stats = [
            'email_threads'   => EmailThread::where('agency_id', $agencyId)->where('is_archived', false)->count(),
            'email_unread'    => EmailThread::where('agency_id', $agencyId)->where('unread_count', '>', 0)->count(),
            'sms_sent'        => SmsMessage::where('agency_id', $agencyId)->where('direction', 'outbound')->count(),
            'sms_inbound'     => SmsMessage::where('agency_id', $agencyId)->where('direction', 'inbound')->count(),
            'wa_total'        => WhatsAppMessage::where('agency_id', $agencyId)->count(),
            'wa_inbound'      => WhatsAppMessage::where('agency_id', $agencyId)->where('direction', 'inbound')->count(),
        ];

        return view('livewire.marketing.messaging-inbox-page', compact(
            'emailThreads', 'selectedThread', 'threadMessages', 'teamMembers',
            'smsMessages', 'whatsAppMessages',
            'selectedContact', 'thread', 'contacts', 'stats'
        ))->layout('layouts.app');
    }
}
