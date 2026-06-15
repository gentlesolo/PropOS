<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\AgentNumber;
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
        $this->accountSid   = config('services.twilio.sid') ?? '';
        $this->authToken    = config('services.twilio.token') ?? '';
        $this->twimlAppSid  = config('services.twilio.twiml_app_sid') ?? '';
        $this->apiKeySid    = config('services.twilio.api_key_sid') ?? '';
        $this->apiKeySecret = config('services.twilio.api_key_secret') ?? '';

        abort_if(
            empty($this->accountSid) || empty($this->apiKeySid) || empty($this->apiKeySecret),
            503,
            'Twilio is not configured. Set TWILIO_SID, TWILIO_API_KEY_SID, and TWILIO_API_KEY_SECRET in .env.',
        );

        $this->client = new Client($this->accountSid, $this->authToken);
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
     * TwiML for outbound calls.
     * callerId is the number shown to the recipient — the agency's verified/provisioned number.
     * Recording starts automatically when the call is answered (dual-channel) and Twilio
     * posts the completed recording to the /webhooks/calls/recording callback.
     */
    public function buildOutboundTwiml(string $toNumber, string $callbackUrl, ?string $callerId = null): string
    {
        $response = new VoiceResponse();

        $response->say(
            'This call may be recorded for quality and training purposes.',
            ['voice' => 'Polly.Joanna', 'language' => 'en-US'],
        );

        $dialOptions = [
            'action'                        => $callbackUrl,
            'method'                        => 'POST',
            'record'                        => 'record-from-answer-dual',
            'recordingStatusCallback'       => route('api.mobile.calls.recording'),
            'recordingStatusCallbackMethod' => 'POST',
        ];

        if ($callerId) {
            $dialOptions['callerId'] = $callerId;
        }

        $dial = $response->dial($dialOptions);
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

        $agentNumber = $this->getAgentNumber($agent);
        $dialOptions = [];
        if ($agentNumber?->twilio_number) {
            $dialOptions['callerId'] = $agentNumber->twilio_number;
        }

        $dial = $response->dial($dialOptions);
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
            'sid'    => $recording->sid,
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
     * Provision a new Twilio number in the given country and assign it to an agent.
     * Supports any country where Twilio sells local numbers (US, GB, ZA, GH, KE, CA …).
     * Note: some countries (e.g. NG) require a Regulatory Bundle in the Twilio Console
     * before numbers can be purchased — the API call will throw if that bundle is missing.
     */
    public function provisionNumber(User $agent, string $countryCode = 'US', string $areaCode = ''): AgentNumber
    {
        $country   = strtoupper($countryCode);
        $searchOpts = $areaCode ? ['areaCode' => $areaCode] : [];

        $available = $this->client->availablePhoneNumbers($country)->local->read($searchOpts, 1);

        if (empty($available) && $areaCode) {
            // Retry without area-code restriction
            $available = $this->client->availablePhoneNumbers($country)->local->read([], 1);
        }

        abort_if(empty($available), 503, "No available phone numbers in {$country}.");

        $number = $this->client->incomingPhoneNumbers->create([
            'phoneNumber' => $available[0]->phoneNumber,
            'voiceUrl'    => route('api.mobile.calls.inbound'),
            'voiceMethod' => 'POST',
        ]);

        // Deactivate the agent's other numbers
        AgentNumber::where('user_id', $agent->id)->update(['active' => false]);

        return AgentNumber::create([
            'agency_id'      => $agent->agency_id,
            'user_id'        => $agent->id,
            'twilio_number'  => $number->phoneNumber,
            'display_number' => $number->phoneNumber,
            'twilio_sid'     => $number->sid,
            'number_type'    => 'twilio_provisioned',
            'country_code'   => $country,
            'verified'       => true,
            'verified_at'    => now(),
            'active'         => true,
        ]);
    }

    /**
     * Release a Twilio-provisioned number back to Twilio.
     */
    public function releaseNumber(string $twilioSid): void
    {
        $this->client->incomingPhoneNumbers($twilioSid)->delete();
    }

    /**
     * Step 1 of BYON: place a verification call to the agency's number.
     * Twilio calls the number, reads the validation_code, and asks the recipient
     * to press it on their keypad. Once confirmed the number appears in OutgoingCallerIds.
     */
    public function initiateCallerIdVerification(string $phoneNumber): array
    {
        $validation = $this->client->validationRequests->create($phoneNumber, [
            'friendlyName' => 'Propos Agency Number Verification',
        ]);

        return [
            'validation_code' => $validation->validationCode,
            'call_sid'        => $validation->callSid,
        ];
    }

    /**
     * Step 2 of BYON: check whether Twilio has confirmed the number.
     * Returns the OutgoingCallerId SID when verified, null while still pending.
     */
    public function checkCallerIdVerified(string $phoneNumber): ?string
    {
        $callerIds = $this->client->outgoingCallerIds->read(['phoneNumber' => $phoneNumber]);

        return ! empty($callerIds) ? $callerIds[0]->sid : null;
    }

    /**
     * Remove a verified caller ID from the Twilio account.
     */
    public function removeCallerId(string $callerIdSid): void
    {
        $this->client->outgoingCallerIds($callerIdSid)->delete();
    }

    public function getAgentNumber(User $agent): ?AgentNumber
    {
        return AgentNumber::where('user_id', $agent->id)
            ->where('active', true)
            ->latest()
            ->first();
    }
}
