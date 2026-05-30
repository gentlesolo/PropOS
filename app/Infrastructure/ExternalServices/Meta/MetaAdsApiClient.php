<?php

namespace App\Infrastructure\ExternalServices\Meta;

use App\Infrastructure\Persistence\Models\MetaAdCampaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsApiClient
{
    private string $baseUrl = 'https://graph.facebook.com/v19.0';
    private ?string $accessToken;
    private ?string $adAccountId;

    public function __construct()
    {
        $this->accessToken = config('services.meta.access_token');
        $this->adAccountId = config('services.meta.ad_account_id');
    }

    /**
     * Push a locally-created MetaAdCampaign to the Meta Marketing API.
     * Returns the external campaign ID on success, null if not configured.
     */
    public function createCampaign(MetaAdCampaign $campaign): ?string
    {
        if (! $this->isConfigured()) {
            Log::info('Meta Ads: API not configured, campaign stored locally only.', ['campaign_id' => $campaign->id]);
            return null;
        }

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/act_{$this->adAccountId}/campaigns", [
                'name'            => $campaign->name,
                'objective'       => $this->mapObjective($campaign->objective),
                'status'          => 'PAUSED',
                'special_ad_categories' => [],
            ]);

        if ($response->successful()) {
            $externalId = $response->json('id');
            $campaign->update(['external_campaign_id' => $externalId]);
            Log::info('Meta Ads: campaign created', ['local_id' => $campaign->id, 'meta_id' => $externalId]);
            return $externalId;
        }

        Log::error('Meta Ads: createCampaign failed', [
            'campaign_id' => $campaign->id,
            'status'      => $response->status(),
            'body'        => $response->body(),
        ]);

        return null;
    }

    /**
     * Update campaign status on Meta (ACTIVE / PAUSED / DELETED).
     */
    public function updateStatus(MetaAdCampaign $campaign, string $metaStatus): bool
    {
        if (! $this->isConfigured() || ! $campaign->external_campaign_id) {
            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$campaign->external_campaign_id}", [
                'status' => strtoupper($metaStatus),
            ]);

        return $response->successful();
    }

    /**
     * Pull the latest spend/impression/click/lead metrics from Meta Insights
     * for one campaign and update the local record.
     */
    public function syncInsights(MetaAdCampaign $campaign): bool
    {
        if (! $this->isConfigured() || ! $campaign->external_campaign_id) {
            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$campaign->external_campaign_id}/insights", [
                'fields' => 'spend,impressions,clicks,actions,cpm,cpc',
                'date_preset' => 'lifetime',
            ]);

        if (! $response->successful()) {
            Log::warning('Meta Ads: insights fetch failed', [
                'campaign_id' => $campaign->id,
                'status'      => $response->status(),
            ]);
            return false;
        }

        $data = $response->json('data.0') ?? [];

        $leads = collect($data['actions'] ?? [])
            ->firstWhere('action_type', 'lead')['value'] ?? 0;

        $spend      = (float) ($data['spend'] ?? 0);
        $clicks     = (int)   ($data['clicks'] ?? 0);
        $impressions = (int)  ($data['impressions'] ?? 0);
        $cpl        = $leads > 0 ? round($spend / $leads, 2) : null;

        $campaign->update([
            'spend'       => $spend,
            'clicks'      => $clicks,
            'impressions' => $impressions,
            'leads'       => (int) $leads,
            'cpl'         => $cpl,
        ]);

        return true;
    }

    /**
     * Sync insights for all active/paused local campaigns that have a Meta ID.
     * Called by the scheduler.
     */
    public function syncAllInsights(): void
    {
        $campaigns = MetaAdCampaign::whereNotNull('external_campaign_id')
            ->whereIn('status', ['active', 'paused'])
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                $this->syncInsights($campaign);
            } catch (\Throwable $e) {
                Log::error('Meta Ads: syncInsights exception', [
                    'campaign_id' => $campaign->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }

    private function mapObjective(string $local): string
    {
        return match ($local) {
            'lead_generation'  => 'LEAD_GENERATION',
            'brand_awareness'  => 'BRAND_AWARENESS',
            'traffic'          => 'LINK_CLICKS',
            'conversions'      => 'CONVERSIONS',
            default            => 'LINK_CLICKS',
        };
    }

    private function isConfigured(): bool
    {
        return ! empty($this->accessToken) && ! empty($this->adAccountId);
    }
}
