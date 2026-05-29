# PropOS — Property Operating System
## AI-Powered Platform Blueprint for Real Estate Agencies

> **Version 1.0 | May 2026**
> A comprehensive product specification and feature blueprint for a unified, AI-powered operating system designed for modern real estate agencies — covering agents, marketers, managers, and clients.

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Platform Overview](#platform-overview)
3. [Module 1: AI Agent Assistant](#module-1-ai-agent-assistant)
4. [Module 2: Listing Intelligence](#module-2-listing-intelligence)
5. [Module 3: Marketing Hub](#module-3-marketing-hub)
6. [Module 4: CRM & Pipeline Manager](#module-4-crm--pipeline-manager)
7. [Module 5: Transaction & Compliance Center](#module-5-transaction--compliance-center)
8. [Module 6: Agency Intelligence Dashboard](#module-6-agency-intelligence-dashboard)
9. [Module 7: Viewings & Scheduling](#module-7-viewings--scheduling)
10. [Module 8: Knowledge & Training Hub](#module-8-knowledge--training-hub)
11. [Cross-Platform AI Capabilities](#cross-platform-ai-capabilities)
12. [Technical Architecture](#technical-architecture)
13. [Roles & Permissions](#roles--permissions)
14. [Integrations & Ecosystem](#integrations--ecosystem)
15. [Implementation Roadmap](#implementation-roadmap)

---

## Executive Summary

Real estate agencies — especially in fast-growing African and emerging markets — operate in a highly fragmented environment. Agents manage leads across WhatsApp, email, and spreadsheets. Marketers run campaigns without visibility into which listings are converting. Principals have little real-time insight into team performance.

**PropOS** is a single, intelligent operating platform that unifies every function of a real estate agency. It combines CRM, listings management, marketing automation, transaction tracking, training, and business intelligence — all powered by AI — into one cohesive system.

### Core Value Propositions

- **For Agents:** Spend less time on admin and more time closing deals. AI handles follow-ups, generates reports, and tells you exactly what to do next.
- **For Marketers:** Create and distribute multi-channel campaigns in minutes, not days, with content that's already optimised for each platform.
- **For Principals & Managers:** Real-time visibility into every deal, agent, and market trend — with forecasting that actually helps you plan.
- **For the Agency:** A consistent, professional client experience from first inquiry to final transfer.

---

## Platform Overview

### Design Philosophy

PropOS is built on three principles:

**1. AI-Native, Not AI-Added**
AI is not a bolt-on feature. Every module is designed around AI capabilities from the ground up — from how leads are captured to how reports are generated.

**2. Mobile & WhatsApp First**
In markets like Nigeria, Ghana, Kenya, and South Africa, most real estate communication happens on mobile and WhatsApp. PropOS is designed for this reality, with a responsive mobile interface and deep WhatsApp integration.

**3. Unified, Not Siloed**
Every module shares data. A lead captured by the marketing team becomes a contact in the CRM. A property listed by an agent flows directly into the marketing hub. A signed mandate triggers the compliance checklist automatically.

### Who Uses PropOS

| Role | Primary Modules |
|---|---|
| Sales Agent | AI Assistant, CRM, Viewings, Transaction Center |
| Rental Agent | AI Assistant, CRM, Viewings, Compliance |
| Marketing Manager | Marketing Hub, Listing Intelligence |
| Agency Principal | Intelligence Dashboard, CRM, Compliance |
| Branch Manager | Intelligence Dashboard, Agent Performance |
| Admin / PA | Scheduling, Compliance, CRM |
| New Agent (Intern) | Training Hub, AI Assistant |

---

## Module 1: AI Agent Assistant

### Overview

The AI Agent Assistant is the personal productivity layer for every agent on the platform. It acts as a smart co-pilot — organising their day, drafting their communications, reminding them of follow-ups, and helping them navigate client conversations more effectively.

### 1.1 Smart Daily Planner

Every morning, each agent receives a personalised AI-generated daily brief that includes:

- **Priority Actions:** A ranked list of the 5–10 most important tasks for the day, based on deal stage, client activity, and deadlines. For example: "Call Adaeze — she viewed 3 listings on the portal last night" or "Follow up with the Adekunle family — their bond approval deadline is in 48 hours."
- **Deal Alerts:** Flags for any deals that have gone quiet or are at risk of falling through.
- **Viewing Schedule Summary:** A map-optimised viewing route for any property visits scheduled that day.
- **Market Snapshot:** A quick 3-sentence AI summary of relevant market activity in the agent's primary suburbs or property categories.

The daily brief is delivered via the app dashboard, email, and optionally WhatsApp every morning at a time set by the agent.

### 1.2 Intelligent Lead Qualification & Scoring

When a new lead enters the system — whether from a portal, the agency website, a WhatsApp message, or a referral — the AI immediately evaluates it across multiple dimensions:

- **Intent Score (0–100):** How ready is this person to transact? Signals include message tone, urgency language, specificity of requirements, time since inquiry, and portal browsing history (where available).
- **Budget Fit:** Cross-references the stated or estimated budget against available listings.
- **Profile Completeness:** Flags leads with missing information (no phone number, vague requirements) and prompts the agent to qualify further.
- **Duplicate Detection:** Identifies if the same person has come in through multiple channels to prevent double-handling.
- **Assignment Recommendation:** Based on availability, area expertise, and past conversion rates, the AI recommends which agent should handle the lead.

Agents receive leads pre-ranked so they always know which inquiries deserve immediate attention.

### 1.3 AI-Powered Communication Drafting

Agents can generate ready-to-send messages in seconds for any scenario:

- **Initial Response:** The agent clicks "Draft Reply" on a new lead and receives a personalised, professional first response referencing the specific property or area the lead inquired about.
- **Follow-Up Sequences:** For leads that go quiet, the AI generates a multi-touch follow-up sequence — typically a 5–7 message cadence over 2–3 weeks — via WhatsApp, email, or SMS.
- **Objection Responses:** The agent types or selects a common objection ("the price is too high," "I'm not ready yet," "I'm looking at another property"), and the AI suggests 2–3 context-appropriate responses.
- **Viewing Confirmations & Reminders:** Auto-drafted confirmation and reminder messages for upcoming viewings, including property details and directions.
- **Offer & Negotiation Summaries:** After an offer is made, the AI drafts a summary message to the seller outlining the offer terms in plain language.

All drafts are editable before sending and can be customised to match the agent's personal tone through a style preference setting.

### 1.4 Call Intelligence

When an agent logs a call (or integrates a VoIP system), the AI provides:

- **Auto-Transcription:** Calls made via the integrated softphone are transcribed in real time.
- **Smart Summary:** A structured summary is generated automatically — covering client requirements, concerns raised, agreed next steps, and any commitments made by the agent.
- **CRM Auto-Update:** Key details from the call (budget, timeline, property preferences, objections) are automatically written to the client's CRM record.
- **Follow-Up Task Creation:** If the agent promised to "send a list of 3-bed properties in Lekki by tomorrow," the AI creates that task automatically and adds it to the daily planner.
- **Sentiment Indicator:** A simple signal (positive / neutral / concerned) based on tone analysis, helping agents triage which clients need extra attention.

### 1.5 AI Chat Interface (Natural Language Commands)

Agents can interact with the platform using natural language through a persistent chat interface. Examples of what agents can ask:

- *"Show me all my leads that haven't been contacted in over 7 days"*
- *"Draft a WhatsApp message to follow up with clients who viewed Sunset Estate last week"*
- *"How many viewings did I do last month compared to my target?"*
- *"Which of my active listings have had the most portal inquiries this week?"*
- *"Remind me to call John Emeka on Friday at 10am"*

The assistant connects to all modules, making it a single interface for agents who prefer to work conversationally rather than navigating menus.

### 1.6 Performance Nudges & Coaching Tips

The AI monitors agent behaviour patterns and provides proactive, constructive nudges:

- *"You have 8 leads you haven't contacted in over 5 days. Leads contacted within 24 hours convert 3x more often."*
- *"Your viewing-to-offer conversion is below your average this month. Would you like tips on handling post-viewing objections?"*
- *"You closed 2 deals last month from referrals. Consider sending a thank-you message to past clients this week."*

These nudges are non-intrusive notifications, not alerts, and can be muted by the agent.

---

## Module 2: Listing Intelligence

### Overview

Listing Intelligence covers the full lifecycle of a property listing — from the moment a mandate is signed to the moment the property is sold or let. It automates the most time-consuming listing tasks while giving agents the data they need to price accurately, market effectively, and respond to sellers with confidence.

### 2.1 Mandate & Listing Intake

The listing workflow begins with a structured digital intake form, available on mobile for agents capturing mandates on-site:

- **Property Details Capture:** Address (with Google Maps autocomplete), property type, size, bedrooms, bathrooms, parking, features, and condition.
- **Mandate Type & Terms:** Sole mandate, open mandate, or rental mandate — with start and end dates, commission rate, and special conditions.
- **Photo & Video Upload:** Agents upload photos directly from their phone. The system automatically tags, sorts, and assesses photo quality.
- **Seller/Landlord Profile Capture:** Contact details, communication preferences, and reporting frequency preference (weekly, bi-weekly).
- **Go-Live Checklist:** The system won't publish a listing until all required fields are complete and a minimum number of quality photos have been uploaded.

### 2.2 AI Listing Description Generator

Once property details are entered, the AI generates a complete, professional listing description:

- **Multiple Variants:** Three description lengths are generated — short (for portals with character limits), standard (for the agency website), and long-form (for email campaigns and brochures).
- **Tone Selection:** Agent chooses from Formal, Warm & Inviting, or Luxury tones depending on the property and target market.
- **Feature Highlighting:** The AI automatically identifies which features are most marketable based on the property type and area (e.g., "generator backup" is a key selling point in Lagos; "mountain views" in Cape Town).
- **Local Market Context:** Descriptions include a brief area overview — nearby amenities, transport links, and neighbourhood character — drawn from the platform's knowledge base.
- **Headline Generation:** 5 headline options are generated for use in ads and portal listings.

All generated content is editable, and agents can request regeneration with different instructions (e.g., "make it more concise" or "focus more on the investment potential").

### 2.3 AI-Powered Pricing & Valuation Engine

Accurate pricing is one of the most valuable services an agent provides. The Valuation Engine supports this with:

- **Comparable Sales Analysis (CMA):** Pulls sold and active listings from integrated data sources within a defined radius and price range, filtered by property type and size.
- **Price Range Recommendation:** Based on comparables, the AI outputs a recommended listing price range with a suggested optimal list price.
- **Market Absorption Rate:** Shows how many similar properties are available and how fast they're selling in the area, giving context for pricing aggressively vs. conservatively.
- **Price-to-Days-on-Market Modelling:** Historical data showing the relationship between price point and time to sale for similar properties.
- **Seller Report Generator:** A professionally formatted PDF valuation report, branded to the agency, that the agent can present to the seller. It includes the CMA summary, recommended price, and market context narrative — all generated automatically.

### 2.4 Photo & Media Management

- **Quality Assessment:** Each uploaded photo is scored for brightness, sharpness, and composition. Low-scoring images are flagged with specific improvement suggestions (e.g., "this room photo is underexposed — retake near midday or use a flash").
- **Auto-Enhancement:** Basic AI enhancement — brightness correction, colour balance, lens distortion correction — applied automatically to improve photo quality without manual editing.
- **Virtual Staging:** Empty rooms can be virtually furnished. The agent selects a style (Modern, Traditional, Minimalist) and receives a staged version within minutes, suitable for listings and marketing materials.
- **Photo Ordering:** AI suggests the optimal photo ordering for portal listings — typically leading with the best exterior shot, followed by the main living area and kitchen.
- **Floor Plan Generation:** For properties where a floor plan is available (as an upload or scan), the system cleans and formats it for listing use. AI-estimated floor plans can also be generated from room-by-room measurements entered during intake.
- **Video Walkthrough Templates:** Branded video slideshow automatically assembled from uploaded photos with a music track and property details overlay, ready for WhatsApp and social media.

### 2.5 Portal Syndication

- **One-Click Publishing:** Completed listings are formatted and published to connected property portals (PropertyPro, Private Property, Property24, Lamudi, etc.) simultaneously.
- **Format Adaptation:** Each portal has different character limits, photo requirements, and field mappings. PropOS handles all formatting differences automatically.
- **Listing Performance Tracking:** Views, saves, inquiries, and click-through rates from each portal are pulled into the platform and displayed per listing.
- **Auto-Price Reduction Alerts:** If a listing has been active for more than a set number of days (configurable) with low inquiry volume, the agent is alerted and prompted to review the pricing.
- **Listing Refresh Scheduling:** Portals often rank recently updated listings higher. PropOS can schedule periodic listing refreshes (minor content updates) to maintain search visibility.

### 2.6 Seller Communication & Reporting

Sellers want to know what's happening with their property. PropOS makes this effortless:

- **Automated Seller Reports:** Weekly or bi-weekly branded reports sent directly to the seller, summarising portal views, inquiries received, viewings conducted, and market context. Fully generated and sent automatically.
- **Feedback Relay:** Post-viewing feedback collected from buyers is automatically summarised and included in the seller report or sent as a standalone update.
- **Mandate Renewal Alerts:** The system flags mandates expiring within 30 days and prompts the agent to engage the seller about renewal.

---

## Module 3: Marketing Hub

### Overview

The Marketing Hub is the centralised workspace for marketing teams (and individual agents in smaller agencies). It connects listing data with content creation tools, campaign management, and performance analytics — all in one place.

### 3.1 Campaign Builder

The Campaign Builder allows a marketer (or agent) to launch a full multi-channel campaign from a single listing in minutes:

**Step 1 — Select a Listing**
Choose from any active listing. All property details, photos, and descriptions are pulled in automatically.

**Step 2 — Choose Campaign Goal**
Options include: Maximise Inquiries, Promote Open Day, Build Brand Awareness, Target Investors, or Announce a Price Reduction.

**Step 3 — Select Channels**
Choose from: Instagram, Facebook, LinkedIn, WhatsApp Broadcast, Email Newsletter, SMS, or Portal Spotlight.

**Step 4 — AI Generates All Content**
The AI produces channel-specific content for each selected platform:

- **Instagram:** Square and story-format images with overlaid text and captions with relevant hashtags.
- **Facebook:** Ad copy variants (short, medium, carousel) with targeting suggestions.
- **LinkedIn:** Professional tone post for commercial or investment property.
- **WhatsApp Broadcast:** Short-form message with property highlights and a viewing booking link.
- **Email:** Fully designed HTML email with header image, property details, feature bullets, and a prominent call-to-action button.
- **SMS:** 160-character message with a short link to the listing.

**Step 5 — Review, Edit & Schedule**
All content is presented in a preview interface. Marketers can edit any element, swap images, or request AI regeneration. Each piece can be scheduled for a specific date and time per channel.

### 3.2 Brand Identity System

The Marketing Hub enforces brand consistency across all generated content:

- **Brand Kit:** The agency uploads its logo, colour palette (primary, secondary, accent), approved fonts, and tagline. All AI-generated content is automatically styled to match.
- **Template Library:** Pre-built, brand-approved templates for common formats — Just Listed, Price Reduced, Sold, Open House, Agent Spotlight, Market Update. New templates can be created by the marketing team and made available to all agents.
- **Brand Compliance Checker:** Any content created by agents (not just marketers) is checked against brand guidelines before it can be shared externally through the platform.

### 3.3 Social Media Content Calendar

- **Auto-Fill Calendar:** The system automatically populates a monthly content calendar based on active listings, upcoming open days, recent solds, and seasonal content themes. Marketers review, edit, and approve posts before they go live.
- **Content Mix Recommendations:** The AI suggests an optimal content mix per week — for example: 2 listing posts, 1 market insight, 1 team/culture post, 1 testimonial — based on what performs best for the agency's audience.
- **Evergreen Content Rotation:** A library of evergreen posts (homebuying tips, neighbourhood guides, mortgage advice) that can be scheduled to fill gaps in the calendar automatically.
- **Hashtag Intelligence:** For each post, the AI recommends a set of hashtags based on the property type, location, and target audience — mixing high-volume and niche tags for optimal reach.

### 3.4 Paid Advertising Management

- **Meta Ads Integration:** Create and manage Facebook and Instagram ad campaigns directly from PropOS. The AI suggests campaign objectives, audience targeting parameters, budget allocation, and bidding strategy based on the listing type.
- **Audience Builder:** Define and save custom audiences (e.g., "Lagos professionals aged 28–45 interested in homeownership") that can be applied to any future campaign.
- **A/B Testing:** Automatically run two variations of an ad with different headlines or images and let the system identify the better performer after a set period.
- **Budget Pacing Alerts:** Notifies the marketer if a campaign is over- or under-pacing against its daily budget target.
- **Cross-Platform Performance Dashboard:** A unified view of spend, impressions, clicks, leads, and cost-per-lead across Meta, Google, and portal spotlights — without needing to log into each platform separately.

### 3.5 Email Marketing

- **Subscriber Management:** Segment the agency's email database by buyer type (first-time buyer, investor, upgrader), area of interest, and pipeline stage.
- **Automated Drip Campaigns:** Pre-built sequences for common scenarios — New Listing Alert, Monthly Market Report, Relocation Welcome Series, Post-Purchase Check-In.
- **Dynamic Content Blocks:** Email templates that automatically pull in relevant listings based on the recipient's saved preferences in the CRM.
- **Open & Click Tracking:** Per-campaign and per-recipient engagement data flows back into the CRM, updating lead scores and triggering agent notifications when a prospect opens a listing email.

### 3.6 WhatsApp Marketing

Given the dominance of WhatsApp in African markets, this module receives dedicated attention:

- **Broadcast List Manager:** Create and manage segmented broadcast lists by area, property type, buyer stage, and more.
- **Template Message Library:** A curated library of WhatsApp message templates for common scenarios — approved for WhatsApp Business API compliance.
- **Campaign Scheduler:** Schedule broadcast sends for optimal times (research shows mid-morning and early evening perform best in most markets).
- **Link Tracking:** Short links in WhatsApp messages track click-through rates and which specific listings or offers drive the most engagement.
- **Opt-In / Opt-Out Management:** Compliant management of WhatsApp marketing consent, including automated opt-out processing.

### 3.7 Marketing Performance Analytics

- **Channel Comparison:** Side-by-side view of which marketing channels are generating the most leads, viewings, and closed deals.
- **Cost Per Lead by Channel:** Understand which channels deliver the best return on marketing spend.
- **Listing Marketing Report:** Per-listing breakdown of total marketing exposure (portal views + social reach + email opens + WhatsApp clicks) compared to inquiry volume.
- **Content Performance Leaderboard:** Which post types, property categories, and content formats are generating the most engagement — updated in real time.
- **Monthly Marketing Report:** Auto-generated PDF report summarising all marketing activity for the month, designed for agency principal review or client presentations.

---

## Module 4: CRM & Pipeline Manager

### Overview

The CRM is the memory of the agency. Every client, every interaction, every deal lives here. PropOS goes beyond a traditional CRM by making it intelligent — predicting what will happen next, surfacing what needs attention, and connecting every piece of data across the platform.

### 4.1 Contact Management

Every person in the agency's database — buyer, seller, landlord, tenant, investor, or referral partner — has a unified contact record:

- **Unified Timeline:** Every touchpoint is logged chronologically — calls, emails, WhatsApp messages, viewings attended, offers made, documents signed, and notes added by the agent. Nothing is lost.
- **Contact Intelligence Panel:** At a glance, see the contact's property preferences, budget, current pipeline stage, last interaction date, lead source, assigned agent, and AI-calculated intent score.
- **Relationship Map:** Visualise connections — who referred this contact, who are they connected to in the database, and which other contacts are connected to the same deal.
- **Duplicate Management:** The system flags potential duplicate contacts (same phone number, similar name) and provides a one-click merge tool.
- **Contact Enrichment:** Where available, the AI supplements contact records with publicly available information — LinkedIn profile, company, and role — to help agents personalise their outreach.

### 4.2 Pipeline Management

Visual kanban-style pipeline boards for both sales and rentals:

**Standard Sales Pipeline Stages:**
Inquiry → Qualified → Viewing Scheduled → Viewing Done → Offer Made → Under Negotiation → Offer Accepted → Compliance & Documents → Transfer in Progress → Closed

**Standard Rental Pipeline Stages:**
Inquiry → Qualified → Application Received → Credit Check → Approved → Lease Signed → Keys Handed Over → Active Tenant

- **Drag-and-Drop Stage Management:** Agents move deals through stages by dragging cards on the board.
- **Stage-Specific Checklists:** Each pipeline stage has a configurable checklist of required actions (e.g., "Viewing Done" requires: feedback logged, follow-up message sent, next step scheduled).
- **Deal Value Display:** Each pipeline card shows the estimated deal value, commission amount, and probability-weighted value.
- **Stale Deal Detection:** Deals that haven't moved stages or had activity within a configurable number of days are automatically highlighted in amber or red, and the agent receives a notification.

### 4.3 AI-Powered Buyer-Listing Matching

- **Preference Profiling:** As agents interact with buyers, their requirements are captured in a structured preference profile: location, property type, size, budget, must-have features, and deal-breakers.
- **Auto-Match Alerts:** When a new listing enters the system that matches a buyer's profile, that buyer receives an automatic alert (via email or WhatsApp), and the agent is notified to follow up personally.
- **Match Score:** Each buyer-listing pair is given a match score (0–100%) based on how well the listing meets the buyer's stated and inferred preferences.
- **Reverse Search:** Agents can open any listing and instantly see which registered buyers in the database would be a good match, ranked by score.

### 4.4 Lead Source Attribution

- **Multi-Touch Attribution:** Tracks the full journey of a lead from first touchpoint to closed deal. If a buyer first saw the agency on Instagram, then inquired on a portal, then was re-engaged by an email campaign, all three touchpoints are recorded.
- **ROI by Source:** Calculates the average deal value and commission generated from each lead source — helping the agency understand which channels are most valuable.
- **Referral Tracking:** Identifies which past clients or partners are generating the most referrals, enabling targeted referral nurturing campaigns.

### 4.5 Automated Follow-Up Engine

- **Follow-Up Sequences:** Pre-built, AI-customisable sequences for every key scenario — new buyer inquiry, post-viewing follow-up, post-offer follow-up, cold lead re-engagement, lease renewal reminder.
- **Smart Timing:** The AI recommends the best time to send each message based on the contact's past engagement patterns (e.g., this contact typically opens messages on Tuesday mornings).
- **Pause & Override:** Sequences automatically pause when the contact responds, preventing robotic follow-ups after a real conversation has started. Agents can manually pause or override at any time.
- **Channel Preference Learning:** If a contact consistently reads WhatsApp messages but never opens emails, the system notes this and adjusts future outreach to favour WhatsApp.

### 4.6 Deal Momentum & Risk Scoring

For every active deal, the AI continuously calculates a momentum score based on:

- Time since last client interaction
- Number of days in current pipeline stage vs. average
- Document completion status
- Scheduled next steps
- External risk factors (e.g., bond approval deadlines approaching)

Deals falling below a momentum threshold trigger an alert to the agent and, if the agent doesn't act within 24 hours, to their manager.

---

## Module 5: Transaction & Compliance Center

### Overview

Real estate transactions involve significant documentation, regulatory requirements, and time-sensitive deadlines. The Transaction Center ensures nothing slips through the cracks, maintains compliance, and gives every stakeholder visibility into where a deal stands.

### 5.1 Transaction Workflow Management

Each confirmed deal (offer accepted or lease signed) generates a structured transaction record with:

- **Transaction Type Detection:** The system identifies whether the transaction is a freehold sale, sectional title sale, commercial sale, long-term rental, or short-term rental — and loads the appropriate workflow template.
- **Milestone Tracker:** A visual timeline showing all key milestones from offer acceptance to final transfer or lease commencement, with actual vs. target dates displayed.
- **Status Indicators:** Each milestone is marked as Not Started, In Progress, Awaiting External Party, or Complete.
- **Stakeholder Panel:** All parties involved in the transaction — buyer, seller, transferring attorney, bond attorney, bank, and broker — are listed with their contact details and last communication date.

### 5.2 Document Management

- **Document Checklist Automation:** Based on transaction type, jurisdiction, and deal specifics, the system generates a complete checklist of required documents — mandate, OTP (Offer to Purchase), FICA documents, proof of income, bond approval, transfer duty receipt, and so on.
- **Document Upload & Storage:** All documents are uploaded, stored, and organised within the transaction record. Version control ensures the most recent version of any document is always clearly marked.
- **Document Status Tracking:** Each required document has a status: Outstanding, Uploaded (Pending Review), Approved, or Rejected. Automated reminders are sent to the responsible party for any outstanding documents.
- **E-Signature Integration:** Mandates, OTPs, lease agreements, and other documents requiring signatures can be sent for electronic signature directly from the platform. Signed documents are automatically stored in the transaction record.
- **Template Library:** Standard document templates (mandates, OTPs, lease agreements, addenda) are available in the platform, pre-formatted to agency and regional standards. Agents populate key fields; the rest is pre-filled.

### 5.3 Deadline & Critical Date Manager

- **Automated Deadline Calendar:** All contractual deadlines — suspensive conditions, bond approval dates, occupation dates, transfer dates — are extracted from uploaded documents and added to a shared transaction calendar.
- **Escalating Reminders:** As deadlines approach, reminders escalate in frequency. A bond approval deadline might trigger reminders at 14 days, 7 days, 3 days, and 1 day before the due date.
- **Deadline Extension Workflow:** When a deadline needs to be extended, the system generates the appropriate addendum, routes it for e-signature, and updates the milestone tracker automatically.
- **Deal-at-Risk Alerts:** If a suspensive condition is not met by its deadline, the deal is automatically flagged as at risk and both the agent and principal are notified immediately.

### 5.4 FICA & Regulatory Compliance

- **FICA Checklist:** For every buyer, seller, and lessor, the system maintains a FICA (Financial Intelligence Centre Act) compliance checklist — verifying identity documents, proof of address, source of funds, and PEP (Politically Exposed Person) status.
- **Document Expiry Tracking:** Certain FICA documents (e.g., proof of address) expire after a period. The system tracks expiry dates and requests updated documents proactively.
- **Compliance Audit Trail:** A complete, timestamped log of all compliance actions is maintained — who uploaded what, when it was verified, and by whom. This is available for regulatory audit at any time.
- **Regional Rule Sets:** The platform supports configurable compliance rule sets for different jurisdictions — South Africa (FICA, Estate Agency Affairs Board), Nigeria (EFCC, SCUML), Kenya (EAAB equivalent), and others — allowing multi-country agencies to operate within a single platform.
- **Sanctions Screening:** Integration with sanctions and PEP screening databases to automatically flag high-risk counterparties.

### 5.5 Commission Management

- **Commission Calculator:** Based on the sale price and the mandate's commission rate, the system calculates gross commission automatically.
- **Split Configuration:** Agencies configure their commission split structures (e.g., principal agent / introducing agent / referral partner). The system applies these splits automatically to each transaction.
- **Franchise Fee Deduction:** For franchise agencies, franchise fees and levies are deducted automatically from the calculated commission.
- **Commission Statement Generator:** Once a deal closes, a detailed commission statement is generated for each recipient — showing the sale price, gross commission, deductions, and net payable amount.
- **Payment Status Tracking:** Commission payments are marked as Pending, In Progress, or Paid, with payment date recorded.

### 5.6 Attorney & Third-Party Coordination

- **Conveyancing Portal:** A secure, limited-access portal for transferring attorneys to upload documents, update milestones, and communicate with the agent — without needing full platform access.
- **Bond Originator Integration:** Where a bond originator is involved, their submission status, bank approvals, and conditions are tracked within the transaction record.
- **Automated Status Updates:** Key status changes (e.g., bond approved, transfer lodged, registration confirmed) trigger automatic notifications to all relevant parties — agent, buyer, seller, and manager.

---

## Module 6: Agency Intelligence Dashboard

### Overview

The Intelligence Dashboard is the command centre for agency principals, branch managers, and senior leadership. It transforms raw operational data into actionable business intelligence — surfacing insights that help leaders make better decisions about strategy, staffing, marketing spend, and market positioning.

### 6.1 Real-Time Operations Overview

The dashboard's landing view presents a live snapshot of the agency's current state:

- **Active Listings:** Total count, broken down by type (residential sale, residential rental, commercial), with average days on market.
- **Pipeline Summary:** Total deals in each pipeline stage, with combined estimated deal value and commission.
- **Active Agents:** How many agents are logged in and active today, with a quick view of their current workload.
- **Today's Activity Feed:** A real-time stream of significant events across the platform — new mandates signed, offers received, deals closed, high-score leads received.
- **This Month vs. Target:** A prominent progress indicator showing the agency's sales volume, rental placements, and gross commission income for the current month vs. monthly target.

### 6.2 Agent Performance Scorecards

Individual and team performance is tracked across a comprehensive set of metrics:

- **Activity Metrics:** Calls made, emails sent, viewings conducted, open days held, new leads contacted within 24 hours.
- **Pipeline Metrics:** New leads added, deals in pipeline, pipeline value, average deal size.
- **Conversion Metrics:** Lead-to-viewing conversion rate, viewing-to-offer conversion rate, offer-to-close conversion rate.
- **Revenue Metrics:** Commission earned (month, quarter, year), average days to close, best-performing property type.
- **Compliance Metrics:** FICA completion rate, mandate renewal rate, document submission timeliness.
- **Benchmarking:** Each agent's metrics are benchmarked against team averages and top performers, giving managers a clear view of where coaching is needed.

Scorecards are visible to the agent themselves, their manager, and the principal. Agents can only see their own scorecard, not colleagues'.

### 6.3 Revenue Forecasting

- **Pipeline-Based Forecast:** The AI calculates expected commission income for the next 30, 60, and 90 days based on deals currently in the pipeline, their stage, and historical conversion rates at each stage.
- **Confidence Scoring:** Each forecasted deal is given a confidence score (High / Medium / Low), giving the principal a realistic view of what is virtually certain vs. what is speculative.
- **Target Gap Analysis:** If the current pipeline is insufficient to meet the monthly target, the dashboard highlights the gap and suggests the number of new listings or leads needed to close it.
- **Seasonality Adjustment:** The forecast model accounts for seasonal market patterns in the agency's region — for example, factoring in the typical end-of-year slowdown or the post-rainy-season buying surge in certain West African markets.

### 6.4 Market Intelligence Reports

- **Suburb / Area Reports:** AI-generated market reports for any suburb or area the agency operates in — showing average listing prices, average sold prices, days on market trends, supply/demand ratio, and price per square metre.
- **Competitive Positioning:** Where data is available from public portals, the system shows how the agency's active listings compare to competitor listings in the same area (price positioning, photo quality, time on market).
- **Market Trend Summaries:** Weekly AI-generated market narrative — a 3–5 paragraph summary of what's happening in the markets the agency serves, suitable for sharing with clients or posting as social content.
- **Macro Indicator Tracking:** Key macroeconomic indicators relevant to real estate — interest rates, inflation, mortgage approval rates, new development pipeline — are displayed on the dashboard with AI interpretation of their likely impact on the agency's market.

### 6.5 Listing Performance Analysis

- **Listing Health Dashboard:** Every active listing is scored on a health index (0–100) based on photo quality, description completeness, pricing vs. market, days on market, and inquiry volume.
- **Underperforming Listing Alerts:** Listings below a health threshold are flagged, with specific AI recommendations — "This listing has been active for 42 days with only 2 inquiries. Consider a price reduction of 5% or refreshing the photos."
- **Portfolio Mix Analysis:** A breakdown of the agency's listing portfolio by type, price band, area, and mandate type — helping principals identify over- or under-representation in certain segments.

### 6.6 Financial Overview

- **Gross Commission Income (GCI) Tracker:** Monthly, quarterly, and annual GCI vs. targets, with year-over-year comparison.
- **Commission Ledger:** A complete record of all commission earned, split, and disbursed — filterable by agent, property type, area, and time period.
- **Marketing Spend vs. ROI:** Total marketing expenditure (ads, portal subscriptions, promotions) compared to leads generated, deals closed, and commission earned — showing the overall return on marketing investment.
- **Outstanding Commission Report:** Deals that have closed but where commission payment is still pending, with days outstanding flagged.

---

## Module 7: Viewings & Scheduling

### Overview

Viewings are where real estate deals are made or lost. This module ensures they're organised efficiently, attended reliably, and followed up on systematically — with as little administrative burden on the agent as possible.

### 7.1 Self-Service Booking Portal

Every listing has a dedicated viewing booking page that can be embedded on the agency website and shared directly via WhatsApp or social media. Buyers can:

- Select from available time slots without needing to call or message the agent.
- Provide brief details about themselves and their requirements.
- Receive an instant confirmation with property details, address, directions (Google Maps link), and the agent's contact information.
- Add the viewing directly to their Google Calendar or Apple Calendar.

The availability shown on the booking page is synced with the agent's calendar in real time, preventing double-bookings.

### 7.2 Viewing Route Optimiser

When an agent has multiple viewings in a day:

- **Map View:** All viewings are displayed on an interactive map so the agent can visualise their day geographically.
- **Route Optimisation:** The AI suggests the most efficient viewing order based on property locations, appointment times, and estimated travel time between them.
- **Buffer Time Management:** A configurable buffer between viewings accounts for travel time and prevents the agent from being late.
- **Rescheduling Suggestions:** If a new viewing request comes in for a day already scheduled with viewings, the system suggests where to slot it in with minimal disruption to the existing route.

### 7.3 Automated Reminders

A multi-stage reminder sequence runs automatically for every confirmed viewing:

- **Confirmation:** Instant confirmation message when the booking is made (email + WhatsApp).
- **48-Hour Reminder:** Reminder with property details and a "Still coming?" confirmation prompt.
- **Morning-of Reminder:** Sent on the day of the viewing with time, address, and agent contact.
- **1-Hour Reminder:** Final nudge sent 1 hour before the scheduled time.

Reminders are branded and personalised. If a buyer responds to any message to cancel or reschedule, the system flags this immediately to the agent.

### 7.4 No-Show & Late Tracking

- **No-Show Logging:** If a viewing starts without the buyer checking in or the agent confirming attendance, the system prompts the agent to log the outcome.
- **No-Show Follow-Up:** A "Sorry we missed you" message is automatically sent to the buyer, offering a simple rescheduling link.
- **Repeat No-Show Flagging:** Buyers who no-show more than once are flagged in the CRM, prompting the agent to qualify their seriousness before scheduling future viewings.

### 7.5 Open House Management

- **Open House Listing:** Create a dedicated open house event for any listing, with a public RSVP page.
- **RSVP Management:** Track RSVPs, send automatic reminder sequences to all registered attendees, and see a real-time attendee list on the day.
- **Digital Check-In:** Buyers check in at the open house by scanning a QR code or entering their phone number. Their details are captured and a CRM record created automatically.
- **Post-Open House Campaign:** Immediately after the event, the system automatically sends a "Thank you for attending" message to all check-ins, along with the listing link and a call-to-action to schedule a private viewing.
- **Open House Report:** A summary report for the seller — total attendance, profiles of attendees, feedback collected, and next steps.

### 7.6 Post-Viewing Feedback System

After every viewing, buyers receive an automated short feedback survey:

- How did you feel about the property overall? (1–5 stars)
- What did you like most?
- What were your main concerns?
- Are you interested in making an offer?
- Would you like to schedule a second viewing?

Responses are:

- **Summarised for the Agent:** A clear summary with the buyer's sentiment and expressed interest level.
- **Included in Seller Reports:** Aggregated feedback is included in the seller's regular update report.
- **Used to Update the CRM:** Interest level and concerns are written back to the buyer's contact record, updating their pipeline stage automatically where appropriate.
- **Fed into the Valuation Engine:** Recurring themes in buyer feedback (e.g., "price feels too high," "pool not big enough") are aggregated to inform pricing and marketing recommendations.

---

## Module 8: Knowledge & Training Hub

### Overview

Real estate is a skill-intensive profession, and the gap between a new agent and a top performer is largely a knowledge gap. The Training Hub uses AI to close that gap faster — providing personalised, on-demand learning that's directly connected to what the agent is experiencing in their day-to-day work.

### 8.1 AI Onboarding Programme

New agents are enrolled in a structured 90-day onboarding programme automatically upon joining the platform:

**Month 1 — Foundation**
- Platform navigation and core workflows
- The agency's processes, templates, and brand standards
- Introduction to the local property market (area profiles, price benchmarks, key terminology)
- Basic compliance and FICA requirements
- Supervised role-plays with AI client personas

**Month 2 — Building Momentum**
- Advanced CRM usage and lead management
- Pricing and valuation fundamentals
- Listing presentation skills (with AI feedback)
- Effective viewing techniques and buyer qualification

**Month 3 — Accelerating Performance**
- Negotiation strategies and closing techniques
- Building a referral network
- Social media and personal brand building
- Introduction to investment property analysis

Progress through the programme is tracked. Managers can see each new agent's completion rate, quiz scores, and which modules they're spending the most time on.

### 8.2 Skills Library

A self-service library of learning content accessible to all agents at any time:

- **Video Lessons:** Short (5–15 minute) video lessons on specific skills and scenarios.
- **Guides & Playbooks:** Downloadable reference guides — objection handling scripts, area market snapshots, legal requirement summaries, negotiation frameworks.
- **Worked Examples:** Annotated real deal case studies (anonymised) showing how challenging situations were handled — a difficult negotiation, a complex commercial transaction, a deal that nearly fell through.
- **Market Knowledge Base:** Regularly updated articles on the local property market, neighbourhood guides, developer profiles, and macro market commentary.

Content is tagged by role, experience level, and topic so agents can find what they need quickly.

### 8.3 AI Objection Handler

The most immediately practical training tool in the platform. An agent simply types in an objection they've heard from a client and receives:

- **2–3 Suggested Responses:** Crafted for the specific objection, property type, and client profile (buyer vs. seller, first-time buyer vs. investor).
- **Explanation:** A brief note on the psychology behind the objection and why the suggested response addresses the underlying concern.
- **Practice Prompt:** An offer to continue the conversation in role-play mode to practise the response.

Common objection categories covered include: price objections, timing objections, competitor objections (using another agent), market uncertainty objections, and product-specific objections (this property needs too much work).

### 8.4 AI Role-Play Simulator

Agents can practise any sales scenario with an AI client persona:

- **Scenario Selection:** Choose from pre-built scenarios — Qualification Call, Listing Presentation, Viewing Walkthrough, Offer Negotiation, Objection Handling — or describe a specific situation to practise.
- **Persona Customisation:** Select a client profile — first-time buyer, seasoned investor, reluctant seller, aggressive negotiator — to practise with different personality types.
- **Real-Time Coaching:** After each agent response, the AI can optionally provide immediate feedback on what worked well and what could be improved.
- **Session Summary:** At the end of a role-play session, a summary is generated highlighting the agent's strengths and specific areas for development, with links to relevant Skills Library content.

### 8.5 Market Knowledge Assessments

- **Weekly Market Quiz:** A short 5-question quiz on current market conditions, area prices, or recent regulatory changes, delivered every Monday. Top scorers are featured on a leaderboard.
- **Area Knowledge Tests:** Structured assessments testing agents on price benchmarks, property types, key developments, and amenities in each area they operate in.
- **Compliance Refreshers:** Periodic assessments on FICA requirements, estate agency legislation, and ethical practice standards — with mandatory remediation for agents who don't meet the pass threshold.
- **Certification Tracking:** Where the agency runs internal certification programmes (e.g., "Lekki Area Specialist," "Commercial Property Basics"), the Training Hub tracks certifications, expiry dates, and renewal requirements.

### 8.6 Manager Coaching Tools

- **Learning Progress Dashboard:** Managers see a complete view of their team's training activity — modules completed, quiz scores, role-play sessions, and time spent learning.
- **Skill Gap Identification:** The AI cross-references each agent's performance metrics with their training activity to identify likely skill gaps. An agent with a low viewing-to-offer conversion but no completions in the Negotiation module would be flagged.
- **Coaching Recommendation Engine:** For each agent, the AI recommends 1–2 specific training actions that are most likely to improve their performance based on their current metrics and learning history.
- **Group Session Scheduler:** Managers can schedule group training sessions (virtual or in-person) and assign pre-reading or preparation activities from the Skills Library.

---

## Cross-Platform AI Capabilities

Beyond the module-specific AI features described above, several AI capabilities operate across the entire platform.

### Natural Language Search & Commands

A universal search bar accessible from any screen allows users to query the entire platform in plain English:

- *"Show me all listings in Ikoyi priced between ₦200M and ₦400M that have been on the market for more than 30 days"*
- *"Which agents have not had any new leads this week?"*
- *"Find all buyers looking for 4-bedroom properties with a pool"*
- *"What was our total GCI last quarter?"*

Results are displayed intelligently — listings in a gallery, contacts in a list, metrics as charts.

### Predictive Analytics Engine

The AI continuously learns from the agency's own data to make predictions that get more accurate over time:

| Prediction | How It's Used |
|---|---|
| Lead-to-close probability | Prioritise agent time and resources |
| Time to sale for any listing | Set realistic seller expectations |
| Optimal listing price | Maximise speed of sale |
| Best time to contact a specific lead | Improve response rates |
| Agent churn risk | Proactive retention by management |
| Which listings will receive the most interest | Guide marketing spend allocation |

### AI-Generated Reports

Any data in the platform can be turned into a formatted, professional report with a single click:

- Seller Update Reports
- Agent Performance Reports
- Monthly Market Reports
- Transaction Status Reports
- Marketing Performance Reports

Reports are generated in the agency's brand style and can be delivered as PDF, email, or in-platform.

### Sentiment & Intent Monitoring

The AI monitors all written communications passing through the platform (emails, notes, feedback) to surface signals:

- **Buyer Excitement Indicators:** Language suggesting strong interest or readiness to make an offer triggers an alert to the agent.
- **Frustration Flags:** Language suggesting a dissatisfied seller or an agent under pressure is flagged to the manager for proactive intervention.
- **Competitor Mentions:** If a client mentions they're also talking to another agency, this is flagged to help the agent focus retention efforts.

---

## Technical Architecture

### Platform Delivery

PropOS is delivered as:

- **Web Application:** Responsive, browser-based interface accessible on any device.
- **Mobile App (iOS & Android):** Full-featured native mobile app optimised for field use by agents — including offline mode for areas with poor connectivity.
- **WhatsApp Bot Interface:** Core CRM and assistant functions accessible via a WhatsApp bot for agents who prefer to work within WhatsApp.

### AI Infrastructure

- **Large Language Model (LLM) Layer:** Powers all natural language generation, summarisation, objection handling, and chat interfaces.
- **Recommendation Engine:** Collaborative filtering and content-based models for lead assignment, buyer-listing matching, and content recommendations.
- **Predictive Models:** Time-series and regression models for price prediction, time-to-sale forecasting, and revenue forecasting.
- **Computer Vision:** Photo quality assessment and virtual staging capabilities.

### Data & Security

- **Data Residency:** Options for data to be stored within the client's preferred region (West Africa, Southern Africa, East Africa, or international).
- **Encryption:** All data encrypted at rest and in transit.
- **Role-Based Access Control:** Granular permissions ensuring agents only see their own data, managers see their team, and principals see everything.
- **Audit Logging:** Complete, tamper-proof audit trail of all data access and changes — critical for compliance.
- **GDPR / POPIA / NDPR Compliance:** Built-in tools for managing data subject requests, consent, and retention in line with applicable data protection regulations.

---

## Roles & Permissions

| Permission | Agent | Senior Agent | Branch Manager | Marketing Manager | Principal |
|---|---|---|---|---|---|
| View own leads & pipeline | ✅ | ✅ | ✅ | — | ✅ |
| View team leads & pipeline | — | — | ✅ | — | ✅ |
| Create & edit listings | ✅ | ✅ | ✅ | — | ✅ |
| Publish listings to portals | ✅ | ✅ | ✅ | — | ✅ |
| Create & publish marketing campaigns | — | — | — | ✅ | ✅ |
| View own performance metrics | ✅ | ✅ | ✅ | ✅ | ✅ |
| View team performance metrics | — | — | ✅ | — | ✅ |
| View financial / commission data | — | Own only | Team | — | ✅ |
| Manage compliance documents | ✅ | ✅ | ✅ | — | ✅ |
| Access Intelligence Dashboard | — | — | ✅ | Partial | ✅ |
| Manage users & roles | — | — | — | — | ✅ |
| Configure agency settings | — | — | — | — | ✅ |

---

## Integrations & Ecosystem

### Property Portals
- PropertyPro.ng
- Private Property (South Africa)
- Property24 (South Africa & rest of Africa)
- Lamudi (Nigeria, Kenya, Ghana)
- BuyRentKenya
- Custom API for additional portals

### Communication
- WhatsApp Business API
- Gmail & Outlook (email sync)
- Twilio (SMS)
- Zoom / Google Meet (virtual viewing links)

### Document & E-Signature
- DocuSign
- HelloSign / Dropbox Sign
- Adobe Acrobat Sign

### Financial & Compliance
- SARS eFiling (South Africa)
- Local sanctions screening databases
- Major bank bond originator portals

### Marketing & Advertising
- Meta Business Suite (Facebook & Instagram Ads)
- Google Ads
- Mailchimp / ActiveCampaign (email migration)

### Productivity & Calendar
- Google Workspace (Calendar, Drive)
- Microsoft 365 (Outlook, OneDrive)
- Zapier / Make (for custom automations)

### Analytics & Reporting
- Google Analytics (website tracking)
- Looker Studio (advanced custom reporting)

---

## Implementation Roadmap

### Phase 1 — Foundation (Months 1–4)
Core CRM, listing management, portal syndication, basic AI lead scoring, WhatsApp integration, and e-signature.

### Phase 2 — Intelligence (Months 5–8)
AI agent assistant (daily planner, communication drafting), marketing hub, campaign builder, and viewings module.

### Phase 3 — Analytics (Months 9–12)
Agency intelligence dashboard, revenue forecasting, market reports, advanced performance analytics, and compliance center.

### Phase 4 — Learning & Expansion (Months 13–18)
Full training hub, AI role-play simulator, multi-country compliance rule sets, and advanced predictive models.

---

*PropOS — Built for the real estate agencies that move markets.*

*Document prepared May 2026. All module specifications subject to refinement through product discovery and user research.*