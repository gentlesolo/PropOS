<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Lead Routing Rules</h1>
            <p class="text-sm text-text-secondary mt-0.5">Auto-assign incoming leads to agents by strategy</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Rule
        </button>
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editingId ? 'Edit' : 'Create' }} Routing Rule</h2>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Rule Name *</label>
                    <input wire:model="name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="e.g. Durban Buyers — Round Robin">
                    @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Strategy</label>
                    <select wire:model="strategy" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="round_robin">Round Robin</option>
                        <option value="load_balanced">Load Balanced (fewest contacts)</option>
                        <option value="specific_agent">Specific Agent</option>
                        <option value="territory">Territory (city / state)</option>
                    </select>
                </div>
            </div>

            <!-- Agent Pool -->
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-2">Agent Pool *</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($agents as $agent)
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-border-default hover:bg-surface-hover/50 cursor-pointer {{ in_array($agent->id, $selectedAgentIds) ? 'border-brand-primary bg-brand-50' : '' }}">
                        <input type="checkbox" wire:model="selectedAgentIds" value="{{ $agent->id }}" class="rounded border-border-default text-brand-primary focus:ring-brand-primary">
                        <span class="text-sm text-text-primary">{{ $agent->first_name }} {{ $agent->last_name }}</span>
                    </label>
                    @endforeach
                </div>
                @error('selectedAgentIds') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>

            <!-- Conditions -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-text-secondary">
                        @if($strategy === 'territory')
                            Territory Mappings — map each city/state to a specific agent
                        @else
                            Conditions (optional — leave empty to catch all)
                        @endif
                    </label>
                    <button type="button" wire:click="addCondition" class="text-xs text-brand-primary hover:underline">+ Add</button>
                </div>

                @foreach($conditions as $i => $cond)
                @if($strategy === 'territory')
                {{-- Territory condition row: field (city/state) · territory value · assigned agent --}}
                <div class="grid grid-cols-[130px_1fr_1fr_auto] gap-2 mb-2 items-center">
                    <select wire:model="conditions.{{ $i }}.field"
                            class="rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary">
                        <option value="city">City</option>
                        <option value="state_province">State / Province</option>
                    </select>
                    <input wire:model="conditions.{{ $i }}.value" type="text"
                           class="rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary"
                           placeholder="e.g. Lagos">
                    <select wire:model="conditions.{{ $i }}.agent_id"
                            class="rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary">
                        <option value="">— Assign agent —</option>
                        @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->first_name }} {{ $agent->last_name }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="removeCondition({{ $i }})" class="px-2 text-danger-500 hover:text-danger-700">×</button>
                </div>
                @else
                {{-- Standard condition row --}}
                <div class="flex gap-2 mb-2">
                    <select wire:model="conditions.{{ $i }}.field"
                            class="rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary">
                        <option value="type">Contact Type</option>
                        <option value="source">Lead Source</option>
                    </select>
                    <select wire:model="conditions.{{ $i }}.operator"
                            class="rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary">
                        <option value="equals">equals</option>
                        <option value="contains">contains</option>
                        <option value="in">in (comma-separated)</option>
                    </select>
                    <input wire:model="conditions.{{ $i }}.value" type="text"
                           class="flex-1 rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary"
                           placeholder="value…">
                    <button type="button" wire:click="removeCondition({{ $i }})" class="px-2 text-danger-500 hover:text-danger-700">×</button>
                </div>
                @endif
                @endforeach

                @if($strategy === 'territory' && empty($conditions))
                <p class="text-xs text-text-tertiary mt-1">Add territory rows above. Unmatched contacts fall back to round-robin over the agent pool.</p>
                @endif
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Save Rule</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <div class="space-y-4">
        @forelse($rules as $rule)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="font-semibold text-text-primary">{{ $rule->name }}</h3>
                        @if($rule->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Active</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-50 text-secondary-600 border border-secondary-200">Inactive</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs text-text-secondary">
                        <span>Strategy: <strong class="text-text-primary">{{ ucwords(str_replace('_', ' ', $rule->strategy)) }}</strong></span>
                        <span>Agents: <strong class="text-text-primary">{{ count($rule->agent_ids ?? []) }}</strong></span>
                        @if(!empty($rule->conditions))
                        <span>Conditions: <strong class="text-text-primary">{{ count($rule->conditions) }}</strong></span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2 ml-4">
                    <button wire:click="openEdit({{ $rule->id }})" class="text-xs px-3 py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">Edit</button>
                    <button wire:click="toggleActive({{ $rule->id }})" class="text-xs px-3 py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">{{ $rule->is_active ? 'Disable' : 'Enable' }}</button>
                    <button wire:click="delete({{ $rule->id }})" wire:confirm="Delete this routing rule?" class="text-xs px-3 py-1.5 border border-danger-200 rounded-lg text-danger-600 hover:bg-danger-50 transition-colors">Delete</button>
                </div>
            </div>
        </div>
        @empty
        <div class="glass-panel rounded-2xl border border-border-default/60 p-12 text-center">
            <p class="text-text-tertiary text-sm">No routing rules yet. Create your first rule to auto-assign leads.</p>
        </div>
        @endforelse
    </div>
</div>
