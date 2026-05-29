<div class="max-w-5xl mx-auto py-8">
    
    <!-- Wizard Header & Progress -->
    <div class="mb-10 text-center">
        <h1 class="text-4xl font-black tracking-tight text-text-primary mb-3">Campaign Builder</h1>
        <p class="text-text-secondary text-lg">AI-powered multi-channel marketing in 5 steps.</p>
        
        <div class="mt-8 flex items-center justify-center space-x-4">
            @foreach(['Listing', 'Goal', 'Channels', 'Generate', 'Review'] as $idx => $label)
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 {{ $step > ($idx + 1) ? 'bg-success-500 text-white shadow-md' : ($step === ($idx + 1) ? 'bg-brand-primary text-white shadow-lg ring-4 ring-brand-primary/20 scale-110' : 'bg-surface-card border border-border-default/60 text-text-tertiary') }}">
                        @if($step > ($idx + 1))
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            {{ $idx + 1 }}
                        @endif
                    </div>
                    @if($idx < 4)
                        <div class="h-1 w-12 mx-2 rounded-full transition-colors duration-300 {{ $step > ($idx + 1) ? 'bg-success-500' : 'bg-border-default/60' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Wizard Card -->
    <div class="glass-panel rounded-3xl border border-border-default/60 shadow-xl overflow-hidden relative">
        
        @if($step === 1)
            <!-- Step 1: Select Listing -->
            <div class="p-10">
                <h2 class="text-2xl font-bold text-text-primary mb-6">Select a Listing</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($listings as $listing)
                        <div wire:click="selectListing({{ $listing->id }})" class="bg-surface-sunken border-2 border-transparent hover:border-brand-primary/50 rounded-2xl overflow-hidden cursor-pointer hover-spring group transition-all">
                            <div class="h-40 bg-surface-raised relative">
                                <!-- Mock Image Placeholder -->
                                <div class="absolute inset-0 flex items-center justify-center text-text-tertiary">
                                    <svg class="h-12 w-12 opacity-50 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                </div>
                                <div class="absolute top-3 left-3 px-3 py-1 bg-surface-card/90 backdrop-blur border border-border-default/60 rounded-lg text-xs font-bold text-text-primary uppercase tracking-wider">
                                    {{ str_replace('_', ' ', $listing->status) }}
                                </div>
                            </div>
                            <div class="p-5">
                                <h3 class="text-sm font-bold text-text-primary mb-1 truncate">{{ $listing->property->address_line_1 }}</h3>
                                <p class="text-xs text-text-secondary mb-3">{{ $listing->property->city }}, {{ $listing->property->state_province }}</p>
                                <div class="flex items-center justify-between text-brand-primary font-black tracking-tight">
                                    ₦{{ number_format($listing->listing_price) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        @elseif($step === 2)
            <!-- Step 2: Choose Goal -->
            <div class="p-10">
                <h2 class="text-2xl font-bold text-text-primary mb-6">What is the campaign goal?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $goals = [
                            'maximise_inquiries' => ['title' => 'Maximise Inquiries', 'desc' => 'Aggressive push to generate as many leads as possible.', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                            'promote_open_day' => ['title' => 'Promote Open Day', 'desc' => 'Drive foot traffic to an upcoming viewing event.', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                            'target_investors' => ['title' => 'Target Investors', 'desc' => 'Focus on ROI, rental yields, and capital appreciation.', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'price_reduction' => ['title' => 'Price Reduction', 'desc' => 'Create urgency around a recently lowered price.', 'icon' => 'M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z'],
                        ];
                    @endphp

                    @foreach($goals as $key => $g)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="goal" value="{{ $key }}" class="peer sr-only">
                            <div class="p-6 rounded-2xl border-2 border-border-default/60 hover:border-brand-primary/50 peer-checked:border-brand-primary peer-checked:bg-brand-primary/5 transition-all">
                                <div class="flex items-start space-x-4">
                                    <div class="h-12 w-12 rounded-xl bg-surface-raised flex items-center justify-center text-brand-primary shrink-0">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $g['icon'] }}"></path></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-text-primary">{{ $g['title'] }}</h3>
                                        <p class="text-sm text-text-secondary mt-1">{{ $g['desc'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

        @elseif($step === 3)
            <!-- Step 3: Select Channels -->
            <div class="p-10">
                <h2 class="text-2xl font-bold text-text-primary mb-6">Select Distribution Channels</h2>
                <div class="space-y-4">
                    @foreach(['instagram', 'facebook', 'linkedin', 'email', 'whatsapp'] as $channel)
                        <label class="flex items-center justify-between p-5 rounded-2xl border-2 border-border-default/60 hover:border-brand-primary/50 transition-all cursor-pointer @if($channels[$channel]) border-brand-primary bg-brand-primary/5 @endif">
                            <div class="flex items-center space-x-4">
                                <input type="checkbox" wire:model="channels.{{ $channel }}" class="h-5 w-5 text-brand-primary rounded border-border-default focus:ring-brand-primary">
                                <span class="text-lg font-bold text-text-primary capitalize">{{ $channel }}</span>
                            </div>
                            @if($channels[$channel])
                                <span class="px-3 py-1 bg-success-500/10 text-success-500 text-xs font-bold uppercase tracking-wider rounded-lg">Selected</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

        @elseif($step === 4)
            <!-- Step 4: AI Generating -->
            <div class="p-16 text-center" wire:init="completeGeneration">
                <div class="relative w-32 h-32 mx-auto mb-8">
                    <!-- Glowing orb -->
                    <div class="absolute inset-0 bg-brand-primary/20 blur-2xl rounded-full animate-pulse"></div>
                    <!-- Spinner -->
                    <svg class="absolute inset-0 h-full w-full text-brand-primary animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="h-10 w-10 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904ZM19.006 8.246 18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006Z"></path></svg>
                    </div>
                </div>
                <h2 class="text-2xl font-black text-text-primary mb-2">Copilot is drafting your content...</h2>
                <p class="text-text-secondary">Analyzing property features, optimizing for the "{{ $goal }}" goal, and tailoring formats for your selected channels.</p>
            </div>

        @elseif($step === 5)
            <!-- Step 5: Review & Schedule -->
            <div class="p-10">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-text-primary">Review & Schedule</h2>
                    <button wire:click="saveCampaign" class="bg-brand-primary text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg hover:shadow-xl hover:bg-brand-secondary transition-all hover-spring">Schedule Campaign</button>
                </div>
                
                <div class="space-y-8">
                    @foreach($generatedContents as $channel => $content)
                        <div class="glass-panel border border-border-default/60 rounded-3xl overflow-hidden">
                            <div class="px-6 py-4 border-b border-border-default/40 bg-surface-sunken/50 flex items-center justify-between">
                                <h3 class="text-lg font-black text-text-primary capitalize flex items-center space-x-2">
                                    <span class="h-2 w-2 rounded-full bg-brand-primary"></span>
                                    <span>{{ $channel }} Post</span>
                                </h3>
                                <button class="text-xs font-bold text-brand-primary bg-brand-primary/10 px-3 py-1.5 rounded-lg hover:bg-brand-primary/20 transition-colors">Edit</button>
                            </div>
                            <div class="p-6">
                                <textarea class="w-full h-40 bg-transparent border-0 focus:ring-0 text-text-primary resize-none text-sm leading-relaxed">{{ $content }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Footer Actions -->
        @if($step > 1 && $step < 4)
            <div class="px-10 py-6 border-t border-border-default/60 bg-surface-sunken/30 flex items-center justify-between">
                <button wire:click="prevStep" class="px-6 py-2.5 rounded-xl border border-border-default/60 text-text-secondary font-bold hover:bg-surface-raised hover:text-text-primary transition-colors">Back</button>
                <button wire:click="nextStep" class="px-6 py-2.5 rounded-xl bg-text-primary text-surface-page font-bold hover:bg-brand-primary hover:text-white transition-colors shadow-md">Continue &rarr;</button>
            </div>
        @endif
        
    </div>
</div>
