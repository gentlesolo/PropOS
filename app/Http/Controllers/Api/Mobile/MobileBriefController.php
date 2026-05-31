<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\ContactActivity;
use App\Infrastructure\Persistence\Models\DailyBrief;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileBriefController extends Controller
{
    /**
     * Return today's AI daily brief for the authenticated agent.
     */
    public function show(Request $request): JsonResponse
    {
        $brief = DailyBrief::where('user_id', $request->user()->id)
            ->whereDate('date', today())
            ->first();

        if (! $brief) {
            return response()->json([
                'date'            => today()->toDateString(),
                'priority_actions' => [],
                'deal_alerts'     => [],
                'viewing_schedule' => [],
                'market_snapshot' => null,
                'content'         => 'No brief generated yet for today. Check back shortly.',
            ]);
        }

        $brief->update(['is_read' => true]);

        return response()->json([
            'date'             => $brief->date,
            'priority_actions' => $brief->priority_actions ?? [],
            'deal_alerts'      => $brief->deal_alerts ?? [],
            'viewing_schedule' => $brief->viewing_schedule ?? [],
            'market_snapshot'  => $brief->market_snapshot,
            'content'          => $this->buildBriefText($brief),
        ]);
    }

    /**
     * Return the activity timeline for a contact.
     */
    public function contactTimeline(Request $request, int $contactId): JsonResponse
    {
        $activities = ContactActivity::where('contact_id', $contactId)
            ->with('user:id,first_name,last_name,avatar_path')
            ->orderByDesc('occurred_at')
            ->paginate($request->per_page ?? 30);

        return response()->json($activities);
    }

    private function buildBriefText(DailyBrief $brief): string
    {
        $lines = [];

        if (! empty($brief->priority_actions)) {
            $lines[] = 'Today\'s priorities:';
            foreach (array_slice($brief->priority_actions, 0, 3) as $action) {
                $lines[] = '• ' . (is_array($action) ? ($action['title'] ?? '') : $action);
            }
        }

        if (! empty($brief->deal_alerts)) {
            $lines[] = '';
            $lines[] = 'Deal alerts:';
            foreach (array_slice($brief->deal_alerts, 0, 2) as $alert) {
                $lines[] = '• ' . (is_array($alert) ? ($alert['message'] ?? '') : $alert);
            }
        }

        return implode("\n", $lines) ?: 'Have a productive day!';
    }
}
