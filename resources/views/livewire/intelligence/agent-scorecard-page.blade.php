<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Agent Scorecard</h1>
            <p class="mt-2 text-text-secondary">AI-driven performance metrics and coaching for {{ auth()->user()->first_name }}.</p>
        </div>
        <select wire:model="timeframe" class="bg-surface-card border border-border-default/60 text-text-primary rounded-xl px-4 py-2 text-sm font-semibold focus:ring-brand-primary focus:border-brand-primary">
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
        </select>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left: Core Metrics + Chart -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Key Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                <div class="glass-panel rounded-2xl border border-border-default/60 p-5 hover-spring">
                    <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-2">Won Revenue</p>
                    <h3 class="text-2xl font-black text-text-primary">₦{{ number_format($metrics['won_value'] / 1000000, 1) }}M</h3>
                    <p class="text-xs text-text-secondary mt-1">{{ $metrics['won_deals'] }} deal{{ $metrics['won_deals'] !== 1 ? 's' : '' }} closed</p>
                </div>
                <div class="glass-panel rounded-2xl border border-border-default/60 p-5 hover-spring">
                    <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-2">Conversion Rate</p>
                    <h3 class="text-2xl font-black text-text-primary">{{ $metrics['conversion_rate'] }}%</h3>
                    <p class="text-xs text-text-secondary mt-1">{{ $metrics['total_deals'] }} total deals</p>
                </div>
                <div class="glass-panel rounded-2xl border border-border-default/60 p-5 hover-spring">
                    <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-2">Viewings Done</p>
                    <h3 class="text-2xl font-black text-text-primary">{{ $metrics['viewings_completed'] }}</h3>
                    <p class="text-xs text-text-secondary mt-1">{{ $metrics['new_leads'] }} new leads</p>
                </div>
                <div class="glass-panel rounded-2xl border border-border-default/60 p-5 hover-spring">
                    <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-2">Pipeline Value</p>
                    <h3 class="text-2xl font-black text-text-primary">₦{{ number_format($metrics['pipeline_value'] / 1000000, 1) }}M</h3>
                    <p class="text-xs text-text-secondary mt-1">Active deals</p>
                </div>
                <div class="glass-panel rounded-2xl border border-border-default/60 p-5 hover-spring md:col-span-2">
                    <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-2">Monthly Targets</p>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1"><span>New Leads</span><span>{{ $metrics['new_leads'] }} / 50</span></div>
                            <div class="w-full bg-surface-raised rounded-full h-2"><div class="bg-brand-primary h-2 rounded-full" style="width: {{ min(100, ($metrics['new_leads'] / 50) * 100) }}%"></div></div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1"><span>Deals Won</span><span>{{ $metrics['won_deals'] }} / 5</span></div>
                            <div class="w-full bg-surface-raised rounded-full h-2"><div class="bg-success-500 h-2 rounded-full" style="width: {{ min(100, ($metrics['won_deals'] / 5) * 100) }}%"></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real Activity Chart -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-text-primary">14-Week Activity (Deals & Viewings)</h3>
                    <div class="flex items-center gap-4 text-xs font-bold text-text-tertiary">
                        <span class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-full bg-brand-primary"></div>Deals</span>
                        <span class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-full bg-success-500"></div>Viewings</span>
                    </div>
                </div>
                @php $maxVal = max(1, collect($metrics['chart_data'])->max(fn($d) => max($d['deals'], $d['viewings']))); @endphp
                <div class="h-48 flex items-end gap-1">
                    @foreach($metrics['chart_data'] as $point)
                    <div class="flex-1 flex flex-col justify-end gap-0.5 group relative">
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 bg-surface-card border border-border-default shadow rounded-lg px-2 py-1 text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 whitespace-nowrap">
                            {{ $point['label'] }}: {{ $point['deals'] }}D / {{ $point['viewings'] }}V
                        </div>
                        <div class="w-full bg-success-500/80 rounded-sm" style="height: {{ ($point['viewings'] / $maxVal) * 100 }}%"></div>
                        <div class="w-full bg-brand-primary/80 rounded-sm" style="height: {{ ($point['deals'] / $maxVal) * 100 }}%"></div>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2 text-[10px] text-text-tertiary">
                    <span>{{ $metrics['chart_data'][0]['label'] }}</span>
                    <span>{{ $metrics['chart_data'][count($metrics['chart_data']) - 1]['label'] }}</span>
                </div>
            </div>
        </div>

        <!-- Right: AI Coaching -->
        <div class="space-y-6">
            <div class="glass-panel rounded-2xl border border-brand-primary/30 bg-gradient-to-b from-brand-primary/5 to-transparent p-6 shadow-lg relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-brand-primary/10 blur-2xl pointer-events-none"></div>
                <div class="flex items-center mb-5 relative z-10">
                    <div class="h-10 w-10 rounded-xl bg-brand-primary text-white flex items-center justify-center mr-3 shadow-md">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904ZM19.006 8.246 18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006Z"/></svg>
                    </div>
                    <h2 class="text-lg font-black text-text-primary">Copilot Coaching</h2>
                </div>

                <div class="space-y-3 relative z-10 mb-5">
                    @foreach($aiInsights as $insight)
                    <div class="flex items-start bg-surface-card/60 backdrop-blur border border-border-default/40 rounded-xl p-3.5">
                        <div class="mt-1 w-2 h-2 rounded-full bg-brand-primary shrink-0 mr-3"></div>
                        <p class="text-sm text-text-primary leading-relaxed">{{ $insight }}</p>
                    </div>
                    @endforeach
                </div>

                <button wire:click="generateAnalysis" class="w-full py-2.5 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors flex items-center justify-center gap-2 hover-spring">
                    <span wire:loading.remove wire:target="generateAnalysis">
                        <svg class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Generate Full Analysis
                    </span>
                    <span wire:loading wire:target="generateAnalysis" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Analysing...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
