<?php

namespace App\Http\Controllers\Api;

use App\Application\Listing\Actions\GenerateSellerReportPdfAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Listing;

class ReportController extends Controller
{
    public function sellerReport(Listing $listing, GenerateSellerReportPdfAction $action)
    {
        // Only the listing's agent or a principal may download
        $user = auth()->user();
        if ($listing->agency_id !== $user->agency_id) {
            abort(403);
        }

        return $action->execute($listing);
    }
}
