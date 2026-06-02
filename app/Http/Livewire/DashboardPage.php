<?php

namespace App\Http\Livewire;

use App\Infrastructure\Persistence\Models\ComplianceReminder;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Inspection;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Tenancy\TenantResolver;
use Livewire\Component;

class DashboardPage extends Component
{
    const DEFAULT_WIDGETS = [
        'pipeline', 'active_listings', 'new_leads', 'hot_buyers',
        'occupancy_rate', 'maintenance_efficiency', 'compliance_overdue',
    ];

    public function render()
    {
        $resolver = app(TenantResolver::class);
        $agency   = $resolver->getCurrentAgency();
        $user     = auth()->user();
        $agencyId = $user->agency_id;

        $enabledWidgets = $user->dashboard_widgets ?? self::DEFAULT_WIDGETS;

        // Core metrics
        $activeListings    = Listing::where('status', 'active')->count();
        $totalPipelineValue = Listing::whereIn('status', ['active', 'under_offer'])->sum('listing_price');
        $newLeads          = Contact::where('status', 'new')->count();
        $hotBuyers         = Contact::where('type', 'buyer')->where('intent_score', '>=', 80)->count();

        // Occupancy rate: active leases / rental properties
        $rentalProperties = Property::where('agency_id', $agencyId)->count();
        $activeLeases     = Lease::where('agency_id', $agencyId)->where('status', 'active')->count();
        $occupancyRate    = $rentalProperties > 0 ? round(($activeLeases / $rentalProperties) * 100) : 0;

        // Maintenance efficiency: inspections completed on time (completed before or on scheduled_at)
        $totalInspections    = Inspection::where('agency_id', $agencyId)->where('status', 'completed')->count();
        $onTimeInspections   = Inspection::where('agency_id', $agencyId)
            ->where('status', 'completed')
            ->whereColumn('completed_at', '<=', 'scheduled_at')
            ->count();
        $maintenanceEfficiency = $totalInspections > 0 ? round(($onTimeInspections / $totalInspections) * 100) : 100;

        // Compliance overdue
        $complianceOverdue = ComplianceReminder::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->count();

        $metrics = [
            'total_pipeline'         => $totalPipelineValue,
            'active_listings'        => $activeListings,
            'new_leads'              => $newLeads,
            'hot_buyers'             => $hotBuyers,
            'occupancy_rate'         => $occupancyRate,
            'rental_properties'      => $rentalProperties,
            'active_leases'          => $activeLeases,
            'maintenance_efficiency' => $maintenanceEfficiency,
            'compliance_overdue'     => $complianceOverdue,
        ];

        $recentContacts = Contact::latest()->take(5)->get();
        $recentListings = Listing::with(['property', 'coverPhoto'])->latest()->take(4)->get();

        return view('livewire.dashboard-page', [
            'agency'         => $agency,
            'user'           => $user,
            'metrics'        => $metrics,
            'enabledWidgets' => $enabledWidgets,
            'allWidgets'     => self::DEFAULT_WIDGETS,
            'recentContacts' => $recentContacts,
            'recentListings' => $recentListings,
        ])->layout('layouts.app');
    }
}
