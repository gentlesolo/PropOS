<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\RentPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRentPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(NotificationService $notifications): void
    {
        $this->send3DayReminders();
        $this->sendDueTodayReminders();
        $this->sendOverdueNotices($notifications);
    }

    private function send3DayReminders(): void
    {
        $payments = RentPayment::with(['lease.listing.property', 'tenant.contact'])
            ->where('status', 'pending')
            ->whereDate('due_date', now()->addDays(3)->toDateString())
            ->get();

        foreach ($payments as $payment) {
            $this->emailTenant($payment, '3-day');
        }
    }

    private function sendDueTodayReminders(): void
    {
        $payments = RentPayment::with(['lease.listing.property', 'tenant.contact'])
            ->where('status', 'pending')
            ->whereDate('due_date', today())
            ->get();

        foreach ($payments as $payment) {
            $this->emailTenant($payment, 'due-today');
        }
    }

    private function sendOverdueNotices(NotificationService $notifications): void
    {
        $payments = RentPayment::with(['lease.agent', 'lease.listing.property', 'tenant.contact'])
            ->where('status', 'pending')
            ->whereDate('due_date', now()->subDays(3)->toDateString())
            ->get();

        foreach ($payments as $payment) {
            $payment->update(['status' => 'overdue']);
            $this->emailTenant($payment, 'overdue');

            if ($payment->lease?->assigned_agent_id) {
                $contact = $payment->tenant?->contact;
                $notifications->notifyUser(
                    $payment->lease->assigned_agent_id,
                    'rent_overdue',
                    'Rent Payment Overdue',
                    ($contact?->full_name ?? 'Tenant') . " — payment of R " . number_format((float) $payment->amount_due, 2) . " is overdue (Ref: {$payment->reference}).",
                    '/property-management/rent-collection',
                    'error',
                );
            }
        }
    }

    private function emailTenant(RentPayment $payment, string $type): void
    {
        $contact = $payment->tenant?->contact;
        if (! $contact?->email) {
            return;
        }

        $property = $payment->lease?->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'your property';
        $amount   = 'R ' . number_format((float) $payment->amount_due, 2);
        $dueDate  = $payment->due_date->format('d M Y');

        [$subject, $body] = match ($type) {
            '3-day' => [
                "Rent Due in 3 Days — {$address}",
                "Dear {$contact->first_name},\n\nYour rent payment of {$amount} for {$address} is due on {$dueDate}.\n\nPlease ensure payment is made on time.\n\nKind regards,\nProperty Management",
            ],
            'due-today' => [
                "Rent Due Today — {$address}",
                "Dear {$contact->first_name},\n\nYour rent payment of {$amount} for {$address} is due today ({$dueDate}).\n\nPlease make your payment as soon as possible.\n\nKind regards,\nProperty Management",
            ],
            'overdue' => [
                "Rent Payment Overdue — {$address}",
                "Dear {$contact->first_name},\n\nYour rent payment of {$amount} for {$address} was due on {$dueDate} and is now overdue.\n\nPlease contact your property manager immediately to arrange payment.\n\nKind regards,\nProperty Management",
            ],
            default => ['Rent Reminder', "Rent reminder for {$address}."],
        };

        try {
            Mail::raw($body, fn ($msg) => $msg->to($contact->email, $contact->full_name)->subject($subject));
        } catch (\Exception $e) {
            Log::error('Rent payment reminder email failed', ['payment_id' => $payment->id, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }
}
