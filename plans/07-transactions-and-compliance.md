# Plan 07 — Module 5: Transactions & Compliance

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/villacrm/implementation_plan.md) · **Phase 3, Sprints 21–24**

---

## 1. Transaction Workflow Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Transaction/Entities/Transaction.php` |
| Domain | `Domain/Transaction/Entities/TransactionMilestone.php` |
| Domain | `Domain/Transaction/Enums/TransactionType.php` (freehold_sale/sectional_title/commercial_sale/long_term_rental/short_term_rental) |
| Domain | `Domain/Transaction/Enums/TransactionStatus.php` (active/completed/cancelled/disputed) |
| Domain | `Domain/Transaction/Enums/MilestoneStatus.php` (not_started/in_progress/awaiting_external/completed) |
| Domain | `Domain/Transaction/Services/TransactionWorkflowService.php` — Loads correct template per type |
| Domain | `Domain/Transaction/Contracts/TransactionRepositoryInterface.php` |
| Application | `Application/Transaction/Actions/CreateTransactionAction.php` — Auto-creates from accepted deal |
| Application | `Application/Transaction/Actions/UpdateMilestoneAction.php` |
| Application | `Application/Transaction/Actions/UpdateStakeholdersAction.php` |
| Application | `Application/Transaction/DTOs/CreateTransactionData.php` |
| Events | `DealAcceptedEvent` → `CreateTransactionListener` |

### Workflow Templates (per transaction type)

**Freehold Sale Milestones:** Offer Accepted → OTP Signed → FICA Submitted → Bond Application → Bond Approved → Transfer Duty Paid → Documents to Attorney → Lodgement → Registration → Transfer Complete

**Rental Milestones:** Application Approved → Lease Drafted → Lease Signed → Deposit Paid → Inspection Done → Keys Handed Over → Move-In

### Livewire Components

| Component | Purpose |
|---|---|
| `Transaction/TransactionIndexPage.php` | Table of all transactions with status filters |
| `Transaction/TransactionDetailPage.php` | Full view: milestone timeline + document panel + stakeholders + deadline calendar |
| `Transaction/MilestoneTimeline.php` | Visual timeline with status indicators and dates |
| `Transaction/StakeholderPanel.php` | All parties with contact info and last communication |

---

## 2. Document Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Transaction/Entities/TransactionDocument.php` |
| Domain | `Domain/Transaction/Enums/DocumentType.php` |
| Domain | `Domain/Transaction/Enums/DocumentStatus.php` (outstanding/uploaded/pending_review/approved/rejected) |
| Domain | `Domain/Transaction/Services/DocumentChecklistService.php` — Generates checklist per transaction type |
| Application | `Application/Transaction/Actions/UploadDocumentAction.php` |
| Application | `Application/Transaction/Actions/ReviewDocumentAction.php` |
| Application | `Application/Transaction/Actions/SendForSignatureAction.php` |
| Application | `Application/Transaction/Actions/GenerateDocumentFromTemplateAction.php` |
| Infra | `Infrastructure/ExternalServices/ESignature/DocuSignAdapter.php` |
| Infra | `Infrastructure/ExternalServices/ESignature/DropboxSignAdapter.php` |
| Infra | `Infrastructure/ExternalServices/ESignature/ESignatureInterface.php` |

### Document Checklist (auto-generated per transaction type)
Mandate, OTP, FICA (ID + Address + Source of Funds), Proof of Income, Bond Approval Letter, Transfer Duty Receipt, Compliance Certificate, etc.

### E-Signature Flow
1. Agent selects document → "Send for Signature"
2. System prepares document via e-signature provider (DocuSign/DropboxSign)
3. Signers receive signing request via email
4. Signed document automatically stored and status updated to "Approved"
5. Webhook handler processes completion callback

### Livewire Components
- `Transaction/DocumentChecklist.php` — Status per document, upload/download buttons
- `Transaction/DocumentUploader.php` — Upload with type selection, version management
- `Transaction/DocumentViewer.php` — In-browser document preview
- `Transaction/DocumentTemplateSelector.php` — Choose from template library, pre-fill fields

---

## 3. Deadline & Critical Date Manager

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Transaction/Entities/TransactionDeadline.php` |
| Domain | `Domain/Transaction/Services/DeadlineExtractionService.php` — Extracts dates from contracts |
| Application | `Application/Transaction/Actions/CreateDeadlineAction.php` |
| Application | `Application/Transaction/Actions/ExtendDeadlineAction.php` — Generates addendum, routes for signing |
| Application | `Application/Transaction/Actions/CheckDeadlinesAction.php` |
| Infra | `Infrastructure/Queue/Jobs/CheckTransactionDeadlinesJob.php` — Daily |

### Escalating Reminders
- 14 days before → info notification
- 7 days → warning notification
- 3 days → urgent notification + manager CC
- 1 day → critical alert + manager + principal
- Overdue → deal flagged "at risk" automatically

### Livewire Components
- `Transaction/DeadlineCalendar.php` — Calendar view of all deadlines across transactions
- `Transaction/DeadlineAlertPanel.php` — Dashboard widget: upcoming and overdue deadlines
- `Transaction/DeadlineExtensionWizard.php` — Generate addendum, route for e-signature

---

## 4. FICA & Regulatory Compliance

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Transaction/Entities/FicaRecord.php` |
| Domain | `Domain/Transaction/Enums/FicaStatus.php` (outstanding/verified) |
| Domain | `Domain/Transaction/Enums/PepStatus.php` (not_pep/pep/unknown) |
| Domain | `Domain/Transaction/Services/ComplianceRuleEngine.php` — Configurable per jurisdiction |
| Domain | `Domain/Transaction/Contracts/SanctionsScreeningInterface.php` |
| Application | `Application/Transaction/Actions/CreateFicaRecordAction.php` |
| Application | `Application/Transaction/Actions/VerifyFicaDocumentAction.php` |
| Application | `Application/Transaction/Actions/ScreenSanctionsAction.php` |
| Application | `Application/Transaction/Actions/CheckDocumentExpiryAction.php` |
| Infra | `Infrastructure/ExternalServices/Compliance/SanctionsScreeningAdapter.php` |
| Infra | `Infrastructure/Queue/Jobs/CheckFicaExpiryJob.php` — Weekly |

### Regional Rule Sets
- **South Africa:** FICA, EAAB requirements
- **Nigeria:** EFCC, SCUML requirements
- **Kenya:** EAAB equivalent

Configurable per agency via `agency.settings.compliance_region`.

### Audit Trail
Complete timestamped log: who uploaded, who verified, when, IP address. Available for regulatory audit.

### Livewire Components
- `Transaction/FicaChecklist.php` — Per-contact FICA status with upload/verify actions
- `Transaction/ComplianceAuditLog.php` — Searchable audit trail
- `Transaction/SanctionsAlert.php` — Flagged contacts requiring review

---

## 5. Commission Management

### Architecture

| Layer | Classes |
|---|---|
| Domain | `Domain/Transaction/Entities/Commission.php` |
| Domain | `Domain/Transaction/Entities/CommissionSplit.php` |
| Domain | `Domain/Transaction/Services/CommissionCalculationService.php` |
| Application | `Application/Transaction/Actions/CalculateCommissionAction.php` |
| Application | `Application/Transaction/Actions/RecordCommissionPaymentAction.php` |
| Application | `Application/Transaction/Actions/GenerateCommissionStatementAction.php` |

### Calculation Flow
1. Sale price × commission rate = gross commission
2. Apply franchise fee deduction (if applicable)
3. Apply split configuration (listing agent / selling agent / referrer)
4. Generate net payable per recipient

### Split Configuration (per agency)
Stored in `agency.settings.commission_splits`:
```json
{
  "listing_agent_percentage": 50,
  "selling_agent_percentage": 50,
  "referrer_percentage": 10,
  "franchise_fee_percentage": 8
}
```

### Livewire Components
- `Transaction/CommissionCalculator.php` — On transaction page: auto-calculated with override
- `Transaction/CommissionSplitEditor.php` — Configure splits per deal
- `Transaction/CommissionStatementPage.php` — Per-recipient statement view + PDF download
- `Transaction/CommissionLedger.php` — Agency-wide ledger with filters
- `Transaction/OutstandingCommissionsWidget.php` — Dashboard: pending payments

---

## 6. Attorney & Third-Party Coordination

### Architecture

| Layer | Classes |
|---|---|
| Application | `Application/Transaction/Actions/GrantConveyancingAccessAction.php` |
| Application | `Application/Transaction/Actions/ProcessExternalUpdateAction.php` |
| Infra | `Infrastructure/ExternalServices/BondOriginator/BondOriginatorAdapter.php` |

### Conveyancing Portal
- Limited-access portal for attorneys (separate auth, scoped to their transactions)
- Can: upload documents, update milestone status, view checklist, communicate with agent
- Cannot: see other transactions, financial data, or CRM data
- Accessed via unique link + email verification

### Bond Originator Integration
- Track: submission status, bank responses, conditions, approval date
- Auto-update transaction milestones on status changes

### Livewire Components
- `Transaction/ConveyancingPortal/PortalDashboard.php` — Attorney's limited view
- `Transaction/ConveyancingPortal/DocumentUpload.php`
- `Transaction/ConveyancingPortal/MilestoneUpdate.php`
- `Transaction/ExternalPartyInvite.php` — Send access link to attorney/originator

---

## 7. Acceptance Criteria

- [ ] Transaction auto-created from accepted deal with correct workflow template
- [ ] Milestone timeline displays with status indicators and target vs. actual dates
- [ ] Document checklist auto-generated per transaction type
- [ ] Documents uploadable with version control and review workflow
- [ ] E-signature integration sends, tracks, and stores signed documents
- [ ] Deadline calendar shows all critical dates with escalating reminders
- [ ] Deadline extension generates addendum and routes for e-signature
- [ ] FICA checklist tracks per contact with document expiry alerts
- [ ] Compliance audit trail maintains complete, timestamped log
- [ ] Commission auto-calculated with configurable splits and franchise fees
- [ ] Commission statements generate as branded PDF
- [ ] Conveyancing portal provides limited attorney access
- [ ] Bond originator status tracked within transaction record
