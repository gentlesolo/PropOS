<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Viewing;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAgentController extends Controller
{
    /**
     * Return a timezone-aware list of 30-min free slots for an agent
     * over the next 14 days, excluding already-booked viewing slots.
     */
    public function availability(Request $request, int $agentId): JsonResponse
    {
        $agencyId = $request->attributes->get('agency_id');

        $agent = User::where('id', $agentId)
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->firstOrFail();

        $timezone  = $request->query('timezone', $agent->timezone ?? 'UTC');
        $slotMins  = 30;
        $workStart = 8;  // 08:00
        $workEnd   = 18; // 18:00
        $days      = 14;

        $bookedSlots = Viewing::withoutGlobalScopes()
            ->where('assigned_agent_id', $agentId)
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays($days))
            ->whereNotIn('status', ['cancelled'])
            ->pluck('scheduled_at')
            ->map(fn($dt) => $dt->setTimezone($timezone)->format('Y-m-d H:i'));

        $slots = [];
        $start = now()->setTimezone($timezone)->startOfDay()->addDay();

        for ($day = 0; $day < $days; $day++) {
            $date     = $start->copy()->addDays($day);
            $dayOfWeek = (int) $date->format('N'); // 1=Mon … 7=Sun

            if ($dayOfWeek >= 6) {
                continue; // skip weekends
            }

            $cursor = $date->copy()->setHour($workStart)->setMinute(0)->setSecond(0);
            $end    = $date->copy()->setHour($workEnd)->setMinute(0)->setSecond(0);

            while ($cursor < $end) {
                $label = $cursor->format('Y-m-d H:i');
                if (! $bookedSlots->contains($label)) {
                    $slots[] = [
                        'datetime'       => $cursor->toISOString(),
                        'datetime_local' => $cursor->format('Y-m-d\TH:i:s'),
                        'date'           => $cursor->format('Y-m-d'),
                        'time'           => $cursor->format('H:i'),
                        'timezone'       => $timezone,
                    ];
                }
                $cursor->addMinutes($slotMins);
            }
        }

        return response()->json([
            'agent' => [
                'id'     => $agent->id,
                'name'   => $agent->first_name.' '.$agent->last_name,
                'avatar' => $agent->avatar_path,
                'phone'  => $agent->phone,
            ],
            'timezone'   => $timezone,
            'slot_minutes' => $slotMins,
            'slots'      => $slots,
        ]);
    }
}
