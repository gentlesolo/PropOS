<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Contract;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Offer;
use App\Infrastructure\Persistence\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OffersSeeder extends Seeder
{
    public function run(): void
    {
        $agency    = Agency::where('slug', 'demo')->firstOrFail();
        $agent     = User::where('email', 'agent@villacrm.app')->firstOrFail();
        $principal = User::where('email', 'principal@villacrm.app')->firstOrFail();

        $deals    = Deal::with(['listing.property', 'contact'])
            ->where('agency_id', $agency->id)
            ->get();
        $contacts = Contact::where('agency_id', $agency->id)->get();
        $listings = Listing::with('property')->where('agency_id', $agency->id)->get();

        if ($deals->isEmpty() || $contacts->isEmpty()) {
            return;
        }

        // ── Offer scenario data ────────────────────────────────────────────────
        // Each scenario maps to one deal: first/only offer + optional counter + resolution
        $scenarios = [
            // Accepted first offer — no counter
            [
                'deal_idx'   => 0,
                'offers'     => [
                    [
                        'amount'                   => 185_000_000,
                        'type'                     => 'sale',
                        'status'                   => 'accepted',
                        'deposit_amount'           => 18_500_000,
                        'expiry_date'              => now()->subDays(20),
                        'proposed_occupation_date' => now()->addDays(30),
                        'conditions'               => "Subject to bond approval within 21 days. Property to be sold voetstoots. Occupational rent at R50,000 per month from date of occupation to date of registration.",
                        'notes'                    => 'Buyer pre-qualified. Bond approval expected within 10 days.',
                        'responded_at'             => now()->subDays(18),
                        'submitted_by'             => 'agent',
                    ],
                ],
            ],

            // Countered offer — buyer came in low, seller countered, accepted
            [
                'deal_idx'   => 1,
                'offers'     => [
                    [
                        'amount'                   => 95_000_000,
                        'type'                     => 'sale',
                        'status'                   => 'countered',
                        'deposit_amount'           => 9_500_000,
                        'expiry_date'              => now()->subDays(10),
                        'proposed_occupation_date' => now()->addDays(60),
                        'conditions'               => "Subject to sale of buyer's existing property. Bond approval within 30 days. All fixtures and fittings included.",
                        'notes'                    => 'First offer below asking. Seller willing to negotiate.',
                        'counter_amount'           => 108_000_000,
                        'counter_notes'            => 'Seller counter at ₦108M. Fixtures included. Subject to sale clause to be removed.',
                        'responded_at'             => now()->subDays(8),
                        'submitted_by'             => 'principal',
                    ],
                ],
            ],

            // Rejected offer — too low, deal fell through
            [
                'deal_idx'   => 2,
                'offers'     => [
                    [
                        'amount'                   => 45_000_000,
                        'type'                     => 'sale',
                        'status'                   => 'rejected',
                        'deposit_amount'           => 4_500_000,
                        'expiry_date'              => now()->subDays(15),
                        'proposed_occupation_date' => now()->addDays(45),
                        'conditions'               => "Cash purchase, no bond required. Immediate occupation.",
                        'notes'                    => 'Cash buyer but offer is significantly below asking price.',
                        'responded_at'             => now()->subDays(14),
                        'submitted_by'             => 'agent',
                    ],
                ],
            ],

            // Pending offer — recently submitted, awaiting response
            [
                'deal_idx'   => 3,
                'offers'     => [
                    [
                        'amount'                   => 220_000_000,
                        'type'                     => 'sale',
                        'status'                   => 'pending',
                        'deposit_amount'           => 22_000_000,
                        'expiry_date'              => now()->addDays(5),
                        'proposed_occupation_date' => now()->addDays(90),
                        'conditions'               => "Subject to bond approval within 14 days. Property to be professionally cleaned prior to occupation. Electrical compliance certificate to be provided by seller.",
                        'notes'                    => 'Serious buyer. Pre-approval letter attached.',
                        'submitted_by'             => 'agent',
                    ],
                ],
            ],

            // Withdrawn offer — buyer found another property
            [
                'deal_idx'   => 4,
                'offers'     => [
                    [
                        'amount'                   => 67_000_000,
                        'type'                     => 'sale',
                        'status'                   => 'withdrawn',
                        'deposit_amount'           => 6_700_000,
                        'expiry_date'              => now()->subDays(5),
                        'proposed_occupation_date' => now()->addDays(30),
                        'conditions'               => "Bond required. 30-day occupation.",
                        'notes'                    => 'Buyer withdrew to pursue another listing.',
                        'responded_at'             => now()->subDays(3),
                        'submitted_by'             => 'principal',
                    ],
                ],
            ],

            // Rental offer — accepted
            [
                'deal_idx'   => 5,
                'offers'     => [
                    [
                        'amount'                   => 950_000,
                        'type'                     => 'rental',
                        'status'                   => 'accepted',
                        'deposit_amount'           => 1_900_000,
                        'expiry_date'              => now()->subDays(25),
                        'proposed_occupation_date' => now()->subDays(20),
                        'conditions'               => "12-month lease. Tenant responsible for all utilities. No pets without prior written consent. Monthly rental escalates at 7.5% per annum.",
                        'notes'                    => 'Employed professional. References verified.',
                        'responded_at'             => now()->subDays(23),
                        'submitted_by'             => 'agent',
                    ],
                ],
            ],

            // Expired offer — deadline passed before response
            [
                'deal_idx'   => 6,
                'offers'     => [
                    [
                        'amount'                   => 130_000_000,
                        'type'                     => 'sale',
                        'status'                   => 'expired',
                        'deposit_amount'           => 13_000_000,
                        'expiry_date'              => now()->subDays(7),
                        'proposed_occupation_date' => now()->addDays(60),
                        'conditions'               => "Subject to conveyancer approving title deed. 60-day occupation.",
                        'notes'                    => 'Seller unreachable during offer validity period.',
                        'submitted_by'             => 'principal',
                    ],
                ],
            ],
        ];

        foreach ($scenarios as $scenario) {
            $dealIdx = $scenario['deal_idx'];
            if ($dealIdx >= $deals->count()) {
                continue;
            }

            $deal    = $deals->values()->get($dealIdx);
            $submittedBy = $scenario['offers'][0]['submitted_by'] === 'agent' ? $agent : $principal;

            foreach ($scenario['offers'] as $offerData) {
                $alreadyExists = Offer::where('deal_id', $deal->id)
                    ->where('amount', $offerData['amount'])
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $offer = Offer::create([
                    'agency_id'                => $agency->id,
                    'deal_id'                  => $deal->id,
                    'listing_id'               => $deal->listing_id,
                    'contact_id'               => $deal->contact_id,
                    'submitted_by'             => $submittedBy->id,
                    'amount'                   => $offerData['amount'],
                    'type'                     => $offerData['type'],
                    'status'                   => $offerData['status'],
                    'deposit_amount'           => $offerData['deposit_amount'],
                    'expiry_date'              => $offerData['expiry_date'],
                    'proposed_occupation_date' => $offerData['proposed_occupation_date'],
                    'conditions'               => $offerData['conditions'],
                    'notes'                    => $offerData['notes'] ?? null,
                    'counter_amount'           => $offerData['counter_amount'] ?? null,
                    'counter_notes'            => $offerData['counter_notes'] ?? null,
                    'responded_at'             => $offerData['responded_at'] ?? null,
                ]);

                // ── Create linked contract for accepted offers ────────────────
                if ($offerData['status'] === 'accepted') {
                    $this->createLinkedContract($offer, $deal, $submittedBy, $agency->id);
                }
            }
        }
    }

    private function createLinkedContract(Offer $offer, Deal $deal, User $createdBy, int $agencyId): void
    {
        if (Contract::where('offer_id', $offer->id)->exists()) {
            return;
        }

        $isSale   = $offer->type === 'sale';
        $type     = $isSale ? 'offer_to_purchase' : 'lease_agreement';
        $property = $offer->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $buyer    = $offer->contact?->full_name ?? 'Buyer';
        $amount   = '₦' . number_format((float) $offer->amount, 2);

        $body = $isSale
            ? $this->saleAgreementBody($buyer, $address, $amount, $offer->proposed_occupation_date?->format('d M Y') ?? 'TBD', $offer->conditions ?? '')
            : $this->leaseAgreementBody($buyer, $address, $amount, $offer->proposed_occupation_date?->format('d M Y') ?? 'TBD', $offer->conditions ?? '');

        Contract::create([
            'agency_id'      => $agencyId,
            'deal_id'        => $deal->id,
            'offer_id'       => $offer->id,
            'listing_id'     => $offer->listing_id,
            'contact_id'     => $offer->contact_id,
            'created_by'     => $createdBy->id,
            'reference'      => 'CON-' . strtoupper(Str::random(8)),
            'title'          => $isSale
                ? "Offer to Purchase — {$address}"
                : "Lease Agreement — {$address}",
            'type'           => $type,
            'status'         => 'fully_signed',
            'body'           => $body,
            'valid_from'     => now()->subDays(15)->toDateString(),
            'valid_until'    => now()->addDays(350)->toDateString(),
            'signatories'    => [
                ['name' => $buyer,       'role' => $isSale ? 'buyer'    : 'tenant', 'email' => $offer->contact?->email],
                ['name' => 'Demo Agency','role' => $isSale ? 'seller_agent' : 'landlord_agent', 'email' => 'demo@villacrm.app'],
            ],
            'signed_at'      => [
                ['name' => $buyer,        'signed_at' => now()->subDays(13)->toDateTimeString(), 'ip_address' => '105.112.'.rand(1,254).'.'.rand(1,254)],
                ['name' => 'Demo Agency', 'signed_at' => now()->subDays(12)->toDateTimeString(), 'ip_address' => '197.210.'.rand(1,254).'.'.rand(1,254)],
            ],
        ]);
    }

    private function saleAgreementBody(string $buyer, string $address, string $amount, string $occupation, string $conditions): string
    {
        return <<<EOT
OFFER TO PURCHASE

Date: {$this->today()}

PARTIES
Seller: Demo Agency (acting as agent for the registered owner)
Buyer: {$buyer}

PROPERTY
{$address}

PURCHASE PRICE
{$amount}

DEPOSIT
10% of purchase price payable within 3 business days of acceptance.

OCCUPATION DATE
{$occupation}

CONDITIONS
{$conditions}

GENERAL CONDITIONS
1. This offer is subject to the terms and conditions of the standard deed of sale.
2. The property is sold voetstoots (as is).
3. The seller warrants that all SPLUMA certificates and rates clearance will be provided.
4. This offer is irrevocable and binding upon the buyer for the period stated.

SIGNATURES
Buyer: ________________________ Date: ___________
Seller's Agent: _______________ Date: ___________
EOT;
    }

    private function leaseAgreementBody(string $tenant, string $address, string $monthlyRent, string $occupation, string $conditions): string
    {
        return <<<EOT
RESIDENTIAL LEASE AGREEMENT

Date: {$this->today()}

PARTIES
Landlord: Demo Agency (acting for registered owner)
Tenant: {$tenant}

PREMISES
{$address}

LEASE TERM
12 months commencing {$occupation}

MONTHLY RENTAL
{$monthlyRent} payable in advance on the 1st of each month.

DEPOSIT
Equivalent to 2 months rental, refundable subject to inspection at termination.

CONDITIONS
{$conditions}

GENERAL CONDITIONS
1. Tenant shall maintain the premises in good and clean condition.
2. No structural alterations without prior written consent of the landlord.
3. Subletting is strictly prohibited.
4. Breach of any term gives landlord the right to cancel with 20 business days notice.

SIGNATURES
Tenant: ______________________ Date: ___________
Landlord's Agent: ____________ Date: ___________
EOT;
    }

    private function today(): string
    {
        return now()->subDays(15)->format('d M Y');
    }
}
