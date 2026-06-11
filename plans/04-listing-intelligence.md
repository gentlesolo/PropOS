# Plan 04 — Module 2: Listing Intelligence

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/villacrm/implementation_plan.md) · **Phase 1, Sprints 5–8**

---

## 1. Mandate & Listing Intake

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Listing/Entities/Listing.php`, `Domain/Listing/Entities/Property.php` |
| Domain | `Domain/Listing/Enums/MandateType.php` (sole/open/rental) |
| Domain | `Domain/Listing/Enums/ListingStatus.php` (draft/active/under_offer/sold/let/withdrawn/expired) |
| Domain | `Domain/Listing/Enums/PropertyType.php` |
| Domain | `Domain/Listing/Enums/PropertyCondition.php` |
| Domain | `Domain/Listing/ValueObjects/Address.php` — Immutable address with geocoding |
| Domain | `Domain/Listing/ValueObjects/PriceRange.php` |
| Domain | `Domain/Listing/Contracts/ListingRepositoryInterface.php` |
| Domain | `Domain/Listing/Contracts/PropertyRepositoryInterface.php` |
| Application | `Application/Listing/Actions/CreateListingAction.php` |
| Application | `Application/Listing/Actions/UpdateListingAction.php` |
| Application | `Application/Listing/Actions/PublishListingAction.php` — Validates go-live checklist |
| Application | `Application/Listing/DTOs/CreateListingData.php` |
| Application | `Application/Listing/DTOs/PropertyData.php` |

### Go-Live Checklist (validated before publishing)
- All required property fields complete (address, type, bedrooms, bathrooms, size)
- Mandate type and dates set
- At least 5 quality photos uploaded (quality score ≥ 60)
- Listing description generated/written
- Listing price set
- Seller/landlord contact linked

### Livewire Components

| Component | Purpose |
|---|---|
| `Listing/ListingIndexPage.php` | Table view of all listings with filters (status, type, agent, area) |
| `Listing/CreateListingPage.php` | Multi-step wizard: Property Details → Mandate → Media → Description → Review |
| `Listing/EditListingPage.php` | Tabbed editor for existing listing |
| `Listing/ListingDetailPage.php` | Full listing view with performance data, activity timeline |
| `Listing/PropertyDetailsForm.php` | Address (Google autocomplete), type, specs, features |
| `Listing/MandateForm.php` | Mandate type, dates, commission, seller link |
| `Listing/GoLiveChecklist.php` | Visual checklist with status indicators |

---

## 2. AI Listing Description Generator

### Architecture

| Layer | Classes |
|---|---|
| Application | `Application/Listing/Actions/GenerateListingDescriptionAction.php` |
| Application | `Application/Listing/Actions/GenerateListingHeadlinesAction.php` |
| Application | `Application/Listing/DTOs/DescriptionRequestData.php` — property, tone, focus areas |
| Application | `Application/Listing/DTOs/DescriptionResponseData.php` — short, standard, long, headlines[] |
| Infra | `Infrastructure/AI/Prompts/listing-description.txt` |
| Infra | `Infrastructure/AI/Prompts/listing-headlines.txt` |

### Prompt Context Includes
- Full property details (type, size, features, condition, area)
- Mandate type and market positioning
- Local area knowledge (amenities, transport, character)
- Selected tone: Formal / Warm & Inviting / Luxury
- Country/market-specific selling points (e.g., "generator backup" for Lagos)

### Output
- **Short** (120 chars for portal limits)
- **Standard** (200–400 words for agency website)
- **Long-form** (500–800 words for brochures/emails)
- **5 headline options**

### Livewire Component
- `Listing/AiDescriptionGenerator.php` — Embedded in listing editor
  - Tone selector dropdown
  - "Generate" button with loading spinner
  - Tab view: Short | Standard | Long-form
  - Editable textareas for each variant
  - "Regenerate with instructions" — freetext input for refinement
  - "Apply to listing" saves selected descriptions

---

## 3. AI Pricing & Valuation Engine

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Listing/Entities/Valuation.php` |
| Domain | `Domain/Listing/Services/ComparableAnalysisService.php` — Pure logic for CMA |
| Domain | `Domain/Listing/ValueObjects/PriceRecommendation.php` — low, high, optimal |
| Application | `Application/Listing/Actions/GenerateValuationAction.php` |
| Application | `Application/Listing/Actions/GenerateSellerReportAction.php` — PDF report |
| Application | `Application/Listing/DTOs/ValuationData.php` |
| Infra | `Infrastructure/AI/Prompts/valuation-report.txt` |
| Infra | `Infrastructure/Queue/Jobs/GenerateValuationPdfJob.php` |

### CMA Data Sources
- Internal: historical listings + sold prices from agency's own data
- External: portal APIs where available (Property24, PropertyPro)
- Filters: same area (configurable radius), same property type, ±30% size, last 12 months

### Output
- `recommended_price_low`, `recommended_price_high`, `optimal_price`
- Comparable listings table (address, size, sold price, date, DOM)
- Absorption rate (months of inventory)
- Price-to-DOM chart data
- AI-generated market context narrative

### Livewire Components
- `Listing/ValuationPanel.php` — On listing detail page
- `Listing/ValuationReportPage.php` — Full CMA report view
- `Listing/ComparablesList.php` — Table of comparables with map pins

---

## 4. Photo & Media Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Listing/ValueObjects/PhotoQualityReport.php` — score, brightness, sharpness, composition, suggestions |
| Application | `Application/Listing/Actions/UploadListingMediaAction.php` |
| Application | `Application/Listing/Actions/AssessPhotoQualityAction.php` |
| Application | `Application/Listing/Actions/EnhancePhotoAction.php` |
| Application | `Application/Listing/Actions/ReorderListingMediaAction.php` |
| Application | `Application/Listing/Actions/GenerateVideoWalkthroughAction.php` |
| Infra | `Infrastructure/Queue/Jobs/ProcessListingPhotoJob.php` — Resize, assess, enhance |
| Infra | `Infrastructure/ExternalServices/ImageProcessingService.php` |

### Photo Quality Assessment
- Uses `ImageAnalysisInterface` for AI scoring
- Scores: brightness (0–100), sharpness (0–100), composition (0–100) → overall (0–100)
- Threshold: ≥ 60 = acceptable, < 60 = flagged with specific suggestions
- Auto-enhancement: brightness correction, color balance, lens distortion

### Livewire Components
- `Listing/MediaUploader.php` — Drag-and-drop upload with progress bars
- `Listing/MediaGallery.php` — Reorderable grid (Sortable.js), quality badges, delete/replace
- `Listing/PhotoQualityCard.php` — Individual photo scores with improvement suggestions

---

## 5. Portal Syndication

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Listing/Contracts/PortalSyncInterface.php` — Common sync contract |
| Domain | `Domain/Listing/Enums/PortalSyncStatus.php` — pending/synced/failed/removed |
| Application | `Application/Listing/Actions/SyncListingToPortalsAction.php` |
| Application | `Application/Listing/Actions/RemoveListingFromPortalAction.php` |
| Application | `Application/Listing/Actions/FetchPortalPerformanceAction.php` |
| Infra | `Infrastructure/ExternalServices/Portals/PropertyProAdapter.php` |
| Infra | `Infrastructure/ExternalServices/Portals/Property24Adapter.php` |
| Infra | `Infrastructure/ExternalServices/Portals/LamudiAdapter.php` |
| Infra | `Infrastructure/ExternalServices/Portals/AbstractPortalAdapter.php` |
| Infra | `Infrastructure/Queue/Jobs/SyncListingToPortalJob.php` |
| Infra | `Infrastructure/Queue/Jobs/FetchPortalMetricsJob.php` — Scheduled daily |

### Format Adaptation
Each adapter implements `PortalSyncInterface` and handles:
- Character limit truncation for descriptions
- Photo count limits and resolution requirements
- Field mapping (property type codes, amenity codes differ per portal)
- Currency formatting

### Livewire Components
- `Listing/PortalSyncPanel.php` — Per-listing: connected portals, sync status, last synced, performance metrics (views, saves, inquiries)
- `Listing/PortalPerformanceChart.php` — Line chart of views/inquiries over time per portal

---

## 6. Seller Communication & Reporting

### Architecture

| Layer | Classes |
|---|---|
| Application | `Application/Listing/Actions/GenerateSellerUpdateReportAction.php` |
| Application | `Application/Listing/Actions/SendSellerReportAction.php` |
| Application | `Application/Listing/Actions/CheckMandateRenewalsAction.php` |
| Infra | `Infrastructure/Queue/Jobs/SendWeeklySellerReportsJob.php` — Scheduled |
| Infra | `Infrastructure/Queue/Jobs/CheckMandateExpiryJob.php` — Daily |

### Automated Seller Report Contents
- Portal performance (views, saves, inquiries per portal)
- Viewings conducted + summarised feedback
- Market context (comparable activity, DOM trends)
- Agent's notes / next steps
- Generated as branded PDF (agency colors/logo via `pdf.blade.php` layout)

### Livewire Components
- `Listing/SellerReportPreview.php` — Preview report before sending
- `Listing/MandateRenewalAlert.php` — Dashboard widget for expiring mandates

---

## 7. Acceptance Criteria

- [ ] Multi-step listing creation wizard works on mobile
- [ ] Google Maps autocomplete for property address with geocoding
- [ ] Go-live checklist enforced — cannot publish incomplete listing
- [ ] AI generates 3 description lengths + 5 headlines from property data
- [ ] Descriptions editable and regenerable with custom instructions
- [ ] Photo upload with drag-and-drop, quality scoring, and auto-enhancement
- [ ] Photo reordering via drag-and-drop (AI suggests optimal order)
- [ ] CMA pulls comparable listings and calculates price recommendation
- [ ] Valuation report generates as branded PDF
- [ ] One-click publish to connected portals with format adaptation
- [ ] Portal performance metrics (views, inquiries) displayed per listing
- [ ] Automated seller reports sent weekly/biweekly per listing preference
- [ ] Mandate expiry alerts at 30 days before end date
