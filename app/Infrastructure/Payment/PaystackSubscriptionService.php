<?php

namespace App\Infrastructure\Payment;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\PaystackTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackSubscriptionService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', '');
    }

    /**
     * Create a checkout link for a subscription plan or AI credit top-up.
     * Returns ['url' => ..., 'reference' => ...]
     */
    public function createCheckoutLink(Agency $agency, float $amount, string $planCode = null, array $metadata = []): array
    {
        $payload = [
            'email'        => $agency->email,
            'amount'       => (int) ($amount * 100), // convert to kobo
            'currency'     => config('services.paystack.currency', 'NGN'),
            'metadata'     => array_merge($metadata, ['agency_id' => $agency->id]),
            'callback_url' => url('/settings/billing/verify'),
        ];

        if ($planCode) {
            $payload['plan'] = $planCode;
        }

        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/transaction/initialize', $payload);

        if ($response->failed()) {
            Log::error('Paystack init failed', ['error' => $response->json()]);
            throw new \RuntimeException('Unable to initialize Paystack transaction.');
        }

        $data = $response->json('data');

        return [
            'url'       => $data['authorization_url'],
            'reference' => $data['reference'],
        ];
    }

    /**
     * Verify a transaction reference and log it to paystack_transactions.
     * Returns normalised transaction data.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if ($response->failed()) {
            throw new \RuntimeException('Paystack verification failed.');
        }

        $data = $response->json('data');

        return [
            'status'             => $data['status'],
            'amount'             => $data['amount'] / 100,
            'amount_kobo'        => $data['amount'],
            'currency'           => $data['currency'] ?? 'NGN',
            'metadata'           => $data['metadata'],
            'customer_code'      => $data['customer']['customer_code'] ?? null,
            'authorization_code' => $data['authorization']['authorization_code'] ?? null,
            'transaction_id'     => (string) ($data['id'] ?? ''),
        ];
    }

    /**
     * Create a Paystack subscription plan (for recurring billing).
     * Returns the plan object including its plan_code.
     */
    public function createPlan(string $name, int $amountKobo, string $interval = 'monthly'): array
    {
        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/plan', [
                'name'     => $name,
                'amount'   => $amountKobo,
                'interval' => $interval, // daily | weekly | monthly | annually
            ]);

        if ($response->failed()) {
            Log::error('Paystack create plan failed', ['error' => $response->json()]);
            throw new \RuntimeException('Unable to create Paystack plan.');
        }

        return $response->json('data');
    }

    /**
     * Fetch a Paystack plan by its plan_code.
     */
    public function getPlan(string $planCode): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("https://api.paystack.co/plan/{$planCode}");

        if ($response->failed()) {
            throw new \RuntimeException('Unable to fetch Paystack plan.');
        }

        return $response->json('data');
    }

    /**
     * Enable (re-activate) a Paystack subscription.
     */
    public function enableSubscription(string $subscriptionCode, string $token): void
    {
        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/subscription/enable', [
                'code'  => $subscriptionCode,
                'token' => $token,
            ]);

        if ($response->failed()) {
            Log::error('Paystack enable subscription failed', ['error' => $response->json()]);
            throw new \RuntimeException('Unable to enable Paystack subscription.');
        }
    }

    /**
     * Disable (cancel) a Paystack subscription by its code.
     * Requires the email token sent to the subscriber.
     */
    public function cancelSubscription(string $subscriptionCode, string $emailToken): void
    {
        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/subscription/disable', [
                'code'  => $subscriptionCode,
                'token' => $emailToken,
            ]);

        if ($response->failed()) {
            Log::error('Paystack cancel subscription failed', ['error' => $response->json()]);
            throw new \RuntimeException('Unable to cancel Paystack subscription.');
        }
    }

    /**
     * Fetch a subscription by its code.
     */
    public function getSubscription(string $subscriptionCode): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("https://api.paystack.co/subscription/{$subscriptionCode}");

        if ($response->failed()) {
            throw new \RuntimeException('Unable to fetch Paystack subscription.');
        }

        return $response->json('data');
    }

    /**
     * Charge a previously-authorised card directly (for subscription renewals / top-ups).
     */
    public function chargeAuthorization(string $authorizationCode, string $email, int $amountKobo, array $metadata = []): array
    {
        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/transaction/charge_authorization', [
                'authorization_code' => $authorizationCode,
                'email'              => $email,
                'amount'             => $amountKobo,
                'metadata'           => $metadata,
            ]);

        if ($response->failed()) {
            Log::error('Paystack charge authorization failed', ['error' => $response->json()]);
            throw new \RuntimeException('Unable to charge authorization.');
        }

        return $response->json('data');
    }

    /**
     * Log a completed transaction to the audit table.
     */
    public function logTransaction(Agency $agency, array $txData, string $type, array $extra = []): PaystackTransaction
    {
        return PaystackTransaction::create(array_merge([
            'agency_id'               => $agency->id,
            'reference'               => $txData['reference'] ?? uniqid('ps_'),
            'type'                    => $type,
            'status'                  => $txData['status'] === 'success' ? 'success' : 'failed',
            'amount'                  => $txData['amount_kobo'] ?? (int) (($txData['amount'] ?? 0) * 100),
            'currency'                => $txData['currency'] ?? 'NGN',
            'paystack_transaction_id' => $txData['transaction_id'] ?? null,
            'paystack_customer_code'  => $txData['customer_code'] ?? null,
            'authorization_code'      => $txData['authorization_code'] ?? null,
            'metadata'                => $txData['metadata'] ?? null,
        ], $extra));
    }
}
