<?php

namespace App\Http\Livewire\Shared;

use App\Infrastructure\Persistence\Models\Notification;
use Livewire\Component;

class Topbar extends Component
{
    public function render()
    {
        $notificationsCount = 0;
        $user = auth()->user();

        if ($user) {
            $notificationsCount = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();
        }

        return view('livewire.shared.topbar', [
            'notificationsCount' => $notificationsCount,
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return redirect()->route('login');
    }
}
