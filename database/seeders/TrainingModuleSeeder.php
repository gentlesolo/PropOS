<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\TrainingModule;
use Illuminate\Database\Seeder;

class TrainingModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'title' => 'VillaCRM 101: Agent Onboarding',
                'category' => 'onboarding',
                'type' => 'guide',
                'duration' => '15m read',
                'description' => 'A complete overview of navigating the VillaCRM CRM, managing listings, and using the AI Copilot.',
                'thumbnail_color' => 'bg-brand-primary/20',
                'is_mandatory' => true,
                'order' => 1,
                'content_body' => "Welcome to VillaCRM! This guide walks you through the core features:\n\n**1. Dashboard** — Your daily intelligence hub. Review priority actions, deal alerts, and today's viewings.\n\n**2. CRM** — Add contacts, log activities, and track your pipeline. Use the intent score to prioritise outreach.\n\n**3. Listings** — Create mandates, upload photos, generate AI descriptions, and syndicate to portals.\n\n**4. AI Copilot** — Use the chat panel for drafting emails, handling objections, and getting market insights.\n\n**5. Pipeline Board** — Drag deals across stages, track momentum, and log activities directly from deal cards.",
            ],
            [
                'title' => 'Objection Handling Masterclass',
                'category' => 'skills',
                'type' => 'guide',
                'duration' => '45m read',
                'description' => 'Learn the psychology behind common buyer objections and how to confidently pivot conversations.',
                'thumbnail_color' => 'bg-success-500/20',
                'is_mandatory' => false,
                'order' => 2,
                'content_body' => "**The 5 Most Common Objections in Real Estate**\n\n**1. \"The price is too high.\"**\nResponse: Acknowledge, then anchor to value. \"I understand — let me show you what comparable properties have sold for in the last 90 days.\"\n\n**2. \"I need to think about it.\"**\nResponse: Uncover the real concern. \"Absolutely — what specific aspect would you like to think through? I want to make sure I've answered everything.\"\n\n**3. \"I'm working with another agent.\"**\nResponse: Show respect, then differentiate. \"That's great — strong relationships are important. May I ask what you're hoping they can do that you haven't seen yet?\"\n\n**4. \"The market is uncertain.\"**\nResponse: Reframe uncertainty as opportunity. \"That's exactly why active buyers and motivated sellers are in the market — the hesitant ones are waiting, which creates less competition.\"\n\n**5. \"I want to wait until interest rates drop.\"**\nResponse: Show the carrying cost of waiting. \"Let me run some numbers — waiting 6 months at current appreciation rates versus financing today often favours acting now.\"",
            ],
            [
                'title' => 'FICA & AML Compliance 2026',
                'category' => 'compliance',
                'type' => 'quiz',
                'duration' => '20m',
                'description' => 'Mandatory annual compliance refresher on FICA requirements, AML obligations, and fraud detection.',
                'thumbnail_color' => 'bg-danger-500/20',
                'is_mandatory' => true,
                'order' => 3,
                'content_body' => "**FICA (Financial Intelligence Centre Act) Key Requirements**\n\n**Customer Due Diligence (CDD)**\n- Verify identity of all parties in a property transaction\n- Required documents: ID/Passport, Proof of Address (< 3 months old), Source of Funds declaration\n- Enhanced Due Diligence (EDD) required for Politically Exposed Persons (PEPs)\n\n**Record Keeping**\n- All CDD records must be kept for a minimum of 5 years after transaction completion\n- Store securely — physical and digital records must be protected\n\n**Suspicious Transaction Reporting (STR)**\n- Report any suspicious activity to the FIC within 24 hours\n- STRs are confidential — do not 'tip off' the subject\n- Common red flags: all-cash transactions, unusual urgency, reluctance to provide documents\n\n**Penalties for Non-Compliance**\n- Fines up to R10 million or 5 years imprisonment for wilful non-compliance\n- VillaCRM tracks FICA completion per transaction — ensure all required documents are uploaded and approved before closing",
            ],
            [
                'title' => 'Winning the Mandate',
                'category' => 'skills',
                'type' => 'guide',
                'duration' => '10m read',
                'description' => 'A step-by-step playbook for delivering high-impact listing presentations that win sole mandates.',
                'thumbnail_color' => 'bg-warning-500/20',
                'is_mandatory' => false,
                'order' => 4,
                'content_body' => "**The PROVE Framework for Listing Presentations**\n\n**P — Prepare**\nResearch the property, area comps, and the seller's motivation before the appointment. Walk in knowing more about their property than they do.\n\n**R — Rapport**\nSpend the first 5 minutes asking questions about their story. Why are they selling? Where are they moving? What matters most in the process?\n\n**O — Offer (Comparative Market Analysis)**\nPresent your pricing recommendation with data. Show 3 recently sold comparables, 2 active listings (their competition), and your recommended list price with rationale.\n\n**V — Value VillaCRMition**\nDifferentiate your service: VillaCRM's portal syndication reach, AI-generated descriptions, professional photography, 48-hour response time to inquiries.\n\n**E — Execute**\nClose for the sole mandate. Have the document ready. Address the commission question directly and confidently: \"Our fee reflects the marketing investment, the database of qualified buyers we bring, and our track record of selling within X days at Y% of asking price.\"",
            ],
            [
                'title' => 'Market Intelligence: Q3 Dynamics',
                'category' => 'market',
                'type' => 'guide',
                'duration' => '12m read',
                'description' => 'Deep dive into interest rate impacts, pricing pressures, and positioning strategies for Q3.',
                'thumbnail_color' => 'bg-info-500/20',
                'is_mandatory' => false,
                'order' => 5,
                'content_body' => "**Q3 Market Conditions — Key Insights**\n\n**Interest Rate Environment**\nRates have stabilised but remain elevated. Buyers are more cautious — qualify budget before investing time in viewings. Many buyers are waiting for a rate cut; use this to create urgency in qualified leads.\n\n**Pricing Pressure**\nAverage days on market have increased by 18% YoY. Properties priced correctly in the first 30 days sell faster and closer to asking price. Price reductions signal desperation and erode perceived value.\n\n**Segment Performance**\n- Affordable housing (under ₦40M): High demand, limited supply\n- Mid-range (₦40M–₦120M): Increased DOM, negotiate room has widened\n- Luxury (₦120M+): Sticky prices, longer sales cycles, motivated investors\n\n**Winning Strategy**\n1. Price right from day one — show sellers the data\n2. Maximise portal reach — buyers are doing more online research before contacting agents\n3. Focus on yield for investor conversations — present rental projections alongside capital appreciation",
            ],
            [
                'title' => 'VillaCRM AI Features Deep Dive',
                'category' => 'tools',
                'type' => 'guide',
                'duration' => '8m read',
                'description' => 'Mastering the AI Copilot: daily briefs, listing descriptions, lead scoring, and the role-play simulator.',
                'thumbnail_color' => 'bg-purple-500/20',
                'is_mandatory' => false,
                'order' => 6,
                'content_body' => "**Getting the Most from VillaCRM AI**\n\n**Daily Brief**\nEvery morning, your brief is generated from live data: your hottest contacts (by intent score), stale deals needing attention, and today's viewing schedule. Hit 'Regenerate' if you want a fresh snapshot.\n\n**Listing Description Generator**\nOn any listing detail page, click '✨ Generate with AI'. Choose your tone (Professional, Luxury, Friendly, Investment-focused). Review and edit the output — always personalise it with local knowledge.\n\n**Lead Scoring**\nIntent scores (0–100) are calculated from contact completeness, recent activity, deal engagement, and contact type. Focus on leads above 70.\n\n**Copilot Chat**\nUse the floating chat button for:\n- Drafting emails and follow-up messages\n- Getting market data questions answered\n- Brainstorming listing strategies\n\n**Role-Play Simulator**\nPractise difficult client conversations before they happen. Choose a scenario and persona, then chat with an AI client. Get scored on your performance.",
            ],
        ];

        foreach ($modules as $module) {
            TrainingModule::updateOrCreate(
                ['title' => $module['title']],
                array_merge($module, ['agency_id' => null])
            );
        }
    }
}
