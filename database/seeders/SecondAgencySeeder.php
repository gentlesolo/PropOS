<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecondAgencySeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Agency ────────────────────────────────────────────────────────
        $agency = Agency::updateOrCreate(
            ['slug' => 'apex-realty'],
            [
                'name'                => 'Apex Realty Group',
                'email'               => 'info@apexrealty.co.za',
                'phone'               => '+27 21 555 0100',
                'website'             => 'https://apexrealty.co.za',
                'address'             => '14 Buitenkant Street, Gardens, Cape Town, 8001',
                'timezone'            => 'Africa/Johannesburg',
                'currency'            => 'ZAR',
                'country_code'        => 'ZA',
                'subscription_plan'   => 'enterprise',
                'subscription_status' => 'active',
                'tagline'             => 'Where vision meets value in Southern Africa.',
                // ── Branding — Violet × Orange, Poppins, rounded, brand sidebar
                'primary_color'   => '#7C3AED',   // Violet-600 — Apex brand primary
                'secondary_color' => '#18181B',   // Zinc-900   — neutral depth (aligned to design system)
                'accent_color'    => '#F97316',   // Orange-500 — Apex brand accent
                'font_family'     => 'Poppins',
                'border_radius'   => 'rounded',
                'sidebar_style'   => 'brand',
                'custom_css'      => '.selection\\:text-brand-primary { color: #fff; }',
                'default_commission_rate' => 6.00,
                'commission_splits' => [
                    'agent'     => 55.0,
                    'principal' => 35.0,
                    'referral'  => 10.0,
                ],
            ]
        );

        // ── 2. Users ─────────────────────────────────────────────────────────
        setPermissionsTeamId($agency->id);

        $principal = User::firstOrCreate(
            ['email' => 'principal@apex.villacrm.app'],
            [
                'agency_id'          => $agency->id,
                'first_name'         => 'Thandi',
                'last_name'          => 'Mokoena',
                'password'           => Hash::make('password'),
                'job_title'          => 'Managing Director',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );
        $principal->assignRole('principal');

        $agent1 = User::firstOrCreate(
            ['email' => 'agent1@apex.villacrm.app'],
            [
                'agency_id'          => $agency->id,
                'first_name'         => 'Ruan',
                'last_name'          => 'van der Berg',
                'password'           => Hash::make('password'),
                'job_title'          => 'Senior Sales Agent',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );
        $agent1->assignRole('agent');

        $agent2 = User::firstOrCreate(
            ['email' => 'agent2@apex.villacrm.app'],
            [
                'agency_id'          => $agency->id,
                'first_name'         => 'Lerato',
                'last_name'          => 'Dlamini',
                'password'           => Hash::make('password'),
                'job_title'          => 'Rental Specialist',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );
        $agent2->assignRole('agent');

        // ── 3. Contacts ──────────────────────────────────────────────────────
        $contactData = [
            ['first_name' => 'James',      'last_name' => 'Forsyth',    'type' => 'buyer',    'status' => 'qualified',  'city' => 'Cape Town',    'intent' => 88],
            ['first_name' => 'Nadia',      'last_name' => 'Adams',      'type' => 'seller',   'status' => 'active',     'city' => 'Cape Town',    'intent' => 72],
            ['first_name' => 'Sipho',      'last_name' => 'Nkosi',      'type' => 'investor', 'status' => 'active',     'city' => 'Johannesburg', 'intent' => 91],
            ['first_name' => 'Michelle',   'last_name' => 'du Plessis', 'type' => 'buyer',    'status' => 'nurturing',  'city' => 'Stellenbosch', 'intent' => 55],
            ['first_name' => 'Andile',     'last_name' => 'Cele',       'type' => 'tenant',   'status' => 'active',     'city' => 'Cape Town',    'intent' => 60],
            ['first_name' => 'Carine',     'last_name' => 'Joubert',    'type' => 'landlord', 'status' => 'active',     'city' => 'Durban',       'intent' => 78],
            ['first_name' => 'Michael',    'last_name' => 'Okafor',     'type' => 'buyer',    'status' => 'qualified',  'city' => 'Johannesburg', 'intent' => 82],
            ['first_name' => 'Priya',      'last_name' => 'Pillay',     'type' => 'seller',   'status' => 'active',     'city' => 'Cape Town',    'intent' => 69],
            ['first_name' => 'Stefan',     'last_name' => 'Botha',      'type' => 'investor', 'status' => 'qualified',  'city' => 'Pretoria',     'intent' => 94],
            ['first_name' => 'Ayanda',     'last_name' => 'Zulu',       'type' => 'buyer',    'status' => 'new',        'city' => 'Cape Town',    'intent' => 38],
            ['first_name' => 'Geraldine',  'last_name' => 'Marx',       'type' => 'tenant',   'status' => 'nurturing',  'city' => 'Cape Town',    'intent' => 47],
            ['first_name' => 'Brendan',    'last_name' => 'Patel',      'type' => 'buyer',    'status' => 'active',     'city' => 'Johannesburg', 'intent' => 76],
        ];

        $agents = [$principal, $agent1, $agent2];
        $contacts = collect();

        foreach ($contactData as $i => $cd) {
            $contact = \App\Infrastructure\Persistence\Models\Contact::firstOrCreate(
                ['email' => strtolower($cd['first_name'] . '.' . str_replace(' ', '', $cd['last_name'])) . '@example.co.za'],
                [
                    'agency_id'         => $agency->id,
                    'assigned_agent_id' => $agents[$i % 3]->id,
                    'type'              => $cd['type'],
                    'first_name'        => $cd['first_name'],
                    'last_name'         => $cd['last_name'],
                    'phone'             => '+27 8' . rand(1, 9) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'notes'             => 'Based in ' . $cd['city'] . '.',
                    'intent_score'      => $cd['intent'],
                    'status'            => $cd['status'],
                ]
            );
            $contacts->push($contact);
        }

        // ── 4. Properties & Listings ──────────────────────────────────────────
        $propertyData = [
            ['addr' => '12 Kloof Street',          'suburb' => 'Gardens',          'city' => 'Cape Town',    'type' => 'apartment',   'bed' => 2, 'bath' => 1, 'price' => 2_800_000,  'status' => 'active',      'mandate' => 'sole'],
            ['addr' => '7 Signal Hill Road',        'suburb' => 'Sea Point',        'city' => 'Cape Town',    'type' => 'apartment',   'bed' => 3, 'bath' => 2, 'price' => 4_500_000,  'status' => 'active',      'mandate' => 'sole'],
            ['addr' => '34 Vineyard Crescent',      'suburb' => 'Constantia',       'city' => 'Cape Town',    'type' => 'house',       'bed' => 5, 'bath' => 4, 'price' => 14_200_000, 'status' => 'active',      'mandate' => 'sole'],
            ['addr' => '19 Blouberg Strand Ave',    'suburb' => 'Bloubergstrand',   'city' => 'Cape Town',    'type' => 'house',       'bed' => 4, 'bath' => 3, 'price' => 7_900_000,  'status' => 'under_offer', 'mandate' => 'open'],
            ['addr' => '88 Sandton Drive',          'suburb' => 'Sandton',          'city' => 'Johannesburg', 'type' => 'apartment',   'bed' => 2, 'bath' => 2, 'price' => 3_100_000,  'status' => 'active',      'mandate' => 'sole'],
            ['addr' => '5 Melrose Boulevard',       'suburb' => 'Melrose',          'city' => 'Johannesburg', 'type' => 'commercial',  'bed' => 0, 'bath' => 2, 'price' => 22_500_000, 'status' => 'active',      'mandate' => 'sole'],
            ['addr' => '101 Umhlanga Rocks Drive',  'suburb' => 'Umhlanga',         'city' => 'Durban',       'type' => 'house',       'bed' => 4, 'bath' => 3, 'price' => 6_300_000,  'status' => 'active',      'mandate' => 'open'],
            ['addr' => '3 Lanzerac Estate',         'suburb' => 'Stellenbosch',     'city' => 'Stellenbosch', 'type' => 'house',       'bed' => 6, 'bath' => 5, 'price' => 18_700_000, 'status' => 'draft',       'mandate' => 'sole'],
            ['addr' => '22 De Waal Drive',          'suburb' => 'Tamboerskloof',    'city' => 'Cape Town',    'type' => 'apartment',   'bed' => 1, 'bath' => 1, 'price' => 1_950_000,  'status' => 'active',      'mandate' => 'rental'],
            ['addr' => '9 Parktown Road',           'suburb' => 'Parktown',         'city' => 'Johannesburg', 'type' => 'house',       'bed' => 3, 'bath' => 2, 'price' => 4_800_000,  'status' => 'active',      'mandate' => 'rental'],
        ];

        $listings = collect();
        foreach ($propertyData as $j => $pd) {
            $prop = \App\Infrastructure\Persistence\Models\Property::firstOrCreate(
                ['address_line_1' => $pd['addr']],
                [
                    'agency_id'      => $agency->id,
                    'address_line_2' => $pd['suburb'],
                    'city'           => $pd['city'],
                    'state_province' => match ($pd['city']) {
                        'Cape Town', 'Stellenbosch' => 'Western Cape',
                        'Johannesburg', 'Pretoria'  => 'Gauteng',
                        'Durban'                    => 'KwaZulu-Natal',
                        default                     => 'Western Cape',
                    },
                    'country'        => 'ZA',
                    'property_type'  => $pd['type'],
                    'bedrooms'       => $pd['bed'],
                    'bathrooms'      => $pd['bath'],
                ]
            );

            $listing = \App\Infrastructure\Persistence\Models\Listing::firstOrCreate(
                ['property_id' => $prop->id],
                [
                    'agency_id'           => $agency->id,
                    'agent_id'            => $agents[$j % 3]->id,
                    'mandate_type'        => $pd['mandate'],
                    'status'              => $pd['status'],
                    'listing_price'       => $pd['price'],
                    'mandate_start_date'  => now()->subDays(rand(5, 60)),
                ]
            );
            $listings->push($listing);
        }

        // ── 5. Pipeline Stages ────────────────────────────────────────────────
        $stagesDefs = [
            ['name' => 'New Inquiry',          'order' => 1],
            ['name' => 'Needs Analysis',       'order' => 2],
            ['name' => 'Property Matched',     'order' => 3],
            ['name' => 'Viewing Arranged',     'order' => 4],
            ['name' => 'Offer Submitted',      'order' => 5],
            ['name' => 'Negotiation',          'order' => 6],
            ['name' => 'Sale Concluded',       'order' => 7, 'is_won'  => true],
            ['name' => 'Deal Lost',            'order' => 8, 'is_lost' => true],
        ];

        $stages = [];
        foreach ($stagesDefs as $sd) {
            $stages[] = \App\Infrastructure\Persistence\Models\PipelineStage::firstOrCreate(
                ['name' => $sd['name'], 'agency_id' => $agency->id],
                array_merge($sd, ['agency_id' => $agency->id, 'pipeline_type' => 'sale'])
            );
        }

        // ── 6. Deals ──────────────────────────────────────────────────────────
        $dealDefs = [
            ['title' => 'Kloof Street Apt — Forsyth', 'ci' => 0, 'li' => 0, 'ai' => 1, 'si' => 2, 'type' => 'sale'],
            ['title' => 'Constantia Villa — Nkosi',   'ci' => 2, 'li' => 2, 'ai' => 0, 'si' => 4, 'type' => 'sale'],
            ['title' => 'Sandton Apt — du Plessis',   'ci' => 3, 'li' => 4, 'ai' => 2, 'si' => 1, 'type' => 'sale'],
            ['title' => 'Blouberg House — Okafor',    'ci' => 6, 'li' => 3, 'ai' => 1, 'si' => 6, 'type' => 'sale'],  // won
            ['title' => 'Sea Point Apt — Botha',      'ci' => 8, 'li' => 1, 'ai' => 0, 'si' => 5, 'type' => 'sale'],
            ['title' => 'De Waal Rental — Cele',      'ci' => 4, 'li' => 8, 'ai' => 2, 'si' => 2, 'type' => 'rental'],
        ];

        $deals = collect();
        foreach ($dealDefs as $dd) {
            $contact = $contacts->get($dd['ci']);
            $listing = $listings->get($dd['li']);
            $stage   = $stages[$dd['si']];
            if (! $contact || ! $listing) continue;

            $deal = \App\Infrastructure\Persistence\Models\Deal::firstOrCreate(
                ['title' => $dd['title'], 'agency_id' => $agency->id],
                [
                    'agency_id'           => $agency->id,
                    'pipeline_stage_id'   => $stage->id,
                    'contact_id'          => $contact->id,
                    'listing_id'          => $listing->id,
                    'assigned_agent_id'   => $agents[$dd['ai']]->id,
                    'type'                => $dd['type'],
                    'value'               => $listing->listing_price,
                    'momentum_score'      => rand(40, 97),
                ]
            );
            $deals->push($deal);
        }

        // ── 7. Viewings ───────────────────────────────────────────────────────
        $viewingSlots = [
            ['time' => '08:30', 'status' => 'completed', 'li' => 0, 'ci' => 0],
            ['time' => '11:00', 'status' => 'confirmed', 'li' => 2, 'ci' => 2],
            ['time' => '14:30', 'status' => 'scheduled', 'li' => 4, 'ci' => 6],
            ['time' => '16:00', 'status' => 'scheduled', 'li' => 1, 'ci' => 8],
        ];

        foreach ($viewingSlots as $vs) {
            $listing = $listings->get($vs['li']);
            $contact = $contacts->get($vs['ci']);
            if (! $listing || ! $contact) continue;

            \App\Infrastructure\Persistence\Models\Viewing::firstOrCreate(
                [
                    'agency_id'         => $agency->id,
                    'assigned_agent_id' => $agent1->id,
                    'scheduled_at'      => now()->format('Y-m-d') . ' ' . $vs['time'] . ':00',
                ],
                [
                    'listing_id'       => $listing->id,
                    'contact_id'       => $contact->id,
                    'status'           => $vs['status'],
                    'duration_minutes' => 45,
                    'notes'            => 'Apex Realty — viewing arranged by seeder.',
                ]
            );
        }

        // ── 8. Commission Split Config ─────────────────────────────────────────
        \App\Infrastructure\Persistence\Models\CommissionSplitConfig::firstOrCreate(
            ['agency_id' => $agency->id, 'applies_to' => 'agency_default'],
            [
                'name'             => 'Apex Default Split',
                'commission_rate'  => 6.00,
                'agent_split'      => 55.00,
                'agency_split'     => 45.00,
                'is_active'        => true,
            ]
        );

        \App\Infrastructure\Persistence\Models\CommissionSplitConfig::firstOrCreate(
            ['agency_id' => $agency->id, 'applies_to' => 'role', 'role' => 'principal'],
            [
                'name'             => 'MD Override',
                'commission_rate'  => 6.00,
                'agent_split'      => 75.00,
                'agency_split'     => 25.00,
                'is_active'        => true,
            ]
        );

        // ── 9. Transactions & Commissions for Won Deals ───────────────────────
        $wonDeals = $deals->filter(function ($deal) use ($stages) {
            $wonStage = collect($stages)->firstWhere('is_won', true);
            return $wonStage && $deal->pipeline_stage_id === $wonStage->id;
        });

        foreach ($wonDeals as $wIdx => $wDeal) {
            $transaction = \App\Infrastructure\Persistence\Models\Transaction::firstOrCreate(
                ['deal_id' => $wDeal->id],
                [
                    'agency_id'          => $agency->id,
                    'reference'          => 'TX-APX-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'listing_id'         => $wDeal->listing_id,
                    'contact_id'         => $wDeal->contact_id,
                    'assigned_agent_id'  => $wDeal->assigned_agent_id,
                    'sale_price'         => $wDeal->value,
                    'commission_rate'    => 6.00,
                    'agent_split'        => 55.00,
                    'status'             => 'completed',
                    'closed_at'          => now()->subDays(rand(3, 14))->toDateString(),
                ]
            );

            $gross = $wDeal->value * 0.06;
            \App\Infrastructure\Persistence\Models\Commission::firstOrCreate(
                ['deal_id' => $wDeal->id],
                [
                    'agency_id'               => $agency->id,
                    'transaction_id'          => $transaction->id,
                    'agent_id'                => $wDeal->assigned_agent_id,
                    'sale_price'              => $wDeal->value,
                    'commission_rate'         => 6.00,
                    'gross_commission'        => $gross,
                    'agent_split_percentage'  => 55.00,
                    'agent_commission'        => $gross * 0.55,
                    'agency_commission'       => $gross * 0.45,
                    'payment_status'          => 'paid',
                    'expected_payment_date'   => now()->addDays(7)->toDateString(),
                    'paid_at'                 => now()->subDays(2)->toDateString(),
                ]
            );
        }

        // ── 10. Tenant & Lease (rental listings) ─────────────────────────────
        $rentalListings = $listings->filter(fn($l) => $l->mandate_type === 'rental');
        $tenantContact  = $contacts->firstWhere('type', 'tenant') ?? $contacts->first();

        if ($rentalListings->count() > 0 && $tenantContact) {
            $tenant = \App\Infrastructure\Persistence\Models\Tenant::firstOrCreate(
                ['contact_id' => $tenantContact->id],
                [
                    'agency_id'      => $agency->id,
                    'status'         => 'active',
                    'employer'       => 'Cape Peninsula University of Technology',
                    'monthly_income' => 55000.00,
                ]
            );

            $rentalListing = $rentalListings->first();
            $lease = \App\Infrastructure\Persistence\Models\Lease::firstOrCreate(
                ['tenant_id' => $tenant->id, 'listing_id' => $rentalListing->id],
                [
                    'agency_id'         => $agency->id,
                    'contact_id'        => $tenantContact->id,
                    'assigned_agent_id' => $agent2->id,
                    'reference'         => 'LSE-APX-' . strtoupper(bin2hex(random_bytes(3))),
                    'status'            => 'active',
                    'start_date'        => now()->subMonths(2)->toDateString(),
                    'end_date'          => now()->addMonths(10)->toDateString(),
                    'monthly_rent'      => 16500.00,
                    'deposit_amount'    => 33000.00,
                    'payment_day'       => '1',
                ]
            );

            $invoice = \App\Infrastructure\Persistence\Models\Invoice::firstOrCreate(
                ['lease_id' => $lease->id, 'period_month' => now()->month, 'period_year' => now()->year],
                [
                    'agency_id'   => $agency->id,
                    'tenant_id'   => $tenant->id,
                    'reference'   => 'INV-APX-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'        => 'rent',
                    'status'      => 'paid',
                    'subtotal'    => 16500.00,
                    'tax_amount'  => 0.00,
                    'total'       => 16500.00,
                    'amount_paid' => 16500.00,
                    'due_date'    => now()->startOfMonth()->toDateString(),
                    'paid_at'     => now()->startOfMonth()->addDays(1)->toDateTimeString(),
                ]
            );

            \App\Infrastructure\Persistence\Models\RentPayment::firstOrCreate(
                ['lease_id' => $lease->id, 'due_date' => now()->startOfMonth()->toDateString()],
                [
                    'agency_id'      => $agency->id,
                    'tenant_id'      => $tenant->id,
                    'reference'      => 'PMT-APX-' . strtoupper(bin2hex(random_bytes(3))),
                    'amount_due'     => 16500.00,
                    'amount_paid'    => 16500.00,
                    'penalty'        => 0.00,
                    'status'         => 'paid',
                    'paid_date'      => now()->startOfMonth()->addDays(1)->toDateString(),
                    'payment_method' => 'eft',
                ]
            );
        }

        // ── 11. Open House ────────────────────────────────────────────────────
        $activeListings = $listings->where('status', 'active')->take(2);
        foreach ($activeListings as $idx => $activeListing) {
            $openHouse = \App\Infrastructure\Persistence\Models\OpenHouse::firstOrCreate(
                ['listing_id' => $activeListing->id],
                [
                    'agency_id'  => $agency->id,
                    'agent_id'   => $agents[$idx % 3]->id,
                    'starts_at'  => now()->addDays($idx + 3)->setTime(9, 30, 0),
                    'ends_at'    => now()->addDays($idx + 3)->setTime(13, 0, 0),
                    'status'     => 'scheduled',
                    'notes'      => 'Champagne breakfast viewing — invite-only.',
                ]
            );

            $rsvpPool = $contacts->shuffle()->take(4);
            foreach ($rsvpPool as $rc) {
                \App\Infrastructure\Persistence\Models\OpenHouseRsvp::firstOrCreate(
                    ['open_house_id' => $openHouse->id, 'contact_id' => $rc->id],
                    [
                        'guest_name'    => $rc->first_name . ' ' . $rc->last_name,
                        'guest_email'   => $rc->email,
                        'guest_phone'   => $rc->phone,
                        'checked_in'    => false,
                        'reminder_sent' => true,
                    ]
                );
            }
        }

        // ── 12. Contracts ─────────────────────────────────────────────────────
        if ($deals->count() >= 3) {
            \App\Infrastructure\Persistence\Models\Contract::firstOrCreate(
                ['deal_id' => $deals->get(0)->id, 'status' => 'sent'],
                [
                    'agency_id'    => $agency->id,
                    'created_by'   => $principal->id,
                    'title'        => 'Offer to Purchase — ' . $deals->get(0)->title,
                    'type'         => 'sale_agreement',
                    'reference'    => 'CON-APX-' . strtoupper(bin2hex(random_bytes(4))),
                    'body'         => "OFFER TO PURCHASE\n\nSeller: Owner\nBuyer: " . $contacts->get(0)?->first_name . "\nPurchase Price: ZAR " . number_format((float) $deals->get(0)->value, 2) . "\n\nSubject to bond approval within 30 days.",
                    'valid_from'   => now()->toDateString(),
                    'valid_until'  => now()->addDays(30)->toDateString(),
                    'signatories'  => ['sent_at' => now()->toDateTimeString()],
                ]
            );

            \App\Infrastructure\Persistence\Models\Contract::firstOrCreate(
                ['deal_id' => $deals->get(1)->id, 'status' => 'fully_signed'],
                [
                    'agency_id'    => $agency->id,
                    'created_by'   => $principal->id,
                    'title'        => 'Sole Mandate — ' . $deals->get(1)->title,
                    'type'         => 'mandate',
                    'reference'    => 'CON-APX-' . strtoupper(bin2hex(random_bytes(4))),
                    'body'         => "SOLE MANDATE AGREEMENT\n\nAgency: Apex Realty Group\nSeller: " . $contacts->get(2)?->first_name . "\n\nExclusive mandate for 90 days.",
                    'valid_from'   => now()->subDays(5)->toDateString(),
                    'valid_until'  => now()->addDays(85)->toDateString(),
                    'signatories'  => ['signed_at' => now()->subDays(4)->toDateTimeString()],
                    'signed_at'    => [['name' => $contacts->get(2)?->first_name . ' ' . $contacts->get(2)?->last_name, 'signed_at' => now()->subDays(4)->toDateTimeString()]],
                ]
            );
        }
    }
}
