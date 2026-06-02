<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAgencySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create default demo agency
        $agency = Agency::firstOrCreate(
            ['slug' => 'demo'],
            [
                'id'                  => 1,
                'name'                => 'Demo Agency',
                'email'               => 'demo@propos.app',
                'timezone'            => 'Africa/Lagos',
                'currency'            => 'NGN',
                'country_code'        => 'NG',
                'subscription_plan'   => 'pro',
                'subscription_status' => 'active',
            ]
        );

        // Always sync branding so re-running the seeder reflects current defaults
        $agency->update([
            'primary_color'   => '#10B981',
            'secondary_color' => '#18181B',
            'accent_color'    => '#F59E0B',
            'font_family'     => null,
            'border_radius'   => 'default',
            'sidebar_style'   => 'dark',
            'tagline'         => 'Redefining real estate in West Africa.',
        ]);

        // 2. Create Principal user
        $principalUser = User::firstOrCreate(
            ['email' => 'principal@propos.app'],
            [
                'agency_id' => $agency->id,
                'first_name' => 'Demo',
                'last_name' => 'Principal',
                'password' => Hash::make('password'),
                'job_title' => 'Principal Partner',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Assign principal role (Spatie Permission teams requires setPermissionsTeamId)
        setPermissionsTeamId($agency->id);
        $principalUser->assignRole('principal');

        // 3. Create Agent user
        $agentUser = User::firstOrCreate(
            ['email' => 'agent@propos.app'],
            [
                'agency_id' => $agency->id,
                'first_name' => 'Demo',
                'last_name' => 'Agent',
                'password' => Hash::make('password'),
                'job_title' => 'Sales Agent',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $agentUser->assignRole('agent');

        // 4. Create Demo Contacts
        $contactTypes = ['buyer', 'seller', 'landlord', 'tenant', 'investor'];
        $statuses = ['new', 'active', 'qualified', 'nurturing', 'closed'];
        
        for ($i = 1; $i <= 15; $i++) {
            $isHot = $i % 3 == 0;
            \App\Infrastructure\Persistence\Models\Contact::firstOrCreate(
                ['email' => "contact{$i}@example.com"],
                [
                    'agency_id' => $agency->id,
                    'assigned_agent_id' => $i % 2 == 0 ? $principalUser->id : $agentUser->id,
                    'type' => $contactTypes[array_rand($contactTypes)],
                    'first_name' => 'Test',
                    'last_name' => "Contact {$i}",
                    'phone' => '+23480000000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'intent_score' => $isHot ? rand(80, 99) : rand(10, 70),
                    'status' => $statuses[array_rand($statuses)],
                ]
            );
        }

        // 5. Create Demo Properties and Listings
        $propertyTypes = ['house', 'apartment', 'commercial', 'land'];
        $listingStatuses = ['active', 'active', 'active', 'under_offer', 'draft'];
        $locations = ['Ikoyi', 'Victoria Island', 'Lekki Phase 1', 'Ikeja GRA', 'Abuja Central'];

        for ($j = 1; $j <= 10; $j++) {
            $location = $locations[array_rand($locations)];
            $prop = \App\Infrastructure\Persistence\Models\Property::firstOrCreate(
                ['address_line_1' => "{$j}0{$j} Premium Way, {$location}"],
                [
                    'agency_id' => $agency->id,
                    'city' => str_contains($location, 'Abuja') ? 'Abuja' : 'Lagos',
                    'state_province' => str_contains($location, 'Abuja') ? 'FCT' : 'Lagos',
                    'country' => 'NG',
                    'property_type' => $propertyTypes[array_rand($propertyTypes)],
                    'bedrooms' => rand(2, 5),
                    'bathrooms' => rand(2, 5),
                ]
            );

            \App\Infrastructure\Persistence\Models\Listing::firstOrCreate(
                ['property_id' => $prop->id],
                [
                    'agency_id' => $agency->id,
                    'agent_id' => $j % 2 == 0 ? $principalUser->id : $agentUser->id,
                    'mandate_type' => 'open',
                    'status' => $listingStatuses[array_rand($listingStatuses)],
                    'listing_price' => rand(500, 5000) * 100000, // 50M to 500M
                    'mandate_start_date' => now()->subDays(rand(1, 30)),
                ]
            );
        }

        // 6. Create Pipeline Stages
        $stages = [
            ['name' => 'Inquiry', 'order' => 1],
            ['name' => 'Qualified', 'order' => 2],
            ['name' => 'Viewing Scheduled', 'order' => 3],
            ['name' => 'Offer Made', 'order' => 4],
            ['name' => 'Under Negotiation', 'order' => 5],
            ['name' => 'Offer Accepted', 'order' => 6, 'is_won' => true],
            ['name' => 'Lost', 'order' => 7, 'is_lost' => true],
        ];

        $createdStages = [];
        foreach ($stages as $stageData) {
            $createdStages[] = \App\Infrastructure\Persistence\Models\PipelineStage::firstOrCreate(
                ['name' => $stageData['name'], 'agency_id' => $agency->id],
                array_merge($stageData, ['pipeline_type' => 'sale'])
            );
        }

        // 7. Create Demo Deals
        $contacts = \App\Infrastructure\Persistence\Models\Contact::where('agency_id', $agency->id)->get();
        $listings = \App\Infrastructure\Persistence\Models\Listing::where('agency_id', $agency->id)->get();

        if ($contacts->count() > 0 && $listings->count() > 0) {
            for ($k = 1; $k <= 8; $k++) {
                $contact = $contacts->random();
                $listing = $listings->random();
                $stage = $createdStages[array_rand($createdStages)];

                \App\Infrastructure\Persistence\Models\Deal::firstOrCreate(
                    ['title' => "Sale of {$listing->property->address_line_1}"],
                    [
                        'agency_id' => $agency->id,
                        'pipeline_stage_id' => $stage->id,
                        'contact_id' => $contact->id,
                        'listing_id' => $listing->id,
                        'assigned_agent_id' => $k % 2 == 0 ? $principalUser->id : $agentUser->id,
                        'type' => 'sale',
                        'value' => $listing->listing_price,
                        'momentum_score' => rand(30, 95),
                    ]
                );
            }
        }

        // 8. Create Demo Viewings for Today
        if ($contacts->count() > 0 && $listings->count() > 0) {
            $times = ['09:00', '11:30', '14:00', '16:15'];
            foreach ($times as $index => $time) {
                \App\Infrastructure\Persistence\Models\Viewing::firstOrCreate(
                    [
                        'agency_id' => $agency->id,
                        'assigned_agent_id' => $principalUser->id,
                        'scheduled_at' => now()->format('Y-m-d') . ' ' . $time . ':00'
                    ],
                    [
                        'listing_id' => $listings->random()->id,
                        'contact_id' => $contacts->random()->id,
                        'status' => $index === 0 ? 'completed' : ($index === 3 ? 'scheduled' : 'confirmed'),
                        'duration_minutes' => 45,
                        'notes' => 'Generated by seeder.'
                    ]
                );
            }
        }

        // 9. Create Commission Split Configurations
        \App\Infrastructure\Persistence\Models\CommissionSplitConfig::firstOrCreate(
            ['agency_id' => $agency->id, 'applies_to' => 'agency_default'],
            [
                'name' => 'Agency Default Split',
                'commission_rate' => 5.00,
                'agent_split' => 60.00,
                'agency_split' => 40.00,
                'is_active' => true,
            ]
        );

        \App\Infrastructure\Persistence\Models\CommissionSplitConfig::firstOrCreate(
            ['agency_id' => $agency->id, 'applies_to' => 'role', 'role' => 'principal'],
            [
                'name' => 'Principal Role Split Override',
                'commission_rate' => 5.00,
                'agent_split' => 70.00,
                'agency_split' => 30.00,
                'is_active' => true,
            ]
        );

        \App\Infrastructure\Persistence\Models\CommissionSplitConfig::firstOrCreate(
            ['agency_id' => $agency->id, 'applies_to' => 'agent', 'user_id' => $agentUser->id],
            [
                'name' => 'Top Producer Agent Split',
                'commission_rate' => 5.00,
                'agent_split' => 80.00,
                'agency_split' => 20.00,
                'is_active' => true,
            ]
        );

        // 10. Create Open Houses and RSVPs
        $activeListings = \App\Infrastructure\Persistence\Models\Listing::where('agency_id', $agency->id)->where('status', 'active')->get();
        if ($activeListings->count() > 0) {
            foreach ($activeListings->take(2) as $idx => $activeListing) {
                $openHouse = \App\Infrastructure\Persistence\Models\OpenHouse::firstOrCreate(
                    ['listing_id' => $activeListing->id],
                    [
                        'agency_id' => $agency->id,
                        'agent_id' => $idx % 2 == 0 ? $principalUser->id : $agentUser->id,
                        'starts_at' => now()->addDays($idx + 2)->setTime(10, 0, 0),
                        'ends_at' => now()->addDays($idx + 2)->setTime(15, 0, 0),
                        'status' => 'scheduled',
                        'notes' => 'Luxury private showing. Refreshments provided.',
                    ]
                );

                // Add RSVPs
                $rsvpContacts = $contacts->shuffle()->take(3);
                foreach ($rsvpContacts as $rsvpContact) {
                    \App\Infrastructure\Persistence\Models\OpenHouseRsvp::firstOrCreate(
                        ['open_house_id' => $openHouse->id, 'contact_id' => $rsvpContact->id],
                        [
                            'guest_name' => $rsvpContact->first_name . ' ' . $rsvpContact->last_name,
                            'guest_email' => $rsvpContact->email,
                            'guest_phone' => $rsvpContact->phone,
                            'checked_in' => false,
                            'reminder_sent' => true,
                        ]
                    );
                }
            }
        }

        // 11. Create Follow-up Sequences and Steps
        $nurtureContacts = $contacts->where('status', 'nurturing')->take(3);
        foreach ($nurtureContacts as $nIdx => $nContact) {
            $seq = \App\Infrastructure\Persistence\Models\FollowUpSequence::firstOrCreate(
                ['contact_id' => $nContact->id, 'agency_id' => $agency->id],
                [
                    'name' => 'Automated Buyer Nurture Campaign',
                    'assigned_agent_id' => $nIdx % 2 == 0 ? $principalUser->id : $agentUser->id,
                    'status' => 'active',
                    'current_step' => 1,
                    'next_action_at' => now()->addDays(2),
                ]
            );

            \App\Infrastructure\Persistence\Models\FollowUpStep::firstOrCreate(
                ['sequence_id' => $seq->id, 'step_number' => 1],
                [
                    'type' => 'email',
                    'subject' => 'Welcome to PropOS Exclusive Portal!',
                    'message_template' => 'Hi {buyer_name}, thank you for registering with us. We have matching listings in Ikoyi.',
                    'delay_days' => 1,
                    'status' => 'pending',
                ]
            );

            \App\Infrastructure\Persistence\Models\FollowUpStep::firstOrCreate(
                ['sequence_id' => $seq->id, 'step_number' => 2],
                [
                    'type' => 'sms',
                    'message_template' => 'Hi {buyer_name}, check out this property that matches your criteria: {property_address}',
                    'delay_days' => 3,
                    'status' => 'pending',
                ]
            );

            \App\Infrastructure\Persistence\Models\FollowUpStep::firstOrCreate(
                ['sequence_id' => $seq->id, 'step_number' => 3],
                [
                    'type' => 'call',
                    'message_template' => 'Schedule a viewing with {buyer_name}',
                    'delay_days' => 7,
                    'status' => 'pending',
                ]
            );
        }

        // 12. Create Contracts (Draft, Sent, Signed)
        $contractDeals = \App\Infrastructure\Persistence\Models\Deal::where('agency_id', $agency->id)->get();
        if ($contractDeals->count() > 0) {
            // Draft
            \App\Infrastructure\Persistence\Models\Contract::firstOrCreate(
                ['deal_id' => $contractDeals->first()->id, 'status' => 'draft'],
                [
                    'agency_id' => $agency->id,
                    'created_by' => $principalUser->id,
                    'title' => 'Standard Sale Agreement — ' . $contractDeals->first()->title,
                    'type' => 'sale_agreement',
                    'reference' => 'CON-' . strtoupper(bin2hex(random_bytes(4))),
                    'body' => "SALE AGREEMENT\n\nSeller: Owner\nBuyer: " . ($contractDeals->first()->contact->first_name ?? 'Client') . "\nPrice: NGN " . number_format($contractDeals->first()->value, 2) . "\n\nStandard terms apply.",
                    'valid_from' => now()->toDateString(),
                    'valid_until' => now()->addDays(90)->toDateString(),
                ]
            );

            // Sent
            if ($contractDeals->count() > 1) {
                \App\Infrastructure\Persistence\Models\Contract::firstOrCreate(
                    ['deal_id' => $contractDeals->get(1)->id, 'status' => 'sent'],
                    [
                        'agency_id' => $agency->id,
                        'created_by' => $agentUser->id,
                        'title' => 'Residential Lease — ' . $contractDeals->get(1)->title,
                        'type' => 'lease_agreement',
                        'reference' => 'CON-' . strtoupper(bin2hex(random_bytes(4))),
                        'body' => "LEASE AGREEMENT\n\nLandlord: Demo Agency\nTenant: " . ($contractDeals->get(1)->contact->first_name ?? 'Client') . "\nMonthly Rent: NGN 4,500,000.00\n\nStandard lease terms apply.",
                        'valid_from' => now()->toDateString(),
                        'valid_until' => now()->addDays(365)->toDateString(),
                        'signatories' => [
                            'envelope_id' => 'env_abc123',
                            'sent_at' => now()->toDateTimeString(),
                        ]
                    ]
                );
            }

            // Signed
            if ($contractDeals->count() > 2) {
                \App\Infrastructure\Persistence\Models\Contract::firstOrCreate(
                    ['deal_id' => $contractDeals->get(2)->id, 'status' => 'fully_signed'],
                    [
                        'agency_id' => $agency->id,
                        'created_by' => $principalUser->id,
                        'title' => 'Exclusive Listing Mandate — ' . $contractDeals->get(2)->title,
                        'type' => 'mandate',
                        'reference' => 'CON-' . strtoupper(bin2hex(random_bytes(4))),
                        'body' => "EXCLUSIVE SELLER MANDATE\n\nAgency: Demo Agency\nSeller: " . ($contractDeals->get(2)->contact->first_name ?? 'Client') . "\n\nStandard exclusive mandate terms apply.",
                        'valid_from' => now()->subDays(10)->toDateString(),
                        'valid_until' => now()->addDays(80)->toDateString(),
                        'signatories' => [
                            'envelope_id' => 'env_xyz789',
                            'sent_at' => now()->subDays(10)->toDateTimeString(),
                        ],
                        'signed_at' => [
                            [
                                'name' => ($contractDeals->get(2)->contact->first_name ?? 'Client') . ' ' . ($contractDeals->get(2)->contact->last_name ?? ''),
                                'initials' => 'C',
                                'ip_address' => '127.0.0.1',
                                'user_agent' => 'Mozilla/5.0 Chrome/100.0',
                                'signed_at' => now()->subDays(9)->toDateTimeString(),
                            ]
                        ]
                    ]
                );
            }
        }

        // 13. Create Transactions and Commissions for "Won" Deals
        $wonDeals = \App\Infrastructure\Persistence\Models\Deal::where('agency_id', $agency->id)
            ->whereHas('stage', fn($q) => $q->where('is_won', true))
            ->get();

        foreach ($wonDeals as $wIdx => $wDeal) {
            $transaction = \App\Infrastructure\Persistence\Models\Transaction::firstOrCreate(
                ['deal_id' => $wDeal->id],
                [
                    'agency_id' => $agency->id,
                    'reference' => 'TX-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'listing_id' => $wDeal->listing_id,
                    'contact_id' => $wDeal->contact_id,
                    'assigned_agent_id' => $wDeal->assigned_agent_id,
                    'sale_price' => $wDeal->value,
                    'commission_rate' => 5.00,
                    'agent_split' => $wIdx % 2 == 0 ? 80.00 : 60.00,
                    'status' => 'completed',
                    'closed_at' => now()->subDays(rand(2, 10))->toDateString(),
                ]
            );

            $commRate = 5.00;
            $agentSplit = $wIdx % 2 == 0 ? 80.00 : 60.00;
            $gross = $wDeal->value * ($commRate / 100);

            \App\Infrastructure\Persistence\Models\Commission::firstOrCreate(
                ['deal_id' => $wDeal->id],
                [
                    'agency_id' => $agency->id,
                    'transaction_id' => $transaction->id,
                    'agent_id' => $wDeal->assigned_agent_id,
                    'sale_price' => $wDeal->value,
                    'commission_rate' => $commRate,
                    'gross_commission' => $gross,
                    'agent_split_percentage' => $agentSplit,
                    'agent_commission' => $gross * ($agentSplit / 100),
                    'agency_commission' => $gross * ((100 - $agentSplit) / 100),
                    'payment_status' => $wIdx % 2 == 0 ? 'paid' : 'pending',
                    'expected_payment_date' => now()->addDays(15)->toDateString(),
                    'paid_at' => $wIdx % 2 == 0 ? now()->subDays(1)->toDateString() : null,
                ]
            );
        }

        // 14. Tenants, Leases, Rent Payments, and Invoices
        $rentalListings = \App\Infrastructure\Persistence\Models\Listing::where('agency_id', $agency->id)
            ->where('mandate_type', 'rental')
            ->get();
            
        if ($rentalListings->count() === 0) {
            // Let's mark one listing as rental to ensure we have one
            $listingToChange = \App\Infrastructure\Persistence\Models\Listing::where('agency_id', $agency->id)->first();
            if ($listingToChange) {
                $listingToChange->update(['mandate_type' => 'rental', 'listing_price' => 500000.00]);
                $rentalListings = collect([$listingToChange]);
            }
        }

        $tenantContact = $contacts->where('type', 'tenant')->first() ?: $contacts->first();
        if ($rentalListings->count() > 0 && $tenantContact) {
            $tenant = \App\Infrastructure\Persistence\Models\Tenant::firstOrCreate(
                ['contact_id' => $tenantContact->id],
                [
                    'agency_id' => $agency->id,
                    'status' => 'active',
                    'employer' => 'Apex Tech Solutions',
                    'monthly_income' => 1500000.00,
                ]
            );

            $lease = \App\Infrastructure\Persistence\Models\Lease::firstOrCreate(
                ['tenant_id' => $tenant->id, 'listing_id' => $rentalListings->first()->id],
                [
                    'agency_id' => $agency->id,
                    'contact_id' => $tenantContact->id,
                    'assigned_agent_id' => $agentUser->id,
                    'reference' => 'LSE-' . strtoupper(bin2hex(random_bytes(4))),
                    'status' => 'active',
                    'start_date' => now()->subMonths(3)->toDateString(),
                    'end_date' => now()->addMonths(9)->toDateString(),
                    'monthly_rent' => 500000.00,
                    'deposit_amount' => 1000000.00,
                    'payment_day' => '1',
                ]
            );

            // Invoice
            $invoice = \App\Infrastructure\Persistence\Models\Invoice::firstOrCreate(
                ['lease_id' => $lease->id, 'period_month' => 5, 'period_year' => 2026],
                [
                    'agency_id' => $agency->id,
                    'tenant_id' => $tenant->id,
                    'reference' => 'INV-' . strtoupper(bin2hex(random_bytes(4))),
                    'type' => 'rent',
                    'status' => 'paid',
                    'subtotal' => 500000.00,
                    'tax_amount' => 0.00,
                    'total' => 500000.00,
                    'amount_paid' => 500000.00,
                    'due_date' => now()->subDays(10)->toDateString(),
                    'paid_at' => now()->subDays(9)->toDateTimeString(),
                ]
            );

            // Rent payment
            \App\Infrastructure\Persistence\Models\RentPayment::firstOrCreate(
                ['lease_id' => $lease->id, 'due_date' => now()->subDays(10)->toDateString()],
                [
                    'agency_id' => $agency->id,
                    'tenant_id' => $tenant->id,
                    'reference' => 'PMT-' . strtoupper(bin2hex(random_bytes(4))),
                    'amount_due' => 500000.00,
                    'amount_paid' => 500000.00,
                    'penalty' => 0.00,
                    'status' => 'paid',
                    'paid_date' => now()->subDays(9)->toDateString(),
                    'payment_method' => 'bank_transfer',
                ]
            );
        }
    }
}
