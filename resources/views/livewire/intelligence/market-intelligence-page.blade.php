<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Market Intelligence</h1>
            <p class="mt-2 text-text-secondary">AI-generated market reports based on your live agency data.</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Active Listings</p>
            <h3 class="text-2xl font-black text-text-primary">{{ $quickStats['active_listings'] }}</h3>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Deals Closed (30d)</p>
            <h3 class="text-2xl font-black text-success-600">{{ $quickStats['deals_won_30d'] }}</h3>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Viewings (30d)</p>
            <h3 class="text-2xl font-black text-brand-primary">{{ $quickStats['viewings_30d'] }}</h3>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Avg Listing Price</p>
            <h3 class="text-2xl font-black text-text-primary">₦{{ number_format($quickStats['avg_price'] / 1000000, 1) }}M</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Report Generator -->
        <div class="xl:col-span-1">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5 sticky top-6">
                <h3 class="text-sm font-bold text-text-primary mb-4">Generate Market Report</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Area / City</label>
                        <select wire:model.defer="area" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="">All Areas</option>
                            @foreach($areas as $city)
                            <option value="{{ $city }}">{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Property Type</label>
                        <select wire:model.defer="propertyType" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="">All Types</option>
                            <option value="house">House</option>
                            <option value="apartment">Apartment</option>
                            <option value="townhouse">Townhouse</option>
                            <option value="commercial">Commercial</option>
                            <option value="land">Land</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Report Period</label>
                        <select wire:model.defer="reportPeriod" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="30">Last 30 Days</option>
                            <option value="60">Last 60 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                    <button wire:click="generateReport" class="w-full py-2.5 bg-brand-primary text-white rounded-xl font-bold text-sm hover:bg-brand-secondary transition-colors hover-spring flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="generateReport">✨ Generate Report</span>
                        <span wire:loading wire:target="generateReport" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Generating...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Report Output -->
        <div class="xl:col-span-2">
            @if(empty($report))
            <div class="glass-panel rounded-2xl border border-border-default/60 p-12 text-center">
                <div class="h-16 w-16 bg-brand-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl">📈</div>
                <h3 class="text-base font-bold text-text-primary mb-2">No report generated yet</h3>
                <p class="text-sm text-text-secondary">Configure your filters and click "Generate Report" to create an AI-powered market intelligence report using your live agency data.</p>
            </div>
            @else
            <div class="space-y-5">

                <!-- Summary -->
                <div class="glass-panel rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-8 w-8 bg-brand-primary rounded-lg flex items-center justify-center text-white text-sm">📊</div>
                        <h3 class="text-base font-bold text-text-primary">Executive Summary</h3>
                    </div>
                    <p class="text-sm text-text-primary leading-relaxed">{{ $report['summary'] }}</p>
                </div>

                <!-- Market Trends -->
                <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                    <h3 class="text-sm font-bold text-text-primary mb-3">Market Trends</h3>
                    <div class="space-y-2.5">
                        @foreach($report['market_trends'] as $trend)
                        <div class="flex items-start gap-2.5">
                            <div class="h-5 w-5 rounded-full bg-info-100 text-info-700 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">{{ $loop->iteration }}</div>
                            <p class="text-sm text-text-secondary">{{ $trend }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Pricing + Demand in 2 cols -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                        <h3 class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Pricing Insights</h3>
                        <p class="text-sm text-text-primary leading-relaxed">{{ $report['pricing_insights'] }}</p>
                    </div>
                    <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                        <h3 class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Demand Outlook</h3>
                        <p class="text-sm text-text-primary leading-relaxed">{{ $report['demand_outlook'] }}</p>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="glass-panel rounded-2xl border border-success-200 bg-success-50/40 p-5">
                    <h3 class="text-sm font-bold text-text-primary mb-3">Strategic Recommendations</h3>
                    <div class="space-y-2.5">
                        @foreach($report['recommendations'] as $rec)
                        <div class="flex items-start gap-2.5">
                            <div class="h-5 w-5 rounded-full bg-success-500 text-white flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">✓</div>
                            <p class="text-sm text-text-primary">{{ $rec }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
