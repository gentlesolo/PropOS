<?php

namespace App\Infrastructure\Payment\Contracts;

use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\Lease;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create a one-off hosted payment page for the given invoice.
     * Returns ['url' => ..., 'payment_id' => ...]
     */
    public function createPaymentLink(Invoice $invoice): array;

    /**
     * Set up a recurring debit mandate for automatic rent collection.
     * Returns the gateway's mandate/subscription ID.
     */
    public function createMandate(Lease $lease, float $amount, int $collectionDay): string;

    /**
     * Cancel an active mandate by its gateway ID.
     */
    public function cancelMandate(string $mandateId): void;

    /**
     * Verify and parse an inbound webhook payload.
     * Returns normalised payment data array.
     * Throws \RuntimeException on invalid signature.
     */
    public function verifyWebhook(Request $request): array;
}
