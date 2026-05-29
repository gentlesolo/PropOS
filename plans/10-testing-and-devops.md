# Plan 10 — Testing, DevOps & Deployment

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md)

---

## 1. Testing Strategy

### Test Pyramid

```
         ╱ Browser Tests ╲         (5%)  Critical user flows
        ╱  Integration     ╲       (15%) External API adapters
       ╱   Feature Tests    ╲      (30%) Livewire components, HTTP
      ╱    Unit Tests        ╲     (50%) Domain services, actions, VOs
```

### Directory Structure

```
tests/
├── Unit/
│   ├── Domain/
│   │   ├── CRM/
│   │   │   ├── LeadScoringServiceTest.php
│   │   │   ├── DuplicateDetectionServiceTest.php
│   │   │   ├── DealMomentumServiceTest.php
│   │   │   └── BuyerListingMatchServiceTest.php
│   │   ├── Listing/
│   │   │   ├── ComparableAnalysisServiceTest.php
│   │   │   └── PropertyTypeTest.php
│   │   ├── Transaction/
│   │   │   ├── CommissionCalculationServiceTest.php
│   │   │   ├── ComplianceRuleEngineTest.php
│   │   │   └── DocumentChecklistServiceTest.php
│   │   ├── Intelligence/
│   │   │   ├── ScorecardCalculationServiceTest.php
│   │   │   └── ForecastingServiceTest.php
│   │   └── Shared/
│   │       └── ValueObjects/
│   ├── Application/
│   │   ├── CRM/
│   │   │   ├── CreateContactActionTest.php
│   │   │   ├── MergeContactsActionTest.php
│   │   │   └── ScoreLeadActionTest.php
│   │   ├── Listing/
│   │   │   ├── CreateListingActionTest.php
│   │   │   └── PublishListingActionTest.php
│   │   └── ...
│   │
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── RegistrationTest.php
│   │   ├── PasswordResetTest.php
│   │   └── TwoFactorTest.php
│   ├── CRM/
│   │   ├── ContactCrudTest.php
│   │   ├── PipelineBoardTest.php
│   │   └── FollowUpSequenceTest.php
│   ├── Listing/
│   │   ├── ListingCrudTest.php
│   │   ├── PortalSyncTest.php
│   │   └── AiDescriptionTest.php
│   ├── Marketing/
│   │   ├── CampaignBuilderTest.php
│   │   └── EmailCampaignTest.php
│   ├── Transaction/
│   │   ├── TransactionWorkflowTest.php
│   │   └── CommissionTest.php
│   ├── Tenancy/
│   │   ├── DataIsolationTest.php
│   │   └── TenantResolutionTest.php
│   └── Rbac/
│       ├── PermissionEnforcementTest.php
│       └── RoleAccessTest.php
│
├── Integration/
│   ├── WhatsAppApiTest.php          # Mocked API responses
│   ├── PortalSyncTest.php
│   ├── DocuSignTest.php
│   ├── MetaAdsTest.php
│   └── AiServiceTest.php
│
└── Browser/                          # Using browser subagent tool
    ├── OnboardingFlowTest.md         # Test scripts for browser tool
    ├── ListingCreationFlowTest.md
    └── PipelineDragDropTest.md
```

### Testing Conventions

| Principle | Implementation |
|---|---|
| **Domain tests are pure** | No database, no framework, no HTTP — pure PHP unit tests |
| **Feature tests use database** | `RefreshDatabase` trait, test via Livewire test helpers |
| **External APIs are mocked** | All adapters mocked in tests, real calls only in dedicated integration test suite |
| **Factory strategy** | Every Eloquent model has a factory with sensible defaults |
| **Tenant-aware testing** | `ActingAsAgent` trait sets current tenant + user for tests |

### Key Test Utilities

```php
// tests/Concerns/ActingAsAgent.php
trait ActingAsAgent {
    protected Agency $agency;
    protected User $agent;

    protected function setUpAgent(string $role = 'agent'): void
    {
        $this->agency = Agency::factory()->create();
        $this->agent = User::factory()->for($this->agency)->create();
        $this->agent->assignRole($role);
        $this->actingAs($this->agent);
        app(TenantResolver::class)->setCurrentAgency($this->agency);
    }
}
```

### Running Tests

```bash
# Full suite
php artisan test

# Specific module
php artisan test --filter=CRM

# Unit only (fast, no DB)
php artisan test tests/Unit

# Feature only
php artisan test tests/Feature

# With coverage
php artisan test --coverage --min=80
```

---

## 2. CI/CD Pipeline (GitHub Actions)

### Workflow: `.github/workflows/ci.yml`

```yaml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction
      - run: ./vendor/bin/pint --test          # Code style

  test:
    runs-on: ubuntu-latest
    needs: lint
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: propos_test
          MYSQL_ROOT_PASSWORD: password
        ports: ['3306:3306']
      redis:
        image: redis:7
        ports: ['6379:6379']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', extensions: 'mbstring,pdo_mysql,redis' }
      - run: composer install --no-interaction
      - run: cp .env.ci .env
      - run: php artisan key:generate
      - run: php artisan migrate
      - run: php artisan test --coverage --min=80

  build:
    runs-on: ubuntu-latest
    needs: test
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '20' }
      - run: npm ci
      - run: npm run build
      - uses: actions/upload-artifact@v4
        with: { name: build, path: public/build }
```

### Deployment Workflow: `.github/workflows/deploy.yml`

Triggered on merge to `main`. Deploys to production via Laravel Forge or custom SSH.

---

## 3. Environment Setup

### Local Development (Laravel Herd)
- PHP 8.3 via Herd
- MySQL 8 via Herd (or SQLite for quick prototyping)
- Redis via Herd or Docker
- Meilisearch via Docker (`docker run -p 7700:7700 getmeili/meilisearch`)
- `composer dev` starts: artisan serve + queue worker + vite dev

### Staging Environment
- Mirror of production
- Seeded with demo data (`DemoAgencySeeder`)
- Connected to sandbox APIs (WhatsApp, DocuSign test accounts)
- Accessible at `staging.propos.app`

### Production Environment
- Dedicated server or managed (Forge/Vapor)
- MySQL 8 managed database
- Redis for cache + queues + sessions
- S3-compatible storage for uploads
- Meilisearch for search
- SSL via Let's Encrypt / Cloudflare

### Environment Variables (`.env` additions)

```
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=propos
DB_USERNAME=propos
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1

# AI
AI_PROVIDER=openai
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o

# WhatsApp
WHATSAPP_PROVIDER=meta
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=

# E-Signature
ESIGNATURE_PROVIDER=docusign
DOCUSIGN_INTEGRATION_KEY=
DOCUSIGN_SECRET_KEY=

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Search
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700

# Maps
GOOGLE_MAPS_API_KEY=

# Queue
QUEUE_CONNECTION=redis
```

---

## 4. Monitoring & Observability

### In-App Tools

| Tool | Purpose |
|---|---|
| **Laravel Telescope** | Debug queries, requests, jobs, mail, notifications (dev/staging only) |
| **Laravel Horizon** | Queue monitoring dashboard — job throughput, failures, wait times |
| **Laravel Pulse** | Production monitoring — slow queries, cache hits, queue health |

### External Monitoring

| Tool | Purpose |
|---|---|
| **Sentry** | Error tracking + performance monitoring |
| **UptimeRobot / Better Uptime** | Uptime monitoring + status page |
| **Laravel Forge monitoring** | Server metrics (CPU, memory, disk) |

### Logging Strategy

```php
// config/logging.php — production channels
'channels' => [
    'stack' => ['channels' => ['daily', 'sentry']],
    'daily' => ['driver' => 'daily', 'days' => 30],
    'sentry' => ['driver' => 'sentry', 'level' => 'error'],
    'ai' => ['driver' => 'daily', 'path' => 'storage/logs/ai.log'],      // AI-specific
    'integrations' => ['driver' => 'daily', 'path' => 'storage/logs/integrations.log'],
],
```

### Health Check Endpoint

`GET /api/health` → checks: database, redis, queue, meilisearch, storage. Returns JSON status.

---

## 5. Performance Optimization

### Caching Strategy

| Data | Cache Duration | Invalidation |
|---|---|---|
| Agency settings/branding | 1 hour | On update |
| User permissions | 1 hour | On role change |
| Dashboard statistics | 5 minutes | Time-based |
| Portal metrics | 1 hour | On fetch |
| Market reports | 24 hours | On regeneration |
| AI prompt templates | Until deploy | Cache clear on deploy |

### Database Optimization
- Composite indexes on high-traffic queries (see Plan 02)
- Eager loading enforced via `$with` on models or explicit `with()` in queries
- Query monitoring via Telescope (dev) / Pulse (prod) to catch N+1
- Database transactions for multi-table writes

### Queue Optimization
- Separate queues: `default`, `ai` (rate-limited), `notifications`, `sync` (portal), `reports`
- Horizon supervisor configuration per queue with appropriate worker counts
- Failed job monitoring + retry strategy

### Asset Optimization
- Vite build with code splitting per route
- Image optimization on upload (resize, compress)
- CDN for static assets (Cloudflare)
- Livewire lazy loading for heavy components

---

## 6. Deployment Checklist (Per Release)

```
Pre-Deploy:
  ☐ All tests pass in CI
  ☐ Code review approved
  ☐ Migrations tested on staging
  ☐ Changelog updated

Deploy:
  ☐ Enable maintenance mode
  ☐ Pull latest code
  ☐ Install composer dependencies (--no-dev)
  ☐ Run migrations
  ☐ npm ci && npm run build
  ☐ Clear and warm caches
  ☐ Restart queue workers
  ☐ Disable maintenance mode

Post-Deploy:
  ☐ Verify health check endpoint
  ☐ Check Horizon dashboard (queues processing)
  ☐ Monitor Sentry for new errors (30 min)
  ☐ Verify critical user flows
```

---

## 7. Acceptance Criteria

- [ ] Unit tests cover all domain services and value objects (≥80% coverage)
- [ ] Feature tests cover all Livewire CRUD flows
- [ ] Tenant data isolation verified by tests
- [ ] RBAC enforcement verified by tests
- [ ] CI pipeline runs lint + test + build on every PR
- [ ] Deployment script automates full deploy flow
- [ ] Horizon dashboard accessible to principals
- [ ] Health check endpoint returns correct status
- [ ] Error tracking captures and alerts on exceptions
- [ ] Queue failure monitoring with retry mechanism
