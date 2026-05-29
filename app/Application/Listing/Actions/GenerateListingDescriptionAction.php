<?php

namespace App\Application\Listing\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Listing;

class GenerateListingDescriptionAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function execute(Listing $listing, string $tone = 'professional'): array
    {
        $property = $listing->property;

        $details = collect([
            "Property type: {$property->property_type}",
            "Address: {$property->address_line_1}, {$property->city}, {$property->state_province}",
            $property->bedrooms ? "Bedrooms: {$property->bedrooms}" : null,
            $property->bathrooms ? "Bathrooms: {$property->bathrooms}" : null,
            $property->floor_area_sqm ? "Floor area: {$property->floor_area_sqm} sqm" : null,
            $property->land_area_sqm ? "Land area: {$property->land_area_sqm} sqm" : null,
            $property->year_built ? "Year built: {$property->year_built}" : null,
            "Listing price: " . number_format((float) $listing->listing_price),
            "Mandate type: {$listing->mandate_type}",
        ])->filter()->implode("\n");

        $systemPrompt = "You are a professional real estate copywriter. Write compelling property descriptions that attract buyers and sellers. Tone: {$tone}. Keep descriptions factual, vivid, and under 200 words. Return a JSON object with keys: headline (max 12 words), description_short (50 words), description_standard (100 words), description_long (180 words).";

        $userPrompt = "Write listing descriptions for this property:\n\n{$details}";

        $raw = $this->ai->generate($systemPrompt, $userPrompt);

        $parsed = json_decode($raw, true);

        if (!$parsed) {
            $parsed = [
                'headline' => "Beautiful {$property->property_type} in {$property->city}",
                'description_short' => $raw,
                'description_standard' => $raw,
                'description_long' => $raw,
            ];
        }

        $listing->update([
            'headline' => $parsed['headline'] ?? $listing->headline,
            'description_short' => $parsed['description_short'] ?? $listing->description_short,
            'description_standard' => $parsed['description_standard'] ?? $listing->description_standard,
            'description_long' => $parsed['description_long'] ?? $listing->description_long,
        ]);

        return $parsed;
    }
}
