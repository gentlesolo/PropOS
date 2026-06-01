<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Revenue Forecasting</h1>
            <p class="mt-2 text-text-secondary">Pipeline-based forecast with confidence scoring and scenario planning.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$toggle('showScenario')" class="px-4 py-2 border border-brand-primary/40 text-brand-primary rounded-xl text-sm font-bold hover:bg-brand-primary/5 transition-colors">
                {{ $showScenario ? 'Hide Scenario' : '📊 Scenario Planner' }}
            </button>
            <select wire:model="timeframe" class="bg-surface-card border border-border-default/60 text-text-primary rounded-xl px-4 py-2 text-sm font-semibold focus:ring-brand-primary focus:border-brand-primary">
                <option value="30_days">Next 30 Days</option>
                <option value="60_days">Next 60 Days</option>
                <option value="90_days">Next 90 Days</option>
            </select>
        </div>
    </div>

    <!-- Main Forecast Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Weighted Forecast Card -->
        <div class="lg:col-span-2 glass-panel rounded-2xl border border-border-default/60 p-8 shadow-sm relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-brand-primary/5 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
                <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-2">Weighted Pipeline Forecast</p>
                <h2 class="text-5xl font-black tracking-tight text-brand-primary mb-2">
                    {{ $currencySymbol }}{{ number_format($data['weighted_forecast'] / 1000000, 1) }}M
                </h2>
                <p class="text-sm text-text-secondary font-medium">From {{ $data['deals_count'] }} active deals · {{ $this->timeframe }} horizon</p>

                @if($showScenario && $data['scenario_upside'] > 0)
                <div class="mt-4 p-3 bg-success-50 border border-success-200 rounded-xl">
                    <p class="text-xs font-bold text-success-700 mb-1">Scenario: +{{ $scenario_extra_deals }} deals closed</p>
                    <p class="text-xl font-black text-success-700">{{ $currencySymbol }}{{ number_format($data['scenario_forecast'] / 1000000, 1) }}M <span class="text-sm font-medium">(+{{ $currencySymbol }}{{ number_format($data['scenario_upside'] / 1000000, 1) }}M upside)</span></p>
                </div>
                @endif

                <div class="mt-6 pt-5 border-t border-border-default/40 grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-1">Total Pipeline</p>
                        <p class="text-lg font-black text-text-primary">{{ $currencySymbol }}{{ number_format($data['total_pipeline'] / 1000000, 1) }}M</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-1">Annual Target</p>
                        <p class="text-lg font-black text-text-primary">{{ $currencySymbol }}{{ number_format($data['target'] / 1000000, 1) }}M</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-1">Target Gap</p>
                        <p class="text-lg font-black {{ $data['gap'] > 0 ? 'text-danger-600' : 'text-success-600' }}">
                            {{ $data['gap'] > 0 ? '-' : '+' }}{{ $currencySymbol }}{{ number_format(abs($data['gap']) / 1000000, 1) }}M
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confidence + AI Insights -->
        <div class="flex flex-col gap-4">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5 flex items-center gap-4">
                <div class="flex items-center justify-center h-20 w-20 rounded-full border-4 {{ $data['confidence'] > 70 ? 'border-success-500' : 'border-warning-500' }} shrink-0">
                    <div class="text-center">
                        <span class="text-xl font-black text-text-primary block">{{ $data['confidence'] }}%</span>
                        <span class="text-[9px] font-bold text-text-tertiary uppercase">Confidence</span>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-bold text-text-primary">Pipeline Health</p>
                    <p class="text-xs text-text-secondary mt-0.5">
                        @if($data['confidence'] >= 70) Strong pipeline momentum.
                        @elseif($data['confidence'] >= 50) Moderate deal velocity.
                        @else Pipeline needs acceleration.
                        @endif
                    </p>
                </div>
            </div>

            <div class="glass-panel rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-5 flex-1">
                <div class="flex items-center gap-2 mb-3">
                    <div class="h-7 w-7 bg-brand-primary rounded-lg flex items-center justify-center text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-text-primary">Copilot Insights</h3>
                </div>

                @if(empty($aiInsights))
                <p class="text-xs text-text-secondary mb-3">Click below to generate AI insights based on your live pipeline data.</p>
                @else
                <div class="space-y-2.5 mb-3">
                    @foreach($aiInsights as $insight)
                    <div class="flex items-start gap-2">
                        <div class="mt-1.5 h-1.5 w-1.5 rounded-full bg-brand-primary shrink-0"></div>
                        <p class="text-xs text-text-primary leading-relaxed">{{ $insight }}</p>
                    </div>
                    @endforeach
                </div>
                @endif

                <button wire:click="generateInsights" class="w-full py-2 bg-brand-primary text-white rounded-xl text-xs font-bold hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="generateInsights">✨ {{ empty($aiInsights) ? 'Generate Insights' : 'Refresh Insights' }}</span>
                    <span wire:loading wire:target="generateInsights">Generating...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Scenario Planner -->
    @if($showScenario)
    <div class="glass-panel rounded-2xl border border-brand-primary/30 bg-brand-primary/5 p-5 mb-8">
        <h3 class="text-sm font-bold text-text-primary mb-4">Scenario Planner — What if we close more deals?</h3>
        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Extra deals closed</label>
                <input wire:model.lazy="scenario_extra_deals" type="number" min="0" max="20"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Avg deal value ({{ $currencySymbol }})</label>
                <input wire:model.lazy="scenario_avg_value" type="number" min="0"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
        </div>
        <p class="text-xs text-text-secondary mt-3">Upside: <strong class="text-success-600">+{{ $currencySymbol }}{{ number_format($data['scenario_upside'] / 1000000, 2) }}M</strong> → Scenario forecast: <strong class="text-text-primary">{{ $currencySymbol }}{{ number_format($data['scenario_forecast'] / 1000000, 1) }}M</strong></p>
    </div>
    @endif

    <!-- Stage Breakdown -->
    @if(!empty($data['stages']))
    <h3 class="text-lg font-bold text-text-primary mb-5">Forecast by Stage</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($data['stages'] as $stageName => $stageData)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5 hover:border-brand-primary/40 transition-colors">
            <div class="flex justify-between items-start mb-3">
                <h4 class="text-sm font-bold text-text-primary">{{ $stageName }}</h4>
                <span class="text-[10px] font-bold bg-surface-raised text-text-tertiary px-2 py-0.5 rounded-full">{{ $stageData['count'] }}</span>
            </div>
            <p class="text-xl font-black text-text-primary">{{ $currencySymbol }}{{ number_format($stageData['weighted'] / 1000000, 1) }}M</p>
            <p class="text-[10px] text-text-secondary mt-0.5">weighted · {{ $currencySymbol }}{{ number_format($stageData['value'] / 1000000, 1) }}M total</p>
            <div class="w-full bg-surface-raised rounded-full h-1.5 mt-3">
                <div class="bg-brand-primary h-1.5 rounded-full" style="width: {{ $data['weighted_forecast'] > 0 ? min(100, ($stageData['weighted'] / $data['weighted_forecast']) * 100) : 0 }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
