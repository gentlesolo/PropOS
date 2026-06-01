<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contract;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Models\Transaction;
use App\Infrastructure\Persistence\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContractsSeeder extends Seeder
{
    public function run(): void
    {
        $agency    = Agency::where('slug', 'demo')->firstOrFail();
        $agent     = User::where('email', 'agent@propos.app')->firstOrFail();
        $principal = User::where('email', 'principal@propos.app')->firstOrFail();

        $deals    = Deal::with(['contact', 'listing.property'])->where('agency_id', $agency->id)->get();
        $listings = Listing::with('property')->where('agency_id', $agency->id)->get();
        $leases   = Lease::with(['tenant.contact', 'listing.property'])->where('agency_id', $agency->id)->get();

        $this->seedSaleContracts($agency->id, $agent, $principal, $deals);
        $this->seedMandateContracts($agency->id, $agent, $principal, $listings);
        $this->seedLeaseContracts($agency->id, $agent, $principal, $leases);
        $this->seedAddendumContracts($agency->id, $principal, $deals);
    }

    // ── Sale / Purchase Contracts ──────────────────────────────────────────────

    private function seedSaleContracts(int $agencyId, User $agent, User $principal, $deals): void
    {
        $statuses = [
            'fully_signed',
            'signed_buyer',
            'sent',
            'draft',
            'cancelled',
        ];

        foreach ($deals->values()->take(5) as $i => $deal) {
            $status    = $statuses[$i] ?? 'draft';
            $property  = $deal->listing?->property;
            $address   = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
            $buyer     = $deal->contact?->full_name ?? 'Buyer';
            $price     = '₦' . number_format((float) $deal->value, 2);
            $createdBy = $i % 2 === 0 ? $agent : $principal;
            $daysAgo   = rand(5, 30);

            if (Contract::where('deal_id', $deal->id)->where('type', 'offer_to_purchase')->exists()) {
                continue;
            }

            $validFrom  = now()->subDays($daysAgo);
            $validUntil = $validFrom->copy()->addDays(90);

            $signatories = [
                ['name' => $buyer, 'role' => 'buyer', 'email' => $deal->contact?->email],
                ['name' => 'Demo Agency', 'role' => 'seller_agent', 'email' => 'demo@propos.app'],
            ];

            $signedAt = null;
            if (in_array($status, ['signed_buyer', 'fully_signed'])) {
                $signedAt[] = ['name' => $buyer, 'role' => 'buyer', 'signed_at' => $validFrom->copy()->addDays(2)->toDateTimeString(), 'ip_address' => '105.112.'.rand(1,254).'.'.rand(1,254)];
            }
            if ($status === 'fully_signed') {
                $signedAt[] = ['name' => 'Demo Agency', 'role' => 'seller_agent', 'signed_at' => $validFrom->copy()->addDays(3)->toDateTimeString(), 'ip_address' => '197.210.'.rand(1,254).'.'.rand(1,254)];
            }

            Contract::create([
                'agency_id'   => $agencyId,
                'deal_id'     => $deal->id,
                'listing_id'  => $deal->listing_id,
                'contact_id'  => $deal->contact_id,
                'created_by'  => $createdBy->id,
                'reference'   => 'CON-' . strtoupper(Str::random(8)),
                'title'       => "Offer to Purchase — {$address}",
                'type'        => 'offer_to_purchase',
                'status'      => $status,
                'body'        => $this->otpBody($buyer, $address, $price, $validFrom->addMonths(2)->format('d M Y')),
                'valid_from'  => $validFrom->toDateString(),
                'valid_until' => $validUntil->toDateString(),
                'signatories' => $signatories,
                'signed_at'   => $signedAt,
                'esign_provider'     => $status !== 'draft' ? 'docusign' : null,
                'esign_document_id'  => $status !== 'draft' ? 'DS-' . strtoupper(Str::random(16)) : null,
            ]);
        }
    }

    // ── Mandate Contracts ──────────────────────────────────────────────────────

    private function seedMandateContracts(int $agencyId, User $agent, User $principal, $listings): void
    {
        $mandateTypes = [
            ['type' => 'sole_mandate',      'duration_months' => 3],
            ['type' => 'exclusive_mandate', 'duration_months' => 6],
            ['type' => 'open_mandate',      'duration_months' => 3],
            ['type' => 'sole_mandate',      'duration_months' => 4],
        ];

        foreach ($listings->values()->take(4) as $i => $listing) {
            $property   = $listing->property;
            $address    = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
            $mandate    = $mandateTypes[$i];
            $createdBy  = $i % 2 === 0 ? $agent : $principal;
            $status     = $i === 0 ? 'fully_signed' : ($i === 1 ? 'fully_signed' : ($i === 2 ? 'sent' : 'draft'));
            $validFrom  = now()->subMonths(rand(1, 3));
            $validUntil = $validFrom->copy()->addMonths($mandate['duration_months']);

            if (Contract::where('listing_id', $listing->id)->where('type', 'mandate')->exists()) {
                continue;
            }

            $mandateLabel = str_replace('_', ' ', $mandate['type']);
            $commRate     = $mandate['type'] === 'open_mandate' ? '3%' : '5%';

            $signatories = [
                ['name' => 'Property Owner', 'role' => 'seller', 'email' => null],
                ['name' => 'Demo Agency',    'role' => 'agent',  'email' => 'demo@propos.app'],
            ];

            $signedAt = null;
            if ($status === 'fully_signed') {
                $signedAt = [
                    ['name' => 'Property Owner', 'signed_at' => $validFrom->copy()->addDay()->toDateTimeString()],
                    ['name' => 'Demo Agency',    'signed_at' => $validFrom->copy()->addDays(2)->toDateTimeString()],
                ];
            }

            Contract::create([
                'agency_id'   => $agencyId,
                'listing_id'  => $listing->id,
                'created_by'  => $createdBy->id,
                'reference'   => 'CON-' . strtoupper(Str::random(8)),
                'title'       => ucfirst($mandateLabel) . " — {$address}",
                'type'        => 'mandate',
                'status'      => $status,
                'body'        => $this->mandateBody($address, $mandateLabel, $commRate, $validFrom->format('d M Y'), $validUntil->format('d M Y')),
                'valid_from'  => $validFrom->toDateString(),
                'valid_until' => $validUntil->toDateString(),
                'signatories' => $signatories,
                'signed_at'   => $signedAt,
            ]);
        }
    }

    // ── Lease Agreement Contracts ──────────────────────────────────────────────

    private function seedLeaseContracts(int $agencyId, User $agent, User $principal, $leases): void
    {
        foreach ($leases as $lease) {
            if (Contract::where('type', 'lease_agreement')
                ->whereHas('deal', fn ($q) => false)
                ->where('agency_id', $agencyId)
                ->whereExists(function ($q) use ($lease) {
                    // Avoid a costly join — just check title or contact
                    $q->from('contracts')->where('contact_id', $lease->contact_id)->where('type', 'lease_agreement');
                })
                ->exists()
            ) {
                continue;
            }

            // Simpler idempotency check
            if (Contract::where('agency_id', $agencyId)
                ->where('contact_id', $lease->contact_id)
                ->where('type', 'lease_agreement')
                ->exists()
            ) {
                continue;
            }

            $tenant    = $lease->tenant?->contact;
            $property  = $lease->listing?->property;
            $address   = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
            $tenantName= $tenant?->full_name ?? 'Tenant';
            $rent      = '₦' . number_format((float) $lease->monthly_rent, 2);
            $deposit   = '₦' . number_format((float) $lease->deposit_amount, 2);
            $createdBy = $agent;

            $isTerminated = $lease->status === 'terminated';
            $status       = $isTerminated ? 'cancelled' : 'fully_signed';

            $signedAt = [
                ['name' => $tenantName,   'role' => 'tenant',  'signed_at' => Carbon::parse($lease->start_date)->subDays(3)->toDateTimeString()],
                ['name' => 'Demo Agency', 'role' => 'landlord_agent', 'signed_at' => Carbon::parse($lease->start_date)->subDays(2)->toDateTimeString()],
            ];

            Contract::create([
                'agency_id'   => $agencyId,
                'listing_id'  => $lease->listing_id,
                'contact_id'  => $lease->contact_id,
                'created_by'  => $createdBy->id,
                'reference'   => 'CON-' . strtoupper(Str::random(8)),
                'title'       => "Residential Lease Agreement — {$address}",
                'type'        => 'lease_agreement',
                'status'      => $status,
                'body'        => $this->leaseBody(
                    $tenantName,
                    $address,
                    $rent,
                    $deposit,
                    Carbon::parse($lease->start_date)->format('d M Y'),
                    Carbon::parse($lease->end_date)->format('d M Y'),
                    (float) $lease->escalation_percent,
                ),
                'valid_from'  => Carbon::parse($lease->start_date)->toDateString(),
                'valid_until' => Carbon::parse($lease->end_date)->toDateString(),
                'signatories' => [
                    ['name' => $tenantName,   'role' => 'tenant',         'email' => $tenant?->email],
                    ['name' => 'Demo Agency', 'role' => 'landlord_agent', 'email' => 'demo@propos.app'],
                ],
                'signed_at'   => $signedAt,
            ]);
        }
    }

    // ── Addendum Contracts ─────────────────────────────────────────────────────

    private function seedAddendumContracts(int $agencyId, User $principal, $deals): void
    {
        $deal = $deals->values()->first();
        if (! $deal) {
            return;
        }

        if (Contract::where('agency_id', $agencyId)->where('type', 'addendum')->exists()) {
            return;
        }

        $property = $deal->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';

        Contract::create([
            'agency_id'   => $agencyId,
            'deal_id'     => $deal->id,
            'listing_id'  => $deal->listing_id,
            'contact_id'  => $deal->contact_id,
            'created_by'  => $principal->id,
            'reference'   => 'CON-' . strtoupper(Str::random(8)),
            'title'       => "Addendum — Occupation Date Amendment — {$address}",
            'type'        => 'addendum',
            'status'      => 'fully_signed',
            'body'        => $this->addendumBody($address, now()->subDays(5)->format('d M Y')),
            'valid_from'  => now()->subDays(10)->toDateString(),
            'valid_until' => now()->addDays(350)->toDateString(),
            'signatories' => [
                ['name' => $deal->contact?->full_name ?? 'Buyer', 'role' => 'buyer'],
                ['name' => 'Demo Agency', 'role' => 'seller_agent'],
            ],
            'signed_at'   => [
                ['name' => $deal->contact?->full_name ?? 'Buyer', 'signed_at' => now()->subDays(8)->toDateTimeString()],
                ['name' => 'Demo Agency', 'signed_at' => now()->subDays(7)->toDateTimeString()],
            ],
        ]);
    }

    // ── Contract body templates ────────────────────────────────────────────────

    private function otpBody(string $buyer, string $address, string $price, string $occupation): string
    {
        return <<<EOT
OFFER TO PURCHASE

Property: {$address}
Purchase Price: {$price}
Buyer: {$buyer}
Proposed Occupation: {$occupation}

CONDITIONS:
1. Subject to mortgage bond approval within 21 days.
2. Property sold voetstoots (as is). All structural reports disclosed.
3. Deposit of 10% payable within 3 business days of acceptance.
4. Electrical, plumbing, and gas compliance certificates to be provided by seller.
5. Occupational rent at 0.75% per month of the purchase price from occupation to registration.

GENERAL:
This offer is irrevocable for the period stated. The seller shall respond within the validity period failing which this offer shall lapse. All parties confirm they have read and understood these terms.

SIGNATURES:
Buyer: ________________________________ Date: ___________
Agent (on behalf of Seller): __________ Date: ___________
EOT;
    }

    private function mandateBody(string $address, string $mandateType, string $commRate, string $from, string $until): string
    {
        return <<<EOT
{$mandateType} MANDATE

Property: {$address}
Mandate Period: {$from} to {$until}
Commission Rate: {$commRate} (inclusive of VAT where applicable)

MANDATE CONDITIONS:
1. Demo Agency is hereby authorised to market and sell/let the above property.
2. The agent shall endeavour to find a buyer/tenant at the asking price or such other price acceptable to the owner.
3. Commission shall become due and payable upon signature of a sale/lease agreement.
4. The owner shall not market the property independently during the mandate period for a {$mandateType}.
5. The owner warrants that the property is free from encumbrances not disclosed herein.
6. Either party may terminate with 20 business days written notice (open mandates only).

MARKETING PLAN:
- Professional photography and floor plans
- Listing on PropertyPro, Private Property, and private portals
- Social media campaigns and WhatsApp broadcasts
- Email blast to registered buyer/tenant database

SIGNATURES:
Property Owner: _____________________ Date: ___________
Agent (Demo Agency): ________________ Date: ___________
EOT;
    }

    private function leaseBody(string $tenant, string $address, string $rent, string $deposit, string $start, string $end, float $escalation): string
    {
        return <<<EOT
RESIDENTIAL LEASE AGREEMENT

PARTIES:
Landlord: Demo Agency (acting for registered owner)
Tenant: {$tenant}

PREMISES: {$address}

LEASE PERIOD: {$start} to {$end} (12 months)

FINANCIAL TERMS:
Monthly Rental: {$rent} payable in advance on the 1st of each month.
Security Deposit: {$deposit} — held in trust and refundable subject to inspection.
Annual Escalation: {$escalation}% applied on the anniversary of the lease commencement date.

OBLIGATIONS OF TENANT:
1. Maintain premises in clean and habitable condition.
2. Report defects within 48 hours of discovery.
3. Not sublet or allow any other person to occupy without written consent.
4. Not make structural changes or alterations without written consent.
5. Allow landlord/agent access for inspections with 24 hours notice.
6. Return premises in same condition, reasonable wear excepted.

OBLIGATIONS OF LANDLORD:
1. Ensure premises are habitable and in good repair at commencement.
2. Maintain structural elements, roof, and municipal services.
3. Not interfere with tenant's peaceful occupation.

BREACH:
In the event of breach of any material term, the non-breaching party shall give 20 business days written notice to remedy, failing which the lease may be cancelled with damages.

SIGNATURES:
Tenant: _____________________________ Date: ___________
Landlord's Agent: ___________________ Date: ___________
EOT;
    }

    private function addendumBody(string $address, string $newDate): string
    {
        return <<<EOT
ADDENDUM TO SALE AGREEMENT

Property: {$address}
Date of Original Agreement: (as per attached Offer to Purchase)

AMENDMENT:
The parties hereby agree to amend the proposed occupation date as follows:

Original Occupation Date: as per original agreement
Amended Occupation Date: {$newDate}

All other terms and conditions of the original Sale Agreement remain unchanged and in full force and effect.

REASON FOR AMENDMENT:
The buyer has requested a revised occupation date to accommodate the registration timeline with the conveyancers.

SIGNATURES:
Buyer: ________________________________ Date: ___________
Seller's Agent: _______________________ Date: ___________
EOT;
    }
}
