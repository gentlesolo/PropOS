<?php

namespace App\Infrastructure\Notifications;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Notification;
use App\Infrastructure\Persistence\Models\User;

class NotificationService
{
    /**
     * Create an in-app notification for a specific user.
     *
     * Pass $context with values keyed by placeholder name (without braces) to
     * enable interpolation when the agency has a custom template for $type.
     * Example: ['reference' => 'REF-001', 'address' => '12 Orchid Close']
     */
    public function notifyUser(
        User|int $user,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $severity = 'info',
        array $context = [],
    ): ?Notification {
        $userId   = $user instanceof User ? $user->id : $user;
        $agencyId = $user instanceof User ? $user->agency_id : User::find($userId)?->agency_id;

        if ($agencyId) {
            $settings  = ($user instanceof User ? $user->agency : Agency::find($agencyId))?->settings ?? [];
            $templates = $settings['notification_templates'] ?? [];
            $tpl       = $templates[$type] ?? null;

            if ($tpl !== null && ($tpl['enabled'] ?? true) === false) {
                return null;
            }

            if ($tpl) {
                $map = $this->contextMap($context);
                if (! empty($tpl['title'])) {
                    $title = $map ? strtr($tpl['title'], $map) : $tpl['title'];
                }
                if (! empty($tpl['body'])) {
                    $body = $map ? strtr($tpl['body'], $map) : $tpl['body'];
                }
            }
        }

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
        array $context = [],
    ): void {
        $users = User::where('agency_id', $agencyId)->where('status', 'active')->get();

        foreach ($users as $user) {
            $this->notifyUser($user, $type, $title, $body, $actionUrl, $severity, $context);
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

    private function contextMap(array $context): array
    {
        $map = [];
        foreach ($context as $key => $value) {
            $map['{' . $key . '}'] = (string) $value;
        }
        return $map;
    }
}
