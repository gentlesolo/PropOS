<?php

namespace App\Http\Livewire\Intelligence;

use App\Infrastructure\Persistence\Models\ComplianceReminder;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\Inspection;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Property;
use Livewire\Component;

class PortfolioDashboardPage extends Component
{
    public int    $year;
    public string $riskFilter = '';

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function render()
    {
        $agencyId   = auth()->user()->agency_id;
        $properties = Property::where('agency_id', $agencyId)->get();

        $rows = $properties->map(function (Property $property) use ($agencyId) {
            $listingIds = $property->listings()->pluck('id');

            // Occupancy
            $hasActiveLease = Lease::where('agency_id', $agencyId)
                ->whereIn('listing_id', $listingIds)
                ->where('status', 'active')
                ->exists();

            // Revenue for year (paid invoices across listings)
            $revenue = Invoice::where('agency_id', $agencyId)
                ->whereIn('status', ['paid', 'partially_paid'])
                ->where('period_year', $this->year)
                ->whereHas('lease', fn ($q) => $q->whereIn('listing_id', $listingIds))
                ->sum('amount_paid');

            // Expenses for year
            $expenses = Expense::where('agency_id', $agencyId)
                ->where('property_id', $property->id)
                ->whereIn('status', ['approved', 'paid'])
                ->whereYear('expense_date', $this->year)
                ->sum('amount');

            // Monthly revenue trend (last 6 months)
            $trend = [];
            for ($m = 1; $m <= 12; $m++) {
                $trend[$m] = (float) Invoice::where('agency_id', $agencyId)
                    ->whereIn('status', ['paid', 'partially_paid'])
                    ->where('period_year', $this->year)
                    ->where('period_month', $m)
                    ->whereHas('lease', fn ($q) => $q->whereIn('listing_id', $listingIds))
                    ->sum('amount_paid');
            }

            // Risk flags
            $risks = [];

            $vacantDays = null;
            if (! $hasActiveLease) {
                $lastLease = Lease::where('agency_id', $agencyId)
                    ->whereIn('listing_id', $listingIds)
                    ->orderByDesc('end_date')
                    ->first();
                $vacantDays = $lastLease ? (int) now()->diffInDays($lastLease->end_date) : null;
                if ($vacantDays === null || $vacantDays > 30) {
                    $risks[] = 'vacant';
                }
            }

            $overdueInspection = Inspection::where('agency_id', $agencyId)
                ->whereIn('listing_id', $listingIds)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<', now())
                ->exists();
            if ($overdueInspection) $risks[] = 'overdue_inspection';

            $expiringLease = Lease::where('agency_id', $agencyId)
                ->whereIn('listing_id', $listingIds)
                ->where('status', 'active')
                ->where('end_date', '<=', now()->addDays(60))
                ->exists();
            if ($expiringLease) $risks[] = 'expiring_lease';

            $overdueCompliance = ComplianceReminder::where('agency_id', $agencyId)
                ->where('status', 'overdue')
                ->where('related_type', 'App\\Infrastructure\\Persistence\\Models\\Property')
                ->where('related_id', $property->id)
                ->exists();
            if ($overdueCompliance) $risks[] = 'compliance';

            return [
                'property'   => $property,
                'occupied'   => $hasActiveLease,
                'revenue'    => (float) $revenue,
                'expenses'   => (float) $expenses,
                'noi'        => (float) $revenue - (float) $expenses,
                'trend'      => $trend,
                'risks'      => $risks,
            ];
        });

        if ($this->riskFilter === 'at_risk') {
            $rows = $rows->filter(fn ($r) => count($r['risks']) > 0);
        } elseif ($this->riskFilter === 'vacant') {
            $rows = $rows->filter(fn ($r) => ! $r['occupied']);
        } elseif ($this->riskFilter === 'occupied') {
            $rows = $rows->filter(fn ($r) => $r['occupied']);
        }

        $summary = [
            'total_properties' => $properties->count(),
            'occupied'         => $rows->where('occupied', true)->count(),
            'total_revenue'    => $rows->sum('revenue'),
            'total_expenses'   => $rows->sum('expenses'),
            'total_noi'        => $rows->sum('noi'),
            'at_risk'          => $rows->filter(fn ($r) => count($r['risks']) > 0)->count(),
        ];
        $summary['occupancy_rate'] = $summary['total_properties'] > 0
            ? round(($summary['occupied'] / $summary['total_properties']) * 100)
            : 0;

        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        return view('livewire.intelligence.portfolio-dashboard-page', compact('rows', 'summary', 'months'))
            ->layout('layouts.app');
    }
}
