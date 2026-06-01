<?php

namespace App\Infrastructure\Payment;

use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayFastGateway implements PaymentGatewayInterface
{
    private string $merchantId;
    private string $merchantKey;
    private string $passphrase;
    private bool   $sandbox;

    public function __construct()
    {
        $this->merchantId  = config('services.payfast.merchant_id', '');
        $this->merchantKey = config('services.payfast.merchant_key', '');
        $this->passphrase  = config('services.payfast.passphrase', '');
        $this->sandbox     = config('services.payfast.mode', 'sandbox') === 'sandbox';
    }

    public function createPaymentLink(Invoice $invoice): array
    {
        $paymentId = 'PF-' . $invoice->reference . '-' . time();

        $data = [
            'merchant_id'  => $this->merchantId,
            'merchant_key' => $this->merchantKey,
            'return_url'   => url('/finance/invoices'),
            'cancel_url'   => url('/finance/invoices'),
            'notify_url'   => url('/api/webhooks/payfast'),
            'amount'       => number_format((float) $invoice->total, 2, '.', ''),
            'item_name'    => "Invoice {$invoice->reference}",
            'm_payment_id' => $paymentId,
        ];

        $data['signature'] = $this->generateSignature($data);

        $host = $this->sandbox
            ? 'https://sandbox.payfast.co.za/eng/process'
            : 'https://www.payfast.co.za/eng/process';

        $url = $host . '?' . http_build_query($data);

        return [
            'url'        => $url,
            'payment_id' => $paymentId,
        ];
    }

    public function createMandate(Lease $lease, float $amount, int $collectionDay): string
    {
        // PayFast Subscriptions API — returns a token used as mandate ID
        $host = $this->sandbox
            ? 'https://sandbox.payfast.co.za/eng/recurring/charge'
            : 'https://www.payfast.co.za/eng/recurring/charge';

        $mandateId = 'PF-MANDATE-' . $lease->reference . '-' . time();

        Log::info('PayFast mandate created (stub)', [
            'lease_id'      => $lease->id,
            'amount'        => $amount,
            'collection_day'=> $collectionDay,
            'mandate_id'    => $mandateId,
        ]);

        return $mandateId;
    }

    public function cancelMandate(string $mandateId): void
    {
        Log::info('PayFast mandate cancelled (stub)', ['mandate_id' => $mandateId]);
    }

    public function verifyWebhook(Request $request): array
    {
        $data = $request->all();

        // Remove the signature from the data before re-generating it
        $signature = $data['signature'] ?? '';
        unset($data['signature']);

        $expected = $this->generateSignature($data);

        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('PayFast ITN signature mismatch.');
        }

        return [
            'payment_id'     => $data['m_payment_id'] ?? '',
            'status'         => $data['payment_status'] ?? '',
            'amount'         => (float) ($data['amount_gross'] ?? 0),
            'gateway_ref'    => $data['pf_payment_id'] ?? '',
        ];
    }

    private function generateSignature(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            if ($value !== '') {
                $parts[] = $key . '=' . urlencode(trim((string) $value));
            }
        }

        $string = implode('&', $parts);

        if ($this->passphrase !== '') {
            $string .= '&passphrase=' . urlencode(trim($this->passphrase));
        }

        return md5($string);
    }
}
