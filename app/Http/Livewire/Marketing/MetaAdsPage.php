<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\ExternalServices\Meta\MetaAdsApiClient;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\MetaAdCampaign;
use Livewire\Component;

class MetaAdsPage extends Component
{
    public bool $showCreateForm = false;
    public string $name = '';
    public string $objective = 'lead_generation';
    public string $budget_daily = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $campaign_id = '';

    protected array $rules = [
        'name' => 'required|string|max:255',
        'objective' => 'required|in:lead_generation,brand_awareness,traffic,conversions',
        'budget_daily' => 'required|numeric|min:1',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after:start_date',
        'campaign_id' => 'nullable|exists:campaigns,id',
    ];

    public function createCampaign(MetaAdsApiClient $metaClient): void
    {
        $this->validate();

        $adCampaign = MetaAdCampaign::create([
            'agency_id'   => auth()->user()->agency_id,
            'campaign_id' => $this->campaign_id ?: null,
            'name'        => $this->name,
            'objective'   => $this->objective,
            'budget_daily' => $this->budget_daily,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date ?: null,
            'status'      => 'draft',
        ]);

        // Push to Meta Marketing API if credentials are configured
        $metaClient->createCampaign($adCampaign);

        $this->reset(['name', 'objective', 'budget_daily', 'start_date', 'end_date', 'campaign_id', 'showCreateForm']);
        $this->dispatch('notify', message: 'Meta Ad campaign created.', type: 'success');
    }

    public function updateStatus(int $adId, string $status): void
    {
        MetaAdCampaign::where('id', $adId)
            ->where('agency_id', auth()->user()->agency_id)
            ->update(['status' => $status]);
        $this->dispatch('notify', message: 'Status updated.', type: 'success');
    }

    public function deleteCampaign(int $adId): void
    {
        MetaAdCampaign::where('id', $adId)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $campaigns = MetaAdCampaign::with('campaign.listing.property')
            ->where('agency_id', $agencyId)
            ->latest()
            ->get();

        $stats = [
            'total_spend' => $campaigns->sum('spend'),
            'total_impressions' => $campaigns->sum('impressions'),
            'total_clicks' => $campaigns->sum('clicks'),
            'total_leads' => $campaigns->sum('leads'),
            'avg_cpl' => $campaigns->where('leads', '>', 0)->avg('cpl') ?? 0,
            'active' => $campaigns->where('status', 'active')->count(),
        ];

        $linkedCampaigns = Campaign::where('agency_id', $agencyId)->with('listing.property')->get();

        return view('livewire.marketing.meta-ads-page', compact('campaigns', 'stats', 'linkedCampaigns'))
            ->layout('layouts.app');
    }
}
