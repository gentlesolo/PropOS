# Plan 05 — Module 3: Marketing Hub

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md) · **Phase 2, Sprints 13–14**

---

## 1. Campaign Builder

### 5-Step Wizard Flow

**Step 1 — Select Listing:** Choose active listing → auto-pull property data, photos, descriptions.
**Step 2 — Choose Goal:** Maximise Inquiries / Promote Open Day / Build Awareness / Target Investors / Price Reduction.
**Step 3 — Select Channels:** Instagram, Facebook, LinkedIn, WhatsApp Broadcast, Email, SMS, Portal Spotlight.
**Step 4 — AI Generates Content:** Channel-specific content with proper formatting per platform.
**Step 5 — Review, Edit & Schedule:** Preview per channel, edit, swap images, schedule per channel.

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Marketing/Entities/Campaign.php` |
| Domain | `Domain/Marketing/Entities/CampaignContent.php` |
| Domain | `Domain/Marketing/Enums/CampaignGoal.php` |
| Domain | `Domain/Marketing/Enums/MarketingChannel.php` |
| Domain | `Domain/Marketing/Enums/CampaignStatus.php` (draft/scheduled/active/paused/completed) |
| Domain | `Domain/Marketing/Contracts/CampaignRepositoryInterface.php` |
| Application | `Application/Marketing/Actions/CreateCampaignAction.php` |
| Application | `Application/Marketing/Actions/GenerateCampaignContentAction.php` — AI generates per channel |
| Application | `Application/Marketing/Actions/PublishCampaignAction.php` |
| Application | `Application/Marketing/Actions/PauseCampaignAction.php` |
| Application | `Application/Marketing/DTOs/CreateCampaignData.php` |
| Application | `Application/Marketing/DTOs/CampaignContentData.php` |
| Infra | `Infrastructure/AI/Prompts/campaign-instagram.txt` |
| Infra | `Infrastructure/AI/Prompts/campaign-facebook.txt` |
| Infra | `Infrastructure/AI/Prompts/campaign-linkedin.txt` |
| Infra | `Infrastructure/AI/Prompts/campaign-whatsapp.txt` |
| Infra | `Infrastructure/AI/Prompts/campaign-email.txt` |
| Infra | `Infrastructure/AI/Prompts/campaign-sms.txt` |

### Livewire Components

| Component | View |
|---|---|
| `Marketing/CampaignIndexPage.php` | Campaign list with status filters, performance summary |
| `Marketing/CampaignWizard.php` | Full-page 5-step wizard |
| `Marketing/CampaignWizardStep1.php` | Listing selector with preview |
| `Marketing/CampaignWizardStep2.php` | Goal selection cards |
| `Marketing/CampaignWizardStep3.php` | Channel checkboxes with descriptions |
| `Marketing/CampaignWizardStep4.php` | AI generation with loading + preview per channel |
| `Marketing/CampaignWizardStep5.php` | Review all content + schedule date/time per channel |
| `Marketing/CampaignDetailPage.php` | Live campaign with performance metrics |

---

## 2. Brand Identity System

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Marketing/Entities/BrandKit.php` |
| Domain | `Domain/Marketing/Entities/ContentTemplate.php` |
| Domain | `Domain/Marketing/Services/BrandComplianceService.php` — Checks content against brand guidelines |
| Application | `Application/Marketing/Actions/UpdateBrandKitAction.php` |
| Application | `Application/Marketing/Actions/CreateContentTemplateAction.php` |
| Application | `Application/Marketing/Actions/CheckBrandComplianceAction.php` |

### Brand Kit Fields
Logo (light + dark), primary/secondary/accent colors, heading font, body font, tagline, brand guidelines notes.

### Template Categories
Just Listed, Price Reduced, Sold, Open House, Agent Spotlight, Market Update — per channel.

### Livewire Components
- `Settings/BrandKitEditor.php` — Color pickers, font selectors, logo upload, live preview
- `Marketing/TemplateLibrary.php` — Grid of templates, filterable by category + channel
- `Marketing/TemplateEditor.php` — Edit template content with brand preview

---

## 3. Social Media Content Calendar

### Architecture

| Layer | Classes |
|---|---|
| Application | `Application/Marketing/Actions/AutoFillContentCalendarAction.php` — AI populates monthly calendar |
| Application | `Application/Marketing/Actions/SuggestContentMixAction.php` |
| Application | `Application/Marketing/Actions/GenerateHashtagsAction.php` |
| Infra | `Infrastructure/Queue/Jobs/AutoFillCalendarJob.php` — Monthly scheduled |

### Calendar Features
- Monthly grid view with posts per day
- Color-coded by content type (listing/market insight/team/testimonial)
- Drag-and-drop rescheduling
- AI auto-fill: populates based on active listings, open days, recent solds, seasonal themes
- Content mix suggestions: e.g., 2 listings, 1 market insight, 1 team, 1 testimonial per week
- Evergreen content library for gap-filling

### Livewire Components
- `Marketing/ContentCalendarPage.php` — Monthly calendar grid with Alpine.js drag-and-drop
- `Marketing/CalendarPostEditor.php` — Modal editor for individual posts
- `Marketing/EvergreenLibrary.php` — Manage evergreen content pool

---

## 4. Paid Advertising Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Marketing/Contracts/AdPlatformInterface.php` |
| Application | `Application/Marketing/Actions/CreateAdCampaignAction.php` |
| Application | `Application/Marketing/Actions/SyncAdPerformanceAction.php` |
| Infra | `Infrastructure/ExternalServices/MetaAds/MetaAdsAdapter.php` |
| Infra | `Infrastructure/ExternalServices/GoogleAds/GoogleAdsAdapter.php` |

### Features
- Meta Ads integration: create campaigns, set objectives, audiences, budgets
- Audience builder: save reusable audience segments
- A/B testing: auto-split ad variants, declare winner after set period
- Budget pacing alerts (over/under-pacing)
- Cross-platform performance dashboard

### Livewire Components
- `Marketing/AdCampaignsPage.php` — List active ad campaigns with performance
- `Marketing/AdCampaignCreator.php` — Wizard for creating Meta/Google ad campaigns
- `Marketing/AudienceBuilder.php` — Create and save audience segments
- `Marketing/AdPerformanceDashboard.php` — Unified spend, impressions, clicks, leads, CPL

---

## 5. Email Marketing

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Marketing/Entities/EmailCampaign.php` |
| Domain | `Domain/Marketing/Entities/EmailSubscriber.php` |
| Application | `Application/Marketing/Actions/CreateEmailCampaignAction.php` |
| Application | `Application/Marketing/Actions/SendEmailCampaignAction.php` |
| Application | `Application/Marketing/Actions/ManageSubscribersAction.php` |
| Infra | `Infrastructure/Queue/Jobs/SendEmailCampaignJob.php` |
| Infra | `Infrastructure/Queue/Jobs/TrackEmailEngagementJob.php` |

### Features
- Subscriber management with segments (buyer type, area, pipeline stage)
- Visual email builder with brand-styled templates
- Dynamic content blocks (auto-pull matching listings per recipient)
- Automated drip campaigns (New Listing Alert, Monthly Market Report, etc.)
- Open/click tracking → feeds back into CRM lead scores

### Livewire Components
- `Marketing/EmailCampaignIndexPage.php`
- `Marketing/EmailCampaignEditor.php` — Subject, template, segment selection, preview
- `Marketing/SubscriberListPage.php` — Manage subscribers and segments
- `Marketing/DripCampaignBuilder.php` — Visual sequence builder

---

## 6. WhatsApp Marketing

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Marketing/Entities/WhatsAppBroadcast.php` |
| Domain | `Domain/Marketing/Entities/WhatsAppBroadcastList.php` |
| Application | `Application/Marketing/Actions/CreateWhatsAppBroadcastAction.php` |
| Application | `Application/Marketing/Actions/SendWhatsAppBroadcastAction.php` |
| Application | `Application/Marketing/Actions/ManageBroadcastListAction.php` |
| Infra | `Infrastructure/ExternalServices/WhatsApp/WhatsAppApiClient.php` |
| Infra | `Infrastructure/Queue/Jobs/SendWhatsAppBroadcastJob.php` |

### Features
- Broadcast list management with filters (area, type, stage)
- Template message library (WhatsApp Business API compliant)
- Campaign scheduler with optimal send times
- Link tracking (short links with click-through rates)
- Opt-in/opt-out management

### Livewire Components
- `Marketing/WhatsAppBroadcastsPage.php`
- `Marketing/WhatsAppBroadcastCreator.php` — Template selection, list selection, schedule
- `Marketing/WhatsAppBroadcastLists.php` — Manage broadcast lists
- `Marketing/WhatsAppTemplateLibrary.php` — Manage message templates

---

## 7. Marketing Performance Analytics

### Livewire Components
- `Marketing/AnalyticsDashboard.php` — Channel comparison, cost per lead, content leaderboard
- `Marketing/ListingMarketingReport.php` — Per-listing marketing exposure breakdown
- `Marketing/MonthlyReportGenerator.php` — Auto-generated PDF of monthly activity

### Key Metrics
- Channel comparison (leads, viewings, deals per channel)
- Cost per lead by channel
- Content performance leaderboard
- Total marketing exposure per listing
- Monthly marketing ROI

---

## 8. Acceptance Criteria

- [ ] 5-step campaign wizard creates multi-channel campaign from a single listing
- [ ] AI generates channel-appropriate content (Instagram captions, email HTML, WhatsApp messages, SMS)
- [ ] Brand kit enforced on all generated content (colors, fonts, logo)
- [ ] Content calendar auto-fills monthly with AI-suggested posts
- [ ] Email campaigns send to segmented subscriber lists with tracking
- [ ] WhatsApp broadcasts send via Business API with template compliance
- [ ] Marketing analytics show channel comparison and ROI
- [ ] Monthly marketing report generates as branded PDF
