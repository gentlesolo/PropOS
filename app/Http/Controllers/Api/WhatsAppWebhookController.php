<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle WhatsApp Cloud API webhook verification (GET).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Receive inbound WhatsApp messages (POST).
     */
    public function receive(Request $request)
    {
        $payload = $request->all();

        // Walk the Cloud API payload structure
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $msg) {
                    $this->storeInbound($msg, $value['metadata'] ?? []);
                }

                // Handle status updates for existing outbound messages
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->updateStatus($status);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function storeInbound(array $msg, array $meta): void
    {
        $from = $msg['from'] ?? null;
        if (!$from) return;

        $body = match ($msg['type'] ?? 'text') {
            'text' => $msg['text']['body'] ?? '',
            'image' => '[Image received]',
            'document' => '[Document received]',
            'audio' => '[Audio received]',
            'video' => '[Video received]',
            'location' => '[Location: ' . ($msg['location']['latitude'] ?? '') . ',' . ($msg['location']['longitude'] ?? '') . ']',
            default => '[Unsupported message type: ' . ($msg['type'] ?? 'unknown') . ']',
        };

        // Try to find a matching contact by phone
        $contact = Contact::where('phone', 'like', '%' . ltrim($from, '+'))
            ->orWhere('phone', $from)
            ->first();

        WhatsAppMessage::create([
            'agency_id' => $contact?->agency_id,
            'contact_id' => $contact?->id,
            'external_id' => $msg['id'] ?? null,
            'to_number' => $from,           // inbound: "to" is the sender we'll reply to
            'body' => $body,
            'direction' => 'inbound',
            'status' => 'delivered',
        ]);
    }

    private function updateStatus(array $status): void
    {
        $waId = $status['id'] ?? null;
        $newStatus = $status['status'] ?? null;

        if (!$waId || !$newStatus) return;

        WhatsAppMessage::where('whatsapp_message_id', $waId)
            ->update(['status' => $newStatus]);
    }
}
