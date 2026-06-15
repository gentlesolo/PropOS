<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallTranscript;
use App\Infrastructure\Services\LiveKitVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CallController extends Controller
{
    public function __construct(private readonly LiveKitVoiceService $liveKit) {}

    /**
     * Return LiveKit server info and the agent's active number.
     * Called on app init so the mobile client knows where to connect.
     */
    public function token(Request $request): JsonResponse
    {
        $agentNumber = $this->liveKit->getAgentNumber($request->user());

        return response()->json([
            'server_url'   => config('services.livekit.server_url'),
            'identity'     => (string) $request->user()->id,
            'agent_number' => $agentNumber?->getEffectiveDisplayNumber(),
            'number_type'  => $agentNumber?->number_type,
            'verified'     => $agentNumber?->verified ?? false,
        ]);
    }

    /**
     * Initiate an outbound call.
     * Creates the LiveKit room, starts Egress recording, dials the lead via SIP,
     * and returns the room token so the mobile SDK can join.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'contact_id'    => 'nullable|exists:contacts,id',
            'remote_number' => 'required|string|max:20',
        ]);

        $user        = $request->user();
        $agentNumber = $this->liveKit->getAgentNumber($user);

        abort_unless(
            $agentNumber,
            422,
            'No verified phone number found. Go to More → Phone Numbers to set one up.',
        );

        $roomName = 'call_' . Str::uuid();
        $callerId = $agentNumber->getEffectiveDisplayNumber();

        // 1. Create the LiveKit room
        $this->liveKit->createRoom($roomName);

        // 2. Start dual-participant audio recording
        $this->liveKit->startEgress($roomName);

        // 3. Dial the lead via Africa's Talking SIP trunk
        $this->liveKit->dialSipParticipant($roomName, $request->remote_number, $callerId);

        // 4. Mint the agent's room access token
        $token = $this->liveKit->generateAccessToken($user, $roomName);

        // 5. Persist the call record
        $call = Call::create([
            'agency_id'         => $user->agency_id,
            'agent_id'          => $user->id,
            'contact_id'        => $request->contact_id,
            'direction'         => 'outbound',
            'status'            => 'initiated',
            'livekit_room_name' => $roomName,
            'twilio_number'     => $agentNumber->display_number,
            'remote_number'     => $request->remote_number,
            'started_at'        => now(),
        ]);

        return response()->json([
            'call_id'    => $call->id,
            'room_name'  => $roomName,
            'token'      => $token,
            'server_url' => config('services.livekit.server_url'),
        ], 201);
    }

    /**
     * Paginated call history for the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $calls = Call::with(['contact:id,first_name,last_name,avatar_path', 'summary:id,call_id,sentiment,sentiment_score'])
            ->where('agent_id', $request->user()->id)
            ->when($request->direction, fn ($q) => $q->where('direction', $request->direction))
            ->when($request->sentiment, fn ($q) => $q->whereHas('summary', fn ($s) => $s->where('sentiment', $request->sentiment)))
            ->latest('started_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($calls);
    }

    /**
     * Single call with transcript and summary.
     */
    public function show(Call $call): JsonResponse
    {
        $this->authorizeCall($call);
        $call->load(['contact', 'transcript', 'summary', 'agent:id,first_name,last_name']);
        return response()->json($call);
    }

    /**
     * Update call status (from mobile SDK callbacks).
     */
    public function updateStatus(Request $request, Call $call): JsonResponse
    {
        $this->authorizeCall($call);

        $request->validate([
            'status'           => 'required|in:ringing,in-progress,completed,no-answer,busy,failed,canceled',
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        $updates = ['status' => $request->status];

        if ($request->status === 'completed') {
            $updates['ended_at']         = now();
            $updates['duration_seconds'] = $request->duration_seconds;
        }

        $call->update($updates);

        return response()->json($call->fresh());
    }

    /**
     * Agent confirms or edits the AI-generated summary.
     */
    public function confirmSummary(Request $request, Call $call): JsonResponse
    {
        $this->authorizeCall($call);

        $request->validate([
            'summary_text'        => 'required|string',
            'action_items'        => 'nullable|array',
            'action_items.*'      => 'string',
            'suggested_next_step' => 'nullable|string',
        ]);

        $summary = $call->summary;

        if (! $summary) {
            return response()->json(['message' => 'Summary not yet generated.'], 404);
        }

        $edited = $summary->summary_text !== $request->summary_text
            || $summary->suggested_next_step !== $request->suggested_next_step;

        $summary->update([
            'summary_text'        => $request->summary_text,
            'action_items'        => $request->action_items ?? $summary->action_items,
            'suggested_next_step' => $request->suggested_next_step,
            'agent_confirmed_at'  => now(),
            'agent_edited'        => $edited,
        ]);

        return response()->json($summary->fresh());
    }

    /**
     * Return a short-lived signed URL for streaming a call recording.
     */
    public function recording(Call $call): JsonResponse
    {
        $this->authorizeCall($call);

        if (! $call->recording_url) {
            return response()->json(['message' => 'No recording available.'], 404);
        }

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'api.mobile.calls.recording.proxy',
            now()->addHour(),
            ['call' => $call->id],
        );

        return response()->json([
            'url'        => $signedUrl,
            'expires_at' => now()->addHour()->toISOString(),
            'duration'   => $call->duration_seconds,
        ]);
    }

    /**
     * Search calls by transcript keyword.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $callIds = CallTranscript::where('full_text', 'like', '%' . $request->input('q') . '%')
            ->pluck('call_id');

        $calls = Call::with(['contact:id,first_name,last_name', 'summary:id,call_id,sentiment,summary_text'])
            ->where('agent_id', $request->user()->id)
            ->whereIn('id', $callIds)
            ->latest('started_at')
            ->paginate(20);

        return response()->json($calls);
    }

    private function authorizeCall(Call $call): void
    {
        abort_unless(
            $call->agent_id === request()->user()->id || request()->user()->hasRole('admin|manager'),
            403,
            'Access denied.',
        );
    }
}
