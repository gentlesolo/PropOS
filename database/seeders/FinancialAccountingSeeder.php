<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Budget;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\InvoiceLineItem;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\PaymentMandate;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\RentPayment;
use App\Infrastructure\Persistence\Models\TaxConfig;
use App\Infrastructure\Persistence\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinancialAccountingSeeder extends Seeder
{
    public function run(): void
    {
        $agency    = Agency::where('slug', 'demo')->firstOrFail();
        $agent     = User::where('email', 'agent@villacrm.app')->firstOrFail();
        $principal = User::where('email', 'principal@villacrm.app')->firstOrFail();

        // ── 1. Tax Configurations ──────────────────────────────────────────────
        $this->seedTaxConfigs($agency->id);

        // ── 2. Invoices from lease payment history ────────────────────────────
        $this->seedInvoices($agency->id);

        // ── 3. Expenses ───────────────────────────────────────────────────────
        $this->seedExpenses($agency->id, $principal->id);

        // ── 4. Annual Budget ──────────────────────────────────────────────────
        $this->seedBudget($agency->id, $principal->id);

        // ── 5. Payment Mandates ───────────────────────────────────────────────
        $this->seedMandates($agency->id);
    }

    // ── Tax Configs ────────────────────────────────────────────────────────────

    private function seedTaxConfigs(int $agencyId): void
    {
        // Nigerian residential leases are VAT-exempt
        TaxConfig::firstOrCreate(
            ['agency_id' => $agencyId, 'name' => 'Residential — VAT Exempt'],
            [
                'tax_type'   => 'vat',
                'rate'       => 0.00,
                'applies_to' => 'residential',
                'is_default' => true,
                'is_active'  => true,
            ]
        );

        // Commercial spaces attract 7.5% VAT (Nigeria Finance Act 2019)
        TaxConfig::firstOrCreate(
            ['agency_id' => $agencyId, 'name' => 'Commercial — VAT 7.5%'],
            [
                'tax_type'   => 'vat',
                'rate'       => 7.50,
                'applies_to' => 'commercial',
                'is_default' => false,
                'is_active'  => true,
            ]
        );

        // Withholding Tax on rent (10% for corporate tenants under FIRS rules)
        TaxConfig::firstOrCreate(
            ['agency_id' => $agencyId, 'name' => 'WHT on Rent — Corporate 10%'],
            [
                'tax_type'   => 'withholding',
                'rate'       => 10.00,
                'applies_to' => 'all',
                'is_default' => false,
                'is_active'  => true,
            ]
        );
    }

    // ── Invoices ───────────────────────────────────────────────────────────────

    private function seedInvoices(int $agencyId): void
    {
        $leases = Lease::where('agency_id', $agencyId)
            ->whereIn('status', ['active', 'expiring_soon', 'terminated'])
            ->with(['tenant', 'rentPayments'])
            ->get();

        foreach ($leases as $lease) {
            $this->buildInvoicesForLease($lease, $agencyId);
        }
    }

    private function buildInvoicesForLease(Lease $lease, int $agencyId): void
    {
        $startDate   = Carbon::parse($lease->start_date);
        $today       = Carbon::now();
        $monthlyRent = (float) $lease->monthly_rent;
        $cursor      = $startDate->copy()->startOfMonth();

        while ($cursor->lte($today)) {
            $month = $cursor->month;
            $year  = $cursor->year;

            // Skip if already seeded
            if (Invoice::where('lease_id', $lease->id)->where('period_month', $month)->where('period_year', $year)->exists()) {
                $cursor->addMonth();
                continue;
            }

            $payDay  = min((int) ($lease->payment_day ?? 1), $cursor->daysInMonth);
            $dueDate = $cursor->copy()->day($payDay);

            // Find the matching RentPayment if it exists
            $rentPayment = $lease->rentPayments
                ->where('due_date', $dueDate->toDateString())
                ->first();

            $status     = $this->deriveInvoiceStatus($dueDate, $today, $rentPayment);
            $amountPaid = $rentPayment ? (float) ($rentPayment->amount_paid ?? 0) : 0;
            $paidAt     = ($status === 'paid' && $rentPayment?->paid_date)
                ? Carbon::parse($rentPayment->paid_date)
                : null;

            $invoice = Invoice::create([
                'agency_id'    => $agencyId,
                'lease_id'     => $lease->id,
                'tenant_id'    => $lease->tenant_id,
                'reference'    => 'INV-' . strtoupper(Str::random(8)),
                'type'         => 'rent',
                'status'       => $status,
                'subtotal'     => $monthlyRent,
                'tax_amount'   => 0,
                'total'        => $monthlyRent,
                'amount_paid'  => $amountPaid,
                'due_date'     => $dueDate->toDateString(),
                'issued_at'    => $dueDate->copy()->subDays(5),
                'paid_at'      => $paidAt,
                'period_month' => $month,
                'period_year'  => $year,
                'payment_gateway' => $amountPaid > 0 ? 'manual' : null,
            ]);

            $monthName = $cursor->format('F Y');

            InvoiceLineItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Monthly Rent — {$monthName}",
                'category'    => 'rent',
                'quantity'    => 1,
                'unit_price'  => $monthlyRent,
                'amount'      => $monthlyRent,
                'is_taxable'  => false,
            ]);

            // Add a late fee line item to overdue invoices where tenant has missed 2+ weeks
            if ($status === 'overdue' && $dueDate->diffInDays($today) > 14) {
                $fee = max(50_000, round($monthlyRent * 0.05, 2)); // 5% or ₦50k min
                InvoiceLineItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => 'Late Payment Fee',
                    'category'    => 'late_fee',
                    'quantity'    => 1,
                    'unit_price'  => $fee,
                    'amount'      => $fee,
                    'is_taxable'  => false,
                ]);

                $invoice->update([
                    'subtotal' => $monthlyRent + $fee,
                    'total'    => $monthlyRent + $fee,
                ]);
            }

            $cursor->addMonth();
        }
    }

    private function deriveInvoiceStatus(Carbon $dueDate, Carbon $today, $rentPayment): string
    {
        if (! $rentPayment) {
            return $dueDate->gt($today) ? 'sent' : 'overdue';
        }

        return match ($rentPayment->status) {
            'paid'    => 'paid',
            'partial' => 'partially_paid',
            'overdue' => 'overdue',
            'pending' => $dueDate->gt($today) ? 'sent' : 'overdue',
            'waived'  => 'void',
            default   => 'sent',
        };
    }

    // ── Expenses ───────────────────────────────────────────────────────────────

    private function seedExpenses(int $agencyId, int $approverId): void
    {
        $properties = Property::where('agency_id', $agencyId)->get();

        $expenseTemplates = [
            // Maintenance
            ['vendor' => 'Landmark Properties & Facilities Mgmt', 'category' => 'maintenance',     'desc' => 'Quarterly plumbing inspection and repairs — Block A',       'amount' => 185_000, 'months_ago' => 5, 'status' => 'paid'],
            ['vendor' => 'AES Atros Electrical Solutions',        'category' => 'maintenance',     'desc' => 'Generator servicing and diesel refill — 14A Bourdillon',    'amount' => 95_000,  'months_ago' => 4, 'status' => 'paid'],
            ['vendor' => 'Broll Nigeria Ltd',                     'category' => 'maintenance',     'desc' => 'Air conditioning units full service and regas',             'amount' => 240_000, 'months_ago' => 3, 'status' => 'paid'],
            ['vendor' => 'Dansol Maintenance Services',           'category' => 'maintenance',     'desc' => 'Perimeter fence repair and repainting — Victoria Island',   'amount' => 320_000, 'months_ago' => 2, 'status' => 'approved'],
            ['vendor' => 'QuickFix Handyman Services',            'category' => 'maintenance',     'desc' => 'Door locks replacement and internal painting touch-up',     'amount' => 78_000,  'months_ago' => 1, 'status' => 'approved'],
            ['vendor' => 'Eko Lifts & Escalators',               'category' => 'maintenance',     'desc' => 'Elevator annual maintenance contract renewal',              'amount' => 550_000, 'months_ago' => 0, 'status' => 'pending'],

            // Utilities
            ['vendor' => 'Eko Electricity Distribution Company',  'category' => 'utilities',       'desc' => 'EKEDC electricity bill — Ikoyi cluster Q1',                'amount' => 210_000, 'months_ago' => 5, 'status' => 'paid'],
            ['vendor' => 'Lagos Water Corporation',               'category' => 'utilities',       'desc' => 'Water rates — Q1 2026 Victoria Island portfolio',          'amount' => 85_000,  'months_ago' => 4, 'status' => 'paid'],
            ['vendor' => 'Eko Electricity Distribution Company',  'category' => 'utilities',       'desc' => 'EKEDC electricity bill — Ikoyi cluster Q2',                'amount' => 198_500, 'months_ago' => 2, 'status' => 'paid'],
            ['vendor' => 'Abuja Electricity Distribution Company','category' => 'utilities',       'desc' => 'AEDC electricity bill — Asokoro & Maitama properties',     'amount' => 145_000, 'months_ago' => 1, 'status' => 'approved'],

            // Insurance
            ['vendor' => 'Leadway Assurance Company Ltd',         'category' => 'insurance',       'desc' => 'Annual property insurance premium — full portfolio',        'amount' => 1_250_000,'months_ago' => 6,'status' => 'paid'],
            ['vendor' => 'AXA Mansard Insurance PLC',             'category' => 'insurance',       'desc' => 'Landlord liability insurance renewal — Lekki properties',  'amount' => 380_000, 'months_ago' => 1, 'status' => 'approved'],

            // Municipal Rates
            ['vendor' => 'Lagos State Internal Revenue Service',  'category' => 'municipal_rates', 'desc' => 'Annual land use charge — Lagos Island portfolio',           'amount' => 750_000, 'months_ago' => 5, 'status' => 'paid'],
            ['vendor' => 'FCT Abuja Municipal Area Council',      'category' => 'municipal_rates', 'desc' => 'Development levy — Maitama & Asokoro',                     'amount' => 420_000, 'months_ago' => 4, 'status' => 'paid'],

            // Management Fees
            ['vendor' => 'Demo Agency Property Management',       'category' => 'management_fee',  'desc' => 'Property management fee Q1 2026 (8% of rent collected)',   'amount' => 485_000, 'months_ago' => 3, 'status' => 'paid'],
            ['vendor' => 'Demo Agency Property Management',       'category' => 'management_fee',  'desc' => 'Property management fee Q2 2026 (8% of rent collected)',   'amount' => 512_000, 'months_ago' => 0, 'status' => 'pending'],

            // Legal
            ['vendor' => 'Aluko & Oyebode Legal Practitioners',  'category' => 'legal',           'desc' => 'Lease agreement drafting and review — 6 new leases',       'amount' => 680_000, 'months_ago' => 7, 'status' => 'paid'],
            ['vendor' => 'Templars Barristers & Solicitors',      'category' => 'legal',           'desc' => 'Tenant dispute mediation — Lekki Phase 1 unit',           'amount' => 195_000, 'months_ago' => 2, 'status' => 'paid'],

            // Advertising
            ['vendor' => 'PropertyPro Nigeria',                   'category' => 'advertising',     'desc' => 'Premium listing placement — 3 months digital advertising', 'amount' => 150_000, 'months_ago' => 4, 'status' => 'paid'],
            ['vendor' => 'Private Property Nigeria',              'category' => 'advertising',     'desc' => 'Sponsored listings package — Q2 2026',                    'amount' => 120_000, 'months_ago' => 1, 'status' => 'approved'],

            // Cleaning
            ['vendor' => 'Sparkle Clean Services Ltd',            'category' => 'cleaning',        'desc' => 'Monthly deep-clean service — common areas all properties', 'amount' => 95_000,  'months_ago' => 2, 'status' => 'paid'],
            ['vendor' => 'Sparkle Clean Services Ltd',            'category' => 'cleaning',        'desc' => 'Monthly deep-clean service — common areas all properties', 'amount' => 95_000,  'months_ago' => 1, 'status' => 'paid'],
            ['vendor' => 'Sparkle Clean Services Ltd',            'category' => 'cleaning',        'desc' => 'Monthly deep-clean service — common areas all properties', 'amount' => 95_000,  'months_ago' => 0, 'status' => 'pending'],
        ];

        foreach ($expenseTemplates as $tpl) {
            $expenseDate = now()->subMonths($tpl['months_ago'])->day(rand(5, 25));
            $property    = $properties->isNotEmpty() ? $properties->random() : null;

            $existing = Expense::where('agency_id', $agencyId)
                ->where('vendor_name', $tpl['vendor'])
                ->where('description', $tpl['desc'])
                ->exists();

            if ($existing) {
                continue;
            }

            Expense::create([
                'agency_id'        => $agencyId,
                'property_id'      => $property?->id,
                'reference'        => 'EXP-' . strtoupper(Str::random(8)),
                'category'         => $tpl['category'],
                'vendor_name'      => $tpl['vendor'],
                'description'      => $tpl['desc'],
                'amount'           => $tpl['amount'],
                'tax_amount'       => 0,
                'is_tax_deductible'=> true,
                'expense_date'     => $expenseDate->toDateString(),
                'status'           => $tpl['status'],
                'period_month'     => (int) $expenseDate->format('m'),
                'period_year'      => (int) $expenseDate->format('Y'),
                'approved_by'      => in_array($tpl['status'], ['approved', 'paid']) ? $approverId : null,
                'approved_at'      => in_array($tpl['status'], ['approved', 'paid']) ? $expenseDate->copy()->addDays(2) : null,
            ]);
        }
    }

    // ── Budget ─────────────────────────────────────────────────────────────────

    private function seedBudget(int $agencyId, int $approverId): void
    {
        if (Budget::where('agency_id', $agencyId)->where('year', 2026)->exists()) {
            return;
        }

        // ₦ monthly income target = total active monthly rents
        $totalRent = (float) Lease::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->sum('monthly_rent');

        // Assume 5% vacancy cushion built into targets
        $incomeTarget   = round($totalRent * 0.95, -3);

        // Monthly expense targets based on historical average (~₦800k/month for portfolio)
        $expenseTargets = [820_000, 750_000, 810_000, 680_000, 920_000, 780_000,
                           810_000, 760_000, 840_000, 720_000, 890_000, 950_000];

        // Income ramps slightly as leases are renewed with escalations
        $incomeTargets = [];
        for ($m = 0; $m < 12; $m++) {
            $escalation     = $m >= 6 ? 1.075 : 1.0;
            $incomeTargets[] = round($incomeTarget * $escalation, -3);
        }

        Budget::create([
            'agency_id'               => $agencyId,
            'property_id'             => null, // portfolio-level
            'year'                    => 2026,
            'name'                    => 'FY2026 Portfolio Budget',
            'status'                  => 'approved',
            'monthly_income_targets'  => $incomeTargets,
            'monthly_expense_targets' => $expenseTargets,
            'vacancy_rate_assumption' => 5.00,
            'escalation_assumption'   => 7.50,
            'approved_by'             => $approverId,
            'approved_at'             => now()->startOfYear()->addDays(14),
            'notes'                   => 'Approved by principal. Assumptions: 5% vacancy, 7.5% annual escalation, Q3 major maintenance provision.',
        ]);
    }

    // ── Payment Mandates ───────────────────────────────────────────────────────

    private function seedMandates(int $agencyId): void
    {
        $activeLeases = Lease::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->get();

        foreach ($activeLeases as $lease) {
            if (PaymentMandate::where('lease_id', $lease->id)->exists()) {
                continue;
            }

            $collectionDay = (int) ($lease->payment_day ?? 1);
            $nextCollection = now()->day($collectionDay);
            if ($nextCollection->isPast()) {
                $nextCollection->addMonth();
            }

            PaymentMandate::create([
                'agency_id'            => $agencyId,
                'lease_id'             => $lease->id,
                'tenant_id'            => $lease->tenant_id,
                'gateway'              => 'payfast',
                'gateway_mandate_id'   => 'PF-MANDATE-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'status'               => 'active',
                'collection_day'       => $collectionDay,
                'amount'               => $lease->monthly_rent,
                'last_collected_at'    => now()->subMonth()->day($collectionDay),
                'next_collection_date' => $nextCollection->toDateString(),
            ]);
        }
    }
}
