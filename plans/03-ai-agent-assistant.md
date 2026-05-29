# Plan 03 — Module 1: AI Agent Assistant

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md) · **Phase 2, Sprints 9–10**

---

## 1. Smart Daily Planner

### What It Does
Every morning, each agent receives a personalised AI-generated daily brief with: priority actions (ranked), deal alerts, viewing schedule with map-optimised route, and a 3-sentence market snapshot.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/AI/Entities/DailyBrief.php` — Value object holding sections |
| Domain | `Domain/AI/Contracts/DailyBriefGeneratorInterface.php` |
| Application | `Application/AI/Actions/GenerateDailyBriefAction.php` — Orchestrates data gathering + AI call |
| Application | `Application/AI/DTOs/DailyBriefData.php` |
| Infra | `Infrastructure/Queue/Jobs/GenerateDailyBriefsJob.php` — Scheduled job, runs per agency |
| Infra | `Infrastructure/AI/Prompts/daily-brief.txt` — Prompt template |
| Livewire | `Http/Livewire/Dashboard/DailyBriefPanel.php` |

### Data Flow
1. **Scheduler** (`console.php`) triggers `GenerateDailyBriefsJob` at each agent's preferred time (default 7am local)
2. Action queries: overdue follow-ups, stale deals, today's viewings, new high-score leads, market data
3. Context packaged into prompt → sent to `TextGenerationInterface`
4. Response parsed into `DailyBriefData` sections and stored in `daily_briefs` table
5. Delivered via: in-app dashboard widget, email, optionally WhatsApp

### Livewire Component
- `DailyBriefPanel.php` — Full-page section on agent dashboard
- Sections: Priority Actions (clickable → links to contact/deal), Deal Alerts (amber/red badges), Viewing Route (embedded map), Market Snapshot
- "Regenerate" button for fresh brief
- Collapsible sections with Alpine.js

---

## 2. Lead Qualification & Scoring

### What It Does
Every new lead is automatically scored 0–100 on intent, budget fit assessed, profile completeness flagged, duplicates detected, and agent assignment recommended.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/ValueObjects/LeadScore.php` — intent_score, budget_fit, completeness |
| Domain | `Domain/CRM/Services/LeadScoringService.php` — Pure scoring logic (rule-based + AI) |
| Application | `Application/CRM/Actions/ScoreLeadAction.php` |
| Application | `Application/CRM/Actions/DetectDuplicateContactAction.php` |
| Application | `Application/CRM/Actions/RecommendAgentAssignmentAction.php` |
| Infra | `Infrastructure/AI/Prompts/lead-scoring.txt` |
| Events | `LeadCreatedEvent` → listeners: `ScoreLeadListener`, `DetectDuplicateListener`, `AssignAgentListener` |

### Scoring Formula (Hybrid: Rules + AI)
1. **Rule-based factors** (60% weight):
   - Has phone number: +10
   - Has specific budget: +15
   - Has specific location: +10
   - Inquiry mentions urgency words: +10
   - Multiple channel touchpoints: +5 each
   - Recency of inquiry: +10 (< 1hr), +5 (< 24hr), 0 (> 24hr)
2. **AI assessment** (40% weight):
   - Message tone/intent analysis via LLM
   - Structured output: `{ score: int, reasoning: string }`

### Livewire Components
- `Http/Livewire/CRM/LeadScoreBadge.php` — Inline badge (color-coded 0–100)
- `Http/Livewire/CRM/LeadQualificationPanel.php` — Detailed breakdown on contact page

---

## 3. AI Communication Drafting

### What It Does
Agents click "Draft Reply" and get personalised, context-aware messages for: initial responses, follow-up sequences, objection handling, viewing confirmations, and offer summaries.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/AI/Enums/DraftType.php` — InitialResponse, FollowUp, Objection, ViewingConfirmation, OfferSummary |
| Domain | `Domain/AI/Enums/CommunicationChannel.php` — Email, WhatsApp, SMS |
| Application | `Application/AI/Actions/DraftCommunicationAction.php` |
| Application | `Application/AI/Actions/GenerateFollowUpSequenceAction.php` |
| Application | `Application/AI/DTOs/DraftRequestData.php` — contact, listing, draft_type, channel, tone |
| Application | `Application/AI/DTOs/DraftResponseData.php` — subject, body, variants[] |
| Infra | `Infrastructure/AI/Prompts/initial-response.txt` |
| Infra | `Infrastructure/AI/Prompts/follow-up-sequence.txt` |
| Infra | `Infrastructure/AI/Prompts/objection-response.txt` |
| Infra | `Infrastructure/AI/Prompts/viewing-confirmation.txt` |
| Infra | `Infrastructure/AI/Prompts/offer-summary.txt` |

### Livewire Components
- `Http/Livewire/AI/DraftComposer.php` — Modal/slide-over with:
  - Channel selector (email/WhatsApp/SMS)
  - Draft type auto-detected from context
  - Generated draft with editable textarea
  - "Regenerate" and "Try different tone" buttons
  - "Send" button (dispatches via appropriate channel)
- `Http/Livewire/AI/FollowUpSequenceBuilder.php` — Shows multi-step sequence timeline, editable per step

### Agent Style Preferences
Stored in `users.notification_preferences` JSON:
```json
{
  "communication_tone": "warm",       // formal | warm | professional
  "signature_style": "first_name",    // first_name | full_name | initials
  "language": "en"
}
```

---

## 4. Call Intelligence

### What It Does
Agents log calls → AI auto-transcribes (if VoIP), generates structured summary, auto-updates CRM fields, creates follow-up tasks, and provides sentiment indicator.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/AI/ValueObjects/CallSummary.php` — requirements, concerns, next_steps, commitments, sentiment |
| Application | `Application/AI/Actions/TranscribeCallAction.php` |
| Application | `Application/AI/Actions/SummarizeCallAction.php` |
| Application | `Application/AI/Actions/ExtractCallInsightsAction.php` — Updates CRM fields |
| Application | `Application/AI/Actions/CreateFollowUpFromCallAction.php` |
| Infra | `Infrastructure/AI/Prompts/call-summary.txt` |
| Infra | `Infrastructure/Queue/Jobs/ProcessCallRecordingJob.php` |

### Database: `call_logs` table
`id`, `agency_id`, `user_id`, `contact_id`, `direction` (inbound/outbound), `duration_seconds`, `recording_path` (nullable), `transcript` (longText, nullable), `summary` (json, nullable), `sentiment` (positive/neutral/concerned), `follow_up_tasks_created` (json, nullable), `called_at` (timestamp), timestamps

### Livewire Components
- `Http/Livewire/CRM/CallLogger.php` — Quick-log form (contact, duration, notes) + "Process with AI" button
- `Http/Livewire/CRM/CallSummaryCard.php` — Shows AI summary, sentiment badge, extracted action items

---

## 5. AI Chat Interface

### What It Does
A persistent chat panel where agents can query the platform in natural language. Connected to all modules.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/AI/Entities/ChatSession.php` |
| Domain | `Domain/AI/Contracts/ChatInterface.php` |
| Application | `Application/AI/Actions/ProcessChatMessageAction.php` — Intent detection → route to correct module action |
| Application | `Application/AI/DTOs/ChatMessageData.php` |
| Application | `Application/AI/Services/ChatIntentRouter.php` — Maps intents to actions |
| Infra | `Infrastructure/AI/Prompts/chat-system.txt` — System prompt with available tools/functions |
| Infra | `Infrastructure/AI/ChatToolRegistry.php` — Registers callable functions for the LLM |

### Intent Categories & Routing

| Intent | Routed To |
|---|---|
| "Show my leads not contacted in 7 days" | `CRM/Actions/QueryContactsAction` |
| "Draft a WhatsApp to follow up…" | `AI/Actions/DraftCommunicationAction` |
| "How many viewings did I do last month?" | `Intelligence/Actions/QueryAgentMetricsAction` |
| "Which listings have most inquiries?" | `Listing/Actions/QueryListingPerformanceAction` |
| "Remind me to call X on Friday at 10am" | `CRM/Actions/CreateFollowUpTaskAction` |

### Database: `chat_sessions` table
`id`, `agency_id`, `user_id`, `title` (nullable), `created_at`, `updated_at`

### Database: `chat_messages` table
`id`, `chat_session_id` (FK), `role` (user/assistant/system), `content` (text), `tool_calls` (json, nullable), `tool_results` (json, nullable), `tokens_used` (int, nullable), `created_at`

### Livewire Components
- `Http/Livewire/AI/ChatPanel.php` — Slide-over panel (always accessible via floating button)
  - Message history with scrollback
  - Text input with send button
  - Streaming response display
  - Rendered results (tables, lists, charts) based on response type
  - "New conversation" button

---

## 6. Performance Nudges & Coaching Tips

### What It Does
AI monitors agent behavior patterns and sends proactive, constructive nudges as non-intrusive notifications.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/AI/Services/NudgeEvaluationService.php` — Pure logic for detecting nudge-worthy patterns |
| Application | `Application/AI/Actions/EvaluateAgentNudgesAction.php` |
| Application | `Application/AI/DTOs/NudgeData.php` — type, message, action_url, priority |
| Infra | `Infrastructure/Queue/Jobs/EvaluateAgentNudgesJob.php` — Runs daily after scorecard update |
| Infra | `Infrastructure/AI/Prompts/coaching-nudge.txt` |

### Nudge Rules (configurable)

| Trigger | Example Nudge |
|---|---|
| > 5 leads not contacted in 5 days | "You have 8 leads not contacted in over 5 days. Leads contacted within 24h convert 3x more." |
| Viewing-to-offer rate below monthly avg | "Your viewing-to-offer conversion is below average. Want tips on post-viewing objections?" |
| Recent closed deals from referrals | "You closed 2 deals from referrals last month. Consider a thank-you to past clients." |
| No new leads added this week | "You haven't added new leads this week. Consider prospecting in [area]." |

### Delivery
- In-app notification (dismissible)
- Optionally included in the daily brief
- Never sent as email/WhatsApp (non-intrusive)

---

## 7. Acceptance Criteria

- [ ] Daily brief generates for each agent at their preferred time
- [ ] Brief contains: priority actions, deal alerts, viewing schedule, market snapshot
- [ ] New leads are auto-scored 0–100 within 30 seconds of creation
- [ ] Duplicate contacts detected and flagged with merge option
- [ ] "Draft Reply" produces editable, context-aware messages for all 5 types
- [ ] Follow-up sequences generate multi-step cadences (5–7 messages)
- [ ] Call logger captures call details and AI generates structured summary
- [ ] AI chat responds to natural language queries across all modules
- [ ] Chat renders appropriate result types (tables, lists, confirmations)
- [ ] Performance nudges delivered as in-app notifications (non-intrusive)
- [ ] All AI calls logged to `ai_usage_logs` with token counts
