<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\EmailAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAllEmailAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        EmailAccount::where('is_active', true)
            ->whereNotNull('imap_host')
            ->chunk(20, function ($accounts) {
                foreach ($accounts as $account) {
                    SyncEmailAccountJob::dispatch($account->id);
                }
            });
    }
}
