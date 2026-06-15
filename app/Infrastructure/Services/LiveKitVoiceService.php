<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\AgentNumber;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveKitVoiceService
{
    private string $serverUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $sipTrunkId;
    private string $atApiKey;
    private string $atUsername;
    private string $atSenderId;

    public function __construct()
    {
        $this->serverUrl  = rtrim(config('services.livekit.server_url') ?? '', '/');
        $this->apiKey     = config('services.livekit.api_key') ?? '';
        $this->apiSecret  = config('services.livekit.api_secret') ?? '';
        $this->sipTrunkId = config('services.livekit.sip_trunk_id') ?? '';
        $this->atApiKey   = config('services.africastalking.api_key') ?? '';
        $this->atUsername = config('services.africastalking.username') ?? '';
        $this->atSenderId = config('services.africastalking.sender_id') ?? '';

        abort_if(
            empty($this->serverUrl) || empty($this->apiKey) || empty($this->apiSecret),
            503,
            'LiveKit is not configured. Set LIVEKIT_SERVER_URL, LIVEKIT_API_KEY, LIVEKIT_API_SECRET in .env.',
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // JWT helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function mintJwt(array $grants, string $identity, int $ttl = 3600): string
    {
        $header  = $this->b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->b64url(json_encode([
            'iss' => $this->apiKey,
            'sub' => $identity,
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $ttl,
            ...$grants,
        ]));

        $sig = $this->b64url(hash_hmac('sha256', "$header.$payload", $this->apiSecret, true));

        return "$header.$payload.$sig";
    }

    // Short-lived admin token for server-to-server API calls
    private function adminToken(): string
    {
        return $this->mintJwt([
            'video' => ['roomAdmin' => true, 'roomList' => true, 'roomCreate' => true],
        ], 'backend-service', 60);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate a LiveKit access token that lets the mobile SDK join a specific room.
     */
    public function generateAccessToken(User $agent, string $roomName): string
    {
        return $this->mintJwt([
            'video' => [
                'roomJoin'       => true,
                'room'           => $roomName,
                'canPublish'     => true,
                'canSubscribe'   => true,
                'canPublishData' => true,
            ],
        ], (string) $agent->id);
    }

    /**
     * Create a LiveKit room.
     * empty_timeout: auto-close the room after 5 min of no participants.
     */
    public function createRoom(string $roomName): void
    {
        $res = $this->api('livekit.RoomService/CreateRoom', [
            'name'             => $roomName,
            'empty_timeout'    => 300,
            'max_participants' => 10,
        ]);

        abort_if($res->failed(), 503, 'Failed to create call session.');
    }

    /**
     * Start an audio-only Egress recording on the room, delivering an OGG file to S3.
     * Returns the S3 file path so we can construct the recording URL later.
     */
    public function startEgress(string $roomName): string
    {
        $filepath = "recordings/{$roomName}.ogg";

        $res = $this->api('livekit.Egress/StartRoomCompositeEgress', [
            'room_name'    => $roomName,
            'audio_only'   => true,
            'file_outputs' => [[
                'file_type' => 'OGG',
                'filepath'  => $filepath,
                's3'        => [
                    'access_key'       => config('filesystems.disks.s3.key'),
                    'secret'           => config('filesystems.disks.s3.secret'),
                    'bucket'           => config('filesystems.disks.s3.bucket'),
                    'region'           => config('filesystems.disks.s3.region', 'us-east-1'),
                    'force_path_style' => false,
                ],
            ]],
        ]);

        if ($res->failed()) {
            Log::warning('LiveKit: startEgress failed', ['room' => $roomName, 'body' => $res->body()]);
        }

        return $filepath;
    }

    /**
     * Dial the lead's phone number via the Africa's Talking SIP trunk and join them
     * into the LiveKit room as a SIP participant.
     * callerId is the agent's verified local number (BYON) — shown to the lead on caller ID.
     */
    public function dialSipParticipant(string $roomName, string $toNumber, string $callerId): void
    {
        abort_if(empty($this->sipTrunkId), 503, 'SIP trunk not configured. Set LIVEKIT_SIP_TRUNK_ID in .env.');

        $res = $this->api('livekit.SIP/CreateSIPParticipant', [
            'sip_trunk_id'         => $this->sipTrunkId,
            'sip_call_to'          => $toNumber,
            'room_name'            => $roomName,
            'participant_identity' => 'lead_' . preg_replace('/\D/', '', $toNumber),
            'participant_name'     => 'Lead',
            'from'                 => $callerId,
            'play_ringtone'        => true,
        ]);

        abort_if($res->failed(), 503, 'Failed to connect call: ' . $res->json('message', 'SIP error'));
    }

    /**
     * Close a LiveKit room immediately (agent hung up from the app).
     */
    public function closeRoom(string $roomName): void
    {
        $this->api('livekit.RoomService/DeleteRoom', ['room' => $roomName]);
    }

    /**
     * Verify a LiveKit webhook token (sent as Authorization: Bearer <jwt>).
     * The JWT is signed with the API secret using HS256.
     */
    public function verifyWebhookToken(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $expected = hash_hmac('sha256', "$headerB64.$payloadB64", $this->apiSecret, true);
        $actual   = base64_decode(strtr($sigB64, '-_', '+/') . '==');

        if (! hash_equals($expected, $actual)) {
            return false;
        }

        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/') . '=='), true);

        return isset($payload['iss']) && $payload['iss'] === $this->apiKey;
    }

    public function getAgentNumber(User $agent): ?AgentNumber
    {
        return AgentNumber::where('user_id', $agent->id)
            ->where('active', true)
            ->where('verified', true)
            ->latest()
            ->first();
    }

    /**
     * Send a 6-digit OTP to the phone number via Africa's Talking SMS.
     * Used to verify BYON numbers without Twilio.
     */
    public function sendVerificationOtp(string $phoneNumber, string $code): bool
    {
        if (empty($this->atApiKey) || empty($this->atUsername)) {
            Log::warning('LiveKitVoiceService: Africa\'s Talking not configured.');
            return false;
        }

        $res = Http::withHeaders([
            'apiKey' => $this->atApiKey,
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', array_filter([
            'username' => $this->atUsername,
            'to'       => $phoneNumber,
            'message'  => "Your Propos verification code is {$code}. Valid for 10 minutes. Do not share.",
            'from'     => $this->atSenderId ?: null,
        ]));

        if ($res->failed()) {
            Log::warning('LiveKitVoiceService: AT SMS failed', ['body' => $res->body()]);
            return false;
        }

        return true;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────────────────

    // All LiveKit server APIs use Twirp over HTTP with JSON encoding.
    private function api(string $service, array $body): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->adminToken())
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->serverUrl}/twirp/{$service}", $body);
    }
}
