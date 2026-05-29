<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $listings = Listing::with(['property', 'agent', 'coverPhoto'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->mandate_type, fn($q) => $q->where('mandate_type', $request->mandate_type))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($listings);
    }

    public function show(Listing $listing)
    {
        return response()->json($listing->load('property', 'agent', 'media', 'portalSyncs.portal'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state_province' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'property_type' => 'required|in:house,apartment,townhouse,penthouse,land,commercial,office,warehouse',
            'listing_price' => 'required|numeric|min:0',
            'mandate_type' => 'required|in:sole,open,rental',
        ]);

        $property = \App\Infrastructure\Persistence\Models\Property::create([
            'agency_id' => $request->user()->agency_id,
            'address_line_1' => $validated['address_line_1'],
            'city' => $validated['city'],
            'state_province' => $validated['state_province'],
            'country' => $validated['country'],
            'property_type' => $validated['property_type'],
        ]);

        $listing = Listing::create([
            'agency_id' => $request->user()->agency_id,
            'agent_id' => $request->user()->id,
            'property_id' => $property->id,
            'listing_price' => $validated['listing_price'],
            'mandate_type' => $validated['mandate_type'],
            'mandate_start_date' => now(),
            'status' => 'draft',
        ]);

        return response()->json($listing->load('property'), 201);
    }

    public function update(Request $request, Listing $listing)
    {
        $data = $request->validate([
            'listing_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:draft,active,under_offer,sold,let,withdrawn,expired',
            'mandate_type' => 'sometimes|in:sole,open,rental',
            'headline' => 'nullable|string|max:255',
            'description_short' => 'nullable|string',
        ]);

        $listing->update($data);
        return response()->json($listing->fresh());
    }
}
