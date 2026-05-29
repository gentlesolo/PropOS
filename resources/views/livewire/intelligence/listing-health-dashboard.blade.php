<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Listing Health Index</h1>
            <p class="mt-2 text-text-secondary">Real-time health scoring for every active listing — viewings, media, and market signals.</p>
        </div>
    </div>

    <!-- Portfolio Summary (real data) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Active Listings</p>
            <h3 class="text-3xl font-black text-text-primary">{{ $summary['total'] }}</h3>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Avg Health Score</p>
            <h3 class="text-3xl font-black {{ $summary['avg_score'] >= 70 ? 'text-success-600' : ($summary['avg_score'] >= 50 ? 'text-warning-600' : 'text-danger-600') }}">{{ $summary['avg_score'] }}</h3>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Avg Days on Market</p>
            <h3 class="text-3xl font-black text-text-primary">{{ $summary['avg_dom'] }}</h3>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">At Risk</p>
            <h3 class="text-3xl font-black text-danger-600">{{ $summary['at_risk'] }}</h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-border-default/60 flex items-center justify-between bg-surface-sunken/30 gap-4">
            <div class="flex-1 max-w-xs">
                <input wire:model.debounce.300ms="search" type="text" placeholder="Search by address..."
                    class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white/50 focus:ring-2 focus:ring-brand-primary text-sm">
            </div>
            <div class="flex gap-2">
                @foreach(['' => 'All', 'at_risk' => 'At Risk', 'moderate' => 'Moderate', 'healthy' => 'Healthy'] as $val => $label)
                <button wire:click="$set('filterHealth', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors
                    {{ $filterHealth === $val ? 'bg-brand-primary text-white' : 'border border-border-default bg-surface-card text-text-secondary hover:bg-surface-sunken' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-sunken/20 border-b border-border-default/40">
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Property</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Price</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">DOM</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Viewings</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Photos</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Health</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Copilot Recommendation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/40">
                    @forelse($listings as $listing)
                    <tr class="hover:bg-surface-raised/20 transition-colors">
                        <td class="py-4 px-5">
                            <a href="{{ route('listing.detail', $listing) }}" class="text-sm font-bold text-text-primary hover:text-brand-primary">{{ $listing->property->address_line_1 }}</a>
                            <p class="text-xs text-text-secondary">{{ $listing->property->city }} · {{ ucfirst($listing->property->property_type) }}</p>
                        </td>
                        <td class="py-4 px-5">
                            <p class="text-sm font-black text-text-primary">₦{{ number_format($listing->listing_price / 1000000, 1) }}M</p>
                        </td>
                        <td class="py-4 px-5">
                            <span class="text-sm font-bold {{ $listing->days_on_market > 45 ? 'text-danger-600' : ($listing->days_on_market > 21 ? 'text-warning-600' : 'text-text-primary') }}">
                                {{ $listing->days_on_market }}d
                            </span>
                        </td>
                        <td class="py-4 px-5 text-sm font-bold text-text-primary">{{ $listing->viewings_count }}</td>
                        <td class="py-4 px-5">
                            <span class="text-sm font-bold {{ $listing->photo_count < 3 ? 'text-danger-600' : 'text-text-primary' }}">{{ $listing->photo_count }}</span>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2">
                                <div class="w-14 bg-surface-raised rounded-full h-2">
                                    <div class="h-2 rounded-full
                                        @if($listing->health_score >= 80) bg-success-500
                                        @elseif($listing->health_score >= 50) bg-warning-500
                                        @else bg-danger-500 @endif"
                                        style="width: {{ $listing->health_score }}%"></div>
                                </div>
                                <span class="text-xs font-black
                                    @if($listing->health_score >= 80) text-success-600
                                    @elseif($listing->health_score >= 50) text-warning-600
                                    @else text-danger-600 @endif">
                                    {{ $listing->health_score }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-5 max-w-xs">
                            <p class="text-xs text-text-secondary leading-relaxed">{{ $listing->recommendation }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="h-12 w-12 bg-brand-primary/10 rounded-2xl flex items-center justify-center text-xl">🏠</div>
                                <p class="text-sm font-medium text-text-primary">No listings match your filter.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
