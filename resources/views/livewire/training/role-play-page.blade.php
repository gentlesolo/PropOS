<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <svg class="h-8 w-8 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                AI Role-Play Simulator
            </h1>
            <p class="mt-2 text-text-secondary">Practise difficult client conversations with AI personas — get scored and coached.</p>
        </div>
        <a href="{{ route('training.skills') }}" class="px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">
            ← Skills Library
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Controls Sidebar -->
        <div class="lg:col-span-1 space-y-5">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5 shadow-sm">
                <h3 class="text-sm font-bold text-text-primary mb-4">Simulation Setup</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-1.5">Scenario</label>
                        <select wire:model="scenario" class="w-full bg-surface-raised border border-border-default/60 text-text-primary rounded-xl px-3 py-2 text-sm font-medium focus:ring-brand-primary focus:border-brand-primary" {{ $isStarted ? 'disabled' : '' }}>
                            <option value="qualification">First Call Qualification</option>
                            <option value="listing_presentation">Listing Presentation</option>
                            <option value="objection_handling">Objection Handling</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-1.5">Client Persona</label>
                        <select wire:model="persona" class="w-full bg-surface-raised border border-border-default/60 text-text-primary rounded-xl px-3 py-2 text-sm font-medium focus:ring-brand-primary focus:border-brand-primary" {{ $isStarted ? 'disabled' : '' }}>
                            <option value="first_time_buyer">Nervous First-Time Buyer</option>
                            <option value="seasoned_investor">Aggressive Investor</option>
                            <option value="reluctant_seller">Unrealistic Seller</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-border-default/40">
                        @if(!$isStarted && !$feedback)
                        <button wire:click="startSimulation" class="w-full bg-brand-primary text-white py-2.5 rounded-xl font-bold text-sm hover:bg-brand-secondary transition-colors hover-spring shadow-md">
                            Start Simulation
                        </button>
                        @elseif($isStarted)
                        <button wire:click="endSimulation" class="w-full bg-danger-600 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-danger-700 transition-colors hover-spring shadow-md">
                            <span wire:loading.remove wire:target="endSimulation">End & Get AI Feedback</span>
                            <span wire:loading wire:target="endSimulation" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Analysing...
                            </span>
                        </button>
                        @elseif($feedback)
                        <button wire:click="startSimulation" class="w-full bg-surface-raised border border-border-default text-text-primary py-2.5 rounded-xl font-bold text-sm hover:bg-surface-sunken transition-colors">
                            Try Again
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- AI Feedback Panel -->
            @if($feedback)
            <div class="glass-panel rounded-2xl border border-success-300/50 bg-success-50/40 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black text-success-700">AI Coaching Feedback</h3>
                    <span class="px-3 py-1 bg-success-500 text-white font-black rounded-xl text-sm">{{ $feedback['score'] }}/100</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <h4 class="text-[10px] font-bold text-success-700 uppercase tracking-wider mb-2">Strengths</h4>
                        @foreach($feedback['strengths'] as $s)
                        <div class="flex items-start gap-2 mb-1.5">
                            <span class="text-success-500 font-bold shrink-0">✓</span>
                            <p class="text-xs text-text-primary">{{ $s }}</p>
                        </div>
                        @endforeach
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold text-danger-600 uppercase tracking-wider mb-2">To Improve</h4>
                        @foreach($feedback['improvements'] as $item)
                        <div class="flex items-start gap-2 mb-1.5">
                            <span class="text-danger-500 font-bold shrink-0">!</span>
                            <p class="text-xs text-text-primary">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Chat Area -->
        <div class="lg:col-span-3">
            <div class="glass-panel rounded-2xl border border-border-default/60 shadow-sm flex flex-col h-[620px] overflow-hidden">

                @if(!$isStarted && empty($messages) && !$feedback)
                <div class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-surface-card/50 backdrop-blur-sm z-10">
                    <div class="h-20 w-20 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center mb-5 text-4xl">🎭</div>
                    <h2 class="text-xl font-black text-text-primary mb-2">Ready to practise?</h2>
                    <p class="text-sm text-text-secondary max-w-md">Choose a scenario and persona, then click Start. The AI will play the client — you play the agent. Get real AI feedback when you end the session.</p>
                </div>
                @endif

                <!-- Message History -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4" id="chat-messages">
                    @foreach($messages as $msg)
                    @if($msg['role'] !== 'system')
                    <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[78%] rounded-2xl px-4 py-3 text-sm
                            {{ $msg['role'] === 'user'
                                ? 'bg-brand-primary text-white rounded-tr-sm shadow-md'
                                : 'bg-surface-raised border border-border-default/60 text-text-primary rounded-tl-sm shadow-sm' }}">
                            @if($msg['role'] === 'assistant')
                            <p class="text-[10px] font-black uppercase tracking-wider text-text-tertiary mb-1">{{ str_replace('_', ' ', $persona) }}</p>
                            @else
                            <p class="text-[10px] font-black uppercase tracking-wider text-white/70 mb-1">You (Agent)</p>
                            @endif
                            <p class="font-medium leading-relaxed whitespace-pre-wrap">{{ $msg['content'] }}</p>
                        </div>
                    </div>
                    @endif
                    @endforeach

                    @if($isTyping)
                    <div class="flex justify-start">
                        <div class="bg-surface-raised border border-border-default/60 rounded-2xl rounded-tl-sm px-5 py-4 flex items-center gap-1.5">
                            <div class="w-2 h-2 bg-text-tertiary rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-text-tertiary rounded-full animate-bounce" style="animation-delay:.2s"></div>
                            <div class="w-2 h-2 bg-text-tertiary rounded-full animate-bounce" style="animation-delay:.4s"></div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Input -->
                <div class="p-4 bg-surface-sunken/50 border-t border-border-default/60">
                    <form wire:submit.prevent="sendMessage" class="flex gap-3">
                        <input wire:model.defer="inputMessage" type="text"
                            placeholder="{{ $isStarted ? 'Type your response as the agent...' : 'Start the simulation first →' }}"
                            class="flex-1 bg-surface-card border border-border-default/60 rounded-xl px-4 py-2.5 text-sm text-text-primary focus:ring-2 focus:ring-brand-primary/20 focus:border-brand-primary disabled:opacity-50"
                            {{ !$isStarted ? 'disabled' : '' }}>
                        <button type="submit"
                            class="h-10 w-10 bg-brand-primary text-white rounded-xl flex items-center justify-center hover:bg-brand-secondary transition-colors disabled:opacity-50"
                            {{ !$isStarted ? 'disabled' : '' }}>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
