<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Application\Listing\Services\MlsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMlsListingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MlsSyncService $service): void
    {
        Log::info('Background MLS Sync starting.');
        $results = $service->syncAllListings();
        Log::info('Background MLS Sync completed.', ['synced_count' => count($results)]);
    }
}
