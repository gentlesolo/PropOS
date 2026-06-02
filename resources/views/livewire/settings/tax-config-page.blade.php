<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Tax Configuration</h1>
            <p class="text-sm text-text-secondary mt-0.5">Configure VAT and tax rules for your agency</p>
        </div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
            + Add Tax Rule
        </button>
    </div>

    @if($showForm)
    <div class="bg-surface-card rounded-2xl border border-brand-200 p-6 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editId ? 'Edit Tax Rule' : 'New Tax Rule' }}</h2>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Name *</label>
                <input wire:model="name" type="text" placeholder="e.g. Standard VAT 15%" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('name') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Tax Type *</label>
                <select wire:model="tax_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    @foreach(['vat'=>'VAT','withholding'=>'Withholding Tax','municipal'=>'Municipal Tax','other'=>'Other'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Rate (%) *</label>
                <input wire:model="rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('rate') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Applies To *</label>
                <select wire:model="applies_to" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="all">All Properties</option>
                    <option value="residential">Residential Only</option>
                    <option value="commercial">Commercial Only</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <input wire:model="is_default" type="checkbox" id="is_default" class="rounded border-border-default text-brand-primary">
                    <label for="is_default" class="text-sm text-text-secondary">Default Tax Rule</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="is_active" type="checkbox" id="is_active" class="rounded border-border-default text-brand-primary">
                    <label for="is_active" class="text-sm text-text-secondary">Active</label>
                </div>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Save</button>
                <button type="button" wire:click="resetForm" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Rate</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Applies To</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($configs as $config)
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-medium text-text-primary">{{ $config->name }}</div>
                        @if($config->is_default)<span class="text-xs text-brand-600 font-medium">Default</span>@endif
                    </td>
                    <td class="px-4 py-3 text-text-secondary text-xs capitalize">{{ str_replace('_', ' ', $config->tax_type) }}</td>
                    <td class="px-4 py-3 font-bold text-text-primary">{{ $config->rate }}%</td>
                    <td class="px-4 py-3 text-text-secondary text-xs capitalize">{{ $config->applies_to }}</td>
                    <td class="px-4 py-3">
                        @if($config->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-50 text-secondary-600 border border-secondary-200">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1">
                            <button wire:click="edit({{ $config->id }})" class="text-xs px-2 py-1 border border-border-default rounded-lg hover:bg-surface-hover text-text-secondary">Edit</button>
                            @if($config->is_active)
                            <button wire:click="deactivate({{ $config->id }})" class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">Deactivate</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-text-tertiary">No tax rules configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>



