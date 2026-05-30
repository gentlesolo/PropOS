<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\ExternalServices\WhatsApp\WhatsAppApiClient;
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
     * Receive inbound WhatsApp messages and status updates (POST).
     */
    public function receive(Request $request, WhatsAppApiClient $client)
    {
        $parsed = $client->parseWebhook($request->all());

        if ($parsed) {
            $this->storeInbound($parsed);
        }

        // Fallback: also handle via raw payload for edge cases
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->updateStatus($status);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function storeInbound(array $parsed): void
    {
        $from = $parsed['from'] ?? null;
        if (! $from) {
            return;
        }

        $contact = Contact::where('phone', 'like', '%' . ltrim($from, '+'))
            ->orWhere('phone', $from)
            ->first();

        WhatsAppMessage::create([
            'agency_id'           => $contact?->agency_id,
            'contact_id'          => $contact?->id,
            'external_message_id' => $parsed['wa_id'] ?? null,
            'to_number'           => $from,
            'body'                => $parsed['body'] ?? '[' . ($parsed['type'] ?? 'unknown') . ' received]',
            'direction'           => 'inbound',
            'status'              => 'delivered',
        ]);
    }

    private function updateStatus(array $status): void
    {
        $waId = $status['id'] ?? null;
        $newStatus = $status['status'] ?? null;

        if (!$waId || !$newStatus) return;

        WhatsAppMessage::where('external_message_id', $waId)
            ->update(['status' => $newStatus]);
    }
}
