# PropOS — Master Implementation Plan

> **Laravel 13 · Livewire · Tailwind CSS 4 · Clean Architecture**
> Comprehensive plan for building the AI-powered Property Operating System.

---

## 1. Project Snapshot

| Attribute | Value |
|---|---|
| **Framework** | Laravel 13.x (PHP 8.3+) |
| **Frontend** | Livewire 3 + Alpine.js + Tailwind CSS 4 |
| **Database** | PostgreSQL 16 (production) / SQLite (local dev) — DB-agnostic design |
| **Queue** | Database queue (shared hosting) → Redis + Horizon (VPS upgrade) |
| **Search** | Laravel Scout with database driver (upgradeable to Meilisearch) |
| **AI Layer** | OpenAI GPT-4o + DeepSeek via provider-agnostic abstraction |
| **File Storage** | Local disk (shared hosting) → S3-compatible (upgrade path) |
| **Caching** | File cache (shared hosting) → Redis (upgrade path) |
| **Testing** | Pest PHP 4 |
| **CI/CD** | GitHub Actions |
| **Deployment** | Shared hosting (initial) → VPS/Forge (scale) |
| **Distribution** | Dual model: Self-hosted + SaaS (subscription) |

---

## 2. Clean Architecture Overview

The codebase follows a **Domain-Driven, Clean Architecture** pattern. Business logic lives in the **Domain** layer, application orchestration in the **Application** layer, and Laravel-specific wiring in the **Infrastructure** layer. Livewire components serve as the **Presentation** layer.

```
┌─────────────────────────────────────────────┐
│              Presentation Layer              │
│   Livewire Components · Blade Views · API   │
├─────────────────────────────────────────────┤
│              Application Layer               │
│    Actions · DTOs · View Models · Events     │
├─────────────────────────────────────────────┤
│                Domain Layer                  │
│  Entities · Value Objects · Domain Services  │
│  Repository Interfaces · Domain Events       │
├─────────────────────────────────────────────┤
│            Infrastructure Layer              │
│  Eloquent Repos · External APIs · Queue Jobs │
│  Notifications · File Storage · AI Clients   │
└─────────────────────────────────────────────┘
```

### Dependency Rule
> Dependencies point **inward only**. The Domain layer has **zero** dependencies on Laravel or any framework code.

---

## 3. Directory Structure

```
app/
├── Domain/                          # Pure business logic (no framework deps)
│   ├── Shared/                      # Cross-domain value objects, interfaces
│   │   ├── ValueObjects/
│   │   ├── Contracts/
│   │   └── Enums/
│   ├── Identity/                    # Users, Roles, Tenants
│   │   ├── Entities/
│   │   ├── ValueObjects/
│   │   ├── Enums/
│   │   ├── Contracts/               # Repository interfaces
│   │   └── Services/                # Domain services
│   ├── Listing/                     # Property listings & valuations
│   ├── CRM/                         # Contacts, Pipelines, Leads
│   ├── Marketing/                   # Campaigns, Content, Email, WhatsApp
│   ├── Transaction/                 # Deals, Compliance, Commission
│   ├── Viewing/                     # Viewings, Open Houses, Feedback
│   ├── Intelligence/                # Analytics, Forecasting, Reports
│   ├── Training/                    # Knowledge Hub, Assessments
│   └── AI/                          # AI domain contracts & DTOs
│
├── Application/                     # Use-case orchestration
│   ├── Shared/
│   │   ├── DTOs/
│   │   └── Contracts/
│   ├── Identity/
│   │   ├── Actions/                 # Single-responsibility use-case classes
│   │   ├── DTOs/
│   │   └── ViewModels/
│   ├── Listing/
│   ├── CRM/
│   ├── Marketing/
│   ├── Transaction/
│   ├── Viewing/
│   ├── Intelligence/
│   ├── Training/
│   └── AI/
│       ├── Actions/
│       └── DTOs/
│
├── Infrastructure/                  # Framework & external service adapters
│   ├── Persistence/                 # Eloquent models, repositories, migrations
│   │   ├── Models/
│   │   └── Repositories/
│   ├── ExternalServices/            # API clients (WhatsApp, Portals, etc.)
│   ├── AI/                          # LLM client implementations
│   ├── Notifications/
│   ├── Queue/                       # Job classes
│   └── Providers/                   # Service providers (DI bindings)
│
├── Http/                            # Livewire components, middleware, routes
│   ├── Livewire/
│   │   ├── Shared/                  # Layout, Navigation, Notifications
│   │   ├── Dashboard/
│   │   ├── Listing/
│   │   ├── CRM/
│   │   ├── Marketing/
│   │   ├── Transaction/
│   │   ├── Viewing/
│   │   ├── Intelligence/
│   │   ├── Training/
│   │   └── Settings/
│   ├── Middleware/
│   └── Controllers/                 # Minimal — only for webhooks / API
│
resources/
├── views/
│   ├── layouts/
│   ├── components/                  # Blade component library
│   ├── livewire/                    # Livewire component views
│   └── emails/
├── css/
│   └── app.css                      # Tailwind CSS 4 entry point
└── js/
    └── app.js
```

---

## 4. Detailed Sub-Plans

The implementation is broken down into **10 focused planning documents**. Each document is self-contained and covers architecture, models, key classes, UI components, and acceptance criteria for its area.

| # | Document | Scope |
|---|---|---|
| 01 | [Foundation & Shared Infrastructure](file:///C:/Users/ADMIN/Herd/propos/plans/01-foundation-and-infrastructure.md) | Multi-tenancy, Auth, RBAC, Notification Bus, AI Service Layer, Shared UI Shell |
| 02 | [Database Schema & Migrations](file:///C:/Users/ADMIN/Herd/propos/plans/02-database-schema.md) | Complete ERD, all migration files, indexing strategy, seeders |
| 03 | [Module 1 — AI Agent Assistant](file:///C:/Users/ADMIN/Herd/propos/plans/03-ai-agent-assistant.md) | Daily Planner, Lead Scoring, Comm Drafting, Call Intelligence, AI Chat, Nudges |
| 04 | [Module 2 — Listing Intelligence](file:///C:/Users/ADMIN/Herd/propos/plans/04-listing-intelligence.md) | Mandate Intake, AI Descriptions, Valuation Engine, Media, Portal Syndication |
| 05 | [Module 3 — Marketing Hub](file:///C:/Users/ADMIN/Herd/propos/plans/05-marketing-hub.md) | Campaign Builder, Brand Kit, Social Calendar, Ads, Email, WhatsApp Marketing |
| 06 | [Module 4 — CRM & Pipeline](file:///C:/Users/ADMIN/Herd/propos/plans/06-crm-and-pipeline.md) | Contacts, Pipeline Boards, Buyer-Listing Matching, Follow-Up Engine, Deal Scoring |
| 07 | [Module 5 — Transactions & Compliance](file:///C:/Users/ADMIN/Herd/propos/plans/07-transactions-and-compliance.md) | Transaction Workflows, Documents, Deadlines, FICA, Commission, Attorney Portal |
| 08 | [Modules 6-8 — Intelligence, Viewings & Training](file:///C:/Users/ADMIN/Herd/propos/plans/08-intelligence-viewings-training.md) | Dashboards, Forecasting, Market Reports, Viewing Scheduler, Training Hub |
| 09 | [API Integrations & External Services](file:///C:/Users/ADMIN/Herd/propos/plans/09-api-integrations.md) | WhatsApp, Portals, E-Signature, Meta Ads, Payment Gateways, Calendar Sync |
| 10 | [Testing, DevOps & Deployment](file:///C:/Users/ADMIN/Herd/propos/plans/10-testing-and-devops.md) | Test strategy, CI/CD pipeline, staging/production environments, monitoring |

---

## 5. Phased Delivery Roadmap

### Phase 1 — Foundation (Months 1–4)

| Sprint | Deliverables |
|---|---|
| **1–2** | Clean architecture scaffolding, multi-tenancy (dual model), auth (login/register/2FA), RBAC, agency settings, shared UI shell (sidebar, topbar, notifications), installer/updater for self-hosted |
| **3–4** | Core CRM: Contact CRUD, unified timeline, duplicate detection, lead source tracking |
| **5–6** | Listing Management: Mandate intake, property CRUD, photo upload/management, AI description generator |
| **7–8** | Portal Syndication (PropertyPro, Property24), basic AI lead scoring, e-signature (DocuSign), email notifications |

### Phase 2 — Intelligence (Months 5–8)

| Sprint | Deliverables |
|---|---|
| **9–10** | AI Agent Assistant: Daily planner, communication drafting, follow-up sequences, AI chat interface |
| **11–12** | Pipeline Management: Kanban boards, stage checklists, deal momentum scoring, stale deal detection |
| **13–14** | Marketing Hub: Campaign builder, brand kit, social content calendar, email marketing engine |
| **15–16** | Viewings Module: Self-service booking, route optimiser, automated reminders, post-viewing feedback |

### Phase 3 — Analytics (Months 9–12)

| Sprint | Deliverables |
|---|---|
| **17–18** | Agency Intelligence Dashboard: Real-time ops overview, agent scorecards, listing health index |
| **19–20** | Revenue Forecasting: Pipeline-based forecast, confidence scoring, target gap analysis |
| **21–22** | Transaction & Compliance Center: Workflow management, document checklists, deadline manager, FICA |
| **23–24** | Commission Management, attorney portal, market intelligence reports, advanced analytics |

### Phase 4 — Learning & Expansion (Months 13–18)

| Sprint | Deliverables |
|---|---|
| **25–28** | Training Hub: Onboarding programme, skills library, AI objection handler, role-play simulator |
| **29–32** | Paid advertising management (Meta Ads), advanced predictive models, sentiment monitoring |
| **33–36** | WhatsApp Business API integration, WhatsApp bot interface, mobile app (API layer + Sanctum), multi-country compliance, performance optimisation |

---

## 6. Key Architectural Decisions

### 6.1 Why Clean Architecture for This Project

> [!IMPORTANT]
> The PropOS blueprint describes 8 modules, 30+ sub-features, and integrations with 15+ external services. Without strict architectural boundaries, this will become unmaintainable.

- **Domain isolation** means the CRM module can evolve independently of the Marketing module
- **Repository interfaces** in the Domain layer allow swapping Eloquent for any persistence without touching business logic
- **Action classes** in the Application layer keep Livewire components thin — they only handle UI state

### 6.2 Livewire + Alpine.js Strategy

| Concern | Approach |
|---|---|
| **Page-level** | Full-page Livewire components (one per route) |
| **Interactive sections** | Nested Livewire components (e.g., pipeline card, chat panel) |
| **Micro-interactions** | Alpine.js (dropdowns, modals, drag-and-drop) |
| **Real-time updates** | Livewire polling + Laravel Echo (Reverb) for live feeds |
| **Heavy interactivity** | Alpine.js plugins (Sortable.js for Kanban, Chart.js for dashboards) |

### 6.3 AI Service Abstraction

```
Domain/AI/Contracts/
├── AiCompletionServiceInterface.php     # Text generation, drafting
├── AiEmbeddingServiceInterface.php      # Semantic search, matching
├── AiImageAnalysisServiceInterface.php  # Photo quality assessment
└── AiPredictionServiceInterface.php     # Scoring, forecasting

Infrastructure/AI/
├── OpenAiCompletionService.php          # GPT-4o implementation
├── DeepSeekCompletionService.php        # DeepSeek implementation
├── OpenAiEmbeddingService.php
└── AiServiceManager.php                 # Factory / strategy selector
```

Provider-agnostic by design. Swap providers by changing `AI_PROVIDER` in `.env`. Adding new providers requires only a new adapter class — zero business logic changes.

### 6.4 Multi-Tenancy Model (Dual Distribution)

PropOS supports two distribution modes:

**SaaS Mode (hosted by us):**
- Single database, tenant-scoped — every model has `agency_id`
- Global scope `BelongsToAgencyScope` auto-filters all queries
- Tenant resolution via subdomain (`acme.propos.app`) or custom domain
- Super-admin panel for platform management, billing, onboarding

**Self-Hosted Mode (agency runs their own instance):**
- Single agency per installation (agency_id = 1 always)
- Installer script (`deploy/installer.php`) handles setup, migrations, seeding
- Updater script (`deploy/updater.php`) handles version upgrades
- License key validation for premium features
- Agency manages their own server/hosting

The codebase is identical for both modes — a config flag `TENANCY_MODE=saas|self_hosted` controls behavior. In self-hosted mode, the tenant resolver always returns the single agency.

### 6.5 Shared Hosting Compatibility

> [!IMPORTANT]
> The initial deployment target is shared hosting. This constrains certain architectural choices:

| Concern | Shared Hosting Approach | Upgrade Path |
|---|---|---|
| **Queue** | Database driver (`QUEUE_CONNECTION=database`) | Redis + Horizon on VPS |
| **Scheduler** | Cron job (`* * * * * php artisan schedule:run`) | Same |
| **Cache** | File driver (`CACHE_STORE=file`) | Redis |
| **Search** | Scout database driver | Meilisearch on VPS |
| **WebSockets** | Livewire polling (10s intervals) | Laravel Reverb on VPS |
| **File Storage** | Local disk (`FILESYSTEM_DISK=local`) | S3-compatible |
| **Process Workers** | Cron-triggered `queue:work --stop-when-empty` | Supervisor + Horizon |

### 6.5 Event-Driven Communication Between Modules

Modules communicate via **Laravel Events**, not direct method calls:

```
Lead Created → LeadCreatedEvent
  → Listener: ScoreLeadAction (AI Module)
  → Listener: NotifyAssignedAgent (Notification)
  → Listener: AutoMatchBuyerToListings (CRM Module)
```

---

## 7. Required Package Dependencies

### Composer Packages

| Package | Purpose |
|---|---|
| `livewire/livewire` | Reactive UI components |
| `laravel/scout` + `meilisearch/meilisearch-php` | Full-text search |
| `laravel/horizon` | Queue monitoring |
| `laravel/reverb` | WebSocket server (real-time) |
| `spatie/laravel-permission` | Roles & permissions |
| `spatie/laravel-medialibrary` | File/media management |
| `spatie/laravel-activitylog` | Audit trail |
| `spatie/laravel-data` | DTOs |
| `openai-php/laravel` | OpenAI integration |
| `barryvdh/laravel-dompdf` | PDF report generation |
| `maatwebsite/excel` | Excel exports |
| `intervention/image` | Image processing |
| `laravel/socialite` | OAuth (Google, Microsoft) |

### NPM Packages

| Package | Purpose |
|---|---|
| `@tailwindcss/vite` | Tailwind CSS 4 (already installed) |
| `sortablejs` | Drag-and-drop (Kanban) |
| `chart.js` | Charts & dashboards |
| `flatpickr` | Date/time pickers |
| `tippy.js` | Tooltips |
| `@alpinejs/persist` | Alpine state persistence |

---

## 8. Resolved Decisions

| Decision | Resolution |
|---|---|
| **Multi-tenancy** | Dual model — SaaS (multi-tenant, subscription) + Self-hosted (single agency). Same codebase, controlled by `TENANCY_MODE` config. |
| **AI Providers** | Provider-agnostic. Ship with **OpenAI** (GPT-4o) and **DeepSeek** adapters. Add more providers by implementing the interface. |
| **Database** | **PostgreSQL 16** for production. DB-agnostic design (no raw SQL, no MySQL-specific features). SQLite for local dev. |
| **Mobile App** | Deferred to Phase 4. API layer (Sanctum) built when needed — not upfront. |
| **WhatsApp** | Deferred to Phase 4 (Sprint 33–36). Email + in-app notifications for all earlier phases. |
| **Deployment** | **Shared hosting** initially. Architecture designed with upgrade path to VPS/Forge. Database queue, file cache, local storage. |
| **Phase 1 priority** | CRM + Listings + Portal Syndication confirmed. WhatsApp removed from Phase 1. |

---

## 9. Verification Plan

### Automated Tests
- **Unit tests** (Pest) for all Domain Services, Value Objects, and Actions
- **Feature tests** for every Livewire component (HTTP-level)
- **Integration tests** for external API clients (mocked)
- **Browser tests** (Pest + browser tool) for critical user flows per phase
- `php artisan test` runs the full suite in CI

### Manual Verification
- Each phase ends with a demo walkthrough using the browser tool
- Screenshots captured for UI verification at each milestone
- Performance benchmarking before each phase release

---

*Proceed to the detailed sub-plans for implementation specifics per module.*
