<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\LeadRoutingRule;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class LeadRoutingPage extends Component
{
    public bool $showCreateForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $strategy = 'round_robin';
    public array $selectedAgentIds = [];
    public array $conditions = [];

    public function addCondition(): void
    {
        $this->conditions[] = ['field' => 'type', 'operator' => 'equals', 'value' => ''];
    }

    public function removeCondition(int $index): void
    {
        array_splice($this->conditions, $index, 1);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'strategy' => 'required|in:round_robin,territory,load_balanced,specific_agent',
            'selectedAgentIds' => 'required|array|min:1',
        ]);

        $data = [
            'agency_id' => auth()->user()->agency_id,
            'name' => $this->name,
            'strategy' => $this->strategy,
            'agent_ids' => $this->selectedAgentIds,
            'conditions' => $this->conditions ?: null,
            'is_active' => true,
        ];

        if ($this->editingId) {
            LeadRoutingRule::findOrFail($this->editingId)->update($data);
            $msg = 'Routing rule updated.';
        } else {
            LeadRoutingRule::create($data);
            $msg = 'Routing rule created.';
        }

        $this->reset(['showCreateForm', 'editingId', 'name', 'strategy', 'selectedAgentIds', 'conditions']);
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function openEdit(int $id): void
    {
        $rule = LeadRoutingRule::findOrFail($id);
        $this->editingId = $id;
        $this->name = $rule->name;
        $this->strategy = $rule->strategy;
        $this->selectedAgentIds = $rule->agent_ids ?? [];
        $this->conditions = $rule->conditions ?? [];
        $this->showCreateForm = true;
    }

    public function toggleActive(int $id): void
    {
        $rule = LeadRoutingRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);
    }

    public function delete(int $id): void
    {
        LeadRoutingRule::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Rule deleted.', type: 'success');
    }

    public function render()
    {
        $rules = LeadRoutingRule::orderByDesc('priority')->orderByDesc('is_active')->get();
        $agents = User::where('agency_id', auth()->user()->agency_id)->get(['id', 'first_name', 'last_name']);

        return view('livewire.settings.lead-routing-page', compact('rules', 'agents'))
            ->layout('layouts.app');
    }
}
