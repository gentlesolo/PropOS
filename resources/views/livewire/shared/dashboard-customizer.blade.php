<div class="relative" x-data>
    <button wire:click="$set('open', true)"
        class="disabled:opacity-70 disabled:cursor-not-allowed relative bg-surface-card px-5 py-2.5 text-xs font-bold text-text-primary hover:border-brand-primary/50 hover:text-brand-primary transition-all flex items-center space-x-2 shadow-sm" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set"><svg class="h-4 w-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
        </svg>
        <span>Customise</span></span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>

    @if($open)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="$set('open', false)">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative z-10 bg-surface-card rounded-2xl border border-border-default shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-bold text-text-primary">Customise Dashboard</h3>
                <button wire:click="$set('open', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-primary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            <p class="text-xs text-text-secondary mb-4">Choose which metric widgets appear on your dashboard.</p>
            <div class="space-y-2 mb-6">
                @foreach($allWidgets as $widget)
                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-hover cursor-pointer transition-colors">
                    <input type="checkbox"
                        wire:click="toggle('{{ $widget }}')"
                        @checked(in_array($widget, $enabledWidgets))
                        class="rounded border-border-default text-brand-primary focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page w-4 h-4">
                    <span class="text-sm font-medium text-text-primary">{{ $labels[$widget] ?? ucfirst(str_replace('_', ' ', $widget)) }}</span>
                </label>
                @endforeach
            </div>
            <div class="flex gap-3">
                <button wire:click="save"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-2.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-semibold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save Layout</span>
                <span wire:loading wire:target="save" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="$set('open', false)"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2.5 bg-surface-hover text-text-secondary rounded-xl text-sm font-medium hover:text-text-primary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>
    </div>
    @endif
</div>



