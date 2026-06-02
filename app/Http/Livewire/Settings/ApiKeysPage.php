<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\ApiKey;
use Livewire\Component;

class ApiKeysPage extends Component
{
    public string $name         = '';
    public string $type         = 'public_read';
    public bool   $showForm     = false;
    public ?int   $revokeId     = null;

    // Shown once after creation so the user can copy it
    public ?string $newToken = null;

    public function createKey(): void
    {
        $this->guardPermission('agency.manage');

        $this->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:public_read,full_access',
        ]);

        $key = ApiKey::generate(auth()->user()->agency_id, $this->name, $this->type);

        $this->newToken  = $key->token;
        $this->showForm  = false;
        $this->reset(['name', 'type']);

        $this->dispatch('notify', message: 'API key created. Copy it now — it will not be shown again.', type: 'success');
    }

    public function revokeKey(int $id): void
    {
        $this->guardPermission('agency.manage');

        ApiKey::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();

        $this->revokeId = null;
        $this->newToken = null;

        $this->dispatch('notify', message: 'API key revoked.', type: 'info');
    }

    public function dismissToken(): void
    {
        $this->newToken = null;
    }

    private function guardPermission(string $permission): void
    {
        if (! auth()->user()->hasPermissionTo($permission)) {
            $this->dispatch('notify', message: 'You do not have permission to do this.', type: 'error');
        }
    }

    public function render()
    {
        $keys = ApiKey::where('agency_id', auth()->user()->agency_id)
            ->latest()
            ->get();

        return view('livewire.settings.api-keys-page', compact('keys'))
            ->layout('layouts.app');
    }
}
