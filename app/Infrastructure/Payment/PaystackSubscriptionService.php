<?php

namespace App\Infrastructure\Payment;

use App\Infrastructure\Persistence\Models\Agency;
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
     * Create a checkout session/link for subscription or top-up.
     */
    public function createCheckoutLink(Agency $agency, float $amount, string $planCode = null, array $metadata = []): array
    {
        $payload = [
            'email' => $agency->email,
            'amount' => $amount * 100, // Paystack expects Kobo/Cents
            'metadata' => array_merge($metadata, [
                'agency_id' => $agency->id,
            ]),
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
            'url' => $data['authorization_url'],
            'reference' => $data['reference'],
        ];
    }

    /**
     * Verify a transaction after user returns.
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
            'status' => $data['status'],
            'amount' => $data['amount'] / 100,
            'metadata' => $data['metadata'],
            'customer_code' => $data['customer']['customer_code'] ?? null,
            'authorization_code' => $data['authorization']['authorization_code'] ?? null,
        ];
    }
}
