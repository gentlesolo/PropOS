<?php

namespace App\Application\AI\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\DailyBrief;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\Viewing;
use Carbon\Carbon;

class GenerateDailyBriefAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function execute(int $userId, int $agencyId): DailyBrief
    {
        $today = Carbon::today();

        // ── Pull real data ────────────────────────────────────────────────
        $hotContacts = Contact::where('agency_id', $agencyId)
            ->where('intent_score', '>=', 70)
            ->where(fn($q) => $q->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subDays(3)))
            ->orderByDesc('intent_score')
            ->limit(5)
            ->get();

        $staleDeals = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->where('updated_at', '<', now()->subDays(7))
            ->with(['contact', 'stage'])
            ->limit(4)
            ->get();

        $todayViewings = Viewing::where('agency_id', $agencyId)
            ->where('assigned_agent_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->with(['contact', 'listing.property'])
            ->orderBy('scheduled_at')
            ->get();

        $overdueTasks = Task::where('agency_id', $agencyId)
            ->where('assigned_to', $userId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('due_at', '<', now())
            ->orderBy('due_at')
            ->limit(3)
            ->get();

        $todayTasks = Task::where('agency_id', $agencyId)
            ->where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereDate('due_at', $today)
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->limit(5)
            ->get();

        // ── Build priority actions ────────────────────────────────────────
        $priorityActions = [];

        foreach ($overdueTasks as $task) {
            $priorityActions[] = [
                'title'       => $task->title,
                'type'        => $task->type,
                'context'     => 'OVERDUE — was due ' . $task->due_at->diffForHumans() . '. ' . ($task->description ?? ''),
                'due_at'      => now()->toDateTimeString(),
                'completed'   => false,
                'is_overdue'  => true,
                'priority'    => 'urgent',
                'task_id'     => $task->id,
            ];
        }

        foreach ($hotContacts->take(3) as $contact) {
            $priorityActions[] = [
                'title'      => "Follow up with {$contact->first_name} {$contact->last_name}",
                'type'       => 'call',
                'context'    => "Intent score: {$contact->intent_score}%. "
                    . ($contact->last_contacted_at
                        ? "Last contacted {$contact->last_contacted_at->diffForHumans()}."
                        : "Never contacted."),
                'due_at'     => Carbon::today()->setHour(10)->addMinutes(count($priorityActions) * 30)->toDateTimeString(),
                'completed'  => false,
                'is_overdue' => false,
                'priority'   => $contact->intent_score >= 90 ? 'urgent' : 'high',
                'contact_id' => $contact->id,
            ];
        }

        foreach ($staleDeals->take(2) as $deal) {
            $priorityActions[] = [
                'title'      => "Re-engage deal: {$deal->title}",
                'type'       => 'email',
                'context'    => "Stuck in '{$deal->stage->name}' for " . $deal->updated_at->diffForHumans() . ". Momentum: {$deal->momentum_score}.",
                'due_at'     => Carbon::today()->setHour(14)->addMinutes(count($priorityActions) * 20)->toDateTimeString(),
                'completed'  => false,
                'is_overdue' => false,
                'priority'   => 'medium',
                'deal_id'    => $deal->id,
            ];
        }

        foreach ($todayTasks as $task) {
            $priorityActions[] = [
                'title'      => $task->title,
                'type'       => $task->type,
                'context'    => $task->description ?? 'Scheduled for today.',
                'due_at'     => $task->due_at->toDateTimeString(),
                'completed'  => $task->status === 'completed',
                'is_overdue' => false,
                'priority'   => $task->priority,
                'task_id'    => $task->id,
            ];
        }

        // ── Deal alerts ───────────────────────────────────────────────────
        $dealAlerts = [];
        foreach ($staleDeals as $deal) {
            $days = $deal->updated_at->diffInDays(now());
            $dealAlerts[] = [
                'title'    => $days > 14 ? 'Critical: Deal Stalled' : 'Stale Deal Alert',
                'property' => $deal->title,
                'message'  => "No activity for {$days} days. "
                    . ($deal->contact ? "Consider reaching out to {$deal->contact->first_name}." : ''),
                'severity' => $days > 14 ? 'critical' : 'warning',
                'deal_id'  => $deal->id,
                'value'    => $deal->value,
            ];
        }

        // ── Viewing schedule ──────────────────────────────────────────────
        $viewingSchedule = $todayViewings->map(fn($v) => [
            'time'       => $v->scheduled_at->format('H:i'),
            'client'     => "{$v->contact?->first_name} {$v->contact?->last_name}",
            'property'   => $v->listing?->property?->address_line_1 ?? 'Property',
            'status'     => $v->status,
            'viewing_id' => $v->id,
        ])->toArray();

        // ── Aggregate stats for AI prompts ────────────────────────────────
        $hotContactCount  = Contact::where('agency_id', $agencyId)->where('intent_score', '>=', 80)->count();
        $activeListings   = Listing::where('agency_id', $agencyId)->where('status', 'active')->count();
        $dealsInPipeline  = Deal::where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->count();

        $context = implode('. ', array_filter([
            "{$activeListings} active listings",
            "{$dealsInPipeline} deals in pipeline",
            "{$hotContactCount} hot buyers (80+ intent score)",
            $hotContacts->count() . " contacts need follow-up today",
            $staleDeals->count() . " stale deals need attention",
            $overdueTasks->count() > 0 ? "{$overdueTasks->count()} overdue tasks" : null,
            $todayViewings->count() . " viewings scheduled today",
            "Day: " . $today->format('l, F j'),
        ]));

        // ── AI calls ──────────────────────────────────────────────────────
        $marketSnapshot = $this->ai->generate(
            "You are a concise real estate intelligence assistant. Write a 2-sentence morning market snapshot for a real estate agent. Be encouraging, data-driven, and actionable.",
            $context
        );

        $aiSummary = $this->ai->generate(
            "You are a real estate agent's personal AI assistant. Write a 2–3 sentence executive briefing capturing the key priorities and mood for the agent's day. Be direct, motivating, and specific to the data.",
            $context
        );

        $coachingJson = $this->ai->generate(
            "You are a high-performance real estate coach. Based on the agent's data, provide exactly 3 personalized coaching tips. Respond ONLY with a valid JSON array — no explanation, no markdown: [{\"category\":\"category_name\",\"tip\":\"actionable tip text under 20 words\",\"icon\":\"single emoji\"}]. Categories: prospecting, pipeline, mindset, skills, time_management.",
            $context
        );

        $goalsJson = $this->ai->generate(
            "You are a real estate performance coach. Based on the agent's data, suggest exactly 3 specific, achievable daily goals. Respond ONLY with a valid JSON array — no explanation, no markdown: [{\"title\":\"goal title\",\"target\":number,\"unit\":\"unit label\",\"current\":0,\"completed\":false}]. Units: calls, emails, deals, contacts, tasks, viewings.",
            $context
        );

        $coachingTips = $this->parseJson($coachingJson, [
            ['category' => 'mindset',      'tip' => 'Start your day with your 3 most important calls before opening email.',    'icon' => '🎯'],
            ['category' => 'pipeline',     'tip' => 'Touch every stale deal with a quick value-add message today.',             'icon' => '⚡'],
            ['category' => 'prospecting',  'tip' => 'Hot leads go cold in 48 hours — prioritise your top intent scores first.', 'icon' => '🔥'],
        ]);

        $goals = $this->parseJson($goalsJson, [
            ['title' => 'Follow-up calls',  'target' => 3, 'unit' => 'calls',  'current' => 0, 'completed' => false],
            ['title' => 'Deal check-ins',   'target' => 2, 'unit' => 'emails', 'current' => 0, 'completed' => false],
            ['title' => 'Pipeline updates', 'target' => 2, 'unit' => 'deals',  'current' => 0, 'completed' => false],
        ]);

        // Simple heuristic focus score (0–100)
        $focusScore = (int) min(100, max(20,
            80
            - ($overdueTasks->count() * 10)
            - ($staleDeals->count() * 5)
            + ($hotContacts->count() * 3)
            + ($todayViewings->count() * 5)
        ));

        return DailyBrief::updateOrCreate(
            ['agency_id' => $agencyId, 'user_id' => $userId, 'date' => $today],
            [
                'priority_actions' => $priorityActions,
                'deal_alerts'      => $dealAlerts,
                'viewing_schedule' => $viewingSchedule,
                'market_snapshot'  => $marketSnapshot,
                'coaching_tips'    => $coachingTips,
                'goals'            => $goals,
                'ai_summary'       => $aiSummary,
                'focus_score'      => $focusScore,
                'is_read'          => false,
            ]
        );
    }

    private function parseJson(string $raw, array $fallback): array
    {
        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }

        return $fallback;
    }
}
