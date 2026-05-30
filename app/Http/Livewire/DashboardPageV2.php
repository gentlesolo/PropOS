<?php

namespace App\Http\Livewire;

use App\Infrastructure\Tenancy\TenantResolver;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use Livewire\Component;

class DashboardPageV2 extends Component
{
    public function render()
    {
        $resolver = app(TenantResolver::class);
        $agency = $resolver->getCurrentAgency();
        $user = auth()->user();

        // Real Metrics from Database
        $activeListings = Listing::where('status', 'active')->count();
        $totalPipelineValue = Listing::whereIn('status', ['active', 'under_offer'])->sum('listing_price');
        $newLeads = Contact::where('status', 'new')->count();
        $hotBuyers = Contact::where('type', 'buyer')->where('intent_score', '>=', 80)->count();

        // Recent Activity / Data
        $recentContacts = Contact::latest()->take(5)->get();
        $recentListings = Listing::with(['property', 'coverPhoto'])->latest()->take(4)->get();

        $metrics = [
            'active_listings' => $activeListings,
            'total_pipeline' => $totalPipelineValue,
            'new_leads' => $newLeads,
            'hot_buyers' => $hotBuyers,
        ];

        return view('livewire.dashboard-page-v2', [
            'agency' => $agency,
            'user' => $user,
            'metrics' => $metrics,
            'recentContacts' => $recentContacts,
            'recentListings' => $recentListings,
        ])->layout('layouts.app');
    }
}
