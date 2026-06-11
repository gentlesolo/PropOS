<?php

namespace App\Infrastructure\Payment;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\PaystackTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookHandler
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', '');
    }

    /**
     * Verify HMAC-SHA512 signature and return the decoded event payload.
     * Throws \RuntimeException on invalid signature.
     */
    public function verify(Request $request): array
    {
        $signature = $request->header('X-Paystack-Signature');

        if (! $signature) {
            throw new \RuntimeException('Missing X-Paystack-Signature header.');
        }

        $expected = hash_hmac('sha512', $request->getContent(), $this->secretKey);

        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('Paystack webhook signature mismatch.');
        }

        $payload = json_decode($request->getContent(), true);

        if (! isset($payload['event'], $payload['data'])) {
            throw new \RuntimeException('Malformed Paystack webhook payload.');
        }

        return $payload;
    }

    /**
     * Dispatch to the correct handler based on the event type.
     */
    public function dispatch(string $event, array $data): void
    {
        match ($event) {
            'charge.success'         => $this->handleChargeSuccess($data),
            'subscription.create'    => $this->handleSubscriptionCreate($data),
            'subscription.disable'   => $this->handleSubscriptionDisable($data),
            'subscription.not_renew' => $this->handleSubscriptionNotRenew($data),
            default                  => Log::info('Paystack webhook unhandled event', ['event' => $event]),
        };
    }

    /**
     * charge.success — fired when a one-off or recurring charge succeeds.
     */
    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $agencyId  = $data['metadata']['agency_id'] ?? null;
        $type      = $data['metadata']['type'] ?? null;

        if (! $agencyId) {
            Log::warning('Paystack charge.success: missing agency_id in metadata', ['reference' => $reference]);
            return;
        }

        $agency = Agency::find($agencyId);

        if (! $agency) {
            Log::warning('Paystack charge.success: agency not found', ['agency_id' => $agencyId]);
            return;
        }

        // Avoid duplicate processing
        if (PaystackTransaction::where('reference', $reference)->exists()) {
            Log::info('Paystack charge.success: already processed', ['reference' => $reference]);
            return;
        }

        $txBase = [
            'agency_id'               => $agency->id,
            'reference'               => $reference,
            'event'                   => 'charge.success',
            'status'                  => 'success',
            'amount'                  => $data['amount'],
            'currency'                => $data['currency'] ?? 'NGN',
            'paystack_transaction_id' => (string) ($data['id'] ?? ''),
            'paystack_customer_code'  => $data['customer']['customer_code'] ?? null,
            'authorization_code'      => $data['authorization']['authorization_code'] ?? null,
            'metadata'                => $data['metadata'] ?? null,
        ];

        if ($type === 'subscription') {
            $plan  = $data['metadata']['plan'] ?? null;
            $cycle = $data['metadata']['cycle'] ?? 'monthly';

            PaystackTransaction::create(array_merge($txBase, [
                'type'         => 'subscription',
                'plan'         => $plan,
                'billing_cycle'=> $cycle,
            ]));

            $agency->update([
                'subscription_plan'            => $plan,
                'subscription_status'          => 'active',
                'billing_cycle'                => $cycle,
                'paystack_customer_code'       => $data['customer']['customer_code'] ?? $agency->paystack_customer_code,
                'ai_credits_balance'           => config("pricing.plans.{$plan}.ai_credits_monthly"),
                'ai_credits_allocated_monthly' => config("pricing.plans.{$plan}.ai_credits_monthly"),
            ]);

            Log::info('Paystack charge.success: subscription activated', [
                'agency_id' => $agency->id,
                'plan'      => $plan,
            ]);
        } elseif ($type === 'topup') {
            $credits = (int) ($data['metadata']['credits'] ?? 0);

            PaystackTransaction::create(array_merge($txBase, [
                'type'         => 'topup',
                'credits_added'=> $credits,
            ]));

            $agency->increment('ai_credits_balance', $credits);

            Log::info('Paystack charge.success: topup applied', [
                'agency_id' => $agency->id,
                'credits'   => $credits,
            ]);
        } else {
            // Generic charge — just log it
            PaystackTransaction::create(array_merge($txBase, ['type' => 'charge']));
        }
    }

    /**
     * subscription.create — Paystack created a recurring subscription.
     */
    private function handleSubscriptionCreate(array $data): void
    {
        $customerCode      = $data['customer']['customer_code'] ?? null;
        $subscriptionCode  = $data['subscription_code'] ?? null;

        if (! $customerCode) {
            return;
        }

        $agency = Agency::where('paystack_customer_code', $customerCode)->first();

        if (! $agency) {
            Log::warning('Paystack subscription.create: agency not found', ['customer_code' => $customerCode]);
            return;
        }

        $agency->update(['paystack_subscription_code' => $subscriptionCode]);

        Log::info('Paystack subscription.create: subscription code stored', [
            'agency_id'         => $agency->id,
            'subscription_code' => $subscriptionCode,
        ]);
    }

    /**
     * subscription.disable — subscription was cancelled or payment failed repeatedly.
     */
    private function handleSubscriptionDisable(array $data): void
    {
        $subscriptionCode = $data['subscription_code'] ?? null;
        $customerCode     = $data['customer']['customer_code'] ?? null;

        $agency = $this->findAgencyBySubscription($subscriptionCode, $customerCode);

        if (! $agency) {
            return;
        }

        $agency->update(['subscription_status' => 'cancelled']);

        Log::info('Paystack subscription.disable: subscription cancelled', [
            'agency_id'         => $agency->id,
            'subscription_code' => $subscriptionCode,
        ]);
    }

    /**
     * subscription.not_renew — subscriber opted out of renewal (will expire at period end).
     */
    private function handleSubscriptionNotRenew(array $data): void
    {
        $subscriptionCode = $data['subscription_code'] ?? null;
        $customerCode     = $data['customer']['customer_code'] ?? null;

        $agency = $this->findAgencyBySubscription($subscriptionCode, $customerCode);

        if (! $agency) {
            return;
        }

        $agency->update(['subscription_status' => 'pending_cancellation']);

        Log::info('Paystack subscription.not_renew: marked pending cancellation', [
            'agency_id'         => $agency->id,
            'subscription_code' => $subscriptionCode,
        ]);
    }

    private function findAgencyBySubscription(?string $subscriptionCode, ?string $customerCode): ?Agency
    {
        if ($subscriptionCode) {
            $agency = Agency::where('paystack_subscription_code', $subscriptionCode)->first();
            if ($agency) return $agency;
        }

        if ($customerCode) {
            $agency = Agency::where('paystack_customer_code', $customerCode)->first();
            if ($agency) return $agency;
        }

        Log::warning('Paystack webhook: could not resolve agency', [
            'subscription_code' => $subscriptionCode,
            'customer_code'     => $customerCode,
        ]);

        return null;
    }
}
