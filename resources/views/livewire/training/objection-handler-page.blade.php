<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <span class="text-3xl">🛡️</span> AI Objection Handler
            </h1>
            <p class="mt-2 text-text-secondary">Get instant, structured responses to any client objection — practise until it's instinctive.</p>
        </div>
        <a href="{{ route('training.skills') }}" class="px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">← Skills Library</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Left: Objection Selector -->
        <div class="xl:col-span-1 space-y-5">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-bold text-text-primary mb-4">Select Objection Category</h3>
                <div class="grid grid-cols-2 gap-2 mb-4">
                    @foreach(['price' => ['💰','Price'], 'timing' => ['⏰','Timing'], 'competition' => ['🏆','Competition'], 'uncertainty' => ['❓','Uncertainty']] as $cat => [$icon, $label])
                    <button wire:click="$set('objectionCategory', '{{ $cat }}')"
                        class="p-3 rounded-xl border text-center transition-colors
                        {{ $objectionCategory === $cat ? 'bg-brand-primary text-white border-brand-primary' : 'bg-surface-card border-border-default/60 text-text-secondary hover:border-brand-primary/40' }}">
                        <div class="text-lg mb-1">{{ $icon }}</div>
                        <div class="text-xs font-bold">{{ $label }}</div>
                    </button>
                    @endforeach
                </div>

                <!-- Common Objections for Selected Category -->
                <div class="space-y-2">
                    <p class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Common {{ ucfirst($objectionCategory) }} Objections</p>
                    @foreach($commonObjections[$objectionCategory] as $obj)
                    <button wire:click="handleObjection('{{ addslashes($obj) }}')"
                        class="w-full text-left p-3 rounded-xl border border-border-default/40 bg-surface-sunken/30 hover:bg-brand-primary/5 hover:border-brand-primary/30 transition-colors text-sm text-text-primary">
                        <span wire:loading.remove wire:target="handleObjection('{{ addslashes($obj) }}')">{{ $obj }}</span>
                        <span wire:loading wire:target="handleObjection('{{ addslashes($obj) }}')" class="text-brand-primary">Generating response...</span>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Custom Objection -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-bold text-text-primary mb-3">Custom Objection</h3>
                <form wire:submit.prevent="handleCustomObjection" class="space-y-3">
                    <textarea wire:model.defer="customObjection" rows="3"
                        placeholder="Type any objection the client said..."
                        class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    @error('customObjection') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    <button type="submit" class="w-full py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="handleCustomObjection">✨ Get AI Response</span>
                        <span wire:loading wire:target="handleCustomObjection">Generating...</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Response + History -->
        <div class="xl:col-span-2 space-y-5">

            <!-- Current Response -->
            @if($generating)
            <div class="glass-panel rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-8 text-center">
                <div class="flex items-center justify-center gap-3 mb-3">
                    <svg class="animate-spin h-6 w-6 text-brand-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <p class="text-sm font-bold text-brand-primary">Crafting the perfect response...</p>
                </div>
            </div>
            @elseif($response)
            <div class="glass-panel rounded-2xl border border-brand-primary/30 bg-brand-primary/5 p-6">
                <h3 class="text-sm font-bold text-text-primary mb-4 flex items-center gap-2">
                    <span class="h-6 w-6 bg-brand-primary rounded-lg flex items-center justify-center text-white text-xs">AI</span>
                    Objection Response Script
                </h3>
                <div class="space-y-4">
                    @foreach(['empathy' => ['🤝', 'Step 1: Empathise', 'bg-info-50 border-info-200'], 'reframe' => ['🔄', 'Step 2: Reframe', 'bg-warning-50 border-warning-200'], 'response' => ['💬', 'Step 3: Respond', 'bg-brand-primary/5 border-brand-primary/20'], 'close' => ['🎯', 'Step 4: Re-engage', 'bg-success-50 border-success-200']] as $key => [$icon, $label, $classes])
                    @if(isset($response[$key]))
                    <div class="p-4 rounded-xl border {{ $classes }}">
                        <p class="text-[10px] font-black uppercase tracking-wider text-text-secondary mb-1.5">{{ $icon }} {{ $label }}</p>
                        <p class="text-sm text-text-primary leading-relaxed italic">"{{ $response[$key] }}"</p>
                    </div>
                    @endif
                    @endforeach
                </div>
                @if($history)
                <div class="mt-4 pt-4 border-t border-border-default/40 flex justify-between items-center">
                    <p class="text-xs text-text-secondary">{{ count($history) }} response{{ count($history) !== 1 ? 's' : '' }} in session</p>
                    <button wire:click="clearHistory" class="text-xs text-danger-500 hover:text-danger-700 font-medium">Clear History</button>
                </div>
                @endif
            </div>
            @else
            <div class="glass-panel rounded-2xl border border-border-default/60 p-14 text-center">
                <div class="text-5xl mb-4">🎯</div>
                <h3 class="text-base font-bold text-text-primary mb-2">Select an objection to get started</h3>
                <p class="text-sm text-text-secondary">Click any objection on the left — or type a custom one — to receive a structured, AI-powered response script.</p>
            </div>
            @endif

            <!-- History -->
            @if(count($history) > 1)
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-bold text-text-primary mb-4">Session History ({{ count($history) }} objections)</h3>
                <div class="space-y-3">
                    @foreach(array_reverse($history) as $i => $item)
                    @if($i > 0)
                    <div class="p-3 bg-surface-sunken/30 rounded-xl border border-border-default/40">
                        <p class="text-xs font-medium text-text-secondary mb-1 capitalize">{{ $item['category'] }} objection</p>
                        <p class="text-sm font-bold text-text-primary mb-2">"{{ $item['objection'] }}"</p>
                        <p class="text-xs text-text-secondary italic">{{ $item['response']['empathy'] ?? '' }}</p>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
