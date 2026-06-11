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

        $features = is_array($listing->features_highlighted) && count($listing->features_highlighted) > 0 
            ? "Special features: " . implode(', ', $listing->features_highlighted) 
            : null;

        $details = collect([
            "Property type: {$property->property_type}",
            "Address: {$property->address_line_1}, {$property->city}, {$property->state_province}",
            $property->bedrooms ? "Bedrooms: {$property->bedrooms}" : null,
            $property->bathrooms ? "Bathrooms: {$property->bathrooms}" : null,
            $property->floor_area_sqm ? "Floor area: {$property->floor_area_sqm} sqm" : null,
            $property->land_area_sqm ? "Land area: {$property->land_area_sqm} sqm" : null,
            $property->year_built ? "Year built: {$property->year_built}" : null,
            "Listing price: " . number_format((float) $listing->listing_price),
            $property->condition ? "Condition: {$property->condition}" : null,
            $features,
        ])->filter()->implode("\n");

        $systemPrompt = "You are an expert premium real estate copywriter. Write highly engaging, detailed, and vivid property descriptions that paint a compelling lifestyle picture for prospective buyers. Tone: {$tone}. IMPORTANT: You must output ONLY a raw JSON object (no markdown, no backticks) with these exact keys:\n- 'headline' (max 12 words)\n- 'description_short' (around 50 words)\n- 'description_standard' (around 150-200 words, structured into clear paragraphs)\n- 'description_long' (very detailed and comprehensive, 300-400+ words, highlighting lifestyle, unique features, layout, condition, and location benefits).";

        $userPrompt = "Write listing descriptions for this property:\n\n{$details}";

        $raw = $this->ai->generate($systemPrompt, $userPrompt);
        $raw = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', trim($raw));

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
