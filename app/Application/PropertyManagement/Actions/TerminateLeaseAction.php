<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\User;
use Carbon\Carbon;

class TerminateLeaseAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Lease $lease, string $reason, Carbon $terminationDate): Lease
    {
        $lease->update([
            'status'   => 'terminated',
            'end_date' => $terminationDate,
        ]);

        // Waive all future pending payments
        $lease->rentPayments()
            ->where('status', 'pending')
            ->where('due_date', '>', $terminationDate)
            ->update(['status' => 'waived']);

        $lease->tenant?->update(['status' => 'vacating']);

        $message = "Lease {$lease->reference} terminated on {$terminationDate->format('d M Y')}. Reason: {$reason}";

        if ($lease->assigned_agent_id) {
            $this->notifications->notifyUser(
                $lease->assigned_agent_id,
                'lease_terminated',
                'Lease Terminated',
                $message,
                '/property-management/leases',
                'warning',
            );
        }

        // Notify all managers in the agency
        $managers = User::where('agency_id', $lease->agency_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($managers as $manager) {
            if ($manager->id !== $lease->assigned_agent_id) {
                $this->notifications->notifyUser($manager, 'lease_terminated', 'Lease Terminated', $message, '/property-management/leases', 'warning');
            }
        }

        return $lease->fresh();
    }
}
