<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\RentPayment;
use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantLeaseSeeder extends Seeder
{
    public function run(): void
    {
        $agency    = Agency::where('slug', 'demo')->firstOrFail();
        $agent     = User::where('email', 'agent@villacrm.app')->firstOrFail();
        $principal = User::where('email', 'principal@villacrm.app')->firstOrFail();

        // ── Ensure we have 6 rental listings ──────────────────────────────────
        $rentalData = [
            ['address' => '14A Bourdillon Road, Ikoyi',        'city' => 'Lagos',  'bedrooms' => 4, 'bathrooms' => 4, 'type' => 'apartment'],
            ['address' => '7 Adeola Odeku Street, Victoria Island','city' => 'Lagos','bedrooms' => 3, 'bathrooms' => 3, 'type' => 'apartment'],
            ['address' => '22 Admiralty Way, Lekki Phase 1',   'city' => 'Lagos',  'bedrooms' => 5, 'bathrooms' => 4, 'type' => 'house'],
            ['address' => '3 Mambilla Street, Asokoro',        'city' => 'Abuja',  'bedrooms' => 4, 'bathrooms' => 3, 'type' => 'house'],
            ['address' => '9 Kofo Abayomi Street, Victoria Island','city' => 'Lagos','bedrooms' => 2, 'bathrooms' => 2, 'type' => 'apartment'],
            ['address' => '45 Aso Drive, Maitama',             'city' => 'Abuja',  'bedrooms' => 5, 'bathrooms' => 5, 'type' => 'house'],
        ];

        $listings = [];
        foreach ($rentalData as $i => $rd) {
            $property = Property::firstOrCreate(
                ['address_line_1' => $rd['address']],
                [
                    'agency_id'      => $agency->id,
                    'city'           => $rd['city'],
                    'state_province' => str_contains($rd['city'], 'Abuja') ? 'FCT' : 'Lagos',
                    'country'        => 'NG',
                    'property_type'  => $rd['type'],
                    'bedrooms'       => $rd['bedrooms'],
                    'bathrooms'      => $rd['bathrooms'],
                ]
            );

            $listings[] = Listing::firstOrCreate(
                ['property_id' => $property->id, 'agency_id' => $agency->id],
                [
                    'agent_id'            => $i % 2 === 0 ? $agent->id : $principal->id,
                    'mandate_type'        => 'rental',
                    'status'              => 'active',
                    'listing_price'       => match($i) {
                        0 => 1_200_000, 1 => 850_000, 2 => 1_500_000,
                        3 => 950_000,   4 => 450_000,  default => 2_000_000,
                    },
                    'mandate_start_date'  => now()->subMonths(8),
                ]
            );
        }

        // ── Tenant profiles ───────────────────────────────────────────────────
        $tenantProfiles = [
            [
                'first_name'     => 'Chukwuemeka',
                'last_name'      => 'Okonkwo',
                'email'          => 'c.okonkwo@tenant.demo',
                'phone'          => '+2348031234501',
                'employer'       => 'First Bank Nigeria PLC',
                'monthly_income' => 4_500_000,
                'status'         => 'active',
                'listing_idx'    => 0,
                'monthly_rent'   => 1_200_000,
                'deposit'        => 2_400_000,
                'start_offset'   => -8,   // months from now
                'end_offset'     => 4,
                'payment_day'    => 1,
                'escalation'     => 7.5,
                'agent'          => 'agent',
                'lease_status'   => 'active',
            ],
            [
                'first_name'     => 'Ngozi',
                'last_name'      => 'Adeyemi',
                'email'          => 'n.adeyemi@tenant.demo',
                'phone'          => '+2348031234502',
                'employer'       => 'MTN Nigeria Communications',
                'monthly_income' => 3_200_000,
                'status'         => 'active',
                'listing_idx'    => 1,
                'monthly_rent'   => 850_000,
                'deposit'        => 1_700_000,
                'start_offset'   => -6,
                'end_offset'     => 6,
                'payment_day'    => 5,
                'escalation'     => 7.5,
                'agent'          => 'principal',
                'lease_status'   => 'active',
            ],
            [
                'first_name'     => 'Tunde',
                'last_name'      => 'Fashola',
                'email'          => 't.fashola@tenant.demo',
                'phone'          => '+2348031234503',
                'employer'       => 'Dangote Industries Ltd',
                'monthly_income' => 6_000_000,
                'status'         => 'active',
                'listing_idx'    => 2,
                'monthly_rent'   => 1_500_000,
                'deposit'        => 3_000_000,
                'start_offset'   => -11,
                'end_offset'     => 1,
                'payment_day'    => 1,
                'escalation'     => 10.0,
                'agent'          => 'agent',
                'lease_status'   => 'expiring_soon',
            ],
            [
                'first_name'     => 'Amina',
                'last_name'      => 'Ibrahim',
                'email'          => 'a.ibrahim@tenant.demo',
                'phone'          => '+2348031234504',
                'employer'       => 'Central Bank of Nigeria',
                'monthly_income' => 5_500_000,
                'status'         => 'active',
                'listing_idx'    => 3,
                'monthly_rent'   => 950_000,
                'deposit'        => 1_900_000,
                'start_offset'   => -5,
                'end_offset'     => 7,
                'payment_day'    => 10,
                'escalation'     => 7.5,
                'agent'          => 'principal',
                'lease_status'   => 'active',
            ],
            [
                'first_name'     => 'Emeka',
                'last_name'      => 'Eze',
                'email'          => 'e.eze@tenant.demo',
                'phone'          => '+2348031234505',
                'employer'       => 'Guaranty Trust Bank PLC',
                'monthly_income' => 2_000_000,
                'status'         => 'active',
                'listing_idx'    => 4,
                'monthly_rent'   => 450_000,
                'deposit'        => 900_000,
                'start_offset'   => -3,
                'end_offset'     => 9,
                'payment_day'    => 1,
                'escalation'     => 5.0,
                'agent'          => 'agent',
                'lease_status'   => 'active',
            ],
            [
                'first_name'     => 'Fatima',
                'last_name'      => 'Bello',
                'email'          => 'f.bello@tenant.demo',
                'phone'          => '+2348031234506',
                'employer'       => 'Nigerian National Petroleum Corporation',
                'monthly_income' => 7_000_000,
                'status'         => 'vacating',
                'listing_idx'    => 5,
                'monthly_rent'   => 2_000_000,
                'deposit'        => 4_000_000,
                'start_offset'   => -13,
                'end_offset'     => -1,
                'payment_day'    => 1,
                'escalation'     => 10.0,
                'agent'          => 'principal',
                'lease_status'   => 'terminated',
            ],
        ];

        foreach ($tenantProfiles as $profile) {
            $agentUser = $profile['agent'] === 'agent' ? $agent : $principal;
            $listing   = $listings[$profile['listing_idx']];

            // Contact
            $contact = Contact::firstOrCreate(
                ['email' => $profile['email']],
                [
                    'agency_id'         => $agency->id,
                    'assigned_agent_id' => $agentUser->id,
                    'type'              => 'tenant',
                    'first_name'        => $profile['first_name'],
                    'last_name'         => $profile['last_name'],
                    'phone'             => $profile['phone'],
                    'status'            => 'active',
                ]
            );

            // Tenant
            $tenant = Tenant::firstOrCreate(
                ['contact_id' => $contact->id, 'agency_id' => $agency->id],
                [
                    'listing_id'        => $listing->id,
                    'assigned_agent_id' => $agentUser->id,
                    'status'            => $profile['status'],
                    'employer'          => $profile['employer'],
                    'monthly_income'    => $profile['monthly_income'],
                ]
            );

            $startDate = now()->addMonths($profile['start_offset'])->startOfMonth();
            $endDate   = now()->addMonths($profile['end_offset'])->endOfMonth();

            // Lease
            $lease = Lease::firstOrCreate(
                ['tenant_id' => $tenant->id, 'listing_id' => $listing->id],
                [
                    'agency_id'          => $agency->id,
                    'contact_id'         => $contact->id,
                    'assigned_agent_id'  => $agentUser->id,
                    'reference'          => 'LSE-' . strtoupper(Str::random(8)),
                    'status'             => $profile['lease_status'],
                    'monthly_rent'       => $profile['monthly_rent'],
                    'deposit_amount'     => $profile['deposit'],
                    'start_date'         => $startDate,
                    'end_date'           => $endDate,
                    'payment_day'        => $profile['payment_day'],
                    'escalation_percent' => $profile['escalation'],
                ]
            );

            // ── Rent payment history ───────────────────────────────────────────
            $this->seedPaymentHistory($lease, $tenant, $agency->id, $profile);
        }
    }

    private function seedPaymentHistory(Lease $lease, Tenant $tenant, int $agencyId, array $profile): void
    {
        $startDate  = Carbon::parse($lease->start_date);
        $today      = Carbon::now();
        $monthlyRent= (float) $lease->monthly_rent;
        $cursor     = $startDate->copy()->startOfMonth();

        while ($cursor->lte($today)) {
            $dueDate = $cursor->copy()->day(min($profile['payment_day'], $cursor->daysInMonth));

            $existing = RentPayment::where('lease_id', $lease->id)
                ->whereDate('due_date', $dueDate->toDateString())
                ->exists();

            if ($existing) {
                $cursor->addMonth();
                continue;
            }

            $monthsAgo = $today->diffInMonths($cursor);

            // Determine payment status realistically
            if ($profile['lease_status'] === 'terminated' && $cursor->gte($today->copy()->startOfMonth())) {
                $cursor->addMonth();
                continue;
            }

            if ($dueDate->gt($today)) {
                // Future — pending
                $status    = 'pending';
                $amountPaid= null;
                $paidDate  = null;
                $method    = null;
            } elseif ($profile['email'] === 'e.eze@tenant.demo' && $monthsAgo === 0) {
                // Current month — partial (Emeka often pays partial)
                $status    = 'partial';
                $amountPaid= round($monthlyRent * 0.6, 2);
                $paidDate  = $today->copy()->subDays(rand(1, 5))->toDateString();
                $method    = 'bank_transfer';
            } elseif ($profile['email'] === 't.fashola@tenant.demo' && $monthsAgo === 0) {
                // Tunde's current month — overdue (hasn't paid yet)
                $status    = 'overdue';
                $amountPaid= null;
                $paidDate  = null;
                $method    = null;
            } else {
                // Historical — paid on time (slightly varied)
                $daysLate  = ($monthsAgo === 0) ? 0 : rand(0, 4);
                $status    = 'paid';
                $amountPaid= $monthlyRent;
                $paidDate  = $dueDate->copy()->addDays($daysLate)->toDateString();
                $method    = $this->randomMethod();
            }

            RentPayment::create([
                'agency_id'      => $agencyId,
                'lease_id'       => $lease->id,
                'tenant_id'      => $tenant->id,
                'reference'      => 'PAY-' . strtoupper(Str::random(8)),
                'amount_due'     => $monthlyRent,
                'amount_paid'    => $amountPaid,
                'penalty'        => 0,
                'status'         => $status,
                'due_date'       => $dueDate->toDateString(),
                'paid_date'      => $paidDate,
                'payment_method' => $method,
                'notes'          => $status === 'partial' ? 'Partial payment received — balance outstanding' : null,
            ]);

            $cursor->addMonth();
        }
    }

    private function randomMethod(): string
    {
        return collect(['bank_transfer', 'bank_transfer', 'bank_transfer', 'card', 'cash'])->random();
    }
}
