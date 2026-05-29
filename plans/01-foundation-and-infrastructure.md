# Plan 01 — Foundation & Shared Infrastructure

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md)

---

## 1. Multi-Tenancy (Single DB, Agency-Scoped)

Every tenant-aware table has `agency_id`. A global scope auto-filters queries.

### Key Classes

| Layer | Path | Purpose |
|---|---|---|
| Domain | `Domain/Identity/Entities/Agency.php` | Pure entity — name, slug, branding, settings |
| Domain | `Domain/Identity/ValueObjects/AgencyId.php` | Typed ID wrapper |
| Domain | `Domain/Identity/Contracts/AgencyRepositoryInterface.php` | Repository contract |
| Infra | `Infrastructure/Persistence/Models/Agency.php` | Eloquent model |
| Infra | `Infrastructure/Persistence/Scopes/BelongsToAgencyScope.php` | Global scope |
| Infra | `Infrastructure/Persistence/Traits/BelongsToAgency.php` | Trait for all tenant models |
| Infra | `Infrastructure/Tenancy/TenantResolver.php` | Resolves from subdomain/custom domain |
| Infra | `Infrastructure/Tenancy/TenantMiddleware.php` | Sets current tenant per request |

**Agency Properties:** `id`, `name`, `slug`, `custom_domain`, `logo_path`, `primary_color`, `secondary_color`, `accent_color`, `tagline`, `address`, `phone`, `email`, `website`, `timezone`, `currency`, `country_code`, `subscription_plan`, `settings` (JSON), timestamps.

**Resolution order:** Subdomain → Custom domain → Session fallback (super-admin).

---

## 2. Authentication

**Features:** Email/password login, registration (creates user + agency), password reset, 2FA (TOTP), remember me, session management, email verification.

### Key Classes

| Layer | Path |
|---|---|
| Domain | `Domain/Identity/Entities/User.php` — Pure entity |
| Domain | `Domain/Identity/Enums/UserStatus.php` — Active, Suspended, Invited, Deactivated |
| Application | `Application/Identity/Actions/RegisterUserAction.php` |
| Application | `Application/Identity/Actions/LoginUserAction.php` |
| Application | `Application/Identity/Actions/InviteTeamMemberAction.php` |
| Application | `Application/Identity/DTOs/RegisterUserData.php` |
| Livewire | `Http/Livewire/Auth/LoginPage.php` |
| Livewire | `Http/Livewire/Auth/RegisterPage.php` |
| Livewire | `Http/Livewire/Auth/ForgotPasswordPage.php` |
| Livewire | `Http/Livewire/Auth/AcceptInvitationPage.php` |

**User Properties:** `id`, `agency_id`, `first_name`, `last_name`, `email`, `phone`, `avatar_path`, `job_title`, `status`, `two_factor_enabled`, `two_factor_secret`, `notification_preferences` (JSON), `last_login_at`, `last_active_at`, timestamps.

---

## 3. Roles & Permissions (RBAC)

Using `spatie/laravel-permission`. All roles scoped to agency via guard.

| Role | Key | Data Scope |
|---|---|---|
| Agent | `agent` | Own data only |
| Senior Agent | `senior_agent` | Own + limited team |
| Branch Manager | `branch_manager` | Branch-level |
| Marketing Manager | `marketing_manager` | Marketing module + listings |
| Admin / PA | `admin` | Scheduling, compliance, CRM |
| Principal | `principal` | Full agency access |
| Super Admin | `super_admin` | Platform-wide |

**Permission Groups:** `listings.*`, `contacts.*`, `pipeline.*`, `campaigns.*`, `transactions.*`, `commission.*`, `dashboard.*`, `training.*`, `agency.*` — with `view_own`, `view_team`, `view_all`, `create`, `edit`, `delete`, `manage` granularity.

**Middleware:** `EnsureAgencyResolved`, `EnsureEmailVerified`, `EnsureUserActive`, `TrackLastActivity`.

---

## 4. Notification Bus

Unified dispatch to multiple channels based on user preferences.

| Component | Path |
|---|---|
| Enum | `Domain/Shared/Enums/NotificationChannel.php` — InApp, Email, WhatsApp, SMS, Push |
| Action | `Application/Shared/Actions/SendNotificationAction.php` |
| Channels | `Infrastructure/Notifications/Channels/{InApp,Email,WhatsApp,Sms,Push}Channel.php` |
| Dispatcher | `Infrastructure/Notifications/NotificationDispatcher.php` |

**`notifications` table:** `id`, `agency_id`, `user_id`, `type`, `title`, `body`, `action_url`, `severity` (info/warning/urgent), `channels_dispatched` (JSON), `read_at`, timestamps.

**Livewire:** `NotificationBell.php` (topbar), `NotificationPanel.php` (slide-out), `NotificationPreferences.php`.

---

## 5. AI Service Abstraction Layer

### Domain Contracts (Provider-Agnostic)

```
Domain/AI/Contracts/
├── TextGenerationInterface.php     # generate(), generateStructured(), stream()
├── EmbeddingInterface.php          # embed(), embedBatch()
├── ImageAnalysisInterface.php      # analyzeQuality(), describeImage()
└── PredictionInterface.php         # predictScore(), predictTimeSeries()
```

### Infrastructure Implementations

```
Infrastructure/AI/
├── OpenAi/OpenAiTextGeneration.php
├── OpenAi/OpenAiEmbedding.php
├── Anthropic/AnthropicTextGeneration.php
├── AiServiceManager.php              # Factory — resolves provider from config
└── Prompts/                           # Template files for each AI use case
```

**Config (`config/ai.php`):** Default provider, API keys, model selection, rate limits. Swap providers by changing `AI_PROVIDER` env var.

---

## 6. Shared UI Shell

### Layouts

| File | Purpose |
|---|---|
| `layouts/app.blade.php` | Main authenticated — sidebar + topbar + content |
| `layouts/auth.blade.php` | Auth pages — centered card |
| `layouts/guest.blade.php` | Public pages |
| `layouts/pdf.blade.php` | PDF reports |

### Blade Component Library (`resources/views/components/ui/`)

`button`, `input`, `select`, `textarea`, `checkbox`, `toggle`, `badge`, `avatar`, `card`, `modal`, `dropdown`, `table`, `pagination`, `tabs`, `alert`, `toast`, `empty-state`, `stat-card`, `progress-bar`, `tooltip`, `search-input`

### Navigation Components

`sidebar`, `sidebar-item`, `sidebar-group`, `topbar`, `breadcrumbs`, `mobile-nav`

### Data Display Components

`data-table` (sortable/filterable), `kanban-board` (drag-drop), `timeline` (activity), `chart` (Chart.js wrapper), `calendar`

### Livewire Shell Components

| Component | Purpose |
|---|---|
| `Shared/Sidebar.php` | Collapsible sidebar, active states, role-filtered items |
| `Shared/Topbar.php` | Search, notifications, profile menu |
| `Shared/GlobalSearch.php` | AI-powered universal search |
| `Shared/CommandPalette.php` | Cmd+K for power users |
| `Shared/QuickActions.php` | Mobile floating action button |

### Sidebar Navigation Structure

Dashboard → AI Assistant → Listings (CRUD, Valuations) → CRM (Contacts, Pipeline, Leads) → Marketing (Campaigns, Calendar, Email, WhatsApp) → Viewings (Open Houses) → Transactions (Compliance, Commissions) → Intelligence (Agents, Market, Financials) → Training (Learning, Skills, Assessments) → Settings (Agency, Team, Integrations, Brand, Billing)

*Items filtered by user role permissions.*

---

## 7. Agency Settings

| Livewire Page | Purpose |
|---|---|
| `Settings/AgencyProfilePage.php` | Name, address, contact, logo |
| `Settings/BrandingPage.php` | Colors, fonts, tagline |
| `Settings/TeamManagementPage.php` | Invite, assign roles, deactivate |
| `Settings/IntegrationsPage.php` | Connect external services |
| `Settings/NotificationSettingsPage.php` | Global notification rules |
| `Settings/BillingPage.php` | Subscription management |

---

## 8. Acceptance Criteria (Phase 1, Sprints 1–2)

- [ ] Clean architecture directories created, PSR-4 autoloading configured
- [ ] Agency registration: user creates account + agency in one step
- [ ] Login, password reset, 2FA, email verification working
- [ ] Team invitation: principal invites via email, agent accepts
- [ ] 7 roles seeded with correct default permissions
- [ ] Sidebar navigation rendered based on role
- [ ] Notification bell with unread count, panel with recent items
- [ ] Agency settings: profile, branding, timezone
- [ ] Tenant isolation verified (Agency A ≠ Agency B)
- [ ] Global search returns contacts and listings
- [ ] Responsive layout (sidebar collapses on mobile)
- [ ] Tailwind CSS 4 styling with agency brand colors
