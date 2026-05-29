<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">AI Daily Planner</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black tracking-widest bg-brand-primary/10 text-brand-primary uppercase">Beta</span>
            </div>
            <p class="mt-2 text-text-secondary">Your personalized itinerary and strategic insights for {{ \Carbon\Carbon::parse($brief->date)->format('l, F jS') }}.</p>
        </div>
        <div>
            <button wire:click="regenerateBrief" class="glass-panel px-5 py-2.5 text-sm font-semibold text-text-primary hover:text-brand-primary transition-all duration-300 hover-spring flex items-center space-x-2 border-border-default/60">
                <svg wire:loading.class="animate-spin" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span>Regenerate Brief</span>
            </button>
        </div>
    </div>

    <!-- Market Snapshot -->
    <div class="mb-8 p-6 glass-panel rounded-3xl border border-brand-primary/20 bg-brand-primary/5 relative overflow-hidden group hover-spring">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-brand-primary/20 rounded-full blur-3xl group-hover:bg-brand-primary/30 transition-colors duration-500"></div>
        <div class="relative z-10 flex items-start space-x-4">
            <div class="mt-1 h-10 w-10 rounded-2xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary shrink-0 shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-brand-primary mb-2">Morning Market Snapshot</h2>
                <p class="text-sm font-medium text-text-primary leading-relaxed max-w-4xl">{{ $brief->market_snapshot }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Priority Actions -->
        <div class="lg:col-span-2 space-y-8">
            <div class="glass-panel rounded-3xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary">Priority Actions</h2>
                    <span class="text-xs font-semibold text-text-secondary">{{ collect($brief->priority_actions)->where('completed', true)->count() }} / {{ count($brief->priority_actions) }} Completed</span>
                </div>
                
                <div class="p-8 space-y-4 bg-surface-sunken/20">
                    @foreach($brief->priority_actions as $index => $action)
                        <div class="p-5 rounded-2xl border transition-all duration-300 {{ $action['completed'] ? 'bg-surface-raised/50 border-border-default/40 opacity-60' : 'bg-surface-card border-border-default/60 hover:shadow-md hover:border-brand-primary/30' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4">
                                    <!-- Status Checkbox -->
                                    <button wire:click="completeAction({{ $index }})" @if($action['completed']) disabled @endif class="mt-1 h-6 w-6 rounded-lg border flex items-center justify-center transition-colors {{ $action['completed'] ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-border-default hover:border-brand-primary text-transparent hover:text-brand-primary' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    
                                    <div>
                                        <div class="flex items-center space-x-3 mb-1">
                                            <h3 class="text-base font-bold text-text-primary {{ $action['completed'] ? 'line-through text-text-tertiary' : '' }}">{{ $action['title'] }}</h3>
                                            @if(!$action['completed'])
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-surface-raised border border-border-default/60 text-text-secondary">
                                                    {{ $action['type'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-text-secondary leading-relaxed">{{ $action['context'] }}</p>
                                    </div>
                                </div>
                                
                                @if(!$action['completed'])
                                    <div class="text-xs font-bold text-text-tertiary bg-surface-raised px-3 py-1.5 rounded-lg border border-border-default/60">
                                        {{ \Carbon\Carbon::parse($action['due_at'])->format('h:i A') }}
                                    </div>
                                @endif
                            </div>
                            
                            @if(!$action['completed'] && in_array($action['type'], ['email', 'call']))
                                <div class="mt-4 ml-10 flex space-x-3">
                                    <button wire:click="draftWithCopilot({{ $index }})" class="px-4 py-2 text-xs font-bold rounded-xl bg-brand-primary text-white hover:bg-brand-secondary transition-colors hover-spring shadow-sm flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Draft with Copilot
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Deal Alerts -->
            <div class="glass-panel rounded-3xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40">
                    <h2 class="text-lg font-bold text-text-primary">Deal Alerts</h2>
                </div>
                
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-5 bg-surface-sunken/20">
                    @foreach($brief->deal_alerts as $alert)
                        <div class="p-5 rounded-2xl bg-surface-card border border-border-default/60 hover-spring">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="h-2 w-2 rounded-full {{ $alert['severity'] === 'warning' ? 'bg-orange-500 animate-pulse' : 'bg-emerald-500' }}"></div>
                                <h4 class="text-sm font-bold text-text-primary">{{ $alert['title'] }}</h4>
                            </div>
                            <p class="text-xs font-semibold text-text-secondary mb-2">{{ $alert['property'] }}</p>
                            <p class="text-sm text-text-primary leading-relaxed opacity-80">{{ $alert['message'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column: Viewing Schedule -->
        <div class="space-y-8">
            <div class="glass-panel rounded-3xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary">Today's Viewings</h2>
                    <a href="#" class="text-sm font-bold text-brand-primary hover:text-brand-secondary">Calendar &rarr;</a>
                </div>
                <div class="p-8">
                    @if(empty($brief->viewing_schedule))
                        <div class="text-center py-6">
                            <div class="w-16 h-16 bg-surface-raised border border-border-default/60 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <p class="text-sm font-bold text-text-primary">No viewings today.</p>
                            <p class="text-xs text-text-secondary mt-1">Time to prospect for new leads.</p>
                        </div>
                    @else
                        <div class="relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-border-default/60 before:to-transparent">
                            @foreach($brief->viewing_schedule as $viewing)
                                <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-surface-card bg-surface-raised text-text-secondary shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-2xl border border-border-default/60 bg-surface-card shadow-sm hover:shadow hover-spring">
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="text-xs font-bold text-brand-primary">{{ $viewing['time'] }}</div>
                                        </div>
                                        <div class="text-sm font-bold text-text-primary">{{ $viewing['client'] }}</div>
                                        <div class="text-xs text-text-secondary mt-1">{{ $viewing['property'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
