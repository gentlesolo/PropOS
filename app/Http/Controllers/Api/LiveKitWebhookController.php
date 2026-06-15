<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Queue\Jobs\TranscribeCallJob;
use App\Infrastructure\Services\LiveKitVoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LiveKitWebhookController extends Controller
{
    public function __construct(private readonly LiveKitVoiceService $liveKit) {}

    public function handle(Request $request): Response
    {
        // LiveKit signs webhooks with a JWT in the Authorization header
        $token = str_replace('Bearer ', '', $request->header('Authorization', ''));

        if (! $this->liveKit->verifyWebhookToken($token)) {
            Log::warning('LiveKit webhook: invalid token');
            return response('', 401);
        }

        $event = $request->input('event');

        // Both room events and egress events carry a room name, just in different paths
        $roomName = $request->input('room.name')
            ?? $request->input('egress.room_name')
            ?? null;

        $call = $roomName
            ? Call::where('livekit_room_name', $roomName)->first()
            : null;

        match ($event) {
            'participant_joined' => $this->onParticipantJoined($call, $request->input('participant', [])),
            'participant_left'   => $this->onParticipantLeft($call, $request->input('participant', [])),
            'room_finished'      => $this->onRoomFinished($call),
            'egress_ended'       => $this->onEgressEnded($call, $request->input('egress', [])),
            default              => null,
        };

        return response('', 204);
    }

    // Lead (SIP participant) answered — call is now in progress
    private function onParticipantJoined(?Call $call, array $participant): void
    {
        if (! $call) return;

        $identity = $participant['identity'] ?? '';

        if (str_starts_with($identity, 'lead_')) {
            $call->update(['status' => 'in-progress']);
        }
    }

    // Lead (SIP participant) dropped the call
    private function onParticipantLeft(?Call $call, array $participant): void
    {
        if (! $call) return;

        $identity = $participant['identity'] ?? '';

        if (str_starts_with($identity, 'lead_') && $call->status === 'in-progress') {
            $call->update(['status' => 'completed', 'ended_at' => now()]);
        }
    }

    // Room closed (everyone left or agent hung up from the app)
    private function onRoomFinished(?Call $call): void
    {
        if (! $call) return;

        if (! in_array($call->status, ['completed', 'no-answer', 'failed'])) {
            $status = $call->status === 'initiated' ? 'no-answer' : 'completed';
            $call->update(['status' => $status, 'ended_at' => now()]);
        }
    }

    // Recording complete — store URL and kick off transcription
    private function onEgressEnded(?Call $call, array $egress): void
    {
        if (! $call) return;

        $status = $egress['status'] ?? '';

        if ($status !== 'EGRESS_COMPLETE') {
            Log::warning('LiveKit: egress ended with non-complete status', [
                'status'    => $status,
                'egress_id' => $egress['egress_id'] ?? null,
            ]);
            return;
        }

        $fileResults = $egress['file_results'] ?? [];
        $recordingUrl = ! empty($fileResults) ? ($fileResults[0]['download_url'] ?? null) : null;

        if (! $recordingUrl) {
            Log::warning('LiveKit: egress_ended event has no download_url', [
                'egress_id' => $egress['egress_id'] ?? null,
            ]);
            return;
        }

        $durationNs = ! empty($fileResults) ? ($fileResults[0]['duration'] ?? 0) : 0;
        $durationSec = (int) round($durationNs / 1_000_000_000);

        $call->update([
            'recording_url'    => $recordingUrl,
            'recording_sid'    => $egress['egress_id'] ?? null,
            'duration_seconds' => $durationSec ?: $call->duration_seconds,
        ]);

        TranscribeCallJob::dispatch($call)->onQueue('ai');
    }
}
