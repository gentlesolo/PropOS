<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerateSellerReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(AiCompletionServiceInterface $ai): void
    {
        $agencies = Agency::all();

        foreach ($agencies as $agency) {
            $this->generateReportsForAgency($agency, $ai);
        }
    }

    private function generateReportsForAgency(Agency $agency, AiCompletionServiceInterface $ai): void
    {
        $activeListings = Listing::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->with(['property', 'agent', 'media', 'portalSyncs'])
            ->get();

        foreach ($activeListings as $listing) {
            $this->sendSellerReport($listing, $ai);
        }
    }

    private function sendSellerReport(Listing $listing, AiCompletionServiceInterface $ai): void
    {
        // Resolve seller contact — stored on the listing or its property
        $sellerEmail = $listing->seller_email ?? null;
        if (! $sellerEmail) {
            return;
        }

        $property   = $listing->property;
        $address    = "{$property->address_line_1}, {$property->city}";
        $daysOnMarket = $listing->days_on_market ?? $listing->created_at->diffInDays(now());
        $viewingCount = $listing->viewings()->count() ?? 0;
        $inquiryCount = $listing->portalSyncs->sum('inquiries_count') ?? 0;

        $contextPrompt = implode("\n", [
            "Property: {$address}",
            "Listing price: " . number_format((float) $listing->listing_price),
            "Days on market: {$daysOnMarket}",
            "Total viewings: {$viewingCount}",
            "Portal inquiries: {$inquiryCount}",
            "Portals active: " . $listing->portalSyncs->where('status', 'synced')->count(),
        ]);

        $narrative = $ai->generate(
            "You are a professional real estate agent writing a brief seller progress report. Be honest, professional, and encouraging. Keep it under 150 words.",
            "Write a seller weekly report narrative for: {$contextPrompt}"
        );

        $body = implode("\n\n", [
            "Dear Seller,",
            "Here is your weekly update for {$address}:",
            "- Days on market: {$daysOnMarket}",
            "- Viewings conducted: {$viewingCount}",
            "- Portal inquiries: {$inquiryCount}",
            $narrative,
            "Please don't hesitate to contact us if you have any questions.",
            "Regards,\n" . ($listing->agent?->name ?? 'Your Agent'),
        ]);

        try {
            Mail::raw($body, function ($message) use ($sellerEmail, $address) {
                $message->to($sellerEmail)
                        ->subject("Weekly update — {$address}");
            });

            Log::info('Seller report sent', ['listing_id' => $listing->id, 'to' => $sellerEmail]);
        } catch (\Exception $e) {
            Log::error('Seller report failed', ['listing_id' => $listing->id, 'error' => $e->getMessage()]);
        }
    }
}
