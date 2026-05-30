<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Commission Split Configuration</h1>
            <p class="text-sm text-text-secondary mt-0.5">Configure commission rates and agent/agency split rules</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Config
        </button>
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editingId ? 'Edit' : 'Create' }} Split Configuration</h2>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Name *</label>
                <input wire:model="name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="e.g. Standard Agent Split">
                @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Applies To</label>
                <select wire:model.live="applies_to" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="agency_default">Agency Default</option>
                    <option value="role">By Role</option>
                    <option value="agent">Specific Agent</option>
                </select>
            </div>
            @if($applies_to === 'role')
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Role</label>
                <select wire:model="role" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="agent">Agent</option>
                    <option value="senior_agent">Senior Agent</option>
                    <option value="manager">Manager</option>
                    <option value="principal">Principal</option>
                </select>
            </div>
            @elseif($applies_to === 'agent')
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Agent</label>
                <select wire:model="user_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select agent…</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->first_name }} {{ $agent->last_name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div></div>
            @endif

            <div class="md:col-span-2">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Commission Rate (%)</label>
                        <input wire:model="commission_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Agent Split (%)</label>
                        <input wire:model.live="agent_split" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Agency Split (%)</label>
                        <input wire:model="agency_split" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Referral Split (%)</label>
                        <input wire:model="referral_split" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                </div>
                <p class="text-xs text-text-tertiary mt-2">Agent + Agency + Referral + Franchise = 100%. Agency split auto-calculated.</p>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Save Configuration</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Config Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($configs as $config)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-text-primary">{{ $config->name }}</h3>
                    <span class="text-xs text-text-secondary capitalize">{{ ucwords(str_replace('_', ' ', $config->applies_to)) }}
                        @if($config->role) · {{ ucfirst($config->role) }} @endif
                        @if($config->user) · {{ $config->user->first_name }} @endif
                    </span>
                </div>
                @if($config->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Active</span>
                @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-50 text-secondary-600 border border-secondary-200">Inactive</span>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                <div class="bg-surface-hover/50 rounded-lg p-2 text-center">
                    <div class="text-lg font-bold text-text-primary">{{ $config->commission_rate }}%</div>
                    <div class="text-xs text-text-secondary">Commission Rate</div>
                </div>
                <div class="bg-surface-hover/50 rounded-lg p-2 text-center">
                    <div class="text-lg font-bold text-text-primary">{{ $config->agent_split }}%</div>
                    <div class="text-xs text-text-secondary">Agent Split</div>
                </div>
                <div class="bg-surface-hover/50 rounded-lg p-2 text-center">
                    <div class="text-lg font-bold text-text-primary">{{ $config->agency_split }}%</div>
                    <div class="text-xs text-text-secondary">Agency Split</div>
                </div>
                <div class="bg-surface-hover/50 rounded-lg p-2 text-center">
                    <div class="text-lg font-bold text-text-primary">{{ $config->referral_split }}%</div>
                    <div class="text-xs text-text-secondary">Referral Split</div>
                </div>
            </div>
            <div class="flex gap-2">
                <button wire:click="openEdit({{ $config->id }})" class="flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">Edit</button>
                <button wire:click="toggleActive({{ $config->id }})" class="flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">{{ $config->is_active ? 'Disable' : 'Enable' }}</button>
                <button wire:click="delete({{ $config->id }})" wire:confirm="Delete this configuration?" class="text-xs py-1.5 px-2.5 border border-danger-200 rounded-lg text-danger-600 hover:bg-danger-50 transition-colors">Del</button>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 glass-panel rounded-2xl border border-border-default/60 p-12 text-center">
            <p class="text-text-tertiary text-sm">No split configurations yet. Create your agency default above.</p>
        </div>
        @endforelse
    </div>
</div>
