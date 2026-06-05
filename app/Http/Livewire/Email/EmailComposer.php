<?php

namespace App\Http\Livewire\Email;

use App\Application\CRM\Actions\LogContactActivityAction;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailAccount;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\EmailTemplate;
use App\Infrastructure\Persistence\Models\EmailThread;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class EmailComposer extends Component
{
    public bool   $open           = false;
    public bool   $minimized      = false;

    // Pre-fill props (set via dispatch event)
    public string $to_email       = '';
    public string $to_name        = '';
    public ?int   $contact_id     = null;
    public ?int   $thread_id      = null;
    public string $subject        = '';
    public string $body_html      = '';

    // Account & template pickers
    public ?int   $email_account_id = null;
    public ?int   $template_id      = null;

    // State
    public bool   $sending        = false;

    protected function rules(): array
    {
        return [
            'to_email'    => 'required|email|max:255',
            'subject'     => 'required|string|max:255',
            'body_html'   => 'required|string|min:1',
        ];
    }

    protected $listeners = [
        'openEmailComposer' => 'openWith',
    ];

    public function openWith(array $data = []): void
    {
        $this->reset(['to_email', 'to_name', 'contact_id', 'thread_id', 'subject', 'body_html', 'template_id', 'email_account_id']);
        $this->fill($data);

        // Prepend signature from default account
        if (! $this->email_account_id) {
            $default = $this->getDefaultAccount();
            if ($default) {
                $this->email_account_id = $default->id;
                if ($default->email_signature_html && ! $this->body_html) {
                    $this->body_html = '<br><br>' . $default->email_signature_html;
                }
            }
        }

        // Pre-fill Re: if replying to a thread
        if ($this->thread_id && ! $this->subject) {
            $thread        = EmailThread::find($this->thread_id);
            $this->subject = $thread ? 'Re: ' . $thread->subject : '';
        }

        $this->open      = true;
        $this->minimized = false;
    }

    public function updatedTemplateId(?int $value): void
    {
        if (! $value) return;

        $template = EmailTemplate::find($value);
        if (! $template) return;

        $contact = $this->contact_id ? Contact::find($this->contact_id) : null;
        $vars    = $contact ? [
            'first_name' => $contact->first_name,
            'last_name'  => $contact->last_name,
            'full_name'  => $contact->full_name,
        ] : [];

        $this->subject  = $template->renderSubject($vars);
        $this->body_html = $template->body_html;
    }

    public function updatedEmailAccountId(?int $value): void
    {
        if (! $value) return;
        $account = EmailAccount::find($value);
        if ($account?->email_signature_html && trim(strip_tags($this->body_html)) === '') {
            $this->body_html = '<br><br>' . $account->email_signature_html;
        }
    }

    public function send(LogContactActivityAction $logger): void
    {
        $this->validate();

        $user    = auth()->user();
        $account = $this->email_account_id
            ? EmailAccount::find($this->email_account_id)
            : $this->getDefaultAccount();

        $messageId = '<' . Str::uuid() . '@propos>';

        // Resolve or create thread
        $threadId = $this->thread_id;
        if (! $threadId) {
            $thread = EmailThread::create([
                'agency_id'        => $user->agency_id,
                'email_account_id' => $account?->id,
                'contact_id'       => $this->contact_id,
                'subject'          => $this->subject,
                'participants'     => array_unique(array_filter([$this->to_email, $account?->email_address])),
                'last_message_at'  => now(),
            ]);
            $threadId = $thread->id;
        } else {
            EmailThread::where('id', $threadId)->update(['last_message_at' => now()]);
        }

        // Dispatch the email
        $this->dispatchEmail($account, $messageId);

        // Log to email_logs
        $log = EmailLog::create([
            'agency_id'        => $user->agency_id,
            'email_account_id' => $account?->id,
            'thread_id'        => $threadId,
            'contact_id'       => $this->contact_id,
            'sent_by'          => $user->id,
            'direction'        => 'outbound',
            'to_email'         => $this->to_email,
            'to_name'          => $this->to_name ?: null,
            'from_email'       => $account?->email_address ?? config('mail.from.address'),
            'from_name'        => $account?->name ?? $user->name,
            'subject'          => $this->subject,
            'body_html'        => $this->body_html,
            'message_id'       => $messageId,
            'in_reply_to'      => $this->getInReplyTo(),
            'status'           => 'sent',
            'sent_at'          => now(),
        ]);

        // Log activity on contact
        if ($this->contact_id) {
            $contact = Contact::find($this->contact_id);
            if ($contact) {
                $logger->execute($contact, 'email', $this->subject, strip_tags($this->body_html));
                $contact->update(['last_contacted_at' => now()]);
            }
        }

        $this->open    = false;
        $this->sending = false;
        $this->dispatch('emailSent', logId: $log->id);
        $this->dispatch('notify', message: 'Email sent.', type: 'success');
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function minimize(): void
    {
        $this->minimized = ! $this->minimized;
    }

    private function dispatchEmail(?EmailAccount $account, string $messageId): void
    {
        $fromAddress = $account?->email_address ?? config('mail.from.address');
        $fromName    = $account?->name ?? config('mail.from.name');
        $toEmail     = $this->to_email;
        $toName      = $this->to_name;
        $subject     = $this->subject;
        $bodyHtml    = $this->body_html;
        $inReplyTo   = $this->getInReplyTo();

        // If account has SMTP override, configure on-the-fly mailer
        if ($account?->smtp_host) {
            Config::set('mail.mailers.smtp', [
                'transport'  => 'smtp',
                'host'       => $account->smtp_host,
                'port'       => $account->smtp_port,
                'encryption' => $account->smtp_encryption === 'none' ? null : $account->smtp_encryption,
                'username'   => $account->username,
                'password'   => $account->password,
            ]);
            $mailer = Mail::mailer('smtp');
        } else {
            $mailer = Mail::mailer();
        }

        $mailer->send([], [], function ($message) use ($fromAddress, $fromName, $toEmail, $toName, $subject, $bodyHtml, $messageId, $inReplyTo) {
            $message->from($fromAddress, $fromName)
                    ->to($toEmail, $toName ?: null)
                    ->subject($subject)
                    ->html($bodyHtml)
                    ->getHeaders()
                    ->addTextHeader('Message-ID', $messageId);

            if ($inReplyTo) {
                $message->getHeaders()->addTextHeader('In-Reply-To', $inReplyTo);
                $message->getHeaders()->addTextHeader('References', $inReplyTo);
            }
        });
    }

    private function getInReplyTo(): ?string
    {
        if (! $this->thread_id) return null;

        $last = EmailLog::where('thread_id', $this->thread_id)
            ->whereNotNull('message_id')
            ->latest()
            ->value('message_id');

        return $last;
    }

    private function getDefaultAccount(): ?EmailAccount
    {
        $user = auth()->user();
        return EmailAccount::where('agency_id', $user->agency_id)
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->where('user_id', $user->id)
                ->orWhere('is_shared', true)
            )
            ->orderByDesc('is_default')
            ->first();
    }

    public function render()
    {
        $user = auth()->user();

        $accounts = EmailAccount::where('agency_id', $user->agency_id)
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->where('user_id', $user->id)
                ->orWhere('is_shared', true)
            )
            ->orderByDesc('is_default')
            ->get(['id', 'name', 'email_address', 'is_default']);

        $templates = EmailTemplate::where('agency_id', $user->agency_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return view('livewire.email.email-composer', compact('accounts', 'templates'));
    }
}
