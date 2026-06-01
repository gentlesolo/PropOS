<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Tenant;

class CreateLeaseAction
{
    public function __construct(
        private readonly GeneratePaymentScheduleAction $scheduleAction,
        private readonly NotificationService $notifications,
    ) {}

    public function execute(array $data): Lease
    {
        $lease = Lease::create($data);

        Tenant::find($lease->tenant_id)?->update([
            'status'     => 'active',
            'listing_id' => $lease->listing_id,
        ]);

        $this->scheduleAction->execute($lease);

        if ($lease->assigned_agent_id) {
            $this->notifications->notifyUser(
                $lease->assigned_agent_id,
                'lease_created',
                'Lease Created',
                "Lease {$lease->reference} has been created.",
                '/property-management/leases',
                'success',
            );
        }

        return $lease;
    }
}
