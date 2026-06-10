<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Lease;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaseRenewalOfferAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Lease $lease): void
    {
        $contact  = $lease->tenant?->contact ?? $lease->contact;
        $property = $lease->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';

        $escalation        = (float) ($lease->escalation_percent ?? 0);
        $newMonthlyRent    = round((float) $lease->monthly_rent * (1 + $escalation / 100), 2);
        $newPeriodRent     = round($newMonthlyRent * $lease->periodMonths, 2);
        $frequencyLabel    = $lease->paymentFrequencyLabel;
        $newEndDate        = $lease->end_date->copy()->addMonths($lease->periodMonths)->format('d M Y');
        $currentEndDate    = $lease->end_date->format('d M Y');

        if ($contact?->email) {
            $rentLine = $lease->payment_frequency === 'monthly'
                ? "Proposed Monthly Rent: ₦" . number_format($newPeriodRent, 2) . "/month"
                : "Proposed {$frequencyLabel} Rent: ₦" . number_format($newPeriodRent, 2)
                    . " (₦" . number_format($newMonthlyRent, 2) . "/month equivalent)";

            $body = "Dear {$contact->first_name},\n\n"
                . "Your lease for {$address} is due to expire on {$currentEndDate}.\n\n"
                . "We would like to offer you a lease renewal on the following terms:\n\n"
                . "New Lease End Date: {$newEndDate}\n"
                . "Payment Frequency: {$frequencyLabel}\n"
                . "{$rentLine}\n\n"
                . "Please contact us to discuss and confirm your renewal.\n\n"
                . "Kind regards,\nProperty Management";

            try {
                Mail::raw($body, fn ($msg) => $msg->to($contact->email, $contact->full_name)->subject("Lease Renewal Offer — {$address}"));
            } catch (\Exception $e) {
                Log::error('Lease renewal offer email failed', ['lease_id' => $lease->id, 'error' => $e->getMessage()]);
            }
        }

        if ($lease->assigned_agent_id) {
            $this->notifications->notifyUser(
                $lease->assigned_agent_id,
                'renewal_offer_sent',
                'Renewal Offer Sent',
                "Renewal offer emailed to {$contact?->full_name} for lease {$lease->reference}.",
                '/property-management/leases',
                'info',
            );
        }
    }
}
