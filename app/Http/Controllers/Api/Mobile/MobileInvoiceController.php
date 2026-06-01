<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Application\Finance\Actions\MarkInvoicePaidAction;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $invoices = Invoice::with(['lease.listing.property', 'lineItems'])
            ->where('agency_id', $agencyId)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('due_date')
            ->paginate(20);

        return response()->json([
            'data' => $invoices->map(fn ($inv) => $this->formatInvoice($inv)),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'total'        => $invoices->total(),
            ],
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['lease.tenant.contact', 'lease.listing.property', 'lineItems']);

        return response()->json([
            'data' => array_merge($this->formatInvoice($invoice), [
                'tenant'     => $invoice->lease?->tenant?->contact?->only(['id', 'first_name', 'last_name', 'phone', 'email']),
                'property'   => $invoice->lease?->listing?->property?->only(['id', 'address_line_1', 'city']),
                'line_items' => $invoice->lineItems->map(fn ($item) => [
                    'description' => $item->description,
                    'category'    => $item->category,
                    'quantity'    => (float) $item->quantity,
                    'unit_price'  => (float) $item->unit_price,
                    'amount'      => (float) $item->amount,
                ]),
            ]),
        ]);
    }

    public function payNow(Invoice $invoice, PaymentGatewayInterface $gateway): JsonResponse
    {
        if (in_array($invoice->status, ['paid', 'void'])) {
            return response()->json(['message' => 'Invoice already settled.'], 422);
        }

        $result = $gateway->createPaymentLink($invoice);

        $invoice->update([
            'payment_gateway'   => 'payfast',
            'gateway_payment_id'=> $result['payment_id'],
            'gateway_payment_url'=> $result['url'],
        ]);

        return response()->json([
            'url'        => $result['url'],
            'payment_id' => $result['payment_id'],
        ]);
    }

    private function formatInvoice(Invoice $invoice): array
    {
        return [
            'id'           => $invoice->id,
            'reference'    => $invoice->reference,
            'type'         => $invoice->type,
            'status'       => $invoice->status,
            'subtotal'     => (float) $invoice->subtotal,
            'tax_amount'   => (float) $invoice->tax_amount,
            'total'        => (float) $invoice->total,
            'amount_paid'  => (float) $invoice->amount_paid,
            'balance'      => (float) $invoice->balance,
            'due_date'     => $invoice->due_date->toDateString(),
            'period_month' => $invoice->period_month,
            'period_year'  => $invoice->period_year,
            'property'     => $invoice->lease?->listing?->property
                ? "{$invoice->lease->listing->property->address_line_1}, {$invoice->lease->listing->property->city}"
                : null,
            'issued_at'    => $invoice->issued_at?->toDateTimeString(),
            'paid_at'      => $invoice->paid_at?->toDateTimeString(),
        ];
    }
}
