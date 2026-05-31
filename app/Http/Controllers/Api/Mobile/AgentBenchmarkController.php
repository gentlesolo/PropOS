<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentBenchmarkController extends Controller
{
    /**
     * Compare the authenticated agent against team averages.
     * Returns percentile ranking across key metrics.
     */
    public function compare(Request $request): JsonResponse
    {
        $days     = (int) $request->input('days', 30);
        $since    = now()->subDays($days);
        $user     = $request->user();
        $agencyId = $user->agency_id;

        // ── Personal stats ───────────────────────────────────────────────────
        $personal = $this->agentStats($user->id, $agencyId, $since);

        // ── All agents in the agency ─────────────────────────────────────────
        $allStats = Call::where('agency_id', $agencyId)
            ->where('status', 'completed')
            ->where('started_at', '>=', $since)
            ->select(
                'agent_id',
                DB::raw('count(*) as call_count'),
                DB::raw('avg(duration_seconds) as avg_duration'),
            )
            ->groupBy('agent_id')
            ->get();

        $allSentiment = CallSummary::whereHas('call', fn ($q) =>
            $q->where('agency_id', $agencyId)->where('started_at', '>=', $since)
        )
            ->select('call_id', 'sentiment_score')
            ->with('call:id,agent_id')
            ->get()
            ->groupBy(fn ($s) => $s->call?->agent_id)
            ->map(fn ($group) => $group->avg('sentiment_score'));

        $agentCount = $allStats->count();

        if ($agentCount < 2) {
            // Not enough peers for meaningful benchmarking
            return response()->json([
                'personal'      => $personal,
                'team_avg'      => null,
                'percentiles'   => null,
                'message'       => 'Not enough team data for benchmarking yet.',
            ]);
        }

        // ── Team averages ────────────────────────────────────────────────────
        $teamAvgCalls    = $allStats->avg('call_count');
        $teamAvgDuration = $allStats->avg('avg_duration');
        $teamAvgSentiment = $allSentiment->avg();

        // ── Percentile calculation ───────────────────────────────────────────
        $callCounts   = $allStats->pluck('call_count')->sort()->values();
        $durations    = $allStats->pluck('avg_duration')->sort()->values();
        $sentiments   = $allSentiment->sort()->values();

        return response()->json([
            'period_days' => $days,
            'personal'    => $personal,
            'team_avg'    => [
                'calls_per_period'   => round($teamAvgCalls, 1),
                'avg_duration_sec'   => (int) $teamAvgDuration,
                'avg_sentiment_score' => round((float) $teamAvgSentiment, 1),
                'agent_count'        => $agentCount,
            ],
            'percentiles' => [
                'calls'     => $this->percentileOf($personal['total_calls'], $callCounts->toArray()),
                'duration'  => $this->percentileOf($personal['avg_duration_sec'], $durations->toArray()),
                'sentiment' => $this->percentileOf($personal['avg_sentiment_score'], $sentiments->toArray()),
            ],
            'rankings' => $this->buildRankings($allStats, $allSentiment, $user->id),
        ]);
    }

    /**
     * Leaderboard — ordered ranking across the team for a single metric.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $metric   = $request->input('metric', 'calls');
        $days     = (int) $request->input('days', 30);
        $since    = now()->subDays($days);
        $agencyId = $request->user()->agency_id;

        $query = Call::where('agency_id', $agencyId)
            ->where('status', 'completed')
            ->where('started_at', '>=', $since)
            ->select(
                'agent_id',
                DB::raw('count(*) as call_count'),
                DB::raw('sum(duration_seconds) as total_duration'),
                DB::raw('avg(duration_seconds) as avg_duration'),
            )
            ->groupBy('agent_id')
            ->with('agent:id,first_name,last_name,avatar_path');

        $rows = $query->get()->map(function ($row) {
            return [
                'agent'          => $row->agent?->only(['id', 'first_name', 'last_name', 'avatar_path']),
                'call_count'     => $row->call_count,
                'total_duration' => (int) $row->total_duration,
                'avg_duration'   => (int) $row->avg_duration,
            ];
        });

        $sorted = match ($metric) {
            'duration' => $rows->sortByDesc('avg_duration'),
            'volume'   => $rows->sortByDesc('call_count'),
            default    => $rows->sortByDesc('call_count'),
        };

        return response()->json([
            'metric'      => $metric,
            'period_days' => $days,
            'leaderboard' => $sorted->values(),
        ]);
    }

    private function agentStats(int $agentId, int $agencyId, \Carbon\Carbon $since): array
    {
        $base  = Call::where('agent_id', $agentId)->where('status', 'completed')->where('started_at', '>=', $since);
        $total = (clone $base)->count();
        $avgDur = (clone $base)->avg('duration_seconds') ?? 0;

        $avgSentiment = CallSummary::whereIn('call_id', (clone $base)->pluck('id'))
            ->avg('sentiment_score') ?? 0;

        return [
            'total_calls'        => $total,
            'avg_duration_sec'   => (int) $avgDur,
            'avg_sentiment_score' => round((float) $avgSentiment, 1),
        ];
    }

    private function percentileOf(float $value, array $sorted): int
    {
        if (empty($sorted)) return 0;
        $below = count(array_filter($sorted, fn ($v) => $v < $value));
        return (int) round(($below / count($sorted)) * 100);
    }

    private function buildRankings(
        \Illuminate\Support\Collection $allStats,
        \Illuminate\Support\Collection $allSentiment,
        int $myAgentId,
    ): array {
        $ranked = $allStats
            ->sortByDesc('call_count')
            ->values()
            ->map(fn ($s, $i) => ['agent_id' => $s->agent_id, 'rank' => $i + 1]);

        $myRank = $ranked->firstWhere('agent_id', $myAgentId);

        return [
            'my_rank'   => $myRank['rank'] ?? null,
            'out_of'    => $ranked->count(),
        ];
    }
}
