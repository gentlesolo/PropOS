<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <span class="text-3xl">🧠</span> Sentiment Monitoring
            </h1>
            <p class="mt-2 text-text-secondary">AI analysis of client feedback and interaction sentiment to guide strategy.</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model="period" class="border border-border-default rounded-xl px-3 py-2 text-sm bg-surface-card text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                <option value="30">Last 30 Days</option>
                <option value="60">Last 60 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
            <button wire:click="analyseSentiment" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                <span wire:loading.remove wire:target="analyseSentiment">✨ Analyse Sentiment</span>
                <span wire:loading wire:target="analyseSentiment" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Analysing...
                </span>
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-text-primary">{{ $stats['total_feedback'] }}</p>
            <p class="text-xs text-text-secondary mt-1">Feedback Records</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black {{ $stats['avg_rating'] >= 4 ? 'text-success-600' : ($stats['avg_rating'] >= 3 ? 'text-warning-600' : 'text-danger-600') }}">
                {{ $stats['avg_rating'] }}/5
            </p>
            <p class="text-xs text-text-secondary mt-1">Avg Viewing Rating</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-success-600">{{ $stats['would_offer'] }}</p>
            <p class="text-xs text-text-secondary mt-1">Would Consider Offer</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-brand-primary">{{ $stats['very_interested'] }}</p>
            <p class="text-xs text-text-secondary mt-1">Very Interested</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- AI Summary (if generated) -->
        @if($aiSummary)
        <div class="xl:col-span-1 space-y-5">
            <div class="bg-surface-card rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-5">
                <h3 class="text-sm font-bold text-text-primary mb-4 flex items-center gap-2">
                    <span class="h-6 w-6 bg-brand-primary rounded-lg flex items-center justify-center text-white text-xs">AI</span>
                    Sentiment Analysis
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-text-secondary">Overall Sentiment</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase
                            @if($aiSummary['overall_sentiment'] === 'positive') bg-success-100 text-success-700
                            @elseif($aiSummary['overall_sentiment'] === 'negative') bg-danger-100 text-danger-700
                            @else bg-warning-100 text-warning-700 @endif">
                            {{ $aiSummary['overall_sentiment'] }}
                        </span>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="text-text-secondary">Sentiment Score</span><span class="font-bold">{{ $aiSummary['sentiment_score'] }}/100</span></div>
                        <div class="w-full bg-surface-raised rounded-full h-2">
                            <div class="h-2 rounded-full {{ $aiSummary['sentiment_score'] >= 70 ? 'bg-success-500' : ($aiSummary['sentiment_score'] >= 50 ? 'bg-warning-500' : 'bg-danger-500') }}" style="width: {{ $aiSummary['sentiment_score'] }}%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 bg-surface-sunken/40 rounded-lg">
                            <p class="text-text-secondary mb-0.5">Buyer Confidence</p>
                            <p class="font-bold text-text-primary capitalize">{{ $aiSummary['buyer_confidence'] }}</p>
                        </div>
                        <div class="p-2 bg-surface-sunken/40 rounded-lg">
                            <p class="text-text-secondary mb-0.5">Price Sensitivity</p>
                            <p class="font-bold text-text-primary capitalize">{{ $aiSummary['price_sensitivity'] }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-text-secondary mb-2 uppercase tracking-wider">Key Themes</p>
                        @foreach($aiSummary['key_themes'] as $theme)
                        <div class="flex items-start gap-2 mb-1.5">
                            <div class="h-1.5 w-1.5 rounded-full bg-brand-primary shrink-0 mt-1.5"></div>
                            <p class="text-xs text-text-primary">{{ $theme }}</p>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-3 bg-success-50 border border-success-200 rounded-xl">
                        <p class="text-xs font-bold text-success-700 mb-1">Recommendation</p>
                        <p class="text-xs text-success-700">{{ $aiSummary['recommendation'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="xl:col-span-2">
        @else
        <div class="xl:col-span-3">
        @endif

            <!-- Rating Distribution -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5 mb-5">
                <h3 class="text-sm font-bold text-text-primary mb-4">Rating Distribution</h3>
                @php $maxCount = max(1, max($ratingDist)); @endphp
                <div class="space-y-2">
                    @foreach(array_reverse([1,2,3,4,5]) as $star)
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-warning-500 w-6">{{ str_repeat('★', $star) }}</span>
                        <div class="flex-1 bg-surface-raised rounded-full h-3">
                            <div class="h-3 rounded-full bg-warning-400 transition-all" style="width: {{ ($ratingDist[$star] / $maxCount) * 100 }}%"></div>
                        </div>
                        <span class="text-xs text-text-secondary w-5 text-right">{{ $ratingDist[$star] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Feedback Table -->
            <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-5 py-3 border-b border-border-default bg-surface-sunken/30">
                    <h3 class="text-sm font-bold text-text-primary">Recent Viewing Feedback</h3>
                </div>
                <div class="divide-y divide-border-default/40">
                    @forelse($feedbacks->take(15) as $feedback)
                    <div class="px-5 py-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-text-primary truncate">{{ $feedback->viewing?->contact?->first_name }} {{ $feedback->viewing?->contact?->last_name }}</p>
                                <p class="text-xs text-text-secondary">{{ $feedback->viewing?->listing?->property?->address_line_1 }} · {{ $feedback->created_at->diffForHumans() }}</p>
                                @if($feedback->concerns)
                                <p class="text-xs text-danger-600 mt-1">Concern: {{ Str::limit($feedback->concerns, 80) }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="text-warning-500 text-sm">{{ str_repeat('★', $feedback->overall_rating ?? 0) }}{{ str_repeat('☆', 5 - ($feedback->overall_rating ?? 0)) }}</div>
                                @if($feedback->would_make_offer)
                                <span class="px-1.5 py-0.5 bg-success-100 text-success-700 text-[10px] font-bold rounded">Offer Intent</span>
                                @endif
                                <span class="text-[10px] text-text-secondary capitalize">{{ str_replace('_', ' ', $feedback->interest_level ?? '') }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-sm text-text-secondary">No viewing feedback in this period. Complete viewings and log feedback to see sentiment data here.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>



