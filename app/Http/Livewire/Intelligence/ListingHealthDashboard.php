<?php

namespace App\Http\Livewire\Intelligence;

use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Viewing;
use Livewire\Component;

class ListingHealthDashboard extends Component
{
    public string $filterHealth = '';
    public string $search = '';

    public function getListingsProperty()
    {
        $listings = Listing::with(['property', 'media', 'agent'])
            ->where('agency_id', auth()->user()->agency_id)
            ->where('status', 'active')
            ->get();

        return $listings->map(function (Listing $listing) {
            $daysOnMarket = (int) now()->diffInDays($listing->mandate_start_date ?? $listing->created_at);

            // Real viewings count from the viewings table
            $viewingsCount = Viewing::where('listing_id', $listing->id)->count();
            $completedViewings = Viewing::where('listing_id', $listing->id)->where('status', 'completed')->count();

            // Photo count from listing_media
            $photoCount = $listing->media->count();

            // Health scoring: real signals
            $score = 100;
            $score -= min(40, $daysOnMarket * 1.2);       // Age penalty
            $score += min(20, $viewingsCount * 4);          // Viewing activity bonus
            $score += min(10, $photoCount * 2);             // Photo quality bonus
            if ($listing->headline) $score += 5;            // Has headline
            if ($listing->description_standard) $score += 5; // Has description
            $score = max(0, min(100, round($score)));

            // AI-style recommendation based on real signals
            if ($score < 50) {
                if ($daysOnMarket > 30 && $viewingsCount < 2) {
                    $rec = "High risk. Consider a 5–8% price reduction and run a targeted social campaign.";
                } elseif ($photoCount < 3) {
                    $rec = "Listing needs more photos. Poor media is reducing inquiry rates.";
                } else {
                    $rec = "Stale listing. Host an Open Day and refresh the portal descriptions.";
                }
            } elseif ($score < 80) {
                $rec = $viewingsCount > 0
                    ? "Moderate traction ({$viewingsCount} viewings). Boost with a sponsored social post."
                    : "Moderate listing health but no viewings yet. Syndicate to all portals.";
            } else {
                $rec = $completedViewings > 2
                    ? "High performing. {$completedViewings} completed viewings — a deal may close soon."
                    : "Performing well. Ensure all portals are synced and descriptions are up to date.";
            }

            $listing->health_score = $score;
            $listing->days_on_market = $daysOnMarket;
            $listing->viewings_count = $viewingsCount;
            $listing->photo_count = $photoCount;
            $listing->recommendation = $rec;

            return $listing;
        })
        ->when($this->filterHealth === 'at_risk', fn($c) => $c->filter(fn($l) => $l->health_score < 50))
        ->when($this->filterHealth === 'moderate', fn($c) => $c->filter(fn($l) => $l->health_score >= 50 && $l->health_score < 80))
        ->when($this->filterHealth === 'healthy', fn($c) => $c->filter(fn($l) => $l->health_score >= 80))
        ->when($this->search, fn($c) => $c->filter(fn($l) => str_contains(strtolower($l->property->address_line_1 ?? ''), strtolower($this->search))))
        ->sortBy('health_score');
    }

    public function render()
    {
        $listings = $this->listings;

        $summary = [
            'total' => $listings->count(),
            'avg_score' => $listings->count() ? round($listings->avg('health_score')) : 0,
            'avg_dom' => $listings->count() ? round($listings->avg('days_on_market')) : 0,
            'at_risk' => $listings->filter(fn($l) => $l->health_score < 50)->count(),
        ];

        return view('livewire.intelligence.listing-health-dashboard', compact('listings', 'summary'))
            ->layout('layouts.app');
    }
}
