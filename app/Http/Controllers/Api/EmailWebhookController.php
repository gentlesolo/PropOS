<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailWebhookController extends Controller
{
    /**
     * Mailgun webhook — POST /api/webhooks/email/mailgun
     * Mailgun sends a JSON body with event data signed with an HMAC.
     */
    public function mailgun(Request $request)
    {
        if (! $this->validateMailgunSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $eventData = $request->input('event-data', []);
        $event     = $eventData['event'] ?? $request->input('event');
        $recipient = $eventData['recipient'] ?? $request->input('recipient');
        $messageId = $eventData['message']['headers']['message-id'] ?? $request->input('Message-Id');

        $this->applyEvent($event, $recipient, $messageId);

        return response()->json(['status' => 'ok']);
    }

    /**
     * SendGrid webhook — POST /api/webhooks/email/sendgrid
     * SendGrid sends an array of event objects.
     */
    public function sendgrid(Request $request)
    {
        $events = $request->json()->all();

        if (! is_array($events)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        foreach ($events as $event) {
            $type      = $event['event'] ?? null;
            $email     = $event['email'] ?? null;
            $messageId = $event['smtp-id'] ?? $event['sg_message_id'] ?? null;

            if ($type && $email) {
                $this->applyEvent($type, $email, $messageId);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Map a provider event type to an EmailLog status and persist it.
     */
    private function applyEvent(string $event, ?string $email, ?string $messageId): void
    {
        // Map provider event names to email_logs.status enum values
        // Enum: queued | sent | delivered | opened | clicked | bounced | failed
        $status = match (strtolower($event)) {
            'opened', 'open'                              => 'opened',
            'clicked', 'click'                            => 'clicked',
            'bounced', 'bounce', 'permanent_fail'         => 'bounced',
            'failed', 'dropped', 'rejected'               => 'failed',
            'unsubscribed', 'unsubscribe', 'complained',
            'spam', 'spam_complaint'                      => 'failed',
            'delivered', 'delivery'                       => 'delivered',
            default                                       => null,
        };

        if (! $status || ! $email) {
            return;
        }

        // Try to match by provider message-id first (most precise), fall back to email
        $matched = null;

        if ($messageId) {
            $clean   = trim($messageId, '<>');
            $matched = EmailLog::where('provider_message_id', $clean)->first();
        }

        $log = $matched ?? EmailLog::where('to_email', $email)
            ->whereIn('status', ['sent', 'delivered', 'queued'])
            ->latest()
            ->first();

        if (! $log) {
            Log::debug('EmailWebhook: no matching EmailLog', compact('event', 'email', 'messageId'));
            return;
        }

        $updates = ['status' => $status];

        if ($status === 'opened' && ! $log->opened_at) {
            $updates['opened_at'] = now();
        } elseif ($status === 'clicked' && ! $log->clicked_at) {
            $updates['clicked_at'] = now();
        } elseif (in_array($status, ['bounced', 'failed'])) {
            $updates['error_message'] = $log->error_message ?? "Delivery failed: {$event}";
        }

        $log->update($updates);
    }

    private function validateMailgunSignature(Request $request): bool
    {
        $signingKey = config('services.mailgun.webhook_signing_key');

        // Skip in local/testing
        if (! $signingKey || app()->environment('local', 'testing')) {
            return true;
        }

        // Mailgun sends signature data at event-data.signature or top-level signature
        $sig       = $request->input('signature', $request->input('event-data.signature', []));
        $timestamp = $sig['timestamp'] ?? null;
        $token     = $sig['token'] ?? null;
        $signature = $sig['signature'] ?? null;

        if (! $timestamp || ! $token || ! $signature) {
            return false;
        }

        // Reject stale webhooks (>5 min)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $token, $signingKey);

        return hash_equals($expected, $signature);
    }
}
