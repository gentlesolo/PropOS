<?php

namespace App\Http\Livewire\Shared;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Notification;
use Livewire\Component;

class Topbar extends Component
{
    public bool $showNotifications = false;

    public function toggleNotifications(): void
    {
        $this->showNotifications = ! $this->showNotifications;

        // Auto-mark visible notifications as read when panel opens
        if ($this->showNotifications) {
            Notification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
    }

    public function deleteNotification(int $notificationId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', auth()->id())
            ->delete();
    }

    public function logout(): mixed
    {
        auth()->logout();
        return redirect()->route('login');
    }

    public function render()
    {
        $user = auth()->user();

        $notifications = $user
            ? Notification::where('user_id', $user->id)
                ->latest()
                ->limit(25)
                ->get()
            : collect();

        $unreadCount = $user
            ? Notification::where('user_id', $user->id)->whereNull('read_at')->count()
            : 0;

        return view('livewire.shared.topbar', compact('notifications', 'unreadCount'));
    }
}
