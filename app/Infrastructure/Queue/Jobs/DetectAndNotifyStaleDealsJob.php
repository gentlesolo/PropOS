<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Application\CRM\Actions\CalculateDealMomentumAction;
use App\Application\CRM\Actions\DetectStaleDealsAction;
use App\Infrastructure\Persistence\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DetectAndNotifyStaleDealsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(
        DetectStaleDealsAction $detectAction,
        CalculateDealMomentumAction $momentumAction,
    ): void {
        $agencies = Agency::all();

        foreach ($agencies as $agency) {
            $staleDeals = $detectAction->execute($agency->id, staleAfterDays: 14);

            foreach ($staleDeals as $deal) {
                // Recalculate and persist the momentum score
                $momentumAction->execute($deal);

                // Notify the assigned agent
                $agent = $deal->agent;
                if ($agent?->email) {
                    $this->notifyAgent($deal, $agent);
                }

                Log::info('Stale deal detected', [
                    'deal_id'    => $deal->id,
                    'title'      => $deal->title,
                    'agency_id'  => $agency->id,
                    'stale_days' => $deal->updated_at->diffInDays(now()),
                ]);
            }
        }
    }

    private function notifyAgent($deal, $agent): void
    {
        $staleDays = $deal->updated_at->diffInDays(now());
        $contactName = $deal->contact?->full_name ?? 'the client';
        $stageName = $deal->stage?->name ?? 'current stage';

        $body = implode("\n\n", [
            "Hi {$agent->name},",
            "This is an automated alert for a deal that has gone quiet:",
            "Deal: {$deal->title}",
            "Client: {$contactName}",
            "Stage: {$stageName}",
            "Last activity: {$staleDays} days ago",
            "Momentum score: {$deal->momentum_score}/100",
            "Please log an activity or move this deal forward to keep it on track.",
        ]);

        try {
            Mail::raw($body, fn($m) => $m
                ->to($agent->email, $agent->name)
                ->subject("Stale deal alert: {$deal->title}")
            );
        } catch (\Exception $e) {
            Log::error('Stale deal notification failed', [
                'deal_id' => $deal->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
