<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\AgentNumber;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\User;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class TwilioVoiceService
{
    private Client $client;
    private string $accountSid;
    private string $authToken;
    private string $twimlAppSid;
    private string $apiKeySid;
    private string $apiKeySecret;

    public function __construct()
    {
        $this->accountSid   = config('services.twilio.sid');
        $this->authToken    = config('services.twilio.token');
        $this->twimlAppSid  = config('services.twilio.twiml_app_sid');
        $this->apiKeySid    = config('services.twilio.api_key_sid');
        $this->apiKeySecret = config('services.twilio.api_key_secret');
        $this->client       = new Client($this->accountSid, $this->authToken);
    }

    /**
     * Generate a short-lived Twilio Access Token for the mobile Voice SDK.
     */
    public function generateAccessToken(User $agent): string
    {
        $token = new AccessToken(
            $this->accountSid,
            $this->apiKeySid,
            $this->apiKeySecret,
            3600,
            (string) $agent->id,
        );

        $voiceGrant = new VoiceGrant();
        $voiceGrant->setOutgoingApplicationSid($this->twimlAppSid);
        $voiceGrant->setIncomingAllow(true);

        $token->addGrant($voiceGrant);

        return $token->toJWT();
    }

    /**
     * TwiML for outbound calls: play consent announcement then dial.
     */
    public function buildOutboundTwiml(string $toNumber, string $callbackUrl): string
    {
        $response = new VoiceResponse();

        $response->say(
            'This call may be recorded for quality and training purposes.',
            ['voice' => 'Polly.Joanna', 'language' => 'en-US'],
        );

        $dial = $response->dial();
        $dial->number($toNumber);

        return (string) $response;
    }

    /**
     * TwiML for inbound calls: greet and connect to agent's browser/app.
     */
    public function buildInboundTwiml(User $agent): string
    {
        $response = new VoiceResponse();

        $response->say('Please hold while we connect your call.', ['voice' => 'Polly.Joanna']);

        $dial = $response->dial(['callerId' => $this->getAgentNumber($agent)?->twilio_number]);
        $dial->client((string) $agent->id);

        return (string) $response;
    }

    /**
     * Enable recording on an active call.
     */
    public function startRecording(string $callSid): array
    {
        $recording = $this->client->calls($callSid)->recordings->create([
            'recordingChannels' => 'dual',
        ]);

        return [
            'sid' => $recording->sid,
            'status' => $recording->status,
        ];
    }

    /**
     * Fetch the recording MP3 URL (accessible without auth via Twilio CDN).
     */
    public function getRecordingUrl(string $recordingSid): string
    {
        return "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Recordings/{$recordingSid}.mp3";
    }

    /**
     * Provision a new Twilio number and assign it to an agent.
     */
    public function provisionNumber(User $agent, string $areaCode = '1'): AgentNumber
    {
        $available = $this->client->availablePhoneNumbers('US')
            ->local
            ->read(['areaCode' => $areaCode], 1);

        if (empty($available)) {
            $available = $this->client->availablePhoneNumbers('US')->local->read([], 1);
        }

        $number = $this->client->incomingPhoneNumbers->create([
            'phoneNumber' => $available[0]->phoneNumber,
            'voiceUrl'    => route('api.mobile.calls.inbound'),
            'voiceMethod' => 'POST',
        ]);

        return AgentNumber::create([
            'agency_id'     => $agent->agency_id,
            'user_id'       => $agent->id,
            'twilio_number' => $number->phoneNumber,
            'twilio_sid'    => $number->sid,
            'active'        => true,
        ]);
    }

    public function getAgentNumber(User $agent): ?AgentNumber
    {
        return AgentNumber::where('user_id', $agent->id)
            ->where('active', true)
            ->latest()
            ->first();
    }
}
