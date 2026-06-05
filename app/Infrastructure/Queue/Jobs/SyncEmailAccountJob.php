<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailAccount;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\EmailThread;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEmailAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 60;

    public function __construct(public readonly int $emailAccountId) {}

    public function handle(NotificationService $notifier): void
    {
        $account = EmailAccount::find($this->emailAccountId);
        if (! $account || ! $account->is_active) return;

        if (! extension_loaded('imap')) {
            Log::warning('SyncEmailAccountJob: php-imap extension not loaded', ['account_id' => $account->id]);
            return;
        }

        $mailbox = $account->imap_connection_string . 'INBOX';

        $connection = @imap_open($mailbox, $account->username, $account->password, 0, 1);

        if (! $connection) {
            $error = imap_last_error();
            $account->update(['sync_error' => $error]);
            Log::error('SyncEmailAccountJob: IMAP connection failed', [
                'account_id' => $account->id,
                'error'      => $error,
            ]);
            return;
        }

        try {
            $account->update(['sync_error' => null]);
            $this->fetchMessages($connection, $account, $notifier);
        } finally {
            imap_close($connection);
            $account->update(['last_synced_at' => now()]);
        }
    }

    private function fetchMessages($connection, EmailAccount $account, NotificationService $notifier): void
    {
        // Fetch unseen messages (or all since last sync if first run)
        $criteria = $account->last_synced_at
            ? 'SINCE "' . $account->last_synced_at->format('d-M-Y') . '"'
            : 'UNSEEN';

        $messageNums = imap_search($connection, $criteria);
        if (! $messageNums) return;

        foreach ($messageNums as $msgNum) {
            try {
                $this->processMessage($connection, $msgNum, $account, $notifier);
            } catch (\Throwable $e) {
                Log::warning('SyncEmailAccountJob: failed to process message', [
                    'account_id' => $account->id,
                    'msg_num'    => $msgNum,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    private function processMessage($connection, int $msgNum, EmailAccount $account, NotificationService $notifier): void
    {
        $headers   = imap_headerinfo($connection, $msgNum);
        $messageId = trim($headers->message_id ?? '');
        $inReplyTo = trim($headers->in_reply_to ?? '');

        // Deduplicate by message_id
        if ($messageId && EmailLog::where('message_id', $messageId)->exists()) {
            return;
        }

        $fromEmail = $headers->from[0]->mailbox . '@' . $headers->from[0]->host;
        $fromName  = isset($headers->from[0]->personal)
            ? imap_utf8($headers->from[0]->personal)
            : $fromEmail;
        $subject   = imap_utf8($headers->subject ?? '(no subject)');
        $toEmail   = $headers->to[0]->mailbox . '@' . $headers->to[0]->host ?? $account->email_address;

        // Resolve or create thread
        $thread = $this->resolveThread($account, $subject, $fromEmail, $toEmail, $inReplyTo);

        // Resolve contact
        $contact = Contact::withoutGlobalScopes()
            ->where('agency_id', $account->agency_id)
            ->where('email', $fromEmail)
            ->first();

        if ($contact && ! $thread->contact_id) {
            $thread->update(['contact_id' => $contact->id]);
        }

        // Extract body
        [$bodyHtml, $bodyText] = $this->extractBody($connection, $msgNum);

        EmailLog::create([
            'agency_id'        => $account->agency_id,
            'email_account_id' => $account->id,
            'thread_id'        => $thread->id,
            'contact_id'       => $contact?->id,
            'direction'        => 'inbound',
            'from_email'       => $fromEmail,
            'from_name'        => $fromName,
            'to_email'         => $toEmail,
            'subject'          => $subject,
            'body_html'        => $bodyHtml,
            'body_text'        => $bodyText,
            'message_id'       => $messageId ?: null,
            'in_reply_to'      => $inReplyTo ?: null,
            'status'           => 'delivered',
            'sent_at'          => now()->createFromTimestamp($headers->udate ?? time()),
        ]);

        $thread->incrementUnread();

        // Notify the assigned agent or account owner
        $notifyUser = $thread->assigned_to
            ? User::find($thread->assigned_to)
            : ($account->user ?? null);

        if ($notifyUser) {
            $notifier->notifyUser(
                $notifyUser,
                'inbound_email',
                'New email from ' . $fromName,
                $subject,
                route('marketing.inbox'),
                'info',
            );
        }
    }

    private function resolveThread(EmailAccount $account, string $subject, string $fromEmail, string $toEmail, string $inReplyTo): EmailThread
    {
        // Try to match via in_reply_to → message_id
        if ($inReplyTo) {
            $parentLog = EmailLog::where('message_id', $inReplyTo)->first();
            if ($parentLog?->thread_id) {
                return EmailThread::find($parentLog->thread_id);
            }
        }

        // Create a new thread
        return EmailThread::create([
            'agency_id'        => $account->agency_id,
            'email_account_id' => $account->id,
            'subject'          => $subject,
            'participants'     => array_unique([$fromEmail, $toEmail]),
            'last_message_at'  => now(),
        ]);
    }

    private function extractBody($connection, int $msgNum): array
    {
        $structure = imap_fetchstructure($connection, $msgNum);
        $html = '';
        $text = '';

        if (! isset($structure->parts)) {
            // Single-part message
            $body = imap_fetchbody($connection, $msgNum, '1');
            $body = $this->decodeBody($body, $structure->encoding ?? 0);
            if (($structure->subtype ?? '') === 'HTML') {
                $html = $body;
            } else {
                $text = $body;
            }
        } else {
            foreach ($structure->parts as $index => $part) {
                $partNum = $index + 1;
                $body    = imap_fetchbody($connection, $msgNum, (string) $partNum);
                $body    = $this->decodeBody($body, $part->encoding ?? 0);
                if (strtoupper($part->subtype ?? '') === 'HTML') {
                    $html = $body;
                } elseif (strtoupper($part->subtype ?? '') === 'PLAIN') {
                    $text = $body;
                }
            }
        }

        return [$html ?: null, $text ?: null];
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($body),
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }
}
