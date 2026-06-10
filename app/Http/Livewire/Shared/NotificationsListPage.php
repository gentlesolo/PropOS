<?php

namespace App\Http\Livewire\Shared;

use App\Infrastructure\Persistence\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsListPage extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all | unread | read

    protected $queryString = ['filter'];

    public function setFilter(string $value): void
    {
        $this->filter = $value;
        $this->resetPage();
    }

    public function markRead(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();
    }

    public function deleteAll(): void
    {
        Notification::where('user_id', auth()->id())->delete();
    }

    public function render()
    {
        $query = Notification::where('user_id', auth()->id())->latest();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20);
        $unreadCount   = Notification::where('user_id', auth()->id())->whereNull('read_at')->count();

        return view('livewire.shared.notifications-list-page', compact('notifications', 'unreadCount'))
            ->layout('layouts.app', ['title' => 'Notifications']);
    }
}
