<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\CommissionSplitConfig;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class CommissionSplitPage extends Component
{
    public bool $showCreateForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $applies_to = 'agency_default';
    public string $role = '';
    public string $user_id = '';
    public string $commission_rate = '5.00';
    public string $agent_split = '50.00';
    public string $agency_split = '50.00';
    public string $referral_split = '0.00';
    public string $franchise_fee = '0.00';

    public function updatedAgentSplit(): void
    {
        $this->agency_split = (string) max(0, 100 - (float) $this->agent_split - (float) $this->referral_split - (float) $this->franchise_fee);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'applies_to' => 'required|in:agency_default,role,agent',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'agent_split' => 'required|numeric|min:0|max:100',
            'agency_split' => 'required|numeric|min:0|max:100',
            'referral_split' => 'required|numeric|min:0|max:100',
            'franchise_fee' => 'required|numeric|min:0|max:100',
        ]);

        $data = [
            'agency_id' => auth()->user()->agency_id,
            'name' => $this->name,
            'applies_to' => $this->applies_to,
            'role' => $this->role ?: null,
            'user_id' => $this->user_id ?: null,
            'commission_rate' => $this->commission_rate,
            'agent_split' => $this->agent_split,
            'agency_split' => $this->agency_split,
            'referral_split' => $this->referral_split,
            'franchise_fee' => $this->franchise_fee,
        ];

        if ($this->editingId) {
            CommissionSplitConfig::findOrFail($this->editingId)->update($data);
            $msg = 'Split configuration updated.';
        } else {
            CommissionSplitConfig::create($data);
            $msg = 'Split configuration created.';
        }

        $this->reset(['showCreateForm', 'editingId', 'name', 'applies_to', 'role', 'user_id',
            'commission_rate', 'agent_split', 'agency_split', 'referral_split', 'franchise_fee']);
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function openEdit(int $id): void
    {
        $config = CommissionSplitConfig::findOrFail($id);
        $this->editingId = $id;
        $this->name = $config->name;
        $this->applies_to = $config->applies_to;
        $this->role = $config->role ?? '';
        $this->user_id = (string) ($config->user_id ?? '');
        $this->commission_rate = (string) $config->commission_rate;
        $this->agent_split = (string) $config->agent_split;
        $this->agency_split = (string) $config->agency_split;
        $this->referral_split = (string) $config->referral_split;
        $this->franchise_fee = (string) $config->franchise_fee;
        $this->showCreateForm = true;
    }

    public function toggleActive(int $id): void
    {
        $config = CommissionSplitConfig::findOrFail($id);
        $config->update(['is_active' => !$config->is_active]);
    }

    public function delete(int $id): void
    {
        CommissionSplitConfig::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Configuration deleted.', type: 'success');
    }

    public function render()
    {
        $configs = CommissionSplitConfig::with('user')->orderBy('applies_to')->get();
        $agents = User::where('agency_id', auth()->user()->agency_id)->get(['id', 'first_name', 'last_name']);

        return view('livewire.settings.commission-split-page', compact('configs', 'agents'))
            ->layout('layouts.app');
    }
}
