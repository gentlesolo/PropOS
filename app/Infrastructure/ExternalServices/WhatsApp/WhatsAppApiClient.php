<?php

namespace App\Infrastructure\ExternalServices\WhatsApp;

use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiClient
{
    private ?string $phoneNumberId;
    private ?string $accessToken;
    private string $apiVersion;
    private string $baseUrl;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken   = config('services.whatsapp.access_token');
        $this->apiVersion    = config('services.whatsapp.api_version', 'v19.0');
        $this->baseUrl       = "https://graph.facebook.com/{$this->apiVersion}";
    }

    public function sendTextMessage(WhatsAppMessage $message): bool
    {
        if (! $this->isConfigured()) {
            Log::info('WhatsApp: API not configured, message queued locally only.', [
                'message_id' => $message->id,
                'to'         => $message->to_number,
            ]);
            $message->update(['status' => 'queued']);
            return false;
        }

        $to = $this->normalisePhone($message->to_number);

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $message->body],
                ]);

            if ($response->successful()) {
                $waMessageId = $response->json('messages.0.id');
                $message->update([
                    'status'              => 'sent',
                    'external_message_id' => $waMessageId,
                    'sent_at'             => now(),
                ]);
                return true;
            }

            Log::error('WhatsApp send failed', [
                'message_id' => $message->id,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            $message->update(['status' => 'failed']);
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);
            $message->update(['status' => 'failed']);
            return false;
        }
    }

    public function sendTemplateMessage(WhatsAppMessage $message, string $templateName, array $components = []): bool
    {
        if (! $this->isConfigured()) {
            $message->update(['status' => 'queued']);
            return false;
        }

        $to = $this->normalisePhone($message->to_number);

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $templateName,
                        'language'   => ['code' => 'en_US'],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                $waMessageId = $response->json('messages.0.id');
                $message->update([
                    'status'              => 'sent',
                    'external_message_id' => $waMessageId,
                    'sent_at'             => now(),
                ]);
                return true;
            }

            $message->update(['status' => 'failed']);
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp template send exception', ['error' => $e->getMessage()]);
            $message->update(['status' => 'failed']);
            return false;
        }
    }

    /**
     * Process an inbound webhook payload from Meta.
     * Returns parsed message data or null if not a valid inbound message.
     */
    public function parseWebhook(array $payload): ?array
    {
        $entry   = $payload['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        $value   = $changes['value'] ?? null;

        if (! $value) {
            return null;
        }

        // Status update (delivery/read receipts)
        if (! empty($value['statuses'])) {
            $this->handleStatusUpdate($value['statuses'][0]);
            return null;
        }

        // Inbound message
        $inbound = $value['messages'][0] ?? null;
        if (! $inbound) {
            return null;
        }

        return [
            'from'       => $inbound['from'],
            'wa_id'      => $inbound['id'],
            'type'       => $inbound['type'],
            'body'       => $inbound['text']['body'] ?? null,
            'timestamp'  => $inbound['timestamp'],
            'contact'    => $value['contacts'][0] ?? null,
        ];
    }

    private function handleStatusUpdate(array $status): void
    {
        $waMessageId  = $status['id'] ?? null;
        $statusValue  = $status['status'] ?? null;

        if (! $waMessageId || ! $statusValue) {
            return;
        }

        WhatsAppMessage::where('external_message_id', $waMessageId)
            ->update(['status' => $statusValue]);
    }

    /**
     * Dispatch all queued outbound messages (called by scheduler).
     */
    public function flushQueue(): void
    {
        $messages = WhatsAppMessage::where('status', 'queued')
            ->where('direction', 'outbound')
            ->limit(100)
            ->get();

        foreach ($messages as $message) {
            $this->sendTextMessage($message);
        }
    }

    private function normalisePhone(string $phone): string
    {
        // Strip all non-digit characters, ensure no leading +
        $digits = preg_replace('/\D/', '', $phone);

        // Prepend country code if missing (default to +234 Nigeria — configurable)
        if (strlen($digits) <= 10) {
            $default = config('services.whatsapp.default_country_code', '234');
            $digits = ltrim($digits, '0');
            $digits = $default . $digits;
        }

        return $digits;
    }

    private function isConfigured(): bool
    {
        return ! empty($this->phoneNumberId) && ! empty($this->accessToken);
    }
}
