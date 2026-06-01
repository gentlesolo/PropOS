<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\TaxConfig;
use Livewire\Component;

class TaxConfigPage extends Component
{
    public bool   $showForm    = false;
    public ?int   $editId      = null;

    public string $name       = '';
    public string $tax_type   = 'vat';
    public string $rate       = '15.00';
    public string $applies_to = 'all';
    public bool   $is_default = false;
    public bool   $is_active  = true;

    public function save(): void
    {
        $this->validate([
            'name'       => 'required|string|max:255',
            'tax_type'   => 'required|string',
            'rate'       => 'required|numeric|min:0|max:100',
            'applies_to' => 'required|string',
        ]);

        $agencyId = auth()->user()->agency_id;

        // Only one default allowed
        if ($this->is_default) {
            TaxConfig::where('agency_id', $agencyId)->update(['is_default' => false]);
        }

        $data = [
            'agency_id'  => $agencyId,
            'name'       => $this->name,
            'tax_type'   => $this->tax_type,
            'rate'       => (float) $this->rate,
            'applies_to' => $this->applies_to,
            'is_default' => $this->is_default,
            'is_active'  => $this->is_active,
        ];

        if ($this->editId) {
            TaxConfig::where('id', $this->editId)->where('agency_id', $agencyId)->update($data);
            $this->dispatch('notify', message: 'Tax config updated.', type: 'success');
        } else {
            TaxConfig::create($data);
            $this->dispatch('notify', message: 'Tax config created.', type: 'success');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $config = TaxConfig::where('agency_id', auth()->user()->agency_id)->findOrFail($id);
        $this->editId     = $config->id;
        $this->name       = $config->name;
        $this->tax_type   = $config->tax_type;
        $this->rate       = (string) $config->rate;
        $this->applies_to = $config->applies_to;
        $this->is_default = (bool) $config->is_default;
        $this->is_active  = (bool) $config->is_active;
        $this->showForm   = true;
    }

    public function deactivate(int $id): void
    {
        TaxConfig::where('id', $id)->where('agency_id', auth()->user()->agency_id)->update(['is_active' => false]);
        $this->dispatch('notify', message: 'Tax config deactivated.', type: 'info');
    }

    public function resetForm(): void
    {
        $this->reset(['showForm', 'editId', 'name', 'tax_type', 'rate', 'applies_to', 'is_default', 'is_active']);
        $this->rate = '15.00';
    }

    public function render()
    {
        $configs = TaxConfig::where('agency_id', auth()->user()->agency_id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('livewire.settings.tax-config-page', compact('configs'))
            ->layout('layouts.app');
    }
}
