<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\ExternalServices\Meta\MetaAdsApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMetaAdsInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(MetaAdsApiClient $client): void
    {
        $client->syncAllInsights();
    }
}
