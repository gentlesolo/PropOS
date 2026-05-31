<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\SmsMessage;
use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use App\Infrastructure\ExternalServices\WhatsApp\WhatsAppApiClient;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class MobileMessagingController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly EmailService $email,
        private readonly WhatsAppApiClient $whatsapp,
    ) {}

    /**
     * Unified inbox — one thread per contact, most recent message first.
     */
    public function inbox(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $whatsapp = WhatsAppMessage::where('agency_id', $agencyId)
            ->with('contact:id,first_name,last_name,avatar_path')
            ->select(['id', 'contact_id', 'body', 'direction', 'status', 'created_at'])
            ->selectRaw("'whatsapp' as channel")
            ->latest()
            ->get();

        $sms = SmsMessage::where('agency_id', $agencyId)
            ->with('contact:id,first_name,last_name,avatar_path')
            ->select(['id', 'contact_id', 'body', 'direction', 'status', 'created_at'])
            ->selectRaw("'sms' as channel")
            ->latest()
            ->get();

        $email = EmailLog::where('agency_id', $agencyId)
            ->with('contact:id,first_name,last_name,avatar_path')
            ->select(['id', 'contact_id', 'subject as body', 'direction', 'status', 'sent_at as created_at'])
            ->selectRaw("'email' as channel")
            ->latest('sent_at')
            ->get();

        // Merge and group by contact; one row per contact showing latest message
        $threads = collect()
            ->merge($whatsapp)
            ->merge($sms)
            ->merge($email)
            ->filter(fn ($m) => $m->contact_id)
            ->sortByDesc('created_at')
            ->unique('contact_id')
            ->values()
            ->map(fn ($m) => [
                'contact_id'   => $m->contact_id,
                'contact'      => $m->contact,
                'last_message' => [
                    'id'        => $m->id,
                    'body'      => mb_substr((string) $m->body, 0, 80),
                    'channel'   => $m->channel,
                    'direction' => $m->direction,
                    'status'    => $m->status,
                    'sent_at'   => $m->created_at,
                ],
            ]);

        if ($request->search) {
            $s = mb_strtolower($request->search);
            $threads = $threads->filter(fn ($t) =>
                $t['contact'] &&
                str_contains(mb_strtolower($t['contact']->first_name . ' ' . $t['contact']->last_name), $s)
            )->values();
        }

        return response()->json($threads->take(50));
    }

    /**
     * All messages for a single contact across all channels, ordered chronologically.
     */
    public function thread(Contact $contact): JsonResponse
    {
        $whatsapp = WhatsAppMessage::where('contact_id', $contact->id)
            ->select(['id', 'body', 'direction', 'status', 'created_at'])
            ->selectRaw("'whatsapp' as channel")
            ->get();

        $sms = SmsMessage::where('contact_id', $contact->id)
            ->select(['id', 'body', 'direction', 'status', 'created_at'])
            ->selectRaw("'sms' as channel")
            ->get();

        $email = EmailLog::where('contact_id', $contact->id)
            ->select(['id', 'subject as body', 'direction', 'status', 'sent_at as created_at'])
            ->selectRaw("'email' as channel")
            ->get();

        $messages = collect()
            ->merge($whatsapp)
            ->merge($sms)
            ->merge($email)
            ->sortBy('created_at')
            ->values();

        return response()->json([
            'contact'  => $contact->only(['id', 'first_name', 'last_name', 'phone', 'email', 'avatar_path']),
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message on the preferred channel (WhatsApp → SMS fallback).
     */
    public function send(Request $request, Contact $contact): JsonResponse
    {
        $request->validate([
            'body'    => 'required|string|max:1600',
            'channel' => 'required|in:whatsapp,sms,email',
        ]);

        $message = match ($request->channel) {
            'sms'   => $this->sms->send($contact->phone, $request->input('body'), $contact),
            'email' => $this->sendEmail($contact, $request->input('body')),
            default => $this->sendWhatsApp($contact, $request->input('body')),
        };

        return response()->json(['message' => 'Sent.', 'id' => $message->id ?? null], 201);
    }

    private function sendWhatsApp(Contact $contact, string $body): WhatsAppMessage
    {
        $message = WhatsAppMessage::create([
            'agency_id'  => $contact->agency_id,
            'contact_id' => $contact->id,
            'to_number'  => $contact->phone,
            'body'       => $body,
            'direction'  => 'outbound',
            'status'     => 'queued',
        ]);

        $this->whatsapp->sendTextMessage($message);

        return $message;
    }

    private function sendEmail(Contact $contact, string $body): EmailLog
    {
        return $this->email->sendRaw(
            toEmail: $contact->email,
            subject: 'Message from PropOS',
            bodyHtml: nl2br(e($body)),
            contact: $contact,
        );
    }
}
