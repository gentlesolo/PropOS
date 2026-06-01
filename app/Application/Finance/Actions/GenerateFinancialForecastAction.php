<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Lease;
use Carbon\Carbon;

class GenerateFinancialForecastAction
{
    public function __construct(private readonly CalculateCashFlowAction $cashFlow) {}

    public function execute(
        int $months = 12,
        ?int $propertyId = null,
        float $vacancyRateOverride = 0,
        float $escalationOverride = 0,
    ): array {
        $now       = Carbon::now();
        $agencyId  = auth()->user()?->agency_id;

        // Build trailing 3-month average expenses for projection baseline
        $trailingExpenses = [];
        for ($i = 3; $i >= 1; $i--) {
            $past = $now->copy()->subMonths($i);
            $trailingExpenses[] = (float) Expense::where('period_month', $past->month)
                ->where('period_year', $past->year)
                ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
                ->whereIn('status', ['approved', 'paid'])
                ->sum('amount');
        }
        $avgMonthlyExpenses = count($trailingExpenses) > 0
            ? array_sum($trailingExpenses) / count($trailingExpenses)
            : 0;

        // Load active leases
        $leaseQuery = Lease::where('status', 'active');
        if ($propertyId) {
            $leaseQuery->whereHas('listing', fn ($q) => $q->where('property_id', $propertyId));
        }
        if ($agencyId) {
            $leaseQuery->where('agency_id', $agencyId);
        }
        $leases = $leaseQuery->get();

        $vacancyRate     = $vacancyRateOverride > 0 ? $vacancyRateOverride : 5.0;
        $cpiAdjustment   = 1.06;

        $projections = [];

        for ($i = 1; $i <= $months; $i++) {
            $target = $now->copy()->addMonths($i);
            $month  = $target->month;
            $year   = $target->year;

            $projectedIncome = 0;
            foreach ($leases as $lease) {
                $rent            = (float) $lease->monthly_rent;
                $escalationPct   = $escalationOverride > 0
                    ? $escalationOverride
                    : (float) ($lease->escalation_percent ?? 7.0);

                // Apply escalation if this month is the lease anniversary month
                $startMonth = $lease->start_date?->month;
                if ($startMonth === $month) {
                    $rent *= (1 + $escalationPct / 100);
                }

                $projectedIncome += $rent * (1 - $vacancyRate / 100);
            }

            $projectedExpenses = $avgMonthlyExpenses * $cpiAdjustment;

            $projections[] = [
                'month'               => $month,
                'year'                => $year,
                'label'               => $target->format('M Y'),
                'projected_income'    => round($projectedIncome, 2),
                'projected_expenses'  => round($projectedExpenses, 2),
                'projected_net'       => round($projectedIncome - $projectedExpenses, 2),
            ];
        }

        return $projections;
    }
}
