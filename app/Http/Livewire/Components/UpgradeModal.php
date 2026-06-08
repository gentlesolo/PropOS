<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;

class UpgradeModal extends Component
{
    public bool $show = false;
    public string $message = 'You have reached a limit on your current plan. Upgrade to unlock more features.';
    
    protected $listeners = ['show-upgrade-modal' => 'openModal'];

    public function openModal($message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.components.upgrade-modal');
    }
}
