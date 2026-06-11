<?php

namespace App\Http\Controllers;

use App\Application\Finance\Actions\MarkInvoicePaidAction;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Payment\PaystackWebhookHandler;
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

    /**
     * User-facing return URL after Paystack checkout.
     * Verifies the reference and updates the agency accordingly.
     */
    public function verifyPaystackSubscription(Request $request, PaystackSubscriptionService $paystack)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('settings.billing')->with('error', 'No reference supplied.');
        }

        try {
            $data = $paystack->verifyTransaction($reference);

            if ($data['status'] !== 'success') {
                return redirect()->route('settings.billing')->with('error', 'Payment was not successful.');
            }

            $agencyId = $data['metadata']['agency_id'] ?? null;
            $type     = $data['metadata']['type'] ?? null;

            if (! $agencyId) {
                return redirect()->route('settings.billing')->with('error', 'Invalid payment metadata.');
            }

            /** @var Agency|null $agency */
            $agency = Agency::find($agencyId);

            if (! $agency) {
                return redirect()->route('settings.billing')->with('error', 'Agency not found.');
            }

            if ($type === 'subscription') {
                $plan  = $data['metadata']['plan'];
                $cycle = $data['metadata']['cycle'] ?? 'monthly';

                $agency->update([
                    'subscription_plan'            => $plan,
                    'subscription_status'          => 'active',
                    'billing_cycle'                => $cycle,
                    'paystack_customer_code'       => $data['customer_code'] ?? $agency->paystack_customer_code,
                    'ai_credits_balance'           => config("pricing.plans.{$plan}.ai_credits_monthly"),
                    'ai_credits_allocated_monthly' => config("pricing.plans.{$plan}.ai_credits_monthly"),
                ]);

                $paystack->logTransaction($agency, $data, 'subscription', [
                    'plan'          => $plan,
                    'billing_cycle' => $cycle,
                ]);

                Log::info('Paystack billing verified: subscription', ['agency_id' => $agency->id, 'plan' => $plan]);

                return redirect()->route('settings.billing')->with('success', 'Plan upgraded successfully!');
            }

            if ($type === 'topup') {
                $credits = (int) ($data['metadata']['credits'] ?? 0);

                $agency->increment('ai_credits_balance', $credits);

                $paystack->logTransaction($agency, $data, 'topup', ['credits_added' => $credits]);

                Log::info('Paystack billing verified: topup', ['agency_id' => $agency->id, 'credits' => $credits]);

                return redirect()->route('settings.billing')->with('success', 'AI Credits topped up successfully!');
            }

            return redirect()->route('settings.billing')->with('error', 'Unknown payment type.');
        } catch (\Exception $e) {
            Log::error('Paystack verification failed', ['error' => $e->getMessage(), 'reference' => $reference]);
            return redirect()->route('settings.billing')->with('error', 'Could not verify payment.');
        }
    }

    /**
     * Server-to-server Paystack webhook (charge.success, subscription.*, etc.).
     * Must return 200 immediately; Paystack retries on non-200.
     */
    public function handlePaystackWebhook(Request $request, PaystackWebhookHandler $handler): Response
    {
        try {
            $payload = $handler->verify($request);
        } catch (\RuntimeException $e) {
            Log::warning('Paystack webhook rejected', ['reason' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        try {
            $handler->dispatch($payload['event'], $payload['data']);
        } catch (\Throwable $e) {
            Log::error('Paystack webhook dispatch error', [
                'event' => $payload['event'],
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
