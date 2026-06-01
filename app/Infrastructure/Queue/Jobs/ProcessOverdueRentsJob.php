<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\RentPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOverdueRentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(NotificationService $notifications): void
    {
        $payments = RentPayment::with(['lease.agent', 'lease.listing.property', 'tenant.contact'])
            ->where('status', 'pending')
            ->whereDate('due_date', '<', today())
            ->get();

        foreach ($payments as $payment) {
            $payment->update(['status' => 'overdue']);

            if (! $payment->lease?->assigned_agent_id) {
                continue;
            }

            $contact = $payment->tenant?->contact;
            $property = $payment->lease?->listing?->property;
            $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'a property';

            $notifications->notifyUser(
                $payment->lease->assigned_agent_id,
                'rent_overdue',
                'Rent Payment Overdue',
                ($contact?->full_name ?? 'Tenant') . " — R " . number_format((float) $payment->amount_due, 2) . " overdue for {$address} (Ref: {$payment->reference}).",
                '/property-management/rent-collection',
                'error',
            );
        }
    }
}
