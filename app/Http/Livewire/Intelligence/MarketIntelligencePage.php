<?php

namespace App\Http\Livewire\Intelligence;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Viewing;
use Carbon\Carbon;
use Livewire\Component;

class MarketIntelligencePage extends Component
{
    public string $area = '';
    public string $propertyType = '';
    public string $reportPeriod = '30';
    public array $report = [];
    public bool $generatingReport = false;

    public function generateReport(AiCompletionServiceInterface $ai): void
    {
        $this->generatingReport = true;

        $agencyId = auth()->user()->agency_id;
        $since = Carbon::now()->subDays((int) $this->reportPeriod);

        // Pull real agency data
        $listings = Listing::with('property')
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->when($this->area, fn($q) => $q->whereHas('property', fn($p) => $p->where('city', 'like', "%{$this->area}%")))
            ->when($this->propertyType, fn($q) => $q->whereHas('property', fn($p) => $p->where('property_type', $this->propertyType)))
            ->get();

        $wonDeals = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', true))
            ->where('updated_at', '>=', $since)
            ->get();

        $viewingsCount = Viewing::where('agency_id', $agencyId)->where('created_at', '>=', $since)->count();
        $avgPrice = $listings->avg('listing_price') ?? 0;
        $avgDom = $listings->map(fn($l) => now()->diffInDays($l->mandate_start_date ?? $l->created_at))->avg() ?? 0;

        $dataContext = implode("\n", [
            "Market area: " . ($this->area ?: 'All areas'),
            "Property type: " . ($this->propertyType ?: 'All types'),
            "Period: last {$this->reportPeriod} days",
            "Active listings: {$listings->count()}",
            "Average listing price: ₦" . number_format($avgPrice),
            "Average days on market: " . round($avgDom) . " days",
            "Deals closed this period: {$wonDeals->count()}",
            "Total closed value: ₦" . number_format($wonDeals->sum('value')),
            "Viewings conducted: {$viewingsCount}",
        ]);

        $raw = $ai->generate(
            "You are a real estate market analyst. Write a structured market intelligence report. Return valid JSON with keys: summary (2 sentences), market_trends (array of 3 strings), pricing_insights (string), demand_outlook (string), recommendations (array of 3 strings).",
            "Generate a market report based on this agency data:\n\n{$dataContext}"
        );

        $parsed = json_decode($raw, true);

        $this->report = $parsed ?: [
            'summary' => "The {$this->area} market shows " . ($listings->count() > 5 ? 'strong' : 'moderate') . " listing activity over the past {$this->reportPeriod} days with {$listings->count()} active properties averaging ₦" . number_format($avgPrice / 1000000, 1) . "M.",
            'market_trends' => [
                "Average days on market: " . round($avgDom) . " days",
                "{$wonDeals->count()} deals closed with total value ₦" . number_format($wonDeals->sum('value') / 1000000, 1) . "M",
                "{$viewingsCount} viewings conducted — buyer interest is " . ($viewingsCount > 10 ? 'high' : 'moderate'),
            ],
            'pricing_insights' => "Average listing price is ₦" . number_format($avgPrice) . ". " . ($avgDom > 30 ? "Extended DOM suggests pricing pressure — consider value adjustments." : "Strong absorption rate indicates healthy pricing."),
            'demand_outlook' => $viewingsCount > 5 ? "Demand signals are positive. Expect continued activity in the coming weeks." : "Demand is building. Focus on increasing portal syndication and open days.",
            'recommendations' => [
                "Prioritise listings with DOM > 30 days for price reviews.",
                "Increase social media campaigns for " . ($this->propertyType ?: 'residential') . " properties.",
                "Schedule Open Days for high-value listings to accelerate closures.",
            ],
        ];

        $this->generatingReport = false;
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $quickStats = [
            'active_listings' => Listing::where('agency_id', $agencyId)->where('status', 'active')->count(),
            'deals_won_30d' => Deal::where('agency_id', $agencyId)
                ->whereHas('stage', fn($q) => $q->where('is_won', true))
                ->where('updated_at', '>=', now()->subDays(30))->count(),
            'viewings_30d' => Viewing::where('agency_id', $agencyId)->where('created_at', '>=', now()->subDays(30))->count(),
            'avg_price' => Listing::where('agency_id', $agencyId)->where('status', 'active')->avg('listing_price') ?? 0,
        ];

        $areas = Listing::where('agency_id', $agencyId)
            ->with('property')
            ->get()
            ->pluck('property.city')
            ->unique()
            ->filter()
            ->sort()
            ->values();

        return view('livewire.intelligence.market-intelligence-page', compact('quickStats', 'areas'))
            ->layout('layouts.app');
    }
}
