<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Commission Split Configuration</h1>
            <p class="text-sm text-text-secondary mt-0.5">Configure commission rates and agent/agency split rules</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Config</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>

    @if($showCreateForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editingId ? 'Edit' : 'Create' }} Split Configuration</h2>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Name *</label>
                <input wire:model="name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page" placeholder="e.g. Standard Agent Split">
                @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Applies To</label>
                <select wire:model.live="applies_to" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="agency_default">Agency Default</option>
                    <option value="role">By Role</option>
                    <option value="agent">Specific Agent</option>
                </select>
            </div>
            @if($applies_to === 'role')
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Role</label>
                <select wire:model="role" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="agent">Agent</option>
                    <option value="senior_agent">Senior Agent</option>
                    <option value="manager">Manager</option>
                    <option value="principal">Principal</option>
                </select>
            </div>
            @elseif($applies_to === 'agent')
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Agent</label>
                <select wire:model="user_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
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
                        <input wire:model="commission_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Agent Split (%)</label>
                        <input wire:model.live="agent_split" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Agency Split (%)</label>
                        <input wire:model="agency_split" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Referral Split (%)</label>
                        <input wire:model="referral_split" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                </div>
                <p class="text-xs text-text-tertiary mt-2">Agent + Agency + Referral + Franchise = 100%. Agency split auto-calculated.</p>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Configuration</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Config Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($configs as $config)
        <div class="bg-surface-card rounded-2xl border border-border-default p-5">
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
                <button wire:click="openEdit({{ $config->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="openEdit">
                <span wire:loading.remove wire:target="openEdit">Edit</span>
                <span wire:loading wire:target="openEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="toggleActive({{ $config->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="toggleActive">
                <span wire:loading.remove wire:target="toggleActive">{{ $config->is_active ? 'Disable' : 'Enable' }}</span>
                <span wire:loading wire:target="toggleActive" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="delete({{ $config->id }})" wire:confirm="Delete this configuration?" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs py-1.5 px-2.5 border border-danger-200 rounded-lg text-danger-600 hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="delete">
                <span wire:loading.remove wire:target="delete">Del</span>
                <span wire:loading wire:target="delete" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 bg-surface-card rounded-2xl border border-border-default p-12 text-center">
            <p class="text-text-tertiary text-sm">No split configurations yet. Create your agency default above.</p>
        </div>
        @endforelse
    </div>
</div>



