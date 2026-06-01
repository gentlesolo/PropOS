<?php

namespace App\Http\Controllers;

use App\Application\Finance\Actions\MarkInvoicePaidAction;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handlePayFastItn(
        Request $request,
        PaymentGatewayInterface $gateway,
        MarkInvoicePaidAction $markPaid,
    ): Response {
        try {
            $payload = $gateway->verifyWebhook($request);
        } catch (\RuntimeException $e) {
            Log::warning('PayFast ITN rejected', ['reason' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        $invoice = Invoice::where('gateway_payment_id', $payload['payment_id'])->first();

        if (! $invoice) {
            Log::warning('PayFast ITN: no invoice found', ['payment_id' => $payload['payment_id']]);
            return response('OK', 200);
        }

        if ($payload['status'] === 'COMPLETE') {
            $markPaid->execute(
                invoice: $invoice,
                amountPaid: $payload['amount'],
                method: 'payfast',
                reference: $payload['gateway_ref'],
            );

            Log::info('PayFast ITN: invoice marked paid', ['invoice_id' => $invoice->id]);
        } else {
            $invoice->update(['status' => 'sent']);

            Log::info('PayFast ITN: payment not completed', [
                'invoice_id' => $invoice->id,
                'status'     => $payload['status'],
            ]);
        }

        // PayFast requires a 200 OK to stop retrying
        return response('OK', 200);
    }
}
