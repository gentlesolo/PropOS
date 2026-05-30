<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\SmsMessage;

class SmsService
{
    public function send(
        string $toNumber,
        string $body,
        ?Contact $contact = null,
        string $provider = 'twilio',
    ): SmsMessage {
        $agencyId = auth()->user()?->agency_id ?? $contact?->agency_id ?? 0;

        $message = SmsMessage::create([
            'agency_id' => $agencyId,
            'contact_id' => $contact?->id,
            'sent_by' => auth()->id(),
            'to_number' => $this->normalizeNumber($toNumber),
            'body' => $body,
            'direction' => 'outbound',
            'status' => 'queued',
            'provider' => $provider,
        ]);

        try {
            $result = $this->dispatch($message->to_number, $body, $provider);
            $message->update([
                'status' => 'sent',
                'provider_message_id' => $result['id'] ?? null,
                'cost' => $result['cost'] ?? null,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return $message;
    }

    public function recordInbound(string $fromNumber, string $body, string $providerMessageId, ?Contact $contact = null): SmsMessage
    {
        return SmsMessage::create([
            'agency_id' => $contact?->agency_id ?? 0,
            'contact_id' => $contact?->id,
            'to_number' => config('services.sms.from_number', ''),
            'from_number' => $fromNumber,
            'body' => $body,
            'direction' => 'inbound',
            'status' => 'delivered',
            'provider_message_id' => $providerMessageId,
            'delivered_at' => now(),
        ]);
    }

    private function dispatch(string $number, string $body, string $provider): array
    {
        // Twilio adapter
        if ($provider === 'twilio' && config('services.twilio.sid')) {
            $client = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
            $msg = $client->messages->create($number, [
                'from' => config('services.twilio.from'),
                'body' => $body,
            ]);
            return ['id' => $msg->sid, 'cost' => null];
        }

        // Africa's Talking adapter
        if ($provider === 'africastalking' && config('services.africastalking.api_key')) {
            $at = \AfricasTalking\SDK\AfricasTalking::initialize(
                config('services.africastalking.username'),
                config('services.africastalking.api_key')
            );
            $result = $at->sms()->send(['to' => $number, 'message' => $body]);
            return ['id' => $result['SMSMessageData']['Recipients'][0]['messageId'] ?? null, 'cost' => null];
        }

        // Log-only fallback (dev/test)
        \Log::info("SMS [mock] to {$number}: {$body}");
        return ['id' => 'mock-' . uniqid(), 'cost' => 0];
    }

    private function normalizeNumber(string $number): string
    {
        $number = preg_replace('/[^+\d]/', '', $number);
        if (!str_starts_with($number, '+')) {
            $number = '+' . ltrim($number, '0');
        }
        return $number;
    }
}
