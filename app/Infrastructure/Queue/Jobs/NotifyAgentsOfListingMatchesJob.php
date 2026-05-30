<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Application\CRM\Actions\MatchBuyersToListingAction;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAgentsOfListingMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly int $listingId) {}

    public function handle(MatchBuyersToListingAction $matchAction): void
    {
        $listing = Listing::with(['property', 'agency'])->find($this->listingId);

        if (!$listing) {
            return;
        }

        $matches = $matchAction->execute($listing);

        if ($matches->isEmpty()) {
            return;
        }

        // Group matched contacts by their assigned agent
        $byAgent = $matches->groupBy(fn($item) => $item['contact']->assigned_agent_id);

        foreach ($byAgent as $agentId => $agentMatches) {
            $agent = $agentMatches->first()['contact']->agent ?? null;

            if (!$agent?->email) {
                continue;
            }

            $this->notifyAgent($agent, $listing, $agentMatches);
        }

        Log::info('Buyer-listing match notifications dispatched', [
            'listing_id' => $listing->id,
            'match_count' => $matches->count(),
        ]);
    }

    private function notifyAgent($agent, Listing $listing, $matches): void
    {
        $property = $listing->property;
        $address = trim(($property->address_line_1 ?? '') . ', ' . ($property->city ?? ''));
        $price = '₦' . number_format((float) $listing->listing_price);

        $lines = [
            "Hi {$agent->first_name},",
            "",
            "A new listing matches {$matches->count()} buyer(s) in your contact list:",
            "",
            "Property: {$address}",
            "Price: {$price}",
            "Type: " . ($property->property_type ?? 'N/A'),
            "Bedrooms: " . ($property->bedrooms ?? 'N/A'),
            "",
            "Matched buyers:",
        ];

        foreach ($matches->take(10) as $item) {
            $contact = $item['contact'];
            $score = $item['score'];
            $reasons = implode(', ', $item['reasons']);
            $lines[] = "  - {$contact->full_name} (match score: {$score}/100) — {$reasons}";
        }

        if ($matches->count() > 10) {
            $lines[] = "  ... and " . ($matches->count() - 10) . " more.";
        }

        $lines[] = "";
        $lines[] = "Log in to PropOS to view these contacts and send them the listing.";

        $body = implode("\n", $lines);

        try {
            Mail::raw($body, fn($m) => $m
                ->to($agent->email, $agent->first_name . ' ' . $agent->last_name)
                ->subject("New listing match: {$matches->count()} buyer(s) for {$address}")
            );
        } catch (\Exception $e) {
            Log::error('Listing match notification failed', [
                'listing_id' => $this->listingId,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
