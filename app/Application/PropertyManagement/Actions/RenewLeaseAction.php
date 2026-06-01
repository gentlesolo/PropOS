<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Lease;

class RenewLeaseAction
{
    public function __construct(
        private readonly GeneratePaymentScheduleAction $scheduleAction,
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Lease $lease, int $months = 12): Lease
    {
        $newEnd = $lease->end_date->copy()->addMonths($months);

        $escalation    = (float) ($lease->escalation_percent ?? 0);
        $newMonthlyRent = round((float) $lease->monthly_rent * (1 + $escalation / 100), 2);

        $lease->update([
            'status'         => 'renewed',
            'end_date'       => $newEnd,
            'renewed_until'  => $newEnd,
            'monthly_rent'   => $newMonthlyRent,
            'reminder_30d_sent' => false,
            'reminder_14d_sent' => false,
            'reminder_7d_sent'  => false,
        ]);

        $this->scheduleAction->execute($lease->fresh());

        if ($lease->assigned_agent_id) {
            $this->notifications->notifyUser(
                $lease->assigned_agent_id,
                'lease_renewed',
                'Lease Renewed',
                "Lease {$lease->reference} renewed until {$newEnd->format('d M Y')}. New rent: R " . number_format($newMonthlyRent, 2),
                '/property-management/leases',
                'success',
            );
        }

        return $lease->fresh();
    }
}
