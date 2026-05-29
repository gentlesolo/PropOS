# Plan 06 — Module 4: CRM & Pipeline Manager

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md) · **Phase 1 Sprints 3–4 (CRM Core) + Phase 2 Sprints 11–12 (Pipeline & AI)**

---

## 1. Contact Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/Entities/Contact.php` |
| Domain | `Domain/CRM/Entities/ContactActivity.php` |
| Domain | `Domain/CRM/Enums/ContactType.php` (buyer/seller/landlord/tenant/investor/referral_partner) |
| Domain | `Domain/CRM/Enums/ContactStatus.php` (new/active/qualified/nurturing/closed/archived) |
| Domain | `Domain/CRM/ValueObjects/ContactPreferences.php` — budget, locations, property types, must-haves |
| Domain | `Domain/CRM/Contracts/ContactRepositoryInterface.php` |
| Domain | `Domain/CRM/Services/DuplicateDetectionService.php` — Phone/email/name fuzzy matching |
| Application | `Application/CRM/Actions/CreateContactAction.php` |
| Application | `Application/CRM/Actions/UpdateContactAction.php` |
| Application | `Application/CRM/Actions/MergeContactsAction.php` |
| Application | `Application/CRM/Actions/LogContactActivityAction.php` |
| Application | `Application/CRM/Actions/EnrichContactAction.php` — AI-powered enrichment |
| Application | `Application/CRM/DTOs/CreateContactData.php` |
| Application | `Application/CRM/DTOs/ContactFilterData.php` |

### Unified Timeline
Every interaction logged as `ContactActivity` with types: call, email, whatsapp, note, viewing, offer, document, system. Displayed chronologically on contact detail page.

### Contact Intelligence Panel (sidebar on contact page)
- Intent score badge (0–100, color-coded)
- Budget range + matched listings count
- Current pipeline stage + deal value
- Last interaction date + days since
- Lead source + channel breakdown
- Assigned agent with reassignment option

### Livewire Components

| Component | Purpose |
|---|---|
| `CRM/ContactIndexPage.php` | Data table with filters (type, status, agent, source, score range), bulk actions |
| `CRM/ContactDetailPage.php` | Full contact view: intelligence panel + tabs (Timeline, Deals, Listings, Documents) |
| `CRM/ContactCreateModal.php` | Quick-add modal with essential fields |
| `CRM/ContactEditForm.php` | Full edit form with preferences |
| `CRM/ContactTimeline.php` | Chronological activity stream with icons per type |
| `CRM/DuplicateDetectionAlert.php` | Banner showing potential duplicates with merge button |
| `CRM/ContactMergeWizard.php` | Side-by-side comparison, field-by-field merge selection |
| `CRM/RelationshipMap.php` | Visual graph of contact connections |

---

## 2. Pipeline Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/Entities/Deal.php` |
| Domain | `Domain/CRM/Entities/PipelineStage.php` |
| Domain | `Domain/CRM/Enums/PipelineType.php` (sale/rental) |
| Domain | `Domain/CRM/Services/DealMomentumService.php` — Calculates momentum score |
| Domain | `Domain/CRM/Contracts/DealRepositoryInterface.php` |
| Application | `Application/CRM/Actions/CreateDealAction.php` |
| Application | `Application/CRM/Actions/MoveDealToStageAction.php` — Validates checklist |
| Application | `Application/CRM/Actions/UpdateDealAction.php` |
| Application | `Application/CRM/Actions/CalculateDealMomentumAction.php` |
| Application | `Application/CRM/DTOs/CreateDealData.php` |
| Application | `Application/CRM/DTOs/MoveDealData.php` |

### Default Pipeline Stages

**Sales:** Inquiry → Qualified → Viewing Scheduled → Viewing Done → Offer Made → Under Negotiation → Offer Accepted → Compliance & Documents → Transfer in Progress → Closed

**Rental:** Inquiry → Qualified → Application Received → Credit Check → Approved → Lease Signed → Keys Handed Over → Active Tenant

### Stage Checklists (configurable per agency)
Example for "Viewing Done": ✓ Feedback logged, ✓ Follow-up message sent, ✓ Next step scheduled

### Stale Deal Detection
- Configurable threshold per stage (default: 7 days no activity)
- Amber at threshold, Red at 2x threshold
- Auto-notification to agent → escalation to manager after 24h inaction

### Livewire Components

| Component | Purpose |
|---|---|
| `CRM/PipelineBoard.php` | Kanban board with drag-and-drop (Alpine.js + Sortable.js) |
| `CRM/PipelineCard.php` | Deal card: contact name, listing address, value, days in stage, momentum badge |
| `CRM/DealDetailSlideOver.php` | Full deal info in slide-over: checklist, timeline, documents |
| `CRM/DealCreateModal.php` | Create new deal linked to contact + listing |
| `CRM/PipelineFilterBar.php` | Filter by type (sale/rental), agent, date range, value range |
| `CRM/StaleDealAlerts.php` | Dashboard widget showing deals needing attention |

---

## 3. AI Buyer-Listing Matching

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/Services/BuyerListingMatchService.php` — Scoring algorithm |
| Domain | `Domain/CRM/ValueObjects/MatchScore.php` — score, breakdown per factor |
| Application | `Application/CRM/Actions/MatchBuyerToListingsAction.php` |
| Application | `Application/CRM/Actions/MatchListingToBuyersAction.php` — Reverse search |
| Application | `Application/CRM/Actions/SendAutoMatchAlertAction.php` |
| Infra | `Infrastructure/Queue/Jobs/ProcessNewListingMatchesJob.php` — Triggered on listing publish |
| Events | `ListingPublishedEvent` → `MatchBuyerToListingsListener` |

### Match Score Calculation (0–100%)

| Factor | Weight | Logic |
|---|---|---|
| Location match | 30% | Exact area = 100%, adjacent area = 60%, same city = 30% |
| Budget fit | 25% | Within budget = 100%, 10% over = 60%, 20% over = 20% |
| Property type | 20% | Exact match = 100% |
| Size match | 15% | Within ±20% of requirement = 100% |
| Feature match | 10% | % of must-have features present |

### Livewire Components
- `CRM/BuyerMatchesPanel.php` — On contact page: matched listings ranked by score
- `CRM/ListingMatchesPanel.php` — On listing page: matched buyers ranked by score
- `CRM/AutoMatchAlertCard.php` — Notification card for new matches

---

## 4. Lead Source Attribution

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/Entities/LeadSource.php` |
| Domain | `Domain/CRM/Services/AttributionService.php` — Multi-touch attribution model |
| Application | `Application/CRM/Actions/TrackLeadSourceAction.php` |
| Application | `Application/CRM/Actions/CalculateSourceRoiAction.php` |

### Multi-Touch Attribution
Contact `contact_activities` tracks every touchpoint. Attribution model: first-touch gets 40%, last-touch gets 40%, middle touches split 20%.

### Livewire Components
- `CRM/LeadSourceBreakdown.php` — On contact page: attribution timeline
- `Intelligence/LeadSourceRoiDashboard.php` — ROI by source chart + table

---

## 5. Automated Follow-Up Engine

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/Entities/FollowUpSequence.php` |
| Domain | `Domain/CRM/Entities/FollowUpTask.php` |
| Application | `Application/CRM/Actions/EnrollInFollowUpSequenceAction.php` |
| Application | `Application/CRM/Actions/ProcessFollowUpTaskAction.php` |
| Application | `Application/CRM/Actions/PauseFollowUpSequenceAction.php` — Auto-pause on reply |
| Infra | `Infrastructure/Queue/Jobs/ProcessDueFollowUpsJob.php` — Runs every 15 min |
| Events | `ContactRepliedEvent` → `PauseActiveFollowUpsListener` |

### Built-In Sequence Templates
1. **New Buyer Inquiry** — Day 0: immediate response, Day 1: property suggestions, Day 3: check-in, Day 7: market insight, Day 14: re-engage
2. **Post-Viewing** — Day 0: thank you + feedback link, Day 2: follow-up, Day 5: alternative suggestions
3. **Cold Lead Re-engagement** — Day 0: "still looking?", Day 7: market update, Day 21: new listings alert
4. **Lease Renewal** — 90 days: early renewal offer, 60 days: reminder, 30 days: urgent

### Smart Features
- **Smart Timing:** AI recommends send time based on contact's historical engagement patterns
- **Auto-Pause:** Sequence pauses when contact replies (prevents robotic follow-ups)
- **Channel Learning:** Tracks which channel contact engages with → adjusts future outreach

### Livewire Components
- `CRM/FollowUpSequenceBuilder.php` — Visual builder with step timeline
- `CRM/ActiveSequencesPage.php` — All running sequences with status
- `CRM/FollowUpTaskQueue.php` — Agent's pending follow-up tasks

---

## 6. Deal Momentum & Risk Scoring

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/CRM/Services/DealMomentumService.php` |
| Application | `Application/CRM/Actions/CalculateDealMomentumAction.php` |
| Infra | `Infrastructure/Queue/Jobs/RecalculateDealMomentumJob.php` — Runs hourly |

### Score Factors (0–100)

| Factor | Weight | Description |
|---|---|---|
| Recency of contact | 25% | Days since last interaction vs. average |
| Stage velocity | 25% | Days in current stage vs. historical average |
| Document status | 20% | % of required docs complete |
| Next steps scheduled | 15% | Has scheduled follow-up/viewing |
| External risks | 15% | Bond deadline proximity, condition deadlines |

### Thresholds
- ≥ 70: Healthy (green)
- 40–69: Needs attention (amber) → agent notified
- < 40: At risk (red) → agent + manager notified

### Livewire Components
- `CRM/MomentumBadge.php` — Inline score badge on pipeline cards
- `CRM/DealHealthPanel.php` — Detailed breakdown on deal page
- `CRM/AtRiskDealsWidget.php` — Dashboard widget for manager

---

## 7. Acceptance Criteria

- [ ] Contact CRUD with all fields, types, and statuses
- [ ] Unified timeline shows all interaction types chronologically
- [ ] Duplicate detection flags matching phone/email/name with merge tool
- [ ] Kanban pipeline board with drag-and-drop between stages
- [ ] Stage checklists enforced (configurable per agency)
- [ ] Deal cards show value, days in stage, momentum badge
- [ ] Stale deals auto-highlighted and notifications sent
- [ ] Buyer-listing matching returns scored results (0–100%)
- [ ] New listing auto-matches to registered buyers with alerts
- [ ] Follow-up sequences enroll contacts and auto-send per schedule
- [ ] Sequences auto-pause when contact replies
- [ ] Deal momentum recalculated hourly with threshold alerts
- [ ] Lead source attribution tracks multi-touch journey
