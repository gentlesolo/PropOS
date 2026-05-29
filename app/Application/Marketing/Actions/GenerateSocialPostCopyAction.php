<?php

namespace App\Application\Marketing\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\ListingGraphic;
use Illuminate\Support\Facades\Log;

class GenerateSocialPostCopyAction
{
    /** Character limits and tone per channel */
    private const CHANNEL_CONFIG = [
        'instagram' => [
            'limit'      => 2200,
            'tone'       => 'visually-driven, lifestyle-focused, aspirational',
            'guidelines' => 'Use 3–5 emojis. End with a call to action. Include 20–25 relevant hashtags on a new line.',
        ],
        'facebook' => [
            'limit'      => 500,
            'tone'       => 'warm, conversational, community-oriented',
            'guidelines' => 'Write 2–3 short paragraphs. Include a link placeholder [LINK]. Add 3–5 hashtags.',
        ],
        'linkedin' => [
            'limit'      => 700,
            'tone'       => 'professional, investment-focused, data-driven',
            'guidelines' => 'Lead with a compelling statistic or insight. Mention investment or lifestyle value. Use 3 hashtags max.',
        ],
        'whatsapp' => [
            'limit'      => 500,
            'tone'       => 'direct, friendly, personal',
            'guidelines' => 'Keep it conversational as if texting a contact. Include key facts: price, beds, location. End with a clear CTA.',
        ],
        'twitter' => [
            'limit'      => 280,
            'tone'       => 'punchy, concise, attention-grabbing',
            'guidelines' => 'One powerful sentence + key stat. Max 2 hashtags. Include [LINK].',
        ],
    ];

    public function __construct(private AiCompletionServiceInterface $ai) {}

    /**
     * Generate platform-specific post copy for all channels and attach to the graphic.
     *
     * @return array<string, array{caption: string, hashtags: string[], char_count: int}>
     */
    public function executeForListing(Listing $listing, ?string $goal = 'maximise_inquiries'): array
    {
        $property = $listing->property;
        $agency   = $listing->agency;

        $propertyContext = implode("\n", array_filter([
            "Property: {$property->property_type} in {$property->city}" . ($property->suburb ? ", {$property->suburb}" : ''),
            "Address: {$property->address_line_1}",
            "Price: " . ($agency->currency ?? 'NGN') . ' ' . number_format((float) $listing->listing_price),
            $property->bedrooms  ? "Bedrooms: {$property->bedrooms}" : null,
            $property->bathrooms ? "Bathrooms: {$property->bathrooms}" : null,
            $property->floor_area_sqm ? "Size: {$property->floor_area_sqm} m²" : null,
            $listing->headline ? "Headline: {$listing->headline}" : null,
            $listing->description_short ? "Description: {$listing->description_short}" : null,
            "Mandate: {$listing->mandate_type}",
            "Goal: " . str_replace('_', ' ', $goal),
            "Agency: {$agency->name}",
        ]));

        $results = [];

        foreach (self::CHANNEL_CONFIG as $channel => $config) {
            $results[$channel] = $this->generateForChannel($channel, $config, $propertyContext);
        }

        return $results;
    }

    /**
     * Generate copy for a single channel and save it to a ListingGraphic record.
     */
    public function attachToGraphic(ListingGraphic $graphic, Listing $listing): void
    {
        $channel = $graphic->channel;
        $config  = self::CHANNEL_CONFIG[$channel] ?? self::CHANNEL_CONFIG['instagram'];

        $property = $listing->property;
        $agency   = $listing->agency;

        $context = implode("\n", array_filter([
            "{$property->property_type} in {$property->city}",
            "Price: " . number_format((float) $listing->listing_price),
            $property->bedrooms  ? "{$property->bedrooms} beds" : null,
            $property->bathrooms ? "{$property->bathrooms} baths" : null,
            $listing->headline ?? null,
            "Agency: {$agency->name}",
        ]));

        $copy = $this->generateForChannel($channel, $config, $context);

        $graphic->update(['post_copy' => $copy]);
    }

    private function generateForChannel(string $channel, array $config, string $context): array
    {
        $systemPrompt = implode(' ', [
            "You are an expert real estate social media copywriter.",
            "Channel: {$channel}.",
            "Tone: {$config['tone']}.",
            "Guidelines: {$config['guidelines']}.",
            "Character limit: {$config['limit']} characters.",
            "Return a JSON object with exactly these keys:",
            '"caption" (the full post text ready to publish),',
            '"hashtags" (array of hashtag strings without #),',
            '"short_version" (max 80 characters, for notifications/previews).',
            "Do NOT include markdown fences. Output raw JSON only.",
        ]);

        $userPrompt = "Write a {$channel} post for this property:\n\n{$context}";

        try {
            $raw     = $this->ai->generate($systemPrompt, $userPrompt, ['temperature' => 0.82]);
            $parsed  = json_decode($raw, true);

            if (! is_array($parsed) || empty($parsed['caption'])) {
                throw new \RuntimeException('Invalid JSON response from AI');
            }

            return [
                'caption'       => $parsed['caption'],
                'hashtags'      => (array) ($parsed['hashtags'] ?? []),
                'short_version' => $parsed['short_version'] ?? mb_substr($parsed['caption'], 0, 80),
                'char_count'    => mb_strlen($parsed['caption']),
                'channel'       => $channel,
            ];
        } catch (\Exception $e) {
            Log::warning("Social copy generation failed for {$channel}", ['error' => $e->getMessage()]);

            // Deterministic fallback — always returns usable content
            return $this->fallbackCopy($channel, $context);
        }
    }

    private function fallbackCopy(string $channel, string $context): array
    {
        $lines   = explode("\n", $context);
        $first   = $lines[0] ?? 'Beautiful property';
        $caption = match ($channel) {
            'instagram' => "✨ {$first}\n\nThis is a property you need to see! Book your private viewing today. 🏡\n\n#RealEstate #PropertyForSale #NewListing #DreamHome",
            'facebook'  => "{$first}\n\nAn incredible opportunity for buyers and investors. Contact us today to arrange a viewing. [LINK]",
            'linkedin'  => "{$first}\n\nAn outstanding opportunity in the current market. Reach out to discuss investment potential.",
            'whatsapp'  => "Hi! 👋 We have a new listing you might love:\n\n{$first}\n\nReply to book a viewing!",
            'twitter'   => "New listing! {$first} — book your viewing today [LINK] #RealEstate",
            default     => $first,
        };

        return [
            'caption'       => $caption,
            'hashtags'      => ['RealEstate', 'PropertyForSale', 'NewListing'],
            'short_version' => mb_substr($caption, 0, 80),
            'char_count'    => mb_strlen($caption),
            'channel'       => $channel,
        ];
    }
}
