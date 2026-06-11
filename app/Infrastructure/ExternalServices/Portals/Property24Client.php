<?php

namespace App\Infrastructure\ExternalServices\Portals;

use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Property24Client
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.property24.api_key');
        $this->baseUrl = config('services.property24.base_url', 'https://api.property24.com/v1');
    }

    public function publishListing(Listing $listing): string
    {
        $property = $listing->property;

        $payload = [
            'reference'       => "VILLACRM-{$listing->id}",
            'listing_type'    => $listing->listing_type === 'sale' ? 'ForSale' : 'ToLet',
            'price'           => (float) $listing->listing_price,
            'property_type'   => $this->mapPropertyType($property->property_type),
            'headline'        => $listing->headline ?? "Property in {$property->city}",
            'description'     => $listing->description_standard ?? $listing->description_short,
            'bedrooms'        => $property->bedrooms,
            'bathrooms'       => $property->bathrooms,
            'garages'         => $property->parking_spaces,
            'floor_size'      => $property->floor_area_sqm,
            'erf_size'        => $property->land_area_sqm,
            'address'         => [
                'street'   => $property->address_line_1,
                'suburb'   => $property->suburb,
                'city'     => $property->city,
                'province' => $property->state_province,
                'country'  => $property->country ?? 'ZA',
            ],
            'contact_email'   => config('mail.from.address'),
            'images'          => $listing->media()
                ->where('file_type', 'image')
                ->orderBy('order')
                ->limit(20)
                ->pluck('url')
                ->toArray(),
        ];

        if (empty($this->apiKey)) {
            Log::info('Property24: API key not set, skipping real publish.', ['listing_id' => $listing->id]);
            return 'P24-DEMO-' . $listing->id;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept'        => 'application/json',
        ])->post("{$this->baseUrl}/listings", $payload);

        if ($response->successful()) {
            return (string) $response->json('listing_id');
        }

        Log::error('Property24 publish failed', [
            'listing_id' => $listing->id,
            'status'     => $response->status(),
            'body'       => $response->body(),
        ]);

        throw new \RuntimeException('Property24 publish failed: ' . $response->body());
    }

    public function unpublishListing(string $externalId): bool
    {
        if (empty($this->apiKey)) {
            return true;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->delete("{$this->baseUrl}/listings/{$externalId}");

        return $response->successful();
    }

    public function updateListing(string $externalId, Listing $listing): bool
    {
        if (empty($this->apiKey)) {
            return true;
        }

        $property = $listing->property;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept'        => 'application/json',
        ])->put("{$this->baseUrl}/listings/{$externalId}", [
            'price'       => (float) $listing->listing_price,
            'headline'    => $listing->headline,
            'description' => $listing->description_standard ?? $listing->description_short,
            'images'      => $listing->media()->where('file_type', 'image')->pluck('url')->toArray(),
        ]);

        return $response->successful();
    }

    private function mapPropertyType(string $type): string
    {
        return match (strtolower($type)) {
            'apartment', 'flat'         => 'Apartment',
            'house', 'freestanding'     => 'House',
            'townhouse'                 => 'Townhouse',
            'commercial', 'office'      => 'Commercial',
            'industrial'                => 'Industrial',
            'vacant land', 'land'       => 'VacantLand',
            'farm'                      => 'Farm',
            default                     => 'House',
        };
    }
}
