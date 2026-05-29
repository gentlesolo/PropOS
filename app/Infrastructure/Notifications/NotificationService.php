<?php

namespace App\Infrastructure\Notifications;

use App\Infrastructure\Persistence\Models\Notification;
use App\Infrastructure\Persistence\Models\User;

class NotificationService
{
    /**
     * Create an in-app notification for a specific user.
     */
    public function notifyUser(
        User|int $user,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $severity = 'info',
    ): Notification {
        $userId   = $user instanceof User ? $user->id : $user;
        $agencyId = $user instanceof User ? $user->agency_id : User::find($userId)?->agency_id;

        return Notification::create([
            'agency_id'  => $agencyId,
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'action_url' => $actionUrl,
            'severity'   => $severity,
        ]);
    }

    /**
     * Notify all active agents in an agency.
     */
    public function notifyAgency(
        int $agencyId,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $severity = 'info',
    ): void {
        $users = User::where('agency_id', $agencyId)->where('status', 'active')->get();

        foreach ($users as $user) {
            $this->notifyUser($user, $type, $title, $body, $actionUrl, $severity);
        }
    }

    /**
     * Mark all unread notifications as read for a user.
     */
    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
