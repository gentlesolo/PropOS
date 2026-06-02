<?php

namespace App\Http\Controllers\Governance;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\ComplianceDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $agencyId = auth()->user()->agency_id;
        $category = $request->input('category');
        $status   = $request->input('status');

        return response()->streamDownload(function () use ($agencyId, $category, $status) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Title', 'Category', 'Type', 'Status', 'Expiry Date', 'Uploaded By', 'Linked To', 'Uploaded At']);

            ComplianceDocument::with(['uploadedBy', 'transaction', 'lease', 'listing.property', 'property'])
                ->where('agency_id', $agencyId)
                ->when($category, fn ($q) => $q->where('category', $category))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')
                ->get()
                ->each(function (ComplianceDocument $doc) use ($out) {
                    $linkedTo = match (true) {
                        (bool) $doc->transaction_id => 'Transaction: ' . $doc->transaction?->reference,
                        (bool) $doc->lease_id       => 'Lease #' . $doc->lease_id,
                        (bool) $doc->listing_id     => 'Listing: ' . $doc->listing?->property?->address_line_1,
                        (bool) $doc->property_id    => 'Property: ' . $doc->property?->address_line_1,
                        default                     => 'Standalone',
                    };

                    fputcsv($out, [
                        $doc->title,
                        str_replace('_', ' ', $doc->category),
                        $doc->document_type,
                        $doc->status,
                        $doc->expiry_date?->format('d M Y') ?? '',
                        $doc->uploadedBy?->name ?? '',
                        $linkedTo,
                        $doc->created_at->format('d M Y H:i'),
                    ]);
                });

            fclose($out);
        }, 'documents-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
