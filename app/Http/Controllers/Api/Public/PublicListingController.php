<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->attributes->get('agency_id');

        $listings = Listing::withoutGlobalScopes()
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->with(['property', 'agent:id,first_name,last_name,avatar_path,phone,email', 'coverPhoto'])
            ->when($request->type, fn($q) => $q->whereHas('property', fn($p) => $p->where('property_type', $request->type)))
            ->when($request->mandate_type, fn($q) => $q->where('mandate_type', $request->mandate_type))
            ->when($request->city, fn($q) => $q->whereHas('property', fn($p) => $p->where('city', 'like', "%{$request->city}%")))
            ->when($request->min_price, fn($q) => $q->where('listing_price', '>=', $request->min_price))
            ->when($request->max_price, fn($q) => $q->where('listing_price', '<=', $request->max_price))
            ->when($request->bedrooms, fn($q) => $q->whereHas('property', fn($p) => $p->where('bedrooms', $request->bedrooms)))
            ->when($request->bathrooms, fn($q) => $q->whereHas('property', fn($p) => $p->where('bathrooms', '>=', $request->bathrooms)))
            ->latest('published_at')
            ->paginate($request->per_page ?? 12);

        return response()->json($listings->through(fn($l) => $this->formatListing($l)));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $agencyId = $request->attributes->get('agency_id');

        $listing = Listing::withoutGlobalScopes()
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->with([
                'property',
                'agent:id,first_name,last_name,avatar_path,phone,email,job_title,bio',
                'media',
                'coverPhoto',
            ])
            ->findOrFail($id);

        return response()->json($this->formatListing($listing, detailed: true));
    }

    private function formatListing(Listing $listing, bool $detailed = false): array
    {
        $data = [
            'id'             => $listing->id,
            'headline'       => $listing->headline,
            'description'    => $detailed ? $listing->description_standard : $listing->description_short,
            'status'         => $listing->status,
            'mandate_type'   => $listing->mandate_type,
            'listing_price'  => (float) $listing->listing_price,
            'days_on_market' => $listing->days_on_market,
            'published_at'   => $listing->published_at?->toISOString(),
            'features'       => $listing->features_highlighted ?? [],
            'cover_photo'    => $listing->coverPhoto?->url,
            'property'       => $listing->property ? [
                'type'            => $listing->property->property_type,
                'bedrooms'        => $listing->property->bedrooms,
                'bathrooms'       => $listing->property->bathrooms,
                'parking_spaces'  => $listing->property->parking_spaces,
                'floor_area_sqm'  => (float) $listing->property->floor_area_sqm,
                'land_area_sqm'   => (float) $listing->property->land_area_sqm,
                'address'         => $listing->property->address_line_1,
                'city'            => $listing->property->city,
                'state'           => $listing->property->state_province,
                'country'         => $listing->property->country,
                'latitude'        => (float) $listing->property->latitude,
                'longitude'       => (float) $listing->property->longitude,
            ] : null,
            'agent'          => $listing->agent ? [
                'id'         => $listing->agent->id,
                'name'       => $listing->agent->first_name.' '.$listing->agent->last_name,
                'avatar'     => $listing->agent->avatar_path,
                'phone'      => $listing->agent->phone,
                'email'      => $listing->agent->email,
            ] : null,
        ];

        if ($detailed) {
            $data['description_long'] = $listing->description_long;
            $data['agent']['job_title'] = $listing->agent?->job_title;
            $data['agent']['bio']       = $listing->agent?->bio;
            $data['media'] = $listing->media->map(fn($m) => [
                'url'      => $m->url,
                'type'     => $m->file_type,
                'is_cover' => (bool) $m->is_cover,
                'caption'  => $m->caption ?? null,
            ]);
        }

        return $data;
    }
}
