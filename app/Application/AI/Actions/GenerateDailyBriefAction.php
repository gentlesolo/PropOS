<?php

namespace App\Application\AI\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\DailyBrief;
use App\Infrastructure\Persistence\Models\Viewing;
use Carbon\Carbon;

class GenerateDailyBriefAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function execute(int $userId, int $agencyId): DailyBrief
    {
        $today = Carbon::today();

        // Pull real data
        $hotContacts = Contact::where('agency_id', $agencyId)
            ->where('intent_score', '>=', 70)
            ->where(fn($q) => $q->whereNull('last_contacted_at')->orWhere('last_contacted_at', '<', now()->subDays(3)))
            ->orderByDesc('intent_score')
            ->limit(3)
            ->get();

        $staleDeals = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->where('updated_at', '<', now()->subDays(7))
            ->with(['contact', 'stage'])
            ->limit(3)
            ->get();

        $todayViewings = Viewing::where('agency_id', $agencyId)
            ->where('assigned_agent_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->with(['contact', 'listing.property'])
            ->orderBy('scheduled_at')
            ->get();

        // Build priority actions from real data
        $priorityActions = [];

        foreach ($hotContacts as $contact) {
            $priorityActions[] = [
                'title' => "Follow up with {$contact->first_name} {$contact->last_name}",
                'type' => 'call',
                'context' => "Intent score: {$contact->intent_score}%. " . ($contact->last_contacted_at ? "Last contacted {$contact->last_contacted_at->diffForHumans()}." : "Never contacted."),
                'due_at' => Carbon::today()->setHour(10)->addMinutes(count($priorityActions) * 30)->toDateTimeString(),
                'completed' => false,
                'contact_id' => $contact->id,
            ];
        }

        foreach ($staleDeals as $deal) {
            $priorityActions[] = [
                'title' => "Re-engage deal: {$deal->title}",
                'type' => 'email',
                'context' => "Stuck in '{$deal->stage->name}' for " . $deal->updated_at->diffForHumans() . ". Momentum: {$deal->momentum_score}.",
                'due_at' => Carbon::today()->setHour(14)->addMinutes(count($priorityActions) * 20)->toDateTimeString(),
                'completed' => false,
                'deal_id' => $deal->id,
            ];
        }

        // Deal alerts
        $dealAlerts = [];
        foreach ($staleDeals as $deal) {
            $dealAlerts[] = [
                'title' => 'Stale Deal Alert',
                'property' => $deal->title,
                'message' => "This deal has had no updates for " . $deal->updated_at->diffInDays(now()) . " days. Consider reaching out to {$deal->contact?->first_name}.",
                'severity' => 'warning',
                'deal_id' => $deal->id,
            ];
        }

        // Viewing schedule
        $viewingSchedule = $todayViewings->map(fn($v) => [
            'time' => $v->scheduled_at->format('H:i'),
            'client' => "{$v->contact?->first_name} {$v->contact?->last_name}",
            'property' => $v->listing?->property?->address_line_1 ?? 'Property',
            'status' => $v->status,
            'viewing_id' => $v->id,
        ])->toArray();

        // Generate market snapshot with AI
        $hotContactCount = Contact::where('agency_id', $agencyId)->where('intent_score', '>=', 80)->count();
        $activeListings = \App\Infrastructure\Persistence\Models\Listing::where('agency_id', $agencyId)->where('status', 'active')->count();
        $dealsInPipeline = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->count();

        $marketSnapshot = $this->ai->generate(
            "You are a concise real estate intelligence assistant. Write a 2-sentence morning market snapshot for a real estate agent. Be encouraging, data-driven, and actionable.",
            "Agency stats: {$activeListings} active listings, {$dealsInPipeline} deals in pipeline, {$hotContactCount} hot buyers. Today: {$todayViewings->count()} viewings scheduled. Day: " . $today->format('l, F j')
        );

        return DailyBrief::updateOrCreate(
            ['agency_id' => $agencyId, 'user_id' => $userId, 'date' => $today],
            [
                'priority_actions' => $priorityActions,
                'deal_alerts' => $dealAlerts,
                'viewing_schedule' => $viewingSchedule,
                'market_snapshot' => $marketSnapshot,
                'is_read' => false,
            ]
        );
    }
}
