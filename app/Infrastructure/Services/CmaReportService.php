<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\CmaReport;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Contact;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CmaReportService
{
    public function generate(
        array $data,
        ?Listing $listing = null,
        ?Contact $contact = null,
    ): CmaReport {
        $report = CmaReport::create([
            'agency_id' => auth()->user()->agency_id,
            'listing_id' => $listing?->id,
            'contact_id' => $contact?->id,
            'created_by' => auth()->id(),
            'title' => $data['title'],
            'subject_address' => $data['subject_address'],
            'estimated_value_low' => $data['estimated_value_low'] ?? null,
            'estimated_value_high' => $data['estimated_value_high'] ?? null,
            'recommended_list_price' => $data['recommended_list_price'] ?? null,
            'comparable_sales' => $data['comparable_sales'] ?? [],
            'market_stats' => $data['market_stats'] ?? [],
            'summary' => $data['summary'] ?? null,
        ]);

        $pdf = $this->generatePdf($report);
        $path = "cma-reports/{$report->id}/cma-report.pdf";
        Storage::put($path, $pdf);
        $report->update(['pdf_path' => $path]);

        return $report;
    }

    public function generatePdf(CmaReport $report): string
    {
        $pdf = Pdf::loadView('pdfs.cma-report', ['report' => $report]);
        return $pdf->output();
    }
}
