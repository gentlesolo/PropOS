<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Support\Collection;

class MatchBuyersToListingAction
{
    /**
     * Returns buyer contacts scored against the listing, sorted by match score descending.
     *
     * @return Collection<int, array{contact: Contact, score: int, reasons: string[]}>
     */
    public function execute(Listing $listing): Collection
    {
        $property = $listing->property;

        $buyers = Contact::where('agency_id', $listing->agency_id)
            ->whereIn('type', ['buyer', 'investor', 'tenant'])
            ->whereNotNull('preferences')
            ->with('agent')
            ->get();

        return $buyers->map(function (Contact $contact) use ($listing, $property) {
            $prefs = $contact->preferences ?? [];
            $score = 0;
            $reasons = [];

            // Budget match
            $maxBudget = (float) ($prefs['max_budget'] ?? 0);
            $minBudget = (float) ($prefs['min_budget'] ?? 0);
            $price = (float) $listing->listing_price;

            if ($maxBudget > 0 && $price <= $maxBudget) {
                $score += 30;
                $reasons[] = 'Within budget';
            } elseif ($maxBudget > 0 && $price <= $maxBudget * 1.1) {
                $score += 15;
                $reasons[] = 'Slightly over budget (within 10%)';
            } elseif ($maxBudget > 0) {
                $score -= 20;
            }

            if ($minBudget > 0 && $price >= $minBudget) {
                $score += 5;
            }

            // Property type match
            $wantedTypes = array_map('strtolower', (array) ($prefs['property_types'] ?? []));
            if (! empty($wantedTypes) && in_array(strtolower($property->property_type), $wantedTypes)) {
                $score += 20;
                $reasons[] = 'Property type matches';
            }

            // Location match
            $wantedAreas = array_map('strtolower', (array) ($prefs['areas'] ?? []));
            $propertyArea = strtolower($property->suburb ?? $property->city ?? '');
            foreach ($wantedAreas as $area) {
                if (str_contains($propertyArea, $area) || str_contains(strtolower($property->city ?? ''), $area)) {
                    $score += 25;
                    $reasons[] = 'Location matches preferred area';
                    break;
                }
            }

            // Bedrooms match
            $minBeds = (int) ($prefs['min_bedrooms'] ?? 0);
            $maxBeds = (int) ($prefs['max_bedrooms'] ?? 99);
            $beds = (int) ($property->bedrooms ?? 0);
            if ($minBeds > 0 && $beds >= $minBeds && $beds <= $maxBeds) {
                $score += 15;
                $reasons[] = "{$beds} bedrooms matches requirement";
            } elseif ($minBeds > 0 && $beds < $minBeds) {
                $score -= 10;
            }

            // Features match
            $wantedFeatures = array_map('strtolower', (array) ($prefs['must_have_features'] ?? []));
            $listingFeatures = array_map('strtolower', (array) ($listing->features_highlighted ?? []));
            $matched = array_intersect($wantedFeatures, $listingFeatures);
            if (! empty($matched)) {
                $score += min(10, count($matched) * 5);
                $reasons[] = 'Has ' . count($matched) . ' must-have feature(s)';
            }

            // Intent score boost
            $score += (int) round(($contact->intent_score ?? 0) * 0.1);

            return [
                'contact' => $contact,
                'score'   => max(0, min(100, $score)),
                'reasons' => $reasons,
            ];
        })
        ->filter(fn($item) => $item['score'] >= 30)
        ->sortByDesc('score')
        ->values();
    }

    /**
     * Returns listings scored against a buyer contact's preferences, sorted by score desc.
     *
     * @return Collection<int, array{listing: Listing, score: int, reasons: string[]}>
     */
    public function matchListingsForBuyer(Contact $contact): Collection
    {
        $prefs = $contact->preferences ?? [];

        if (empty($prefs)) {
            return collect();
        }

        $listings = Listing::where('agency_id', $contact->agency_id)
            ->where('status', 'active')
            ->with('property')
            ->get();

        return $listings->map(function (Listing $listing) use ($prefs) {
            $property = $listing->property;
            $score = 0;
            $reasons = [];

            $maxBudget = (float) ($prefs['max_budget'] ?? 0);
            $price = (float) $listing->listing_price;

            if ($maxBudget > 0 && $price <= $maxBudget) {
                $score += 30;
                $reasons[] = 'Within budget';
            } elseif ($maxBudget > 0 && $price <= $maxBudget * 1.1) {
                $score += 15;
            } elseif ($maxBudget > 0) {
                $score -= 20;
            }

            $wantedTypes = array_map('strtolower', (array) ($prefs['property_types'] ?? []));
            if (! empty($wantedTypes) && in_array(strtolower($property->property_type), $wantedTypes)) {
                $score += 20;
                $reasons[] = 'Property type matches';
            }

            $wantedAreas = array_map('strtolower', (array) ($prefs['areas'] ?? []));
            $propertyArea = strtolower($property->suburb ?? $property->city ?? '');
            foreach ($wantedAreas as $area) {
                if (str_contains($propertyArea, $area) || str_contains(strtolower($property->city ?? ''), $area)) {
                    $score += 25;
                    $reasons[] = 'Location match';
                    break;
                }
            }

            $minBeds = (int) ($prefs['min_bedrooms'] ?? 0);
            $beds = (int) ($property->bedrooms ?? 0);
            if ($minBeds > 0 && $beds >= $minBeds) {
                $score += 15;
                $reasons[] = "{$beds} bedrooms";
            } elseif ($minBeds > 0 && $beds < $minBeds) {
                $score -= 10;
            }

            return [
                'listing' => $listing,
                'score'   => max(0, min(100, $score)),
                'reasons' => $reasons,
            ];
        })
        ->filter(fn($item) => $item['score'] >= 30)
        ->sortByDesc('score')
        ->values();
    }
}
