<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaseExpiryReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(NotificationService $notifications): void
    {
        $this->sendReminders(30, 'reminder_30d_sent', $notifications);
        $this->sendReminders(14, 'reminder_14d_sent', $notifications);
        $this->sendReminders(7, 'reminder_7d_sent', $notifications);
    }

    private function sendReminders(int $daysAhead, string $flag, NotificationService $notifications): void
    {
        $leases = Lease::with(['tenant.contact', 'listing.property', 'agent'])
            ->where('status', 'active')
            ->where($flag, false)
            ->whereDate('end_date', now()->addDays($daysAhead)->toDateString())
            ->get();

        foreach ($leases as $lease) {
            $this->sendAgentEmail($lease, $daysAhead);
            $this->sendTenantEmail($lease, $daysAhead);

            if ($lease->assigned_agent_id) {
                $notifications->notifyUser(
                    $lease->assigned_agent_id,
                    'lease_expiry_reminder',
                    "Lease Expiring in {$daysAhead} Days",
                    "Lease {$lease->reference} expires on {$lease->end_date->format('d M Y')}.",
                    '/property-management/leases',
                    'warning',
                );
            }

            $lease->update([$flag => true]);
        }
    }

    private function sendAgentEmail(Lease $lease, int $days): void
    {
        $agent = $lease->agent;
        if (! $agent?->email) {
            return;
        }

        $property = $lease->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $tenant   = $lease->tenant?->contact?->full_name ?? 'Tenant';

        $body = "Hi {$agent->name},\n\n"
            . "This is a reminder that lease {$lease->reference} for {$tenant} at {$address} "
            . "is expiring in {$days} day(s) on {$lease->end_date->format('d M Y')}.\n\n"
            . "Please contact the tenant to discuss renewal or vacating.\n\n"
            . "View lease: " . url('/property-management/leases');

        try {
            Mail::raw($body, fn ($msg) => $msg->to($agent->email, $agent->name)->subject("Lease Expiry Reminder ({$days} days) — {$lease->reference}"));
        } catch (\Exception $e) {
            Log::error('Lease expiry agent email failed', ['lease_id' => $lease->id, 'days' => $days, 'error' => $e->getMessage()]);
        }
    }

    private function sendTenantEmail(Lease $lease, int $days): void
    {
        $contact = $lease->tenant?->contact ?? $lease->contact;
        if (! $contact?->email) {
            return;
        }

        $property = $lease->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';

        $body = "Dear {$contact->first_name},\n\n"
            . "Your lease for {$address} (Ref: {$lease->reference}) is due to expire "
            . "in {$days} day(s) on {$lease->end_date->format('d M Y')}.\n\n"
            . "Please contact your property manager to discuss renewal options.\n\n"
            . "Kind regards,\nProperty Management";

        try {
            Mail::raw($body, fn ($msg) => $msg->to($contact->email, $contact->full_name)->subject("Your Lease Expires in {$days} Day(s) — {$address}"));
        } catch (\Exception $e) {
            Log::error('Lease expiry tenant email failed', ['lease_id' => $lease->id, 'days' => $days, 'error' => $e->getMessage()]);
        }
    }
}
