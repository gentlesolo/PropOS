<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Viewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePerformanceNudgesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(NotificationService $notify): void
    {
        $agents = User::where('status', 'active')
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'senior_agent', 'branch_manager', 'principal']))
            ->get();

        foreach ($agents as $agent) {
            $this->analyseAgent($agent, $notify);
        }
    }

    private function analyseAgent(User $agent, NotificationService $notify): void
    {
        $agencyId = $agent->agency_id;

        // Nudge 1: Uncontacted high-intent leads
        $neglectedLeads = Contact::where('agency_id', $agencyId)
            ->where('assigned_agent_id', $agent->id)
            ->where('intent_score', '>=', 60)
            ->where(fn($q) => $q
                ->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subDays(5))
            )
            ->count();

        if ($neglectedLeads >= 3) {
            $notify->notifyUser(
                $agent,
                'performance_nudge',
                "You have {$neglectedLeads} high-intent leads waiting",
                "Leads contacted within 24 hours convert 3× more often. Review and reach out today.",
                route('crm.contacts'),
                'warning',
            );
        }

        // Nudge 2: Stale deals — no activity in 7 days
        $staleDeals = Deal::where('agency_id', $agencyId)
            ->where('assigned_agent_id', $agent->id)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        if ($staleDeals >= 2) {
            $notify->notifyUser(
                $agent,
                'performance_nudge',
                "{$staleDeals} deals have gone quiet",
                "You have {$staleDeals} active deals with no activity in 7+ days. Check in with your clients.",
                route('crm.pipeline'),
                'warning',
            );
        }

        // Nudge 3: Low viewing count this week
        $weeklyViewings = Viewing::where('agency_id', $agencyId)
            ->where('assigned_agent_id', $agent->id)
            ->where('scheduled_at', '>=', now()->startOfWeek())
            ->count();

        if ($weeklyViewings === 0 && now()->dayOfWeek >= 3) { // Mid-week with no viewings
            $notify->notifyUser(
                $agent,
                'performance_nudge',
                'No viewings scheduled this week',
                'You have no viewings booked this week. Follow up with your active buyers to schedule visits.',
                route('viewing.day'),
                'info',
            );
        }
    }
}
