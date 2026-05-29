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
                'id' => 1,
                'name' => 'Demo Agency',
                'email' => 'demo@propos.app',
                'timezone' => 'Africa/Lagos',
                'currency' => 'NGN',
                'country_code' => 'NG',
                'subscription_plan' => 'pro',
                'subscription_status' => 'active',
            ]
        );

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
    }
}
