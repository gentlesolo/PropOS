Good clarification. "Using the agent's local phone number" in this context means the agent's real SIM number (e.g. their MTN +234 number) is the caller ID the lead sees — not a platform-assigned number. The call itself still goes through the app (VoIP via LiveKit), which is what makes recording possible.

Here's the full picture:

Infrastructure you'd run

Your Server (e.g. $30/mo Hetzner or DigitalOcean VM)
├── LiveKit Server        — WebRTC SFU, handles agent ↔ lead audio
├── LiveKit SIP Service   — bridges the internet call to the phone network
├── LiveKit Egress        — records rooms, delivers MP3 to your S3
└── Coturn (TURN)         — NAT traversal (bundled with LiveKit)

SIP Provider (third-party, pick one)
├── Africa's Talking  ← best for Nigeria/Africa, local routing, cheap
├── Telnyx            ← good global coverage, clean API
└── SignalWire        ← cheapest, Twilio-compatible API

Your existing backend (Laravel) + mobile app (React Native)
How the agent's local number works
This is exactly the BYON flow we already built. The agent registers their real number (e.g. +2348012345678) in the app. That number gets verified at the SIP provider as an outgoing caller ID. When the SIP provider dials the lead, it sets CLI (Caller Line Identification) to the agent's real number — the lead's phone shows the agent's real number, not a platform number.

Full call flow
One-time setup per agent

Agent opens app → Phone Numbers screen
→ Enters +2348012345678 (their MTN number)
→ SIP provider calls them, they press the code on keypad
→ Number verified as outgoing caller ID at SIP layer
→ Stored as display_number in agent_numbers table
Outbound call

Agent taps "Call" on a lead
           │
           ▼
App → POST /mobile/calls  {remote_number: "+2347011223344"}
           │
           ▼
Backend does 4 things in parallel:
  ①  Creates LiveKit room          → room_id = "call_abc123"
  ②  Mints agent's access token    → signed JWT for the room
  ③  Starts Egress recording       → MP3 → your S3 bucket
  ④  SIP dial-out to lead          → via Africa's Talking SIP trunk
                                      caller ID = agent's +2348012345678
           │
           ▼
Backend returns {room_name, token} to app
           │
     ┌─────┴─────┐
     ▼           ▼
App joins     Lead's phone rings
LiveKit room  (sees +2348012345678)
(agent's mic     │
connected)       ▼
             Lead answers
                 │
                 ▼
         SIP participant joins
         the same LiveKit room
                 │
                 ▼
     Agent ↔ Lead audio streams
     flowing through LiveKit room
     (Egress is recording both tracks)
Call ends

Agent hangs up in app
        │
        ▼
LiveKit room closes → SIP leg drops → lead's call ends
        │
        ▼
LiveKit Egress finalises MP3 → uploads to S3
        │
        ▼
LiveKit fires webhook: egress_ended → your backend receives it
        │
        ▼
Backend dispatches TranscribeCallJob (same job you have now)
        │
        ├── Downloads MP3 from S3
        ├── Sends to Deepgram/Whisper
        ├── Saves transcript + speaker segments
        └── Runs AI summary → push notification to agent
Inbound call
This is the one complexity. If a lead calls back on the agent's real number, the call goes straight to their SIM — it bypasses the platform entirely.

Two practical options:

Option A — Accept it (simplest)
Inbound calls go to the agent's real phone normally. No recording, no transcription for inbound. Agent uses the app for all outbound (where most proactive sales calls happen anyway).

Option B — Call forwarding
Agent sets a forward on their SIM: when someone calls +2348012345678, their carrier forwards to a dedicated SIP number on your platform. That SIP number routes through LiveKit, app gets a push notification, agent accepts in-app, full recording applies.


Lead calls +2348012345678
        │
        ▼  (carrier-level forward, agent configures once)
Africa's Talking SIP number
        │
        ▼
LiveKit SIP bridge creates/joins room
        │
        ▼
FCM/APNs push → agent's app shows incoming call UI
        │
        ▼
Agent accepts → joins LiveKit room → call connects + records
What changes in your codebase
Backend — replace TwilioVoiceService with LiveKitVoiceService


// Instead of Twilio token generation:
$token = new \Agnostic\LiveKit\AccessToken($apiKey, $apiSecret);
$token->setIdentity((string) $agent->id);
$token->addGrant(new \Agnostic\LiveKit\VideoGrant(roomJoin: true, room: $roomName));

// Instead of TwiML + Twilio recording:
$egress->startRoomCompositeEgress($roomName, new S3Upload(...));

// Instead of Twilio dial:
$sipClient->createSipOutboundTrunk(...)->dial($leadNumber, callerId: $agent->displayNumber);
Webhooks change

Now (Twilio)	LiveKit
POST /webhooks/calls/outbound (TwiML)	LiveKit room created event
POST /webhooks/calls/recording	egress_ended webhook
POST /webhooks/calls/status	participant_joined/left events
Mobile app — replace Twilio Voice SDK


// Remove:
import {Voice, Call} from '@twilio/voice-react-native-sdk';

// Add:
import {Room, connect} from '@livekit/react-native';

// Instead of voice.connect(token, {params: {To: number}}):
const room = await connect(livekitUrl, token, {
  audio: true,
  video: false,
});
// Backend handles the SIP dial-out separately
The agent-facing call UI (InCallScreen, mute, speaker, hangup) stays almost identical — just different event names.

What stays exactly the same
TranscribeCallJob and your entire Deepgram/Whisper pipeline
Call, CallTranscript, CallSummary models and DB structure
All analytics, sentiment, AI coaching screens
The BYON flow we just built (agent_numbers, display_number)
Post-call summary, recording playback
Cost comparison (Nigeria example)
Twilio	LiveKit self-hosted + Africa's Talking
Server	none	~$30/mo
Per minute (NG call)	~$0.10+	~$0.02–0.04 (AT local rates)
Recording storage	Twilio hosted	Your S3 (~$0.023/GB)
Break-even	—	~350 mins/month
Africa's Talking specifically routes Nigerian calls through local carriers, which also means higher answer rates and better call quality than Twilio routing through US/EU infrastructure to Nigeria.