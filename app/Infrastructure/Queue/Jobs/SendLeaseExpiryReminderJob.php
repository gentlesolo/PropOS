<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Agency;
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

    // Maps reminder slot index (0-3) to the corresponding lease flag column.
    private const FLAGS = [
        'reminder_30d_sent',
        'reminder_14d_sent',
        'reminder_7d_sent',
        'reminder_0d_sent',
    ];

    private const DEFAULT_REMINDERS = [
        ['days' => 30, 'enabled' => true],
        ['days' => 14, 'enabled' => true],
        ['days' => 7,  'enabled' => true],
        ['days' => 0,  'enabled' => true],
    ];

    public function handle(NotificationService $notifications): void
    {
        Agency::query()->each(function (Agency $agency) use ($notifications) {
            $settings       = $agency->settings ?? [];
            $leaseReminders = $settings['lease_reminders'] ?? [];

            if (($leaseReminders['enabled'] ?? true) === false) {
                return;
            }

            $reminders = $leaseReminders['reminders'] ?? self::DEFAULT_REMINDERS;

            foreach ($reminders as $index => $reminder) {
                $flag = self::FLAGS[$index] ?? null;

                if (! $flag || ! ($reminder['enabled'] ?? true)) {
                    continue;
                }

                $days = (int) ($reminder['days'] ?? 0);
                $this->sendRemindersForAgency($agency->id, $days, $flag, $notifications, $reminder);
            }
        });
    }

    private function sendRemindersForAgency(int $agencyId, int $days, string $flag, NotificationService $notifications, array $reminder): void
    {
        $leases = Lease::with(['tenant.contact', 'listing.property', 'agent'])
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->where($flag, false)
            ->whereDate('end_date', now()->addDays($days)->toDateString())
            ->get();

        foreach ($leases as $lease) {
            $this->sendAgentEmail($lease, $days, $reminder);
            $this->sendTenantEmail($lease, $days, $reminder);

            if ($lease->assigned_agent_id) {
                $title   = $days === 0 ? 'Lease Expires Today' : "Lease Expiring in {$days} Days";
                $message = $days === 0
                    ? "Lease {$lease->reference} expires today ({$lease->end_date->format('d M Y')})."
                    : "Lease {$lease->reference} expires on {$lease->end_date->format('d M Y')}.";

                $notifications->notifyUser(
                    $lease->assigned_agent_id,
                    'lease_expiry_reminder',
                    $title,
                    $message,
                    '/property-management/leases',
                    $days === 0 ? 'error' : 'warning',
                );
            }

            $lease->update([$flag => true]);
        }
    }

    private function resolveTemplate(?string $template, Lease $lease, int $days): ?string
    {
        if (! $template) {
            return null;
        }

        $property  = $lease->listing?->property;
        $address   = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $contact   = $lease->tenant?->contact ?? $lease->contact;
        $agent     = $lease->agent;
        $daysText  = $days === 0 ? 'today' : "{$days} day(s)";
        $portalUrl = $lease->tenant?->portal_token ? url('/tenant-portal/' . $lease->tenant->portal_token) : '';

        return strtr($template, [
            '{first_name}'  => $contact?->first_name ?? 'Tenant',
            '{full_name}'   => $contact?->full_name ?? 'Tenant',
            '{address}'     => $address,
            '{reference}'   => $lease->reference,
            '{end_date}'    => $lease->end_date?->format('d M Y') ?? '',
            '{days}'        => $daysText,
            '{agent_name}'  => $agent?->name ?? 'Property Manager',
            '{portal_url}'  => $portalUrl,
        ]);
    }

    private function sendAgentEmail(Lease $lease, int $days, array $reminder): void
    {
        $agent = $lease->agent;
        if (! $agent?->email) {
            return;
        }

        $property = $lease->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $tenant   = $lease->tenant?->contact?->full_name ?? 'Tenant';
        $dayText  = $days === 0
            ? "today ({$lease->end_date->format('d M Y')})"
            : "in {$days} day(s) on {$lease->end_date->format('d M Y')}";

        $subject = $this->resolveTemplate($reminder['agent_subject'] ?? null, $lease, $days)
            ?? ($days === 0
                ? "Lease Expires Today — {$lease->reference}"
                : "Lease Expiry Reminder ({$days} days) — {$lease->reference}");

        $body = $this->resolveTemplate($reminder['agent_body'] ?? null, $lease, $days)
            ?? ("Hi {$agent->name},\n\n"
                . "This is a reminder that lease {$lease->reference} for {$tenant} at {$address} "
                . "is expiring {$dayText}.\n\n"
                . "Please contact the tenant to discuss renewal or vacating.\n\n"
                . "View lease: " . url('/property-management/leases'));

        try {
            Mail::raw($body, fn ($msg) => $msg->to($agent->email, $agent->name)->subject($subject));
        } catch (\Exception $e) {
            Log::error('Lease expiry agent email failed', ['lease_id' => $lease->id, 'days' => $days, 'error' => $e->getMessage()]);
        }
    }

    private function sendTenantEmail(Lease $lease, int $days, array $reminder): void
    {
        $contact = $lease->tenant?->contact ?? $lease->contact;
        if (! $contact?->email) {
            return;
        }

        $property = $lease->listing?->property;
        $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
        $dayText  = $days === 0
            ? "today ({$lease->end_date->format('d M Y')})"
            : "in {$days} day(s) on {$lease->end_date->format('d M Y')}";

        $subject = $this->resolveTemplate($reminder['tenant_subject'] ?? null, $lease, $days)
            ?? ($days === 0
                ? "Your Lease Expires Today — {$address}"
                : "Your Lease Expires in {$days} Day(s) — {$address}");

        $body = $this->resolveTemplate($reminder['tenant_body'] ?? null, $lease, $days)
            ?? ("Dear {$contact->first_name},\n\n"
                . "Your lease for {$address} (Ref: {$lease->reference}) is due to expire "
                . "{$dayText}.\n\n"
                . "Please contact your property manager to discuss renewal options.\n\n"
                . "Kind regards,\nProperty Management");

        try {
            Mail::raw($body, fn ($msg) => $msg->to($contact->email, $contact->full_name)->subject($subject));
        } catch (\Exception $e) {
            Log::error('Lease expiry tenant email failed', ['lease_id' => $lease->id, 'days' => $days, 'error' => $e->getMessage()]);
        }
    }
}
