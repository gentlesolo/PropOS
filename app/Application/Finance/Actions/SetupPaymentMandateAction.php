<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\PaymentMandate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SetupPaymentMandateAction
{
    public function __construct(private readonly PaymentGatewayInterface $gateway) {}

    public function execute(Lease $lease): PaymentMandate
    {
        // Cancel any existing active mandate for this lease
        PaymentMandate::where('lease_id', $lease->id)
            ->where('status', 'active')
            ->each(function (PaymentMandate $mandate) {
                try {
                    $this->gateway->cancelMandate($mandate->gateway_mandate_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to cancel old mandate', ['mandate_id' => $mandate->id]);
                }
                $mandate->update(['status' => 'cancelled']);
            });

        $collectionDay = (int) ($lease->payment_day ?? 1);
        $amount        = (float) $lease->monthly_rent;

        $mandateId = $this->gateway->createMandate($lease, $amount, $collectionDay);

        $nextCollection = Carbon::now()->day($collectionDay);
        if ($nextCollection->isPast()) {
            $nextCollection->addMonth();
        }

        $mandate = PaymentMandate::create([
            'agency_id'           => $lease->agency_id,
            'lease_id'            => $lease->id,
            'tenant_id'           => $lease->tenant_id,
            'gateway'             => 'payfast',
            'gateway_mandate_id'  => $mandateId,
            'status'              => 'active',
            'collection_day'      => $collectionDay,
            'amount'              => $amount,
            'next_collection_date'=> $nextCollection,
        ]);

        // Email confirmation to tenant
        $contact = $lease->tenant?->contact ?? $lease->contact;
        if ($contact?->email) {
            $body = "Dear {$contact->first_name},\n\n"
                . "Your recurring payment mandate for R " . number_format($amount, 2)
                . " has been set up successfully. Payments will be collected on day {$collectionDay} of each month.\n\n"
                . "Kind regards,\nProperty Management";

            try {
                Mail::raw(
                    $body,
                    fn ($msg) => $msg
                        ->to($contact->email, $contact->full_name)
                        ->subject('Recurring Payment Mandate Confirmed')
                );
            } catch (\Exception $e) {
                Log::error('Mandate confirmation email failed', ['lease_id' => $lease->id]);
            }
        }

        return $mandate;
    }
}
