<?php

namespace App\Application\Listing\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Viewing;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateSellerReportPdfAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function execute(Listing $listing): \Illuminate\Http\Response
    {
        $listing->load(['property', 'agent', 'media', 'portalSyncs.portal', 'agency']);

        $property     = $listing->property;
        $daysOnMarket = $listing->days_on_market
            ?? (int) now()->diffInDays($listing->mandate_start_date ?? $listing->created_at);

        $viewingsTotal    = Viewing::where('listing_id', $listing->id)->count();
        $viewingsComplete = Viewing::where('listing_id', $listing->id)->where('status', 'completed')->count();
        $portalSyncs      = $listing->portalSyncs->where('status', 'synced');
        $inquiryTotal     = $listing->portalSyncs->sum('inquiries_count') ?? 0;

        $narrative = $this->ai->generate(
            "You are a professional real estate agent writing a concise weekly seller progress report. Be honest, reassuring, and data-driven. Maximum 120 words.",
            implode("\n", [
                "Property: {$property->address_line_1}, {$property->city}",
                "Price: " . number_format((float) $listing->listing_price),
                "Days on market: {$daysOnMarket}",
                "Viewings: {$viewingsTotal} total, {$viewingsComplete} completed",
                "Portal inquiries: {$inquiryTotal}",
                "Portals active: " . $portalSyncs->count(),
                "Listing health score: " . ($listing->health_score ?? 'N/A') . "/100",
            ])
        );

        $pdf = Pdf::loadView('pdfs.seller-report', [
            'listing'          => $listing,
            'property'         => $property,
            'daysOnMarket'     => $daysOnMarket,
            'viewingsTotal'    => $viewingsTotal,
            'viewingsComplete' => $viewingsComplete,
            'portalSyncs'      => $portalSyncs,
            'inquiryTotal'     => $inquiryTotal,
            'narrative'        => $narrative,
            'generatedAt'      => now()->format('F j, Y'),
        ])->setPaper('a4', 'portrait');

        $filename = 'seller-report-' . str()->slug($property->address_line_1) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
