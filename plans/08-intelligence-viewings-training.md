# Plan 08 — Modules 6-8: Intelligence, Viewings & Training

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md)

---

## Module 6: Agency Intelligence Dashboard (Phase 3, Sprints 17–20)

### 6.1 Real-Time Operations Overview

**Dashboard Landing Widgets:**
- Active Listings count by type with avg DOM
- Pipeline summary: deals per stage with combined value
- Active agents today with workload
- Today's activity feed (live stream via Reverb/polling)
- Month vs. Target progress bars (sales volume, rentals, GCI)

| Layer | Key Classes |
|---|---|
| Application | `Application/Intelligence/Actions/GetOperationsOverviewAction.php` |
| Application | `Application/Intelligence/ViewModels/DashboardViewModel.php` |
| Livewire | `Intelligence/DashboardPage.php` |
| Livewire | `Intelligence/ActivityFeed.php` — Real-time with Livewire polling (10s) |
| Livewire | `Intelligence/MonthVsTargetWidget.php` |

### 6.2 Agent Performance Scorecards

**Metrics tracked (daily/weekly/monthly):** Calls made, emails sent, viewings, new leads, leads contacted <24h, pipeline value, deals closed, commission earned, conversion rates (lead→viewing, viewing→offer, offer→close), avg days to close, FICA completion rate.

**Benchmarking:** Each metric compared to team average and top performer.

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Intelligence/Services/ScorecardCalculationService.php` |
| Application | `Application/Intelligence/Actions/CalculateAgentScorecardAction.php` |
| Application | `Application/Intelligence/Actions/GetAgentRankingsAction.php` |
| Infra | `Infrastructure/Queue/Jobs/CalculateDailyScorecardJob.php` — Nightly |
| Livewire | `Intelligence/AgentScorecardPage.php` — Individual scorecard |
| Livewire | `Intelligence/TeamPerformancePage.php` — Manager view of all agents |

### 6.3 Revenue Forecasting

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Intelligence/Services/ForecastingService.php` |
| Application | `Application/Intelligence/Actions/GenerateRevenueForecastAction.php` |
| Livewire | `Intelligence/RevenueForecastPanel.php` — 30/60/90 day forecast with confidence scores |
| Livewire | `Intelligence/TargetGapAnalysis.php` — Gap visualization + required actions |

**Forecast model:** Pipeline deals × stage conversion rate × probability = weighted forecast. Adjusted for seasonality patterns.

### 6.4 Market Intelligence Reports

| Layer | Key Classes |
|---|---|
| Application | `Application/Intelligence/Actions/GenerateMarketReportAction.php` |
| Application | `Application/Intelligence/Actions/GenerateMarketTrendSummaryAction.php` — Weekly AI narrative |
| Infra | `Infrastructure/Queue/Jobs/GenerateWeeklyMarketSummaryJob.php` |
| Livewire | `Intelligence/MarketReportsPage.php` |
| Livewire | `Intelligence/SuburbReportPanel.php` — Per-area: avg price, DOM, supply/demand |

### 6.5 Listing Performance Analysis

| Livewire | Components |
|---|---|
| `Intelligence/ListingHealthDashboard.php` | All listings with health score, sortable |
| `Intelligence/UnderperformingListingsWidget.php` | Flagged listings with AI recommendations |
| `Intelligence/PortfolioMixChart.php` | Breakdown by type, price band, area |

### 6.6 Financial Overview

| Livewire | Components |
|---|---|
| `Intelligence/GciTrackerPage.php` | Monthly/quarterly/annual GCI vs. targets |
| `Intelligence/CommissionLedgerPage.php` | Full commission record, filterable |
| `Intelligence/MarketingRoiPanel.php` | Spend vs. leads vs. deals vs. commission |
| `Intelligence/OutstandingCommissionsWidget.php` | Pending payments with days outstanding |

---

## Module 7: Viewings & Scheduling (Phase 2, Sprints 15–16)

### 7.1 Self-Service Booking Portal

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Viewing/Entities/Viewing.php` |
| Domain | `Domain/Viewing/Enums/ViewingStatus.php` (scheduled/confirmed/completed/no_show/cancelled/rescheduled) |
| Domain | `Domain/Viewing/Contracts/ViewingRepositoryInterface.php` |
| Application | `Application/Viewing/Actions/CreateViewingAction.php` |
| Application | `Application/Viewing/Actions/ConfirmViewingAction.php` |
| Application | `Application/Viewing/Actions/CancelViewingAction.php` |
| Livewire | `Viewing/PublicBookingPage.php` — Guest page (no auth) embedded on listing page |

**Booking page features:** Available time slots synced with agent's calendar, brief buyer details form, instant confirmation with property details + directions + Google Maps link, calendar integration (Google/Apple).

### 7.2 Viewing Route Optimiser

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Viewing/Services/RouteOptimisationService.php` |
| Application | `Application/Viewing/Actions/OptimiseViewingRouteAction.php` |
| Livewire | `Viewing/DayViewPage.php` — Map view of all viewings with optimised route |

**Features:** Interactive map (Leaflet.js / Google Maps), AI-suggested optimal order, configurable buffer time, rescheduling suggestions for new requests.

### 7.3 Automated Reminders

| Infra | `Infrastructure/Queue/Jobs/SendViewingRemindersJob.php` — Scheduled, checks all upcoming viewings |

**Sequence:** Instant confirmation → 48-hour reminder ("Still coming?") → Morning-of reminder → 1-hour final nudge. All branded and personalised. Cancel/reschedule responses flagged to agent.

### 7.4 No-Show & Late Tracking

| Application | `Application/Viewing/Actions/LogViewingOutcomeAction.php` |
| Infra | `Infrastructure/Queue/Jobs/SendNoShowFollowUpJob.php` |

**Features:** Agent logs outcome post-viewing, "Sorry we missed you" auto-sent with reschedule link, repeat no-shows flagged in CRM.

### 7.5 Open House Management

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Viewing/Entities/OpenHouse.php` |
| Domain | `Domain/Viewing/Entities/OpenHouseAttendee.php` |
| Application | `Application/Viewing/Actions/CreateOpenHouseAction.php` |
| Application | `Application/Viewing/Actions/CheckInAttendeeAction.php` |
| Application | `Application/Viewing/Actions/SendPostOpenHouseFollowUpAction.php` |
| Livewire | `Viewing/OpenHouseCreatePage.php` |
| Livewire | `Viewing/OpenHouseRsvpPage.php` — Public RSVP page |
| Livewire | `Viewing/OpenHouseCheckIn.php` — QR code / phone number check-in |
| Livewire | `Viewing/OpenHouseReportPage.php` — Post-event summary |

**Features:** Public RSVP page, reminder sequences to RSVPs, QR code check-in (creates CRM record), post-event "thank you" campaign, seller summary report.

### 7.6 Post-Viewing Feedback System

| Layer | Key Classes |
|---|---|
| Application | `Application/Viewing/Actions/SendFeedbackRequestAction.php` |
| Application | `Application/Viewing/Actions/ProcessViewingFeedbackAction.php` — Updates CRM + pipeline |
| Infra | `Infrastructure/Queue/Jobs/SendFeedbackRequestJob.php` — 2 hours post-viewing |
| Livewire | `Viewing/FeedbackFormPage.php` — Public form (1-5 stars, liked, concerns, offer interest) |
| Livewire | `Viewing/FeedbackSummaryPanel.php` — On listing page: aggregated feedback |

**Feedback flows to:** Agent summary, seller reports, CRM contact record, valuation engine (pricing signals).

### Viewings Acceptance Criteria

- [ ] Self-service booking page with real-time availability
- [ ] Viewing route displayed on map with optimised order
- [ ] 4-stage automated reminder sequence sends correctly
- [ ] No-show follow-up auto-sent with reschedule link
- [ ] Open house: RSVP, check-in, post-event campaign working
- [ ] Feedback survey sent 2 hours post-viewing, results flow to CRM

---

## Module 8: Knowledge & Training Hub (Phase 4, Sprints 25–28)

### 8.1 AI Onboarding Programme

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Training/Entities/TrainingModule.php` |
| Domain | `Domain/Training/Entities/TrainingProgress.php` |
| Domain | `Domain/Training/Enums/ModuleCategory.php` (onboarding/skills/compliance/market) |
| Domain | `Domain/Training/Enums/ContentType.php` (video/guide/quiz/roleplay) |
| Application | `Application/Training/Actions/EnrollInOnboardingAction.php` — Auto on user creation |
| Application | `Application/Training/Actions/CompleteModuleAction.php` |
| Application | `Application/Training/Actions/TrackProgressAction.php` |

**90-day programme:** Month 1 (Foundation) → Month 2 (Building Momentum) → Month 3 (Accelerating Performance). Auto-enrolled on join. Progress tracked by managers.

### 8.2 Skills Library

| Livewire | Components |
|---|---|
| `Training/SkillsLibraryPage.php` | Filterable grid of content (role, level, topic) |
| `Training/ModulePlayerPage.php` | Video player / guide reader / quiz interface |
| `Training/MyLearningPage.php` | Agent's progress, recommended next modules |

**Content types:** Video lessons (5-15 min), guides & playbooks, worked case studies, market knowledge base.

### 8.3 AI Objection Handler

| Layer | Key Classes |
|---|---|
| Application | `Application/Training/Actions/HandleObjectionAction.php` |
| Infra | `Infrastructure/AI/Prompts/objection-handler.txt` |
| Livewire | `Training/ObjectionHandlerPage.php` |

**Input:** Agent types objection text. **Output:** 2-3 suggested responses, psychology explanation, practice prompt (links to role-play). Categories: price, timing, competitor, market uncertainty, product-specific.

### 8.4 AI Role-Play Simulator

| Layer | Key Classes |
|---|---|
| Application | `Application/Training/Actions/StartRolePlayAction.php` |
| Application | `Application/Training/Actions/ProcessRolePlayTurnAction.php` |
| Application | `Application/Training/Actions/SummarizeRolePlayAction.php` |
| Infra | `Infrastructure/AI/Prompts/roleplay-client-persona.txt` |
| Livewire | `Training/RolePlayPage.php` — Chat-style interface with scenario + persona selection |

**Scenarios:** Qualification Call, Listing Presentation, Viewing Walkthrough, Offer Negotiation, Objection Handling. **Personas:** First-time buyer, seasoned investor, reluctant seller, aggressive negotiator. **Features:** Optional real-time coaching, session summary with strengths/improvements.

### 8.5 Market Knowledge Assessments

| Layer | Key Classes |
|---|---|
| Domain | `Domain/Training/Entities/Assessment.php` |
| Domain | `Domain/Training/Entities/AssessmentAttempt.php` |
| Application | `Application/Training/Actions/CreateAssessmentAction.php` |
| Application | `Application/Training/Actions/SubmitAssessmentAction.php` |
| Application | `Application/Training/Actions/GradeAssessmentAction.php` |
| Livewire | `Training/AssessmentPage.php` — Quiz interface with timer |
| Livewire | `Training/AssessmentResultsPage.php` — Score + correct answers + leaderboard |

**Types:** Weekly market quiz (Monday), area knowledge tests, compliance refreshers (mandatory), certification tracking.

### 8.6 Manager Coaching Tools

| Livewire | Components |
|---|---|
| `Training/TeamProgressDashboard.php` | Manager view: team training activity, completion rates, quiz scores |
| `Training/SkillGapAnalysis.php` | AI cross-ref: performance metrics vs. training completions |
| `Training/CoachingRecommendations.php` | Per-agent: suggested training actions based on metrics |
| `Training/GroupSessionScheduler.php` | Schedule group training with pre-reading assignments |

### Training Acceptance Criteria

- [ ] New users auto-enrolled in 90-day onboarding
- [ ] Skills library filterable by role, level, topic
- [ ] Video player, guide reader, quiz interface all functional
- [ ] Objection handler returns 2-3 responses with psychology context
- [ ] Role-play simulator maintains conversation with persona
- [ ] Weekly quiz delivers on Mondays with leaderboard
- [ ] Manager sees team progress dashboard with skill gap analysis
