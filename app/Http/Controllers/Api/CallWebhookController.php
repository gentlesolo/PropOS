<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\AgentNumber;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Queue\Jobs\TranscribeCallJob;
use App\Infrastructure\Services\MobileNotificationService;
use App\Infrastructure\Services\TwilioVoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CallWebhookController extends Controller
{
    public function __construct(
        private readonly TwilioVoiceService $twilio,
        private readonly MobileNotificationService $notifier,
    ) {}

    /**
     * Twilio calls this when an agent dials from the app (TwiML App voice URL).
     */
    public function outbound(Request $request): Response
    {
        $toNumber = $request->input('To');

        // Twilio sends the identity as "client:{userId}" — extract the numeric ID
        $fromRaw  = $request->input('From', '');
        $agentId  = ltrim(str_replace('client:', '', $fromRaw));
        $agent    = User::find($agentId);

        $callerId = null;
        if ($agent) {
            $agentNumber = AgentNumber::where('user_id', $agent->id)
                ->where('active', true)
                ->where('verified', true)
                ->latest()
                ->first();
            $callerId = $agentNumber?->getEffectiveDisplayNumber();
        }

        $twiml = $this->twilio->buildOutboundTwiml(
            $toNumber,
            route('api.mobile.calls.status'),
            $callerId,
        );

        return response($twiml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Twilio calls this for inbound calls to an agent's number.
     */
    public function inbound(Request $request): Response
    {
        $toNumber = $request->input('To');
        $fromNumber = $request->input('From');

        $agentNumber = \App\Infrastructure\Persistence\Models\AgentNumber::where('twilio_number', $toNumber)
            ->where('active', true)
            ->with('agent')
            ->first();

        if (! $agentNumber) {
            Log::warning("CallWebhookController: no agent found for number {$toNumber}");
            $response = new \Twilio\TwiML\VoiceResponse();
            $response->say('This number is not available.');
            return response((string) $response, 200, ['Content-Type' => 'application/xml']);
        }

        $agent = $agentNumber->agent;

        $contact = Contact::where('agency_id', $agent->agency_id)
            ->where('phone', $fromNumber)
            ->first();

        Call::create([
            'agency_id'        => $agent->agency_id,
            'agent_id'         => $agent->id,
            'contact_id'       => $contact?->id,
            'direction'        => 'inbound',
            'status'           => 'ringing',
            'provider_call_sid' => $request->input('CallSid'),
            'twilio_number'    => $toNumber,
            'remote_number'    => $fromNumber,
            'started_at'       => now(),
        ]);

        $this->notifier->sendIncomingCall(
            $agent,
            $fromNumber,
            $contact ? $contact->first_name . ' ' . $contact->last_name : null,
        );

        $twiml = $this->twilio->buildInboundTwiml($agent);

        return response($twiml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Twilio status callback — updates call record as the call progresses.
     */
    public function status(Request $request): Response
    {
        $callSid = $request->input('CallSid');
        $status  = $request->input('CallStatus');

        $call = Call::where('provider_call_sid', $callSid)->first();

        if (! $call) {
            Log::warning("CallWebhookController: unknown CallSid {$callSid}");
            return response('', 204);
        }

        $updates = ['status' => $this->mapTwilioStatus($status)];

        if (in_array($status, ['completed', 'no-answer', 'busy', 'failed', 'canceled'])) {
            $updates['ended_at']        = now();
            $updates['duration_seconds'] = (int) $request->input('CallDuration', 0);
        }

        $call->update($updates);

        return response('', 204);
    }

    /**
     * Twilio recording callback — triggers the transcription pipeline.
     */
    public function recording(Request $request): Response
    {
        $callSid      = $request->input('CallSid');
        $recordingSid = $request->input('RecordingSid');
        $recordingUrl = $request->input('RecordingUrl');

        $call = Call::where('provider_call_sid', $callSid)->first();

        if (! $call) {
            Log::warning("CallWebhookController: recording for unknown CallSid {$callSid}");
            return response('', 204);
        }

        $call->update([
            'recording_sid' => $recordingSid,
            'recording_url' => $recordingUrl . '.mp3',
        ]);

        TranscribeCallJob::dispatch($call)->onQueue('ai');

        return response('', 204);
    }

    private function mapTwilioStatus(string $twilio): string
    {
        return match ($twilio) {
            'queued', 'initiated' => 'initiated',
            'ringing'             => 'ringing',
            'in-progress'         => 'in-progress',
            'completed'           => 'completed',
            'no-answer'           => 'no-answer',
            'busy'                => 'busy',
            'failed'              => 'failed',
            'canceled'            => 'canceled',
            default               => 'initiated',
        };
    }
}
