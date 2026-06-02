<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\CRM\Actions\DetectDuplicateContactsAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Viewing;
use App\Infrastructure\Tenancy\TenantResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicBookingController extends Controller
{
    public function store(
        Request $request,
        DetectDuplicateContactsAction $detector,
        LogContactActivityAction $logger,
        ScoreLeadAction $scorer,
    ): JsonResponse {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'required|string|max:30',
            'listing_id'   => 'required|integer',
            'agent_id'     => 'required|integer',
            'scheduled_at' => 'required|date|after:now',
            'timezone'     => 'nullable|string|max:60',
            'message'      => 'nullable|string|max:1000',
        ]);

        $agencyId = $request->attributes->get('agency_id');
        $agency   = Agency::findOrFail($agencyId);
        app(TenantResolver::class)->setCurrentAgency($agency);

        // Validate listing belongs to this agency and is active
        $listing = Listing::withoutGlobalScopes()
            ->where('id', $validated['listing_id'])
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->firstOrFail();

        // Validate agent belongs to this agency and is active
        $agent = User::where('id', $validated['agent_id'])
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->firstOrFail();

        // Normalise scheduled_at to UTC
        $tz          = $validated['timezone'] ?? 'UTC';
        $scheduledAt = Carbon::parse($validated['scheduled_at'], $tz)->utc();

        // Guard: slot must be in the future
        if ($scheduledAt->isPast()) {
            return response()->json(['error' => 'The selected time slot is no longer available.'], 422);
        }

        // Guard: slot not already taken by this agent (30-min window)
        $conflict = Viewing::withoutGlobalScopes()
            ->where('assigned_agent_id', $agent->id)
            ->whereBetween('scheduled_at', [
                $scheduledAt->copy()->subMinutes(29),
                $scheduledAt->copy()->addMinutes(29),
            ])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($conflict) {
            return response()->json(['error' => 'That time slot is no longer available. Please choose another.'], 409);
        }

        // Find or create contact
        $duplicates = $detector->execute($validated['email'] ?? null, $validated['phone']);

        if ($duplicates->isNotEmpty()) {
            $contact = $duplicates->first();
        } else {
            $contact = Contact::create([
                'agency_id'   => $agencyId,
                'first_name'  => $validated['first_name'],
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'] ?? null,
                'phone'       => $validated['phone'],
                'type'        => 'buyer',
                'source'      => 'website',
                'source_detail' => "Listing #{$listing->id} booking widget",
                'status'      => 'new',
                'tags'        => ['website_lead', 'viewing_request'],
            ]);
        }

        // Create the viewing
        $viewing = Viewing::create([
            'agency_id'        => $agencyId,
            'listing_id'       => $listing->id,
            'contact_id'       => $contact->id,
            'assigned_agent_id'=> $agent->id,
            'scheduled_at'     => $scheduledAt,
            'status'           => 'scheduled',
            'duration_minutes' => 30,
            'notes'            => $validated['message'] ?? null,
        ]);

        $logger->execute(
            $contact,
            'system',
            'Viewing Booked via Website',
            "Self-service viewing booked for {$scheduledAt->format('D d M Y, H:i')} UTC on listing \"{$listing->headline}\".",
        );

        $scorer->execute($contact);

        return response()->json([
            'success'      => true,
            'viewing_id'   => $viewing->id,
            'scheduled_at' => $viewing->scheduled_at->toISOString(),
            'agent'        => [
                'name'  => $agent->first_name.' '.$agent->last_name,
                'phone' => $agent->phone,
                'email' => $agent->email,
            ],
            'message'      => 'Your viewing is booked! The agent will confirm shortly.',
        ], 201);
    }
}
