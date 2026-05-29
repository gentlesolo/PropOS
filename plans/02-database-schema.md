# Plan 02 — Database Schema & Migrations

> Part of the [Master Plan](file:///C:/Users/ADMIN/Herd/propos/implementation_plan.md)

---

## 1. Migration Strategy

- All migrations prefixed with date for ordering
- Each module gets its own migration batch
- Foreign keys reference `agencies` and `users` tables
- Every tenant-aware table includes `agency_id` with index
- JSON columns for flexible/extensible data (preferences, settings, metadata)
- Soft deletes on core entities (contacts, listings, deals)

---

## 2. Core Tables

### `agencies`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| name | string | |
| slug | string | unique, subdomain |
| custom_domain | string | nullable, unique |
| logo_path | string | nullable |
| primary_color | string(7) | hex, default #1E40AF |
| secondary_color | string(7) | hex |
| accent_color | string(7) | hex |
| tagline | string | nullable |
| address | text | nullable |
| phone | string | nullable |
| email | string | |
| website | string | nullable |
| timezone | string | default UTC |
| currency | string(3) | default NGN |
| country_code | string(2) | default NG |
| subscription_plan | string | free/starter/pro/enterprise |
| subscription_status | string | active/trialing/past_due/cancelled |
| settings | json | nullable, configurable options |
| created_at, updated_at | timestamps | |

### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| agency_id | foreignId | FK → agencies |
| first_name | string | |
| last_name | string | |
| email | string | unique |
| phone | string | nullable |
| password | string | |
| avatar_path | string | nullable |
| job_title | string | nullable |
| bio | text | nullable |
| status | enum | active/suspended/invited/deactivated |
| email_verified_at | timestamp | nullable |
| two_factor_enabled | boolean | default false |
| two_factor_secret | text | nullable, encrypted |
| notification_preferences | json | nullable |
| last_login_at | timestamp | nullable |
| last_active_at | timestamp | nullable |
| remember_token | string | |
| created_at, updated_at | timestamps | |
| **Indexes** | | (agency_id), (email), (agency_id, status) |

### `team_invitations`
`id`, `agency_id`, `email`, `role`, `token` (unique), `invited_by` (FK→users), `accepted_at`, timestamps

---

## 3. CRM Tables

### `contacts`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| agency_id | foreignId | FK |
| assigned_agent_id | foreignId | FK → users, nullable |
| type | enum | buyer/seller/landlord/tenant/investor/referral_partner |
| first_name | string | |
| last_name | string | |
| email | string | nullable |
| phone | string | nullable |
| secondary_phone | string | nullable |
| company | string | nullable |
| job_title | string | nullable |
| source | string | nullable — portal/website/whatsapp/referral/walk_in/social |
| source_detail | string | nullable — specific portal name, referrer |
| intent_score | tinyInteger | 0–100, AI-calculated |
| status | enum | new/active/qualified/nurturing/closed/archived |
| preferences | json | nullable — budget, locations, property types, must-haves |
| tags | json | nullable |
| notes | text | nullable |
| last_contacted_at | timestamp | nullable |
| deleted_at | softDeletes | |
| created_at, updated_at | timestamps | |
| **Indexes** | | (agency_id, type), (agency_id, assigned_agent_id), (agency_id, status), (phone), (email) |

### `contact_activities`
`id`, `agency_id`, `contact_id` (FK), `user_id` (FK, nullable), `type` (enum: call/email/whatsapp/note/viewing/offer/document/system), `title`, `description` (text, nullable), `metadata` (json, nullable), `occurred_at` (timestamp), timestamps

### `contact_relationships`
`id`, `agency_id`, `contact_id` (FK), `related_contact_id` (FK), `relationship_type` (referrer/spouse/business_partner/co_buyer), timestamps

### `pipeline_stages`
`id`, `agency_id`, `pipeline_type` (sale/rental), `name`, `slug`, `position` (int), `color` (string), `checklist_items` (json), `is_default` (bool), timestamps

### `deals`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| agency_id | foreignId | FK |
| contact_id | foreignId | FK → contacts |
| listing_id | foreignId | FK → listings, nullable |
| assigned_agent_id | foreignId | FK → users |
| pipeline_stage_id | foreignId | FK → pipeline_stages |
| pipeline_type | enum | sale/rental |
| title | string | |
| estimated_value | decimal(15,2) | nullable |
| commission_rate | decimal(5,2) | nullable |
| probability | tinyInteger | 0–100 |
| momentum_score | tinyInteger | 0–100, AI-calculated |
| checklist_completed | json | nullable, array of completed items |
| stage_entered_at | timestamp | when deal entered current stage |
| expected_close_date | date | nullable |
| closed_at | timestamp | nullable |
| lost_reason | string | nullable |
| notes | text | nullable |
| deleted_at | softDeletes | |
| created_at, updated_at | timestamps | |

### `lead_sources`
`id`, `agency_id`, `name`, `type` (portal/organic/paid/referral/direct), `cost` (decimal, nullable), `is_active` (bool), timestamps

### `follow_up_sequences`
`id`, `agency_id`, `name`, `trigger` (new_lead/post_viewing/cold_re_engage/lease_renewal), `steps` (json — array of {delay_days, channel, template}), `is_active` (bool), timestamps

### `follow_up_tasks`
`id`, `agency_id`, `contact_id` (FK), `deal_id` (FK, nullable), `sequence_id` (FK, nullable), `user_id` (FK), `channel` (enum: email/whatsapp/sms/call), `scheduled_at` (timestamp), `sent_at` (timestamp, nullable), `content` (text), `status` (pending/sent/paused/cancelled), timestamps

---

## 4. Listing Tables

### `listings`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| agency_id | foreignId | FK |
| agent_id | foreignId | FK → users |
| property_id | foreignId | FK → properties |
| mandate_type | enum | sole/open/rental |
| status | enum | draft/active/under_offer/sold/let/withdrawn/expired |
| listing_price | decimal(15,2) | |
| original_price | decimal(15,2) | nullable |
| commission_rate | decimal(5,2) | |
| mandate_start_date | date | |
| mandate_end_date | date | nullable |
| description_short | text | nullable, AI-generated |
| description_standard | text | nullable |
| description_long | text | nullable |
| headline | string | nullable |
| features_highlighted | json | nullable |
| listing_url | string | nullable, agency website URL |
| days_on_market | integer | computed/cached |
| health_score | tinyInteger | 0–100, AI-calculated |
| portal_ids | json | nullable, synced portal references |
| seller_report_frequency | enum | weekly/biweekly/monthly |
| published_at | timestamp | nullable |
| deleted_at | softDeletes | |
| created_at, updated_at | timestamps | |

### `properties`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| agency_id | foreignId | FK |
| address_line_1 | string | |
| address_line_2 | string | nullable |
| city | string | |
| state_province | string | |
| country | string(2) | |
| postal_code | string | nullable |
| latitude | decimal(10,8) | nullable |
| longitude | decimal(11,8) | nullable |
| property_type | enum | house/apartment/townhouse/penthouse/land/commercial/office/warehouse |
| property_subtype | string | nullable |
| bedrooms | tinyInteger | nullable |
| bathrooms | tinyInteger | nullable |
| parking_spaces | tinyInteger | nullable |
| floor_area_sqm | decimal(10,2) | nullable |
| land_area_sqm | decimal(10,2) | nullable |
| year_built | smallInteger | nullable |
| condition | enum | new/excellent/good/fair/needs_work |
| features | json | nullable — pool, garden, generator, borehole, etc. |
| description | text | nullable, raw notes |
| created_at, updated_at | timestamps | |

### `listing_media`
`id`, `agency_id`, `listing_id` (FK), `type` (photo/video/floor_plan/virtual_tour/document), `file_path`, `file_name`, `mime_type`, `size_bytes`, `position` (int), `quality_score` (tinyInt, nullable), `quality_feedback` (text, nullable), `is_enhanced` (bool, default false), `alt_text` (string, nullable), timestamps

### `listing_portal_syncs`
`id`, `agency_id`, `listing_id` (FK), `portal_id` (FK → portals), `external_listing_id` (string, nullable), `status` (pending/synced/failed/removed), `last_synced_at`, `views` (int), `saves` (int), `inquiries` (int), `error_message` (text, nullable), timestamps

### `portals`
`id`, `name`, `slug`, `api_endpoint`, `country_codes` (json), `field_mappings` (json), `is_active` (bool), timestamps

### `valuations`
`id`, `agency_id`, `listing_id` (FK, nullable), `property_id` (FK), `agent_id` (FK→users), `recommended_price_low` (decimal), `recommended_price_high` (decimal), `optimal_price` (decimal, nullable), `comparables_data` (json), `absorption_rate` (decimal, nullable), `market_context` (text, nullable), `report_pdf_path` (string, nullable), `created_at`, `updated_at`

---

## 5. Marketing Tables

### `campaigns`
`id`, `agency_id`, `created_by` (FK→users), `listing_id` (FK, nullable), `name`, `goal` (enum: inquiries/open_day/awareness/investors/price_reduction), `status` (draft/scheduled/active/paused/completed), `channels` (json), `scheduled_at` (timestamp, nullable), `completed_at` (nullable), `total_reach` (int), `total_clicks` (int), `total_leads` (int), `total_spend` (decimal, nullable), timestamps

### `campaign_contents`
`id`, `campaign_id` (FK), `channel` (enum: instagram/facebook/linkedin/whatsapp/email/sms), `content_type` (text/image/html/video), `headline` (string, nullable), `body` (text), `image_path` (string, nullable), `metadata` (json — hashtags, targeting, etc.), `status` (draft/approved/published), `published_at` (nullable), `engagement_data` (json, nullable), timestamps

### `brand_kits`
`id`, `agency_id` (unique), `logo_path`, `logo_dark_path`, `primary_color`, `secondary_color`, `accent_color`, `font_heading`, `font_body`, `tagline`, `guidelines_notes` (text, nullable), timestamps

### `content_templates`
`id`, `agency_id`, `category` (just_listed/price_reduced/sold/open_house/agent_spotlight/market_update), `name`, `channel`, `content` (text), `image_template_path` (nullable), `is_default` (bool), timestamps

### `email_subscribers`
`id`, `agency_id`, `contact_id` (FK, nullable), `email`, `first_name`, `last_name`, `segments` (json), `status` (subscribed/unsubscribed/bounced), `subscribed_at`, `unsubscribed_at`, timestamps

### `email_campaigns`
`id`, `agency_id`, `campaign_id` (FK, nullable), `subject`, `from_name`, `from_email`, `html_content` (longText), `plain_content` (text), `segment_filters` (json), `status` (draft/scheduled/sending/sent), `scheduled_at`, `sent_at`, `total_recipients`, `total_opens`, `total_clicks`, `total_bounces`, timestamps

### `whatsapp_broadcasts`
`id`, `agency_id`, `name`, `template_name`, `broadcast_list_id` (FK), `message_content` (text), `status` (draft/scheduled/sent), `scheduled_at`, `sent_at`, `total_recipients`, `total_delivered`, `total_read`, `total_clicks`, timestamps

### `whatsapp_broadcast_lists`
`id`, `agency_id`, `name`, `filters` (json — area, property type, stage), `contact_count` (int), timestamps

---

## 6. Transaction Tables

### `transactions`
`id`, `agency_id`, `deal_id` (FK), `listing_id` (FK), `transaction_type` (freehold_sale/sectional_title/commercial_sale/long_term_rental/short_term_rental), `status` (active/completed/cancelled/disputed), `sale_price` (decimal, nullable), `rental_amount` (decimal, nullable), `occupation_date` (date, nullable), `transfer_date` (date, nullable), `stakeholders` (json — buyer, seller, attorneys, bank contacts), timestamps

### `transaction_milestones`
`id`, `transaction_id` (FK), `name`, `position`, `status` (not_started/in_progress/awaiting_external/completed), `target_date` (date, nullable), `completed_date` (date, nullable), `completed_by` (FK→users, nullable), `notes` (text, nullable), timestamps

### `transaction_documents`
`id`, `agency_id`, `transaction_id` (FK), `document_type` (mandate/otp/fica_id/fica_address/proof_of_income/bond_approval/transfer_duty/lease/addendum/other), `name`, `file_path`, `version` (int, default 1), `status` (outstanding/uploaded/pending_review/approved/rejected), `uploaded_by` (FK→users, nullable), `reviewed_by` (FK→users, nullable), `reviewed_at` (nullable), `due_date` (date, nullable), `notes` (text, nullable), timestamps

### `transaction_deadlines`
`id`, `transaction_id` (FK), `name`, `deadline_date` (date), `source` (contract/regulatory/internal), `status` (pending/met/extended/missed), `extended_to` (date, nullable), `reminder_days` (json — [14,7,3,1]), `last_reminded_at` (nullable), timestamps

### `fica_records`
`id`, `agency_id`, `contact_id` (FK), `transaction_id` (FK, nullable), `id_document_status` (outstanding/verified), `proof_of_address_status`, `source_of_funds_status`, `pep_status` (not_pep/pep/unknown), `sanctions_check_status` (pending/clear/flagged), `id_document_expiry` (date, nullable), `address_proof_expiry` (date, nullable), `verified_by` (FK→users, nullable), `verified_at` (nullable), timestamps

### `commissions`
`id`, `agency_id`, `transaction_id` (FK), `deal_id` (FK), `sale_price` (decimal), `gross_commission` (decimal), `commission_rate` (decimal), `franchise_fee` (decimal, default 0), `net_commission` (decimal), `payment_status` (pending/in_progress/paid), `paid_at` (date, nullable), timestamps

### `commission_splits`
`id`, `commission_id` (FK), `user_id` (FK→users), `role` (listing_agent/selling_agent/referrer), `split_percentage` (decimal), `amount` (decimal), `payment_status`, `paid_at`, timestamps

---

## 7. Viewing Tables

### `viewings`
`id`, `agency_id`, `listing_id` (FK), `contact_id` (FK), `agent_id` (FK→users), `type` (private/open_house), `scheduled_at` (datetime), `duration_minutes` (int, default 30), `status` (scheduled/confirmed/completed/no_show/cancelled/rescheduled), `confirmation_sent_at`, `reminder_sent_at`, `checked_in_at` (nullable), `notes` (text, nullable), `google_maps_url` (nullable), timestamps

### `viewing_feedback`
`id`, `viewing_id` (FK), `contact_id` (FK), `overall_rating` (tinyInt 1-5), `liked_most` (text, nullable), `concerns` (text, nullable), `interested_in_offer` (bool, nullable), `wants_second_viewing` (bool, nullable), `sentiment` (positive/neutral/negative), `submitted_at` (timestamp), timestamps

### `open_houses`
`id`, `agency_id`, `listing_id` (FK), `agent_id` (FK→users), `event_date` (date), `start_time` (time), `end_time` (time), `rsvp_page_url` (string, nullable), `qr_code_path` (nullable), `total_rsvps` (int, default 0), `total_checkins` (int, default 0), `status` (upcoming/active/completed/cancelled), timestamps

### `open_house_attendees`
`id`, `open_house_id` (FK), `contact_id` (FK), `rsvp_status` (registered/confirmed/attended/no_show), `checked_in_at` (nullable), timestamps

---

## 8. Intelligence & Training Tables

### `agent_scorecards`
`id`, `agency_id`, `user_id` (FK), `period_type` (daily/weekly/monthly), `period_date` (date), `calls_made`, `emails_sent`, `viewings_conducted`, `new_leads`, `leads_contacted_24h`, `pipeline_value`, `deals_closed`, `commission_earned`, `lead_to_viewing_rate`, `viewing_to_offer_rate`, `offer_to_close_rate`, `avg_days_to_close`, `fica_completion_rate`, `overall_score` (0-100), timestamps

### `market_reports`
`id`, `agency_id`, `area_name`, `report_type` (suburb/city/market_trend), `data` (json — avg prices, DOM, supply/demand), `narrative` (text, AI-generated), `report_date` (date), `generated_at` (timestamp), timestamps

### `training_modules`
`id`, `agency_id` (nullable — null = platform-wide), `category` (onboarding/skills/compliance/market), `title`, `description`, `content_type` (video/guide/quiz/roleplay), `content_url` (nullable), `content_body` (longText, nullable), `duration_minutes`, `difficulty` (beginner/intermediate/advanced), `position` (int), `is_mandatory` (bool), timestamps

### `training_progress`
`id`, `agency_id`, `user_id` (FK), `module_id` (FK→training_modules), `status` (not_started/in_progress/completed), `score` (tinyInt, nullable — quiz score), `started_at`, `completed_at`, timestamps

### `assessments`
`id`, `agency_id`, `title`, `type` (market_quiz/area_knowledge/compliance/certification), `questions` (json), `passing_score` (tinyInt), `is_active` (bool), timestamps

### `assessment_attempts`
`id`, `assessment_id` (FK), `user_id` (FK), `answers` (json), `score` (tinyInt), `passed` (bool), `attempted_at` (timestamp), timestamps

---

## 9. System Tables

### `notifications` (covered in Plan 01)

### `activity_log` (via spatie/laravel-activitylog)

### `ai_usage_logs`
`id`, `agency_id`, `user_id` (FK), `feature` (listing_description/lead_scoring/communication_draft/valuation/market_report), `provider`, `model`, `prompt_tokens`, `completion_tokens`, `total_tokens`, `cost_estimate` (decimal), `duration_ms`, `created_at`

### `audit_trail`
`id`, `agency_id`, `user_id` (FK), `action` (create/update/delete/view/export/sign), `entity_type`, `entity_id`, `old_values` (json, nullable), `new_values` (json, nullable), `ip_address`, `user_agent`, timestamps

### `integration_credentials`
`id`, `agency_id`, `service` (whatsapp/docusign/meta_ads/google/property_pro/property24), `credentials` (json, encrypted), `status` (active/expired/revoked), `last_used_at`, timestamps

---

## 10. Indexing Strategy

### Composite Indexes (high-traffic queries)

```sql
-- Contact search
INDEX idx_contacts_agency_search (agency_id, type, status)
INDEX idx_contacts_agency_agent (agency_id, assigned_agent_id, status)

-- Listing search
INDEX idx_listings_agency_status (agency_id, status, listing_price)
INDEX idx_listings_agency_agent (agency_id, agent_id, status)

-- Deal pipeline
INDEX idx_deals_agency_stage (agency_id, pipeline_stage_id, pipeline_type)
INDEX idx_deals_agency_agent (agency_id, assigned_agent_id)

-- Viewings
INDEX idx_viewings_agency_date (agency_id, scheduled_at, status)
INDEX idx_viewings_agent_date (agency_id, agent_id, scheduled_at)

-- Activity timeline
INDEX idx_activities_contact (agency_id, contact_id, occurred_at DESC)

-- Notifications
INDEX idx_notifications_user_read (user_id, read_at, created_at DESC)
```

### Full-Text Search (via Meilisearch / Scout)

Searchable models: `Contact`, `Listing`, `Property`, `Deal`, `TransactionDocument`

---

## 11. Seeder Strategy

| Seeder | Purpose |
|---|---|
| `RoleAndPermissionSeeder` | Default roles + permissions |
| `PipelineStageSeeder` | Default sale + rental pipeline stages |
| `PortalSeeder` | Seed known property portals |
| `ContentTemplateSeeder` | Default marketing templates |
| `TrainingModuleSeeder` | Platform-wide training content |
| `DemoAgencySeeder` | Full demo agency with sample data (dev only) |
