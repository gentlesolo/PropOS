<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceAction
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function execute(Invoice $invoice): void
    {
        $lease   = $invoice->lease?->load(['tenant.contact', 'listing.property']);
        $contact = $lease?->tenant?->contact ?? $lease?->contact;

        if (! $contact?->email) {
            return;
        }

        $property = $lease?->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';

        $lineItemsText = $invoice->lineItems->map(
            fn ($item) => "  {$item->description}: R " . number_format((float) $item->amount, 2)
        )->implode("\n");

        $body = "Dear {$contact->first_name},\n\n"
            . "Please find below your invoice for {$address}.\n\n"
            . "Invoice Reference: {$invoice->reference}\n"
            . "Period: {$invoice->period_month}/{$invoice->period_year}\n"
            . "Due Date: " . $invoice->due_date->format('d M Y') . "\n\n"
            . "Items:\n{$lineItemsText}\n\n"
            . "Subtotal: R " . number_format((float) $invoice->subtotal, 2) . "\n"
            . (((float) $invoice->tax_amount) > 0 ? "Tax: R " . number_format((float) $invoice->tax_amount, 2) . "\n" : '')
            . "Total Due: R " . number_format((float) $invoice->total, 2) . "\n\n"
            . "Please arrange payment before the due date.\n\nKind regards,\nProperty Management";

        try {
            Mail::raw(
                $body,
                fn ($msg) => $msg
                    ->to($contact->email, $contact->full_name)
                    ->subject("Invoice {$invoice->reference} — R " . number_format((float) $invoice->total, 2))
            );

            $invoice->update(['status' => 'sent', 'issued_at' => now()]);

            if ($lease?->assigned_agent_id) {
                $this->notifications->notifyUser(
                    $lease->assigned_agent_id,
                    'invoice_sent',
                    'Invoice Sent',
                    "Invoice {$invoice->reference} sent to {$contact->full_name}.",
                    '/finance/invoices',
                    'info',
                );
            }
        } catch (\Exception $e) {
            Log::error('Invoice email failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
        }
    }
}
