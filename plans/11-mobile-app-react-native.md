# Plan 11 — PropOS Mobile App (React Native)

> **Purpose:** Companion mobile app for real estate agents to manage their field workday — calls, CRM, tasks, messaging, and viewings — with AI-powered call recording and summarization as the flagship feature.

---

## 1. Overview

### Problem
Agents spend the majority of their working day away from a desk — showing properties, meeting clients, driving between viewings. The PropOS web platform is desktop-first and inaccessible in the field. Agents lose context, miss follow-ups, and communicate inconsistently because they cannot access their CRM or communication history on the go.

### Solution
A native React Native app that serves as the agent's field command centre. The app connects to the existing PropOS backend via a REST/API layer and provides mobile-optimised access to the most time-critical features. Call recording with AI transcription and summarisation is the launch hook — the feature that earns the app a place on the agent's home screen.

### Goals
- Enable agents to make and receive VoIP calls through a dedicated business number
- Automatically record, transcribe, and summarise every call with AI
- Push post-call action items directly into the PropOS Tasks module
- Give agents real-time access to contacts, tasks, messaging, and viewings
- Maintain full data sync with the PropOS web platform

---

## 2. Tech Stack

| Layer | Choice | Rationale |
|---|---|---|
| **Framework** | React Native 0.74+ | One codebase for iOS + Android; strong Twilio SDK support; JavaScript-adjacent to existing stack |
| **Language** | TypeScript | Type safety across the app |
| **Navigation** | React Navigation v6 | Industry standard; supports deep linking and tab/stack patterns |
| **State Management** | Zustand | Lightweight, simple; avoids Redux boilerplate for a focused app |
| **API Client** | Axios + React Query | React Query handles caching, background refresh, and loading states |
| **UI Components** | NativeWind (Tailwind for RN) | Consistent with existing Tailwind design tokens on the web platform |
| **VoIP / Calls** | Twilio Voice SDK for React Native | Server-side recording; native call UI via CallKit (iOS) / ConnectionService (Android) |
| **Push Notifications** | Firebase Cloud Messaging (FCM) + APNs | Cross-platform; required for incoming call wake-up |
| **Call Wake-up (iOS)** | PushKit + CallKit | VoIP pushes that wake the app even when killed |
| **Authentication** | Laravel Sanctum (existing) + Secure Storage | Token stored in device keychain/keystore |
| **Offline Storage** | MMKV | Fast key-value store for caching contacts, tasks, and call history |
| **Real-time** | Laravel Echo + Pusher (existing) | Live notifications already wired in the backend |
| **Audio** | React Native Audio Recorder Player | Manual recording fallback; audio playback for call recordings |
| **Testing** | Jest + React Native Testing Library | Unit and component tests |
| **E2E Testing** | Detox | iOS/Android end-to-end tests |
| **CI/CD** | GitHub Actions + Fastlane | Automated builds and App Store / Play Store deployments |

---

## 3. Backend Requirements (PropOS API)

The mobile app consumes the existing PropOS backend. New API endpoints and services are required.

### 3.1 New API Endpoints

#### Authentication
```
POST   /api/mobile/auth/login
POST   /api/mobile/auth/logout
POST   /api/mobile/auth/refresh
POST   /api/mobile/auth/device           — register FCM/APNs token
```

#### Calls
```
POST   /api/mobile/calls/token           — generate Twilio Access Token for VoIP
POST   /api/mobile/calls                 — log a new call record
GET    /api/mobile/calls                 — paginated call history
GET    /api/mobile/calls/{id}            — single call with transcript + summary
GET    /api/mobile/calls/{id}/recording  — stream recording audio
PATCH  /api/mobile/calls/{id}/summary    — agent edits/confirms summary
```

#### Contacts (CRM)
```
GET    /api/mobile/contacts              — paginated, searchable
GET    /api/mobile/contacts/{id}         — contact detail with timeline
POST   /api/mobile/contacts/{id}/notes  — add quick note
GET    /api/mobile/contacts/{id}/calls  — call history for contact
```

#### Tasks
```
GET    /api/mobile/tasks                 — today's tasks + overdue
PATCH  /api/mobile/tasks/{id}            — mark complete / snooze
POST   /api/mobile/tasks                 — create task (from call action items)
```

#### Messaging
```
GET    /api/mobile/inbox                 — unified inbox (WhatsApp + SMS + Email)
GET    /api/mobile/inbox/{contactId}     — conversation thread
POST   /api/mobile/inbox/{contactId}     — send message (channel-aware)
```

#### Viewings
```
GET    /api/mobile/viewings              — today's schedule
GET    /api/mobile/viewings/{id}         — viewing detail
PATCH  /api/mobile/viewings/{id}/status  — confirm / check-in / complete
```

#### Notifications
```
GET    /api/mobile/notifications         — notification history
PATCH  /api/mobile/notifications/{id}    — mark read
```

#### AI Daily Brief
```
GET    /api/mobile/brief                 — today's AI-generated priority list
```

### 3.2 New Backend Services

#### `TwilioVoiceService`
- Generates time-limited Access Tokens for the Twilio Voice SDK
- Manages agent-to-Twilio-number mapping (each agent gets a dedicated number)
- Handles inbound call routing (ring the agent's app)
- Triggers recording on call connect

#### `CallWebhookController`
- Receives Twilio status webhooks: `call.completed`, `recording.available`
- On `recording.available`: queues transcription job
- Stores recording URL, duration, direction, provider call SID

#### `CallTranscriptionJob` (queued)
- Downloads audio from Twilio
- Sends to OpenAI Whisper API with speaker diarisation prompt
- Stores transcript with speaker labels and timestamps in `call_transcripts` table

#### `CallSummarisationJob` (queued, runs after transcription)
- Sends transcript to GPT-4o
- Extracts: summary, key points (JSON array), lead sentiment, action items (JSON array), suggested next step
- Stores in `call_summaries` table
- Dispatches push notification to agent: "Your call summary is ready"
- Auto-creates Tasks from action items (pending agent confirmation)

#### `MobileNotificationService`
- Wraps FCM + APNs for push delivery
- Handles VoIP pushes via PushKit for incoming calls (iOS)
- Notification types: call summary ready, new lead assigned, message received, task due, viewing in 30 min

### 3.3 New Database Models

#### `calls`
```sql
id, agent_id, contact_id, direction (inbound|outbound),
status (initiated|ringing|in-progress|completed|no-answer|busy|failed),
provider_call_sid, twilio_number, duration_seconds,
recording_url, recording_sid,
started_at, ended_at, created_at, updated_at
```

#### `call_transcripts`
```sql
id, call_id, full_text, speaker_labelled_json,
word_count, language, whisper_model, processing_seconds,
created_at
```

#### `call_summaries`
```sql
id, call_id, summary_text, key_points_json,
sentiment (hot|warm|cold|neutral), sentiment_score (0-100),
action_items_json, suggested_next_step,
agent_confirmed_at, agent_edited, gpt_model, created_at
```

#### `agent_devices`
```sql
id, user_id, platform (ios|android), push_token,
push_type (fcm|apns|voip), device_name, last_seen_at, created_at
```

#### `agent_numbers`
```sql
id, user_id, twilio_number, twilio_sid, active, created_at
```

---

## 4. App Architecture

### 4.1 Folder Structure

```
propos-mobile/
├── src/
│   ├── api/                    # Axios instances, endpoint functions
│   │   ├── auth.ts
│   │   ├── calls.ts
│   │   ├── contacts.ts
│   │   ├── tasks.ts
│   │   ├── messaging.ts
│   │   ├── viewings.ts
│   │   └── notifications.ts
│   ├── components/             # Shared UI components
│   │   ├── Avatar.tsx
│   │   ├── Badge.tsx
│   │   ├── CallButton.tsx
│   │   ├── ContactCard.tsx
│   │   ├── SentimentBadge.tsx
│   │   ├── TaskItem.tsx
│   │   └── ...
│   ├── screens/                # One folder per feature
│   │   ├── auth/
│   │   ├── home/
│   │   ├── calls/
│   │   ├── contacts/
│   │   ├── messaging/
│   │   ├── tasks/
│   │   └── viewings/
│   ├── navigation/             # Tab and stack navigators
│   │   ├── RootNavigator.tsx
│   │   ├── TabNavigator.tsx
│   │   └── stacks/
│   ├── store/                  # Zustand stores
│   │   ├── authStore.ts
│   │   ├── callStore.ts
│   │   └── notificationStore.ts
│   ├── hooks/                  # Custom React hooks
│   │   ├── useCall.ts
│   │   ├── useContacts.ts
│   │   └── useRealtime.ts
│   ├── services/               # Non-UI logic
│   │   ├── twilio.ts           # Twilio Voice SDK wrapper
│   │   ├── notifications.ts    # FCM + PushKit setup
│   │   └── storage.ts          # MMKV wrapper
│   ├── utils/
│   └── types/
├── android/
├── ios/
├── __tests__/
└── package.json
```

### 4.2 Navigation Structure

```
RootNavigator
├── AuthStack (unauthenticated)
│   └── LoginScreen
└── MainTabs (authenticated)
    ├── HomeTab         → HomeScreen (Daily Brief)
    ├── ContactsTab     → ContactsScreen → ContactDetailScreen
    │                                   → CallScreen
    │                                   → PostCallSummaryScreen
    ├── MessagingTab    → InboxScreen → ConversationScreen
    ├── TasksTab        → TasksScreen
    └── MoreTab         → ViewingsScreen
                        → CallHistoryScreen → CallDetailScreen
                        → ProfileScreen
```

---

## 5. Feature Specifications

### 5.1 Authentication

- Email + password login (hits `/api/mobile/auth/login`)
- Token stored in iOS Keychain / Android Keystore via `react-native-keychain`
- Biometric unlock (Face ID / fingerprint) after initial login
- Auto-refresh token on 401; logout on refresh failure
- On login: register device push token

---

### 5.2 Home — Daily Brief

**Screen:** `HomeScreen`

Displays the AI-generated daily brief from the existing AI Planner, adapted for mobile. Shows:
- Greeting with agent name
- Today's call targets (leads to reach out to, AI-ranked by urgency)
- Overdue tasks count
- Viewings today
- Unread messages count
- Recent call summaries awaiting confirmation

One-tap actions from each brief item (call, message, open contact).

---

### 5.3 Calls — Core Feature

#### Making a Call

1. Agent taps a contact's phone icon anywhere in the app
2. App fetches a fresh Twilio Access Token from `/api/mobile/calls/token`
3. Twilio Voice SDK initiates the call
4. **In-call screen** shows:
   - Contact name, photo, deal stage
   - Last note / last call summary (so agent has context while talking)
   - Call timer, mute, speaker, hold, end
   - Live transcript panel (Phase 3 — real-time Whisper streaming)
5. Agent ends call → call record created → recording queued

#### Receiving a Call

1. Inbound call to agent's Twilio number triggers a VoIP push (PushKit on iOS, FCM high-priority on Android)
2. Device wakes; native call UI appears (CallKit / ConnectionService)
3. Agent answers → same in-call screen as outbound
4. Contact is looked up by caller ID and shown immediately

#### Post-Call Summary Screen

Appears automatically ~60 seconds after call ends (push notification triggers it).

```
┌─────────────────────────────────────────┐
│  Call with Sarah Johnson — 4 min 32 sec │
│  📅 Today, 10:14am                       │
├─────────────────────────────────────────┤
│  SUMMARY                                │
│  Sarah is interested in the Lekki       │
│  property but concerned about price.    │
│  She will discuss with her husband      │
│  and follow up on Friday.               │
├─────────────────────────────────────────┤
│  SENTIMENT    ● Warm                    │
├─────────────────────────────────────────┤
│  KEY POINTS                             │
│  • Budget: ₦85M max                     │
│  • Timeline: Q3 2026                    │
│  • Concern: Distance from school        │
├─────────────────────────────────────────┤
│  ACTION ITEMS (create as tasks?)        │
│  ☐ Send Lekki property brochure        │
│  ☐ Follow up call — Friday 31 May      │
│  ☐ Share nearby school listings        │
├─────────────────────────────────────────┤
│  [ Confirm & Create Tasks ]  [ Edit ]   │
│  [ View Full Transcript ]               │
└─────────────────────────────────────────┘
```

- Confirming creates tasks in the PropOS Tasks module (syncs to web)
- Agent can edit summary text before saving
- "View Full Transcript" opens a scrollable speaker-labelled transcript with timestamps

#### Call History Screen

- All calls listed chronologically with duration, direction, contact name, sentiment badge
- Filter by: date range, agent (managers only), sentiment, direction
- Search by contact name or keyword (searches transcripts server-side)
- Tap to open Call Detail (recording player + transcript + summary)

---

### 5.4 Contacts (CRM)

- Searchable, paginated list from PropOS CRM
- Each contact shows: name, photo, pipeline stage, last contact date, sentiment badge from last call
- **Contact Detail Screen:**
  - Key info (phone, email, deal stage, assigned agent)
  - Quick actions: Call, WhatsApp, SMS, Email, Add Note
  - Timeline (calls, messages, tasks, notes — reverse chronological)
  - Call history tab with playback
- **Add Note:** voice memo or typed, attaches to contact timeline

---

### 5.5 Messaging Inbox

- Unified inbox aggregating WhatsApp, SMS, and Email
- Conversation view per contact — all channels in one thread, colour-coded by channel
- Send reply: app auto-selects channel (WhatsApp if last message was WhatsApp, etc.)
- AI reply suggestions (tap to use, edit before sending)
- Unread badge on tab icon
- Real-time updates via Laravel Echo / Pusher (existing infrastructure)

---

### 5.6 Tasks

- Today's tasks + overdue tasks at the top
- Upcoming tasks grouped by date
- Swipe right to complete, swipe left to snooze (pick time)
- Tasks created from call action items appear with a phone icon indicator
- Pull to refresh; optimistic UI for completions

---

### 5.7 Viewings

- Today's viewing schedule in chronological order
- Each viewing: property address, client name, time, status
- **Check-in:** agent taps to mark arrived (records timestamp + GPS — optional)
- **Complete:** post-viewing note field + outcome (interested / not interested / offer expected)
- Outcome note pushed to contact timeline

---

### 5.8 Notifications

Types the app handles:

| Notification | Trigger | Action on Tap |
|---|---|---|
| Call summary ready | AI processing complete | Open PostCallSummaryScreen |
| Incoming VoIP call | Inbound call to Twilio number | Native call UI |
| New lead assigned | Lead routing in PropOS | Open Contact Detail |
| Unread message | WhatsApp / SMS received | Open Conversation |
| Task due | Task due time | Open Tasks screen |
| Viewing in 30 min | Viewing scheduler | Open Viewing Detail |

---

## 6. Compliance & Security

### Call Recording Consent
- Every outbound call plays an automated announcement: *"This call may be recorded for quality and training purposes."*
- Announcement configured as a Twilio TwiML instruction before connecting
- Consent timestamp logged per call

### Data Security
- All audio stored server-side only; never on the device
- Recordings served via signed time-limited URLs (expires after 1 hour)
- App requires biometric authentication after 5 minutes background
- Remote device wipe: admin can invalidate agent's push token + Sanctum tokens from PropOS web

### Compliance Controls (per tenant)
- Recording retention period: configurable (30 / 60 / 90 / 180 days, then auto-delete)
- Recording opt-out per contact (GDPR / POPIA right to object)
- Access control: agents see own calls, managers see team, admins see all

### Permissions (what the app requests)
- Microphone — required for calls
- Notifications — required for incoming calls and summaries
- Contacts (read-only, optional) — show CRM leads in native contacts app

---

## 7. UI / UX Design Principles

- **Dark mode first** — agents use phones outdoors; high contrast matters
- **One-thumb reachability** — primary actions at the bottom of the screen
- **Context before action** — before an agent answers or dials, show them who this person is
- **Minimal data entry** — voice notes over typed notes; AI does the writing
- **Optimistic UI** — task completions and note saves feel instant; sync in background
- **Offline grace** — show cached data clearly; queue actions and sync when back online

### Design Tokens
Inherit from the existing PropOS Tailwind configuration to maintain visual consistency between web and mobile.

---

## 8. Development Phases

### Phase 1 — Foundation + Calls (MVP)
**Target: 8–10 weeks**

- [ ] React Native project setup (TypeScript, NativeWind, navigation)
- [ ] Authentication (login, biometric, token storage)
- [ ] Twilio Voice SDK integration (outbound calls)
- [ ] CallKit (iOS) + ConnectionService (Android) for native call UI
- [ ] Server-side call recording + webhook handling
- [ ] OpenAI Whisper transcription pipeline (queued jobs)
- [ ] GPT-4o summarisation pipeline (queued jobs)
- [ ] Post-call summary screen with task confirmation
- [ ] Basic contact search and contact detail screen
- [ ] Push notifications (call summary ready)
- [ ] API: auth, calls, contacts (read-only), tasks (create only)

**Deliverable:** Agent can make VoIP calls, recordings are transcribed and summarised, action items become tasks. Core value proposition validated.

---

### Phase 2 — Full Field App
**Target: 6–8 weeks after Phase 1**

- [ ] Inbound calls (PushKit + FCM high-priority + incoming call screen)
- [ ] Home screen / daily brief
- [ ] Full contacts module (notes, timeline, quick actions)
- [ ] Unified messaging inbox (WhatsApp + SMS + Email)
- [ ] Tasks module (complete, snooze, create)
- [ ] Viewings schedule + check-in + outcome notes
- [ ] Full notification system (all types)
- [ ] Call history screen with search across transcripts
- [ ] Offline caching (MMKV) for contacts, tasks, viewings

**Deliverable:** Full field companion app — agents can manage their entire workday from the phone.

---

### Phase 3 — Intelligence Layer
**Target: 4–6 weeks after Phase 2**

- [ ] Live transcript during call (real-time Whisper streaming or Deepgram)
- [ ] In-call AI hints (objection responses, property details lookup)
- [ ] AI reply suggestions in messaging inbox
- [ ] Sentiment trend on contact detail (across all calls)
- [ ] Manager view: team call activity, listen to recordings, flag for coaching
- [ ] Call analytics dashboard (mobile-optimised)
- [ ] Communication coach: pre-send message scoring

**Deliverable:** AI moves from reactive (post-call) to proactive (during-call guidance).

---

### Phase 4 — Scale & Polish
**Target: Ongoing**

- [ ] Team benchmarking (agent vs. team call metrics)
- [ ] Automated nurture trigger from call outcome
- [ ] Whisper fine-tuning on real estate vocabulary
- [ ] Multi-language support (transcription + UI)
- [ ] Apple Watch companion (quick call stats, task completions)
- [ ] App Store / Play Store public listing

---

## 9. API Integration Points (Existing PropOS Services)

| PropOS Service | Mobile Usage |
|---|---|
| `AiServiceManager` / OpenAI | Whisper transcription, GPT summarisation |
| `SmsService` (Twilio) | Extend to `TwilioVoiceService` for calls |
| `NotificationService` | Extend for FCM / APNs push |
| Laravel Echo / Pusher | Real-time message and notification sync |
| Laravel Sanctum | Mobile token authentication |
| `EmailService` | Send emails from mobile compose |
| Existing CRM models | Contact, Task, Deal, Note, Viewing |

---

## 10. Testing Strategy

### Unit Tests (Jest)
- API client functions
- Zustand store actions
- Utility functions (formatDuration, formatSentiment, etc.)
- Call state machine logic

### Component Tests (React Native Testing Library)
- PostCallSummaryScreen renders correctly with mocked data
- TaskItem swipe interactions
- ContactCard displays correct sentiment badge

### E2E Tests (Detox)
- Login flow
- Outbound call initiation (mocked Twilio)
- Post-call summary appears after call ends
- Task created from action item appears in task list
- Message sent from conversation screen

### Manual QA Checklist (per release)
- [ ] Incoming call wakes device from killed state (iOS + Android)
- [ ] Call audio quality on 4G / WiFi
- [ ] Recording and transcript accuracy (10 test calls)
- [ ] Summary generation time < 90 seconds
- [ ] Biometric lock engages after background
- [ ] Offline: cached data shows, actions queue and sync

---

## 11. CI/CD Pipeline

```
Push to feature branch
        ↓
GitHub Actions: TypeScript check + Jest tests
        ↓
PR merged to main
        ↓
Fastlane: build iOS (TestFlight) + Android (Play Store internal)
        ↓
QA sign-off on TestFlight / internal track
        ↓
Fastlane: promote to production
```

### Environment Configuration
- `.env.development` — local PropOS backend, Twilio test credentials
- `.env.staging` — staging PropOS backend, Twilio test credentials
- `.env.production` — production PropOS backend, Twilio live credentials

---

## 12. App Store Requirements

### iOS (App Store)
- Apple Developer Program membership ($99/year)
- Privacy policy URL (mandatory — app records calls)
- App Privacy labels: Microphone, Contacts (if synced), Location (if check-in enabled)
- CallKit entitlement (for VoIP call screen)
- PushKit entitlement (for VoIP push notifications)
- Background Modes: Voice over IP, Remote notifications
- Review notes: explain recording consent flow to Apple reviewers

### Android (Play Store)
- Google Play Developer account ($25 one-time)
- RECORD_AUDIO permission declaration
- FOREGROUND_SERVICE permission (for active call)
- Privacy policy URL
- Data safety form: audio recorded and uploaded to servers

---

## 13. Open Questions

1. **Calling number strategy:** One shared agency number, or a dedicated Twilio number per agent? Per-agent numbers are cleaner for caller ID but cost more.
2. **Transcription language:** English only for Phase 1, or does the agency operate in multiple languages?
3. **Recording consent jurisdiction:** Which countries/states will agents operate in? Laws differ (Nigeria, South Africa, UK, US have different two-party consent rules).
4. **Agent onboarding:** Self-serve app install or IT-managed MDM deployment?
5. **Existing call volume:** How many calls per agent per day? Determines Whisper + GPT cost at scale.
6. **Video calls:** Are agents doing Zoom/Teams calls? If so, a meeting bot recorder (Phase 3+) may be needed.

---

## 14. Estimated Costs at Scale

| Service | Cost Basis | Estimate (50 agents, 10 calls/day) |
|---|---|---|
| Twilio Voice | ~$0.014/min inbound + outbound | ~$350/month (5 min avg) |
| Twilio Recording | ~$0.0025/min | ~$30/month |
| OpenAI Whisper | ~$0.006/min audio | ~$90/month |
| OpenAI GPT-4o | ~$0.005 per call summary | ~$75/month |
| Twilio Numbers | ~$1.15/number/month | ~$58/month (50 numbers) |
| Push (FCM/APNs) | Free | $0 |
| **Total** | | **~$600/month** |

Costs scale linearly with agents and call volume. At 200 agents: ~$2,400/month.

---

*Last updated: May 2026*
*Related plans: [09-api-integrations.md](09-api-integrations.md), [03-ai-agent-assistant.md](03-ai-agent-assistant.md)*
