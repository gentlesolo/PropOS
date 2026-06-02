<div x-data="{ open: @entangle('isOpen') }" 
     @keydown.window.prevent.cmd.k="open = true; @this.call('toggle')" 
     @keydown.window.prevent.ctrl.k="open = true; @this.call('toggle')" 
     x-show="open" 
     class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20" 
     style="display: none;">
     
    <!-- Backdrop -->
    <div x-show="open" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-surface-overlay/80 backdrop-blur-md transition-opacity" 
         @click="open = false; @this.call('toggle')"></div>

    <!-- Modal Content -->
    <div x-show="open" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95" 
         class="mx-auto max-w-2xl transform divide-y divide-border-default/60 rounded-2xl bg-surface-card border border-border-default shadow-2xl transition-all relative z-10 overflow-hidden">
        
        <div class="relative">
            <!-- Search icon -->
            <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-text-tertiary" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
            </svg>
            <input wire:model.live.debounce.300ms="query" 
                   type="text" 
                   class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-sm text-text-primary placeholder-text-tertiary focus:ring-0 focus:outline-none" 
                   placeholder="Search contacts, properties, transactions, actions... (Esc to close)" 
                   x-ref="input"
                   @keyup.escape="open = false; @this.call('toggle')"
                   x-effect="if (open) { setTimeout(() => $refs.input.focus(), 100) }">
        </div>

        @if(!empty($results))
            <!-- Results List -->
            <ul class="max-h-96 scroll-py-2 overflow-y-auto py-2 text-sm text-text-secondary divide-y divide-border-default/30">
                @foreach($results as $result)
                    <li>
                        <a href="{{ $result['url'] }}" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-sunken/40 transition-colors">
                            <div class="h-8 w-8 rounded-lg bg-brand-primary/10 text-brand-primary flex items-center justify-center shrink-0">
                                @if($result['icon'] === 'user')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                @elseif($result['icon'] === 'home')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                @elseif($result['icon'] === 'shield')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" /></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="font-bold text-text-primary block truncate">{{ $result['title'] }}</span>
                                <span class="text-xs text-text-tertiary block truncate">{{ $result['subtitle'] }}</span>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-surface-sunken px-2 py-0.5 rounded text-text-tertiary">
                                {{ $result['type'] }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @elseif(strlen($query) >= 2)
            <!-- No results state -->
            <div class="px-6 py-14 text-center sm:px-14">
                <svg class="mx-auto h-6 w-6 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="mt-4 text-sm font-semibold text-text-primary">No results found.</p>
                <p class="mt-2 text-xs text-text-tertiary">We couldn't find anything matching your search term. Try another query.</p>
            </div>
        @else
            <!-- Default Quick Actions / Guide -->
            <div class="p-4">
                <span class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider block mb-3 px-2">Quick Navigation</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="{{ route('crm.contacts') }}" class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-sunken/40 transition-colors">
                        <div class="h-8 w-8 rounded-lg bg-brand-primary/10 text-brand-primary flex items-center justify-center shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </div>
                        <span class="text-xs font-bold text-text-primary">All Contacts</span>
                    </a>
                    <a href="{{ route('listing.index') }}" class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-sunken/40 transition-colors">
                        <div class="h-8 w-8 rounded-lg bg-brand-primary/10 text-brand-primary flex items-center justify-center shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        </div>
                        <span class="text-xs font-bold text-text-primary">Agency Listings</span>
                    </a>
                    <a href="{{ route('marketing.sequences') }}" class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-sunken/40 transition-colors">
                        <div class="h-8 w-8 rounded-lg bg-brand-primary/10 text-brand-primary flex items-center justify-center shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2" /></svg>
                        </div>
                        <span class="text-xs font-bold text-text-primary">Nurture Sequences</span>
                    </a>
                    <a href="#" @click.prevent="open = false; $dispatch('toggleChatPanel')" class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-sunken/40 transition-colors">
                        <div class="h-8 w-8 rounded-lg bg-brand-primary/10 text-brand-primary flex items-center justify-center shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <span class="text-xs font-bold text-text-primary">Open AI Copilot</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Help instructions -->
        <div class="bg-surface-sunken/20 px-4 py-2.5 flex items-center justify-between text-[10px] text-text-tertiary font-medium">
            <span>Use <kbd class="px-1.5 py-0.5 bg-surface-card border border-border-default rounded font-semibold text-text-secondary shadow-sm">Ctrl+K</kbd> or <kbd class="px-1.5 py-0.5 bg-surface-card border border-border-default rounded font-semibold text-text-secondary shadow-sm">Cmd+K</kbd> to toggle.</span>
            <span>Esc to close.</span>
        </div>
    </div>
</div>

