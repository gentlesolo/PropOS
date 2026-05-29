# Plan 09 — API Integrations & External Services

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md)

---

## 1. Integration Architecture Pattern

All external services follow the same clean architecture pattern:

```
Domain Layer:      Contract/Interface (provider-agnostic)
Infrastructure:    Adapter/Client (provider-specific implementation)
Configuration:     config/{service}.php + .env variables
Credentials:       Stored encrypted in `integration_credentials` table per agency
```

### Shared Infrastructure

```
Infrastructure/ExternalServices/
├── Contracts/
│   └── ExternalServiceInterface.php   # Base: connect(), disconnect(), isConnected(), getStatus()
├── AbstractServiceAdapter.php         # Shared HTTP client, retry logic, error handling
├── ServiceCredentialManager.php       # Encrypt/decrypt credentials per agency
└── WebhookController.php             # Routes incoming webhooks to correct handler
```

### Webhook Processing
All webhooks route through `/api/webhooks/{service}` → `WebhookController` → dispatches to service-specific handler job (queued for reliability).

---

## 2. WhatsApp Business API

### Contract
```php
Domain/Shared/Contracts/WhatsAppServiceInterface.php
- sendTextMessage(string $to, string $body): MessageResult
- sendTemplateMessage(string $to, string $templateName, array $params): MessageResult
- sendMediaMessage(string $to, string $mediaUrl, string $caption): MessageResult
- getMessageStatus(string $messageId): MessageStatus
- createTemplate(string $name, string $body, string $category): TemplateResult
```

### Infrastructure
```
Infrastructure/ExternalServices/WhatsApp/
├── WhatsAppApiClient.php              # Meta Cloud API implementation
├── WhatsAppWebhookHandler.php         # Processes incoming messages + status updates
├── WhatsAppTemplateManager.php        # Manage approved templates
└── TwilioWhatsAppAdapter.php          # Alternative via Twilio (fallback)
```

### Configuration
```php
// config/whatsapp.php
'provider' => env('WHATSAPP_PROVIDER', 'meta'),  // meta | twilio
'meta' => [
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'api_version' => 'v18.0',
],
'twilio' => [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from_number' => env('TWILIO_WHATSAPP_FROM'),
],
```

### Use Cases
- Agent → buyer communication (1:1 messages)
- Automated follow-up sequences
- Viewing reminders and confirmations
- Marketing broadcasts (template messages)
- Daily brief delivery
- Incoming message → create/update CRM contact activity

---

## 3. Property Portal Integrations

### Contract
```php
Domain/Listing/Contracts/PortalSyncInterface.php
- publishListing(Listing $listing): SyncResult
- updateListing(Listing $listing): SyncResult
- removeListing(string $externalId): bool
- getPerformanceMetrics(string $externalId): PortalMetrics  // views, saves, inquiries
- mapListingToPortalFormat(Listing $listing): array
```

### Infrastructure
```
Infrastructure/ExternalServices/Portals/
├── AbstractPortalAdapter.php          # Shared mapping, validation, retry logic
├── PropertyProAdapter.php             # PropertyPro.ng
├── Property24Adapter.php              # Property24 (SA + Africa)
├── LamudiAdapter.php                  # Lamudi (NG, KE, GH)
├── BuyRentKenyaAdapter.php
├── PortalFieldMapper.php              # Maps PropOS fields → portal-specific codes
└── PortalMetricsFetcher.php           # Scheduled job to pull views/inquiries
```

### Field Mapping Strategy
Each adapter has a `fieldMap` array translating PropOS property types, amenities, and fields to portal-specific codes. Example:
```php
protected array $propertyTypeMap = [
    'house' => 'RES_HOUSE',
    'apartment' => 'RES_FLAT',
    'townhouse' => 'RES_TOWNHOUSE',
    // ...
];
```

### Scheduled Jobs
- `SyncListingToPortalJob` — Triggered on publish/update
- `FetchPortalMetricsJob` — Daily pull of views/saves/inquiries per listing
- `RefreshPortalListingsJob` — Periodic minor update to boost search ranking

---

## 4. E-Signature Integration

### Contract
```php
Domain/Transaction/Contracts/ESignatureServiceInterface.php
- sendForSignature(Document $document, array $signers): SignatureRequest
- getSignatureStatus(string $requestId): SignatureStatus
- downloadSignedDocument(string $requestId): string  // returns file path
- cancelSignatureRequest(string $requestId): bool
```

### Infrastructure
```
Infrastructure/ExternalServices/ESignature/
├── DocuSignAdapter.php
├── DropboxSignAdapter.php
├── ESignatureWebhookHandler.php       # Processes completion callbacks
└── SignatureRequestTracker.php        # Tracks pending requests
```

### Database: `signature_requests` table
`id`, `agency_id`, `transaction_id`, `document_id`, `provider` (docusign/dropbox_sign), `external_request_id`, `status` (sent/viewed/signed/declined/cancelled), `signers` (json), `sent_at`, `completed_at`, timestamps

### Flow
1. Agent selects document → "Send for Signature"
2. Action creates envelope/request via provider API
3. Signers receive email with signing link
4. Provider sends webhook on completion → handler processes
5. Signed PDF downloaded and stored as new document version
6. Transaction milestone auto-updated

---

## 5. Meta Ads (Facebook + Instagram)

### Contract
```php
Domain/Marketing/Contracts/AdPlatformInterface.php
- createCampaign(AdCampaignData $data): CampaignResult
- updateCampaign(string $campaignId, array $updates): bool
- pauseCampaign(string $campaignId): bool
- getCampaignPerformance(string $campaignId): AdPerformanceData
- createAudience(AudienceData $data): AudienceResult
- getAdAccountInfo(): AdAccountInfo
```

### Infrastructure
```
Infrastructure/ExternalServices/MetaAds/
├── MetaAdsAdapter.php                 # Facebook Marketing API client
├── MetaAdsPerformanceSyncer.php       # Pulls performance data daily
└── MetaAudienceBuilder.php            # Creates custom/lookalike audiences
```

### Configuration
Requires: Meta Business Manager account, Marketing API access, `pages_manage_ads` + `ads_management` permissions. Credentials stored per agency in `integration_credentials`.

---

## 6. Email Delivery

### Contract
```php
Domain/Shared/Contracts/EmailDeliveryInterface.php
- send(EmailMessage $message): DeliveryResult
- sendBulk(array $messages): array<DeliveryResult>
- getDeliveryStatus(string $messageId): DeliveryStatus
- trackOpen(string $messageId): void
- trackClick(string $messageId, string $url): void
```

### Infrastructure
- **Transactional email:** Laravel's built-in Mail with Mailgun/SendGrid driver
- **Marketing email:** Dedicated `BulkEmailService` with rate limiting, bounce handling

```
Infrastructure/ExternalServices/Email/
├── MailgunAdapter.php
├── SendGridAdapter.php
├── EmailTrackingService.php           # Open/click tracking pixels
└── BounceHandler.php                  # Process bounces, update subscriber status
```

---

## 7. SMS (Twilio)

### Contract
```php
Domain/Shared/Contracts/SmsServiceInterface.php
- send(string $to, string $message): SmsResult
- sendBulk(array $recipients, string $message): array<SmsResult>
- getDeliveryStatus(string $messageId): DeliveryStatus
```

### Infrastructure
```
Infrastructure/ExternalServices/Sms/
├── TwilioSmsAdapter.php
└── SmsWebhookHandler.php
```

---

## 8. Calendar Sync

### Contract
```php
Domain/Shared/Contracts/CalendarSyncInterface.php
- getAvailableSlots(DateRange $range): array<TimeSlot>
- createEvent(CalendarEvent $event): string  // returns event ID
- updateEvent(string $eventId, CalendarEvent $event): bool
- deleteEvent(string $eventId): bool
- syncAvailability(string $userId): void
```

### Infrastructure
```
Infrastructure/ExternalServices/Calendar/
├── GoogleCalendarAdapter.php          # Google Calendar API
├── OutlookCalendarAdapter.php         # Microsoft Graph API
└── CalendarSyncService.php            # Bi-directional sync
```

### Use Cases
- Agent availability for viewing bookings
- Viewing events synced to agent's calendar
- Open house events synced
- Daily brief includes calendar summary

---

## 9. Maps & Geocoding

### Contract
```php
Domain/Shared/Contracts/GeocodingInterface.php
- geocode(string $address): Coordinates
- reverseGeocode(float $lat, float $lng): string
- getDirections(Coordinates $from, Coordinates $to): Route
- optimiseRoute(array<Coordinates> $waypoints): array<int>  // optimised order
```

### Infrastructure
```
Infrastructure/ExternalServices/Maps/
├── GoogleMapsAdapter.php              # Geocoding, Directions, Places
└── MapboxAdapter.php                  # Alternative provider
```

### Use Cases
- Property address geocoding on listing creation
- Viewing route optimisation
- Map display on listing pages and viewing planner
- Address autocomplete in property form

---

## 10. Integration Settings UI

### Livewire Components
```
Http/Livewire/Settings/IntegrationsPage.php
```

**Per-service card showing:**
- Connection status (connected/disconnected/expired)
- Connect/disconnect button
- Last synced timestamp
- Error alerts if credentials expired

**Services displayed:** WhatsApp, DocuSign, Meta Ads, Google Calendar, Property portals, Email provider

---

## 11. Acceptance Criteria

- [ ] WhatsApp messages send/receive via Business API
- [ ] Portal syndication publishes to at least 2 portals with correct field mapping
- [ ] Portal performance metrics pull daily (views, inquiries)
- [ ] E-signature sends documents, tracks status, stores signed copies
- [ ] Marketing emails send with open/click tracking
- [ ] SMS sends via Twilio
- [ ] Calendar sync shows agent availability for booking
- [ ] Viewing events sync to agent's Google/Outlook calendar
- [ ] Address autocomplete and geocoding work on listing forms
- [ ] Viewing route optimisation returns efficient order
- [ ] Integration settings page shows status per service
- [ ] All webhook endpoints process callbacks reliably (queued)
- [ ] Credentials encrypted at rest in `integration_credentials`
