<?php

namespace App\Infrastructure\ExternalServices\Portals;

use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropertyProClient
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.propertypro.api_key');
        $this->baseUrl = config('services.propertypro.base_url', 'https://api.propertypro.ng/v2');
    }

    public function publishListing(Listing $listing): string
    {
        $property = $listing->property;

        $payload = [
            'source_ref'     => "VILLACRM-{$listing->id}",
            'offer_type'     => $listing->listing_type === 'sale' ? 'sale' : 'rent',
            'price'          => (float) $listing->listing_price,
            'title'          => $listing->headline ?? "Property in {$property->city}",
            'description'    => $listing->description_standard ?? $listing->description_short,
            'category'       => $this->mapPropertyType($property->property_type),
            'bedroom'        => (int) ($property->bedrooms ?? 0),
            'bathroom'       => (int) ($property->bathrooms ?? 0),
            'toilets'        => (int) ($property->bathrooms ?? 0),
            'size'           => $property->floor_area_sqm,
            'state'          => $property->state_province,
            'city'           => $property->city,
            'address'        => $property->address_line_1,
            'pictures'       => $listing->media()
                ->where('file_type', 'image')
                ->orderBy('order')
                ->limit(15)
                ->pluck('url')
                ->values()
                ->toArray(),
        ];

        if (empty($this->apiKey)) {
            Log::info('PropertyPro: API key not set, skipping real publish.', ['listing_id' => $listing->id]);
            return 'PP-DEMO-' . $listing->id;
        }

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept'    => 'application/json',
        ])->post("{$this->baseUrl}/properties", $payload);

        if ($response->successful()) {
            return (string) ($response->json('data.id') ?? $response->json('id'));
        }

        Log::error('PropertyPro publish failed', [
            'listing_id' => $listing->id,
            'status'     => $response->status(),
            'body'       => $response->body(),
        ]);

        throw new \RuntimeException('PropertyPro publish failed: ' . $response->body());
    }

    public function unpublishListing(string $externalId): bool
    {
        if (empty($this->apiKey)) {
            return true;
        }

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->delete("{$this->baseUrl}/properties/{$externalId}");

        return $response->successful();
    }

    public function updateListing(string $externalId, Listing $listing): bool
    {
        if (empty($this->apiKey)) {
            return true;
        }

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept'    => 'application/json',
        ])->put("{$this->baseUrl}/properties/{$externalId}", [
            'price'       => (float) $listing->listing_price,
            'title'       => $listing->headline,
            'description' => $listing->description_standard ?? $listing->description_short,
            'pictures'    => $listing->media()->where('file_type', 'image')->pluck('url')->toArray(),
        ]);

        return $response->successful();
    }

    private function mapPropertyType(string $type): string
    {
        return match (strtolower($type)) {
            'apartment', 'flat'     => 'Flat / Apartment',
            'house', 'freestanding' => 'Detached house',
            'townhouse'             => 'Terraced / Townhouse',
            'commercial', 'office'  => 'Commercial property',
            'industrial'            => 'Warehouse',
            'vacant land', 'land'   => 'Land / Plot',
            'farm'                  => 'Farm',
            default                 => 'Detached house',
        };
    }
}
