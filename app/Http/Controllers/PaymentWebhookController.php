<?php

namespace App\Http\Controllers;

use App\Application\Finance\Actions\MarkInvoicePaidAction;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Payment\PaystackSubscriptionService;
use App\Infrastructure\Persistence\Models\Agency;
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

    public function verifyPaystackSubscription(Request $request, PaystackSubscriptionService $paystack)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return redirect()->route('settings.billing')->with('error', 'No reference supplied.');
        }

        try {
            $data = $paystack->verifyTransaction($reference);
            
            if ($data['status'] === 'success') {
                $agencyId = $data['metadata']['agency_id'] ?? null;
                if ($agencyId) {
                    $agency = Agency::find($agencyId);
                    
                    if ($data['metadata']['type'] === 'subscription') {
                        $agency->update([
                            'subscription_plan' => $data['metadata']['plan'],
                            'billing_cycle' => $data['metadata']['cycle'],
                            'paystack_customer_code' => $data['customer_code'] ?? $agency->paystack_customer_code,
                            'ai_credits_balance' => config("pricing.plans.{$data['metadata']['plan']}.ai_credits_monthly"),
                            'ai_credits_allocated_monthly' => config("pricing.plans.{$data['metadata']['plan']}.ai_credits_monthly"),
                        ]);
                        return redirect()->route('settings.billing')->with('success', 'Plan upgraded successfully!');
                    } elseif ($data['metadata']['type'] === 'topup') {
                        $agency->increment('ai_credits_balance', $data['metadata']['credits']);
                        return redirect()->route('settings.billing')->with('success', 'AI Credits topped up successfully!');
                    }
                }
            }
            
            return redirect()->route('settings.billing')->with('error', 'Payment was not successful.');
        } catch (\Exception $e) {
            Log::error('Paystack verification failed', ['error' => $e->getMessage()]);
            return redirect()->route('settings.billing')->with('error', 'Could not verify payment.');
        }
    }
}
