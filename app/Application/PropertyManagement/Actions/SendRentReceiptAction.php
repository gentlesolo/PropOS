<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\RentPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRentReceiptAction
{
    public function execute(RentPayment $payment): void
    {
        $payment->loadMissing(['lease.tenant.contact', 'lease.listing.property', 'agency']);

        $this->syncInvoice($payment);
        $this->email($payment);
    }

    private function syncInvoice(RentPayment $payment): void
    {
        if (! $payment->lease_id || ! $payment->due_date) {
            return;
        }

        $invoice = Invoice::where('lease_id', $payment->lease_id)
            ->where('period_month', $payment->due_date->month)
            ->where('period_year', $payment->due_date->year)
            ->first();

        if (! $invoice) {
            return;
        }

        $amountPaid = (float) ($payment->amount_paid ?? 0);
        $total      = (float) $invoice->total;

        $status = match (true) {
            $amountPaid >= $total && $total > 0 => 'paid',
            $amountPaid > 0                     => 'partially_paid',
            default                             => $invoice->status,
        };

        $invoice->update([
            'amount_paid' => $amountPaid,
            'status'      => $status,
            'paid_at'     => $status === 'paid' && ! $invoice->paid_at ? now() : $invoice->paid_at,
        ]);
    }

    private function email(RentPayment $payment): void
    {
        $lease   = $payment->lease;
        $contact = $lease?->tenant?->contact ?? $lease?->contact;

        if (! $contact?->email) {
            return;
        }

        $agency     = $payment->agency;
        $color      = $agency?->primary_color ?? '#10B981';
        $currSymbol = $agency?->currency_symbol ?? '₦';
        $property   = $lease?->listing?->property;
        $address    = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $isPartial  = $payment->status === 'partial';

        $amountDue  = (float) $payment->amount_due;
        $amountPaid = (float) ($payment->amount_paid ?? 0);
        $balance    = round($amountDue - $amountPaid, 2);
        $paidDate   = $payment->paid_date
            ? Carbon::parse($payment->paid_date)->format('d M Y')
            : now()->format('d M Y');

        $subject = $isPartial
            ? "Partial Payment Received — {$payment->reference}"
            : "Payment Receipt — {$payment->reference}";

        try {
            $pdf      = Pdf::loadView('pdfs.rent-receipt', ['payment' => $payment, 'color' => $color])
                ->setPaper('a4', 'portrait');
            $pdfBytes = $pdf->output();
            $filename = "receipt-{$payment->reference}.pdf";

            $html = $this->buildEmailHtml(
                $contact, $address, $payment, $isPartial,
                $amountDue, $amountPaid, $balance, $paidDate,
                $color, $currSymbol, $agency
            );

            Mail::send([], [], function ($msg) use ($contact, $subject, $html, $pdfBytes, $filename) {
                $msg->to($contact->email, $contact->full_name)
                    ->subject($subject)
                    ->html($html)
                    ->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
            });
        } catch (\Exception $e) {
            Log::error('Rent receipt email failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function buildEmailHtml(
        $contact, string $address, RentPayment $payment,
        bool $isPartial, float $amountDue, float $amountPaid, float $balance,
        string $paidDate, string $color, string $currSymbol, $agency
    ): string {
        // Pre-compute all expressions — heredoc cannot handle ??, ?->, or ternaries inside {…}
        $firstName    = $contact->first_name ?? 'Tenant';
        $period       = $payment->due_date->format('F Y');
        $ref          = $payment->reference;
        $method       = ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'EFT'));
        $agencyName   = $agency?->name ?? 'Property Management';
        $dueFmt       = $currSymbol . number_format($amountDue, 2);
        $paidFmt      = $currSymbol . number_format($amountPaid, 2);
        $balanceFmt   = $currSymbol . number_format($balance, 2);
        $title        = $isPartial ? 'Partial Payment Received' : 'Payment Receipt';
        $intro        = $isPartial
            ? "We have received a partial payment for <strong>{$address}</strong> ({$period})."
            : "Thank you for your payment for <strong>{$address}</strong> ({$period}).";

        $balanceRow = $isPartial
            ? "<tr><td style='padding:8px 16px;color:#6b7280;'>Outstanding Balance</td><td style='padding:8px 16px;font-weight:700;color:#991b1b;'>{$balanceFmt}</td></tr>"
            : "<tr><td style='padding:8px 16px;color:#6b7280;'>Balance</td><td style='padding:8px 16px;font-weight:700;color:#065f46;'>Fully Paid ✓</td></tr>";

        $notice = $isPartial
            ? "<p style='color:#92400e;background:#fef3c7;border-radius:8px;padding:12px 16px;font-size:13px;margin-top:20px;'>Please arrange payment of the remaining <strong>{$balanceFmt}</strong> to avoid penalties.</p>"
            : "<p style='color:#065f46;background:#d1fae5;border-radius:8px;padding:12px 16px;font-size:13px;margin-top:20px;'>Your account is up to date for this period. Thank you!</p>";

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;font-size:14px;color:#1a1a2e;background:#f9fafb;margin:0;padding:0;}
.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);}
.hdr{background:{$color};color:#fff;padding:28px 36px;}
.hdr h1{margin:0;font-size:20px;}
.hdr p{margin:4px 0 0;font-size:12px;opacity:.85;}
.body{padding:32px 36px;line-height:1.7;color:#374151;}
.footer{padding:20px 36px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;}
table{width:100%;border-collapse:collapse;}
</style></head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>{$title}</h1>
    <p>{$address} &mdash; {$ref}</p>
  </div>
  <div class="body">
    <p>Dear {$firstName},</p>
    <p style="margin-top:12px;">{$intro}</p>

    <table style="margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
      <tr style="background:#f9fafb;">
        <td style="padding:8px 16px;color:#6b7280;font-size:12px;" colspan="2"><strong>RECEIPT SUMMARY</strong></td>
      </tr>
      <tr><td style="padding:8px 16px;color:#6b7280;">Reference</td><td style="padding:8px 16px;font-weight:600;">{$ref}</td></tr>
      <tr style="background:#f9fafb;"><td style="padding:8px 16px;color:#6b7280;">Period</td><td style="padding:8px 16px;font-weight:600;">{$period}</td></tr>
      <tr><td style="padding:8px 16px;color:#6b7280;">Payment Date</td><td style="padding:8px 16px;font-weight:600;">{$paidDate}</td></tr>
      <tr style="background:#f9fafb;"><td style="padding:8px 16px;color:#6b7280;">Method</td><td style="padding:8px 16px;font-weight:600;">{$method}</td></tr>
      <tr><td style="padding:8px 16px;color:#6b7280;">Amount Due</td><td style="padding:8px 16px;font-weight:600;">{$dueFmt}</td></tr>
      <tr style="background:{$color};color:#fff;"><td style="padding:10px 16px;font-weight:700;">Amount Paid</td><td style="padding:10px 16px;font-weight:700;">{$paidFmt}</td></tr>
      {$balanceRow}
    </table>

    {$notice}

    <p style="margin-top:20px;font-size:12px;color:#6b7280;">A PDF copy of this receipt is attached for your records.</p>
  </div>
  <div class="footer">Issued by {$agencyName}. Please retain this email and the attached PDF for your records.</div>
</div>
</body>
</html>
HTML;
    }
}
