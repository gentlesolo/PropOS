<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Call;
use App\Infrastructure\Persistence\Models\CallSummary;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallAnalyticsController extends Controller
{
    /**
     * Personal call analytics for the authenticated agent.
     * Returns stats for the last 30 days by default.
     */
    public function personal(Request $request): JsonResponse
    {
        $days  = (int) $request->input('days', 30);
        $since = now()->subDays($days);
        $user  = $request->user();

        $base = Call::where('agent_id', $user->id)
            ->where('status', 'completed')
            ->where('started_at', '>=', $since);

        $totalCalls     = (clone $base)->count();
        $totalDuration  = (clone $base)->sum('duration_seconds');
        $avgDuration    = $totalCalls > 0 ? round($totalDuration / $totalCalls) : 0;
        $inbound        = (clone $base)->where('direction', 'inbound')->count();
        $outbound       = (clone $base)->where('direction', 'outbound')->count();

        $sentimentBreakdown = CallSummary::whereIn(
            'call_id',
            (clone $base)->pluck('id'),
        )
            ->select('sentiment', DB::raw('count(*) as count'))
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        $avgSentimentScore = CallSummary::whereIn(
            'call_id',
            (clone $base)->pluck('id'),
        )->avg('sentiment_score');

        // Daily call volume for sparkline chart (last 14 days)
        $dailyVolume = Call::where('agent_id', $user->id)
            ->where('status', 'completed')
            ->where('started_at', '>=', now()->subDays(14))
            ->select(
                DB::raw('DATE(started_at) as date'),
                DB::raw('count(*) as count'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->date => $r->count]);

        // Fill missing days with 0
        $filled = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $filled[] = ['date' => $date, 'count' => $dailyVolume[$date] ?? 0];
        }

        return response()->json([
            'period_days'        => $days,
            'total_calls'        => $totalCalls,
            'total_duration_sec' => $totalDuration,
            'avg_duration_sec'   => $avgDuration,
            'inbound'            => $inbound,
            'outbound'           => $outbound,
            'sentiment'          => $sentimentBreakdown,
            'avg_sentiment_score' => round((float) $avgSentimentScore, 1),
            'daily_volume'       => $filled,
        ]);
    }

    /**
     * Contact sentiment trend — all completed calls for one contact, ordered by date.
     */
    public function contactSentiment(Request $request, int $contactId): JsonResponse
    {
        $points = Call::where('contact_id', $contactId)
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->with('summary:id,call_id,sentiment,sentiment_score')
            ->latest('started_at')
            ->limit(20)
            ->get(['id', 'started_at', 'duration_seconds', 'direction'])
            ->filter(fn ($c) => $c->summary)
            ->map(fn ($c) => [
                'call_id'         => $c->id,
                'date'            => $c->started_at?->toDateString(),
                'sentiment'       => $c->summary->sentiment,
                'sentiment_score' => $c->summary->sentiment_score,
                'duration_sec'    => $c->duration_seconds,
                'direction'       => $c->direction,
            ])
            ->reverse()
            ->values();

        return response()->json($points);
    }

    /**
     * Manager view — team call activity and flagged calls.
     * Only accessible to users with admin or manager role.
     */
    public function team(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('admin|manager'),
            403,
            'Manager access required.',
        );

        $agencyId = $request->user()->agency_id;
        $days     = (int) $request->input('days', 7);
        $since    = now()->subDays($days);

        // Per-agent summary
        $agentStats = Call::where('agency_id', $agencyId)
            ->where('status', 'completed')
            ->where('started_at', '>=', $since)
            ->select(
                'agent_id',
                DB::raw('count(*) as call_count'),
                DB::raw('sum(duration_seconds) as total_duration'),
                DB::raw('avg(duration_seconds) as avg_duration'),
            )
            ->groupBy('agent_id')
            ->with('agent:id,first_name,last_name,avatar_path')
            ->get()
            ->map(fn ($r) => [
                'agent'          => $r->agent?->only(['id', 'first_name', 'last_name', 'avatar_path']),
                'call_count'     => $r->call_count,
                'total_duration' => (int) $r->total_duration,
                'avg_duration'   => (int) $r->avg_duration,
            ])
            ->sortByDesc('call_count')
            ->values();

        // Flagged calls awaiting coaching review
        $flagged = Call::where('agency_id', $agencyId)
            ->where('flagged_for_coaching', true)
            ->with([
                'agent:id,first_name,last_name',
                'contact:id,first_name,last_name',
                'summary:id,call_id,sentiment,summary_text',
            ])
            ->latest('started_at')
            ->limit(20)
            ->get(['id', 'agent_id', 'contact_id', 'direction', 'duration_seconds', 'started_at', 'coaching_notes', 'flagged_by']);

        // Team totals
        $totals = Call::where('agency_id', $agencyId)
            ->where('status', 'completed')
            ->where('started_at', '>=', $since)
            ->selectRaw('count(*) as total, sum(duration_seconds) as duration')
            ->first();

        return response()->json([
            'period_days'   => $days,
            'team_totals'   => [
                'calls'            => (int) ($totals->total ?? 0),
                'total_duration'   => (int) ($totals->duration ?? 0),
            ],
            'agent_stats'   => $agentStats,
            'flagged_calls' => $flagged,
        ]);
    }

    /**
     * Manager detail view for a single agent's calls.
     */
    public function agentCalls(Request $request, User $agent): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('admin|manager'),
            403,
        );

        $calls = Call::where('agent_id', $agent->id)
            ->with([
                'contact:id,first_name,last_name',
                'summary:id,call_id,sentiment,sentiment_score,summary_text',
            ])
            ->latest('started_at')
            ->paginate(20);

        return response()->json($calls);
    }

    /**
     * Unflag a call after the manager has reviewed it.
     */
    public function unflag(Request $request, Call $call): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('admin|manager'),
            403,
        );

        $call->update([
            'flagged_for_coaching' => false,
            'coaching_notes'       => null,
        ]);

        return response()->json(['flagged' => false]);
    }
}
