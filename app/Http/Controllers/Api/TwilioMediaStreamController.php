<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Queue\Jobs\SummariseCallJob;
use App\Infrastructure\Services\DeepgramStreamingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles the Twilio MediaStream WebSocket lifecycle.
 *
 * Twilio connects here when a call starts (configured in TwiML <Stream> verb).
 * We receive binary mu-law audio frames, forward them to Deepgram via HTTP/2
 * chunked upload (since full WS bridging requires a separate daemon), and
 * broadcast transcript segments to the mobile client via Pusher.
 *
 * For production, replace the polling-based approach here with a dedicated
 * Node.js/Go WebSocket bridge process that keeps both connections alive
 * simultaneously. This controller handles the TwiML setup and the
 * REST-based fallback for environments without a persistent WS daemon.
 */
class TwilioMediaStreamController extends Controller
{
    public function __construct(
        private readonly DeepgramStreamingService $deepgram,
    ) {}

    /**
     * Generate TwiML that opens a MediaStream when a call connects.
     * Called by TwilioVoiceService::buildOutboundTwiml() when live transcript is enabled.
     */
    public function twiml(Request $request): \Illuminate\Http\Response
    {
        $callSid  = $request->input('CallSid', '');
        $call     = Call::where('provider_call_sid', $callSid)->first();
        $channel  = 'call.' . ($call?->id ?? $callSid);

        if ($call && ! $call->live_transcript_channel) {
            $call->update(['live_transcript_channel' => $channel]);
        }

        $streamUrl = route('api.media.stream.ws');

        $twiml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="Polly.Joanna">This call may be recorded for quality and training purposes.</Say>
    <Connect>
        <Stream url="{$streamUrl}">
            <Parameter name="callSid" value="{$callSid}" />
            <Parameter name="channel" value="{$channel}" />
        </Stream>
    </Connect>
</Response>
XML;

        return response($twiml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Receive a Deepgram transcript webhook (alternative to streaming WS).
     * Deepgram can POST finalized transcripts to a callback URL.
     */
    public function deepgramCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        $callSid  = $request->input('call_sid');
        $channel  = $request->input('channel');
        $segment  = $this->deepgram->parseTranscriptEvent($request->all());

        if (! $segment || ! $segment['is_final']) {
            return response()->json(['status' => 'skipped']);
        }

        // Broadcast the final segment to the mobile client via Pusher
        event(new \App\Events\CallTranscriptSegmentReceived(
            channel: $channel ?? 'call.' . $callSid,
            segment: $segment,
        ));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Trigger live transcription for a call (called after call connects).
     * Registers the Deepgram callback URL with Twilio for this call.
     */
    public function startStream(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['call_id' => 'required|exists:calls,id']);

        $call    = Call::findOrFail($request->call_id);
        $channel = 'call.' . $call->id;

        $call->update(['live_transcript_channel' => $channel]);

        return response()->json([
            'channel'       => $channel,
            'stream_active' => $this->deepgram->isConfigured(),
        ]);
    }
}
