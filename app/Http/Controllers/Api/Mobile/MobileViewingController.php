<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\ContactActivity;
use App\Infrastructure\Persistence\Models\Viewing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileViewingController extends Controller
{
    /**
     * Today's viewing schedule for the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $viewings = Viewing::with([
            'contact:id,first_name,last_name,phone,avatar_path',
            'listing:id,title,address',
        ])
            ->where('assigned_agent_id', $request->user()->id)
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($viewings);
    }

    /**
     * Upcoming viewings (next 7 days).
     */
    public function upcoming(Request $request): JsonResponse
    {
        $viewings = Viewing::with([
            'contact:id,first_name,last_name,phone',
            'listing:id,title,address',
        ])
            ->where('assigned_agent_id', $request->user()->id)
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($viewings);
    }

    /**
     * Single viewing detail.
     */
    public function show(Viewing $viewing): JsonResponse
    {
        $this->authorizeViewing($viewing);

        $viewing->load([
            'contact:id,first_name,last_name,phone,email',
            'listing:id,title,address,price,bedrooms,bathrooms',
        ]);

        return response()->json($viewing);
    }

    /**
     * Agent checks in at the property.
     */
    public function checkIn(Viewing $viewing): JsonResponse
    {
        $this->authorizeViewing($viewing);

        $viewing->update([
            'check_in_at' => now(),
            'status'      => 'confirmed',
        ]);

        return response()->json($viewing->fresh());
    }

    /**
     * Agent completes the viewing with outcome notes.
     */
    public function complete(Request $request, Viewing $viewing): JsonResponse
    {
        $this->authorizeViewing($viewing);

        $request->validate([
            'outcome'       => 'required|in:interested,not_interested,offer_expected,undecided',
            'outcome_notes' => 'nullable|string|max:2000',
        ]);

        $viewing->update([
            'status'        => 'completed',
            'outcome'       => $request->outcome,
            'outcome_notes' => $request->outcome_notes,
        ]);

        // Log to contact timeline
        ContactActivity::create([
            'agency_id'   => $request->user()->agency_id,
            'contact_id'  => $viewing->contact_id,
            'user_id'     => $request->user()->id,
            'type'        => 'viewing',
            'subject'     => 'Viewing completed',
            'body'        => $request->outcome_notes,
            'metadata'    => [
                'viewing_id' => $viewing->id,
                'outcome'    => $request->outcome,
                'listing_id' => $viewing->listing_id,
            ],
            'occurred_at' => now(),
        ]);

        return response()->json($viewing->fresh());
    }

    /**
     * Update viewing status (cancel / no-show).
     */
    public function updateStatus(Request $request, Viewing $viewing): JsonResponse
    {
        $this->authorizeViewing($viewing);

        $request->validate([
            'status' => 'required|in:confirmed,completed,no_show,cancelled',
        ]);

        $viewing->update(['status' => $request->status]);

        return response()->json($viewing->fresh());
    }

    private function authorizeViewing(Viewing $viewing): void
    {
        abort_unless(
            $viewing->assigned_agent_id === request()->user()->id
                || request()->user()->hasRole('admin|manager'),
            403,
            'Access denied.',
        );
    }
}
