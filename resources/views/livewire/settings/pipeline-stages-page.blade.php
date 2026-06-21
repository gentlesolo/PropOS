<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Pipeline Stages</h1>
            <p class="text-sm text-text-secondary mt-0.5">Customize your deals pipeline stages and workflows</p>
        </div>
        <button wire:click="openCreate" class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="openCreate">
                <span wire:loading.remove wire:target="openCreate"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Stage</span>
                <span wire:loading wire:target="openCreate" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>

    @if($showForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-5 mb-6 animate-fade-in">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editingId ? 'Edit Stage' : 'Create New Stage' }}</h2>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Stage Name *</label>
                <input wire:model="name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page" placeholder="e.g. Under Contract">
                @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Pipeline Type *</label>
                <select wire:model="pipeline_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="sale">Sale Pipeline</option>
                    <option value="rental">Rental Pipeline</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Display Order *</label>
                <input wire:model="order" type="number" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('order') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center gap-6 pt-5">
                <label class="inline-flex items-center gap-2 text-sm text-text-primary cursor-pointer">
                    <input wire:model="is_won" type="checkbox" class="rounded border-border-default text-brand-primary focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    Mark as Won Stage
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-text-primary cursor-pointer">
                    <input wire:model="is_lost" type="checkbox" class="rounded border-border-default text-brand-primary focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    Mark as Lost Stage
                </label>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Stage</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button type="button" wire:click="$set('showForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </form>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Pipeline Stages -->
        <div class="bg-surface-card rounded-2xl border border-border-default p-5">
            <h3 class="text-base font-semibold text-text-primary mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Sales Pipeline
            </h3>
            <div class="space-y-2">
                @forelse(collect($stages)->where('pipeline_type', 'sale') as $stage)
                <div class="flex items-center justify-between p-3 rounded-xl bg-surface-card border border-border-default hover:border-brand-primary/40 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono text-text-secondary bg-surface-hover px-2 py-0.5 rounded">#{{ $stage->order }}</span>
                        <div>
                            <span class="text-sm font-medium text-text-primary">{{ $stage->name }}</span>
                            @if($stage->is_won)
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-success-50 text-success-700 border border-success-200">WON</span>
                            @endif
                            @if($stage->is_lost)
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-danger-50 text-danger-700 border border-danger-200">LOST</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button wire:click="moveUp({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-brand-primary rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="moveUp">
                <span wire:loading.remove wire:target="moveUp"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg></span>
                <span wire:loading wire:target="moveUp" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="moveDown({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-brand-primary rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="moveDown">
                <span wire:loading.remove wire:target="moveDown"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></span>
                <span wire:loading wire:target="moveDown" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="openEdit({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-brand-primary rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="openEdit">
                <span wire:loading.remove wire:target="openEdit"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></span>
                <span wire:loading wire:target="openEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="delete({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-danger-600 rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="delete">
                <span wire:loading.remove wire:target="delete"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>
                <span wire:loading wire:target="delete" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                </div>
                @empty
                <div class="text-sm text-text-tertiary text-center py-6">No stages configured.</div>
                @endforelse
            </div>
        </div>

        <!-- Rental Pipeline Stages -->
        <div class="bg-surface-card rounded-2xl border border-border-default p-5">
            <h3 class="text-base font-semibold text-text-primary mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                Rental Pipeline
            </h3>
            <div class="space-y-2">
                @forelse(collect($stages)->where('pipeline_type', 'rental') as $stage)
                <div class="flex items-center justify-between p-3 rounded-xl bg-surface-card border border-border-default hover:border-brand-primary/40 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono text-text-secondary bg-surface-hover px-2 py-0.5 rounded">#{{ $stage->order }}</span>
                        <div>
                            <span class="text-sm font-medium text-text-primary">{{ $stage->name }}</span>
                            @if($stage->is_won)
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-success-50 text-success-700 border border-success-200">WON</span>
                            @endif
                            @if($stage->is_lost)
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-danger-50 text-danger-700 border border-danger-200">LOST</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button wire:click="moveUp({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-brand-primary rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="moveUp">
                <span wire:loading.remove wire:target="moveUp"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg></span>
                <span wire:loading wire:target="moveUp" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="moveDown({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-brand-primary rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="moveDown">
                <span wire:loading.remove wire:target="moveDown"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></span>
                <span wire:loading wire:target="moveDown" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="openEdit({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-brand-primary rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="openEdit">
                <span wire:loading.remove wire:target="openEdit"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></span>
                <span wire:loading wire:target="openEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="delete({{ $stage->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-secondary hover:text-danger-600 rounded hover:bg-surface-hover" wire:loading.attr="disabled" wire:target="delete">
                <span wire:loading.remove wire:target="delete"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>
                <span wire:loading wire:target="delete" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                </div>
                @empty
                <div class="text-sm text-text-tertiary text-center py-6">No stages configured.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>



