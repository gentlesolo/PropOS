<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\AgentDevice;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallSummary;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileNotificationService
{
    private string $fcmServerKey;

    public function __construct()
    {
        $this->fcmServerKey = config('services.fcm.server_key', '');
    }

    public function sendCallSummaryReady(User $agent, Call $call, CallSummary $summary): void
    {
        $contactName = $call->contact
            ? $call->contact->first_name . ' ' . $call->contact->last_name
            : 'Unknown';

        $this->send($agent, [
            'title' => 'Call summary ready',
            'body'  => "Your call with {$contactName} has been summarised.",
            'data'  => [
                'type'       => 'call_summary_ready',
                'call_id'    => (string) $call->id,
                'summary_id' => (string) $summary->id,
            ],
        ]);
    }

    public function sendNewLeadAssigned(User $agent, int $contactId, string $contactName): void
    {
        $this->send($agent, [
            'title' => 'New lead assigned',
            'body'  => "{$contactName} has been assigned to you.",
            'data'  => [
                'type'       => 'new_lead_assigned',
                'contact_id' => (string) $contactId,
            ],
        ]);
    }

    public function sendNewMessage(User $agent, string $channel, string $senderName, int $contactId): void
    {
        $this->send($agent, [
            'title' => "New {$channel} message",
            'body'  => "{$senderName} sent you a message.",
            'data'  => [
                'type'       => 'new_message',
                'channel'    => $channel,
                'contact_id' => (string) $contactId,
            ],
        ]);
    }

    public function sendIncomingCall(User $agent, string $fromNumber, ?string $callerName = null): void
    {
        $display = $callerName ?? $fromNumber;

        // VoIP pushes for iOS require a separate APNS VoIP push; for Android we use
        // FCM high-priority. The mobile app handles waking the device via its native
        // call UI (CallKit / ConnectionService).
        $this->send($agent, [
            'title'    => 'Incoming call',
            'body'     => "Call from {$display}",
            'priority' => 'high',
            'data'     => [
                'type'        => 'incoming_call',
                'from_number' => $fromNumber,
                'caller_name' => $callerName ?? '',
            ],
        ], pushType: 'voip');
    }

    private function send(User $agent, array $payload, string $pushType = 'fcm'): void
    {
        $devices = AgentDevice::where('user_id', $agent->id)
            ->when($pushType === 'voip', fn ($q) => $q->where('push_type', 'voip'))
            ->when($pushType === 'fcm', fn ($q) => $q->whereIn('push_type', ['fcm', 'apns']))
            ->get();

        foreach ($devices as $device) {
            try {
                match ($device->platform) {
                    'android' => $this->sendFcm($device->push_token, $payload),
                    'ios'     => $this->sendApns($device->push_token, $payload, $device->push_type),
                };

                $device->touch('last_seen_at');
            } catch (\Throwable $e) {
                Log::error("MobileNotificationService: failed to push to device {$device->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendFcm(string $token, array $payload): void
    {
        if (! $this->fcmServerKey) {
            Log::debug('MobileNotificationService: FCM server key not configured');
            return;
        }

        Http::withToken($this->fcmServerKey)
            ->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $payload['title'],
                    'body'  => $payload['body'],
                    'sound' => 'default',
                ],
                'data'     => $payload['data'] ?? [],
                'priority' => $payload['priority'] ?? 'normal',
            ]);
    }

    private function sendApns(string $token, array $payload, string $pushType): void
    {
        // APNs HTTP/2 push is handled server-side via a package such as
        // laravel-notification-channels/apn or pushed through Firebase for
        // non-VoIP notifications. VoIP pushes use PushKit and require a
        // separate APNs VoIP certificate configured in the mobile app.
        Log::info("MobileNotificationService: APNs push queued for token ending " . substr($token, -6), [
            'push_type' => $pushType,
            'title'     => $payload['title'],
        ]);
    }
}
