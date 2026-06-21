<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\QuitNotice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ExportQuitNoticePdfAction
{
    public function execute(QuitNotice $quitNotice): Response
    {
        $quitNotice->load('lease.tenant.contact', 'lease.listing.property', 'issuedBy', 'agency');

        $pdf = Pdf::loadView('pdfs.quit-notice', ['quitNotice' => $quitNotice])
            ->setPaper('a4', 'portrait');

        return $pdf->download("quit-notice-{$quitNotice->reference}.pdf");
    }
}
