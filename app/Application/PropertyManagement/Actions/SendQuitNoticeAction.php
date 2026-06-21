<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\QuitNotice;
use App\Infrastructure\Persistence\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendQuitNoticeAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(QuitNotice $quitNotice): QuitNotice
    {
        $quitNotice->load('lease.tenant.contact', 'lease.listing.property', 'issuedBy', 'agency');

        $contact = $quitNotice->lease?->tenant?->contact;
        $email   = $contact?->email;

        if ($email) {
            try {
                $pdf = Pdf::loadView('pdfs.quit-notice', ['quitNotice' => $quitNotice])
                    ->setPaper('a4', 'portrait');

                $pdfContent  = $pdf->output();
                $filename    = "quit-notice-{$quitNotice->reference}.pdf";
                $property    = $quitNotice->lease?->listing?->property;
                $address     = $property
                    ? "{$property->address_line_1}, {$property->city}"
                    : 'the leased premises';

                Mail::send([], [], function ($message) use ($email, $contact, $quitNotice, $pdfContent, $filename, $address) {
                    $message
                        ->to($email, $contact?->full_name)
                        ->subject("Quit Notice — {$address} [{$quitNotice->reference}]")
                        ->html($this->buildEmailHtml($quitNotice))
                        ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
                });
            } catch (\Exception $e) {
                Log::error('Quit notice email failed', [
                    'quit_notice_id' => $quitNotice->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $quitNotice->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        // Notify the issuing agent
        $this->notifications->notifyUser(
            $quitNotice->issued_by,
            'quit_notice_sent',
            'Quit Notice Sent',
            "Quit notice {$quitNotice->reference} sent to {$contact?->full_name}. Vacate by: {$quitNotice->vacate_by_date->format('d M Y')}.",
            '/property-management/quit-notices',
            'info',
        );

        // Notify managers
        $managers = User::where('agency_id', $quitNotice->agency_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($managers as $manager) {
            if ($manager->id !== $quitNotice->issued_by) {
                $this->notifications->notifyUser(
                    $manager,
                    'quit_notice_sent',
                    'Quit Notice Issued',
                    "Quit notice {$quitNotice->reference} issued for {$contact?->full_name}.",
                    '/property-management/quit-notices',
                    'warning',
                );
            }
        }

        return $quitNotice->fresh();
    }

    private function buildEmailHtml(QuitNotice $quitNotice): string
    {
        $contact    = $quitNotice->lease?->tenant?->contact;
        $property   = $quitNotice->lease?->listing?->property;
        $address    = $property ? "{$property->address_line_1}, {$property->city}" : 'the leased premises';
        $agency     = $quitNotice->agency;
        $color      = $agency?->primary_color ?? '#10B981';
        $body       = nl2br(e($quitNotice->notice_body));
        $agencyName = $agency?->name ?? 'your property agent';
        $leaseRef   = $quitNotice->lease?->reference ?? '—';
        $vacateDate = $quitNotice->vacate_by_date->format('d M Y');
        $issueDate  = $quitNotice->issue_date->format('d M Y');
        $ref        = $quitNotice->reference;

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;font-size:14px;color:#1a1a2e;background:#f9fafb;margin:0;padding:0;}
.wrap{max-width:640px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);}
.hdr{background:{$color};color:#fff;padding:28px 36px;}
.hdr h1{margin:0;font-size:20px;}
.hdr p{margin:4px 0 0;font-size:12px;opacity:.85;}
.body{padding:32px 36px;line-height:1.8;color:#374151;}
.ref{background:#f3f4f6;border-radius:8px;padding:12px 16px;font-size:12px;color:#6b7280;margin-bottom:20px;}
.footer{padding:20px 36px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;}
</style></head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>Quit Notice</h1>
    <p>{$address} &mdash; {$ref}</p>
  </div>
  <div class="body">
    <div class="ref">Lease: {$leaseRef} &bull; Vacate by: {$vacateDate} &bull; Issued: {$issueDate}</div>
    <div>{$body}</div>
  </div>
  <div class="footer">This notice was issued by {$agencyName}. Please retain this email for your records.</div>
</div>
</body>
</html>
HTML;
    }
}
