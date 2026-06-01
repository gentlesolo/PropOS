<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <span class="text-3xl">🔮</span> AI Prediction Engine
            </h1>
            <p class="mt-2 text-text-secondary">OpenAI-powered lead scoring and deal momentum prediction to prioritise your pipeline.</p>
        </div>
        <button wire:click="scoreAllContacts" wire:loading.attr="disabled"
            class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors disabled:opacity-60">
            <span wire:loading.remove wire:target="scoreAllContacts">⚡ Score All Contacts</span>
            <span wire:loading wire:target="scoreAllContacts" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Scoring...
            </span>
        </button>
    </div>

    <!-- Contact Heatmap Stats -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="glass-panel p-5 rounded-2xl border border-danger-200 bg-danger-50/30 text-center">
            <p class="text-3xl font-black text-danger-600">{{ $contactStats['hot'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">🔥 Hot Leads (70+)</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-warning-200 bg-warning-50/30 text-center">
            <p class="text-3xl font-black text-warning-600">{{ $contactStats['warm'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">🌤 Warm Leads (40–69)</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-info-200 bg-info-50/30 text-center">
            <p class="text-3xl font-black text-info-600">{{ $contactStats['cold'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">❄️ Cold Leads (&lt;40)</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60 text-center">
            <p class="text-3xl font-black text-text-tertiary">{{ $contactStats['unscored'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">⚪ Unscored</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-border-default/60 mb-6">
        @foreach(['contacts' => '👥 Contact Scores', 'deals' => '🤝 Deal Momentum'] as $tab => $label)
        <button wire:click="$set('scoringTab', '{{ $tab }}')"
            class="px-5 py-2.5 border-b-2 font-bold text-sm transition-colors
            {{ $scoringTab === $tab ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @if($scoringTab === 'contacts')
    <!-- Contact Score Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-sunken/50 border-b border-border-default/40">
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Contact</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Type</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Status</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Activities</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Intent Score</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/40">
                    @forelse($contacts as $contact)
                    @php
                        $score = $scores["contact_{$contact->id}"] ?? $contact->intent_score ?? 0;
                        $scoreColor = $score >= 70 ? 'text-danger-600 bg-danger-50' : ($score >= 40 ? 'text-warning-600 bg-warning-50' : ($score > 0 ? 'text-info-600 bg-info-50' : 'text-text-tertiary bg-surface-sunken'));
                        $barColor = $score >= 70 ? 'bg-danger-500' : ($score >= 40 ? 'bg-warning-500' : 'bg-info-400');
                    @endphp
                    <tr class="hover:bg-surface-raised/20 transition-colors">
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center text-sm font-bold shrink-0">
                                    {{ $contact->initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-text-primary">{{ $contact->full_name }}</p>
                                    <p class="text-xs text-text-secondary">{{ $contact->email ?? $contact->phone ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize bg-surface-sunken text-text-secondary">
                                {{ str_replace('_', ' ', $contact->type) }}
                            </span>
                        </td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize
                                @if($contact->status === 'qualified') bg-success-100 text-success-700
                                @elseif($contact->status === 'active') bg-brand-primary/10 text-brand-primary
                                @elseif($contact->status === 'nurturing') bg-warning-100 text-warning-700
                                @else bg-surface-sunken text-text-secondary @endif">
                                {{ $contact->status }}
                            </span>
                        </td>
                        <td class="py-4 px-5 text-sm text-text-secondary">{{ $contact->activities_count ?? $contact->activities->count() }}</td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3 min-w-[120px]">
                                <div class="flex-1 bg-surface-raised rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $score }}%"></div>
                                </div>
                                <span class="text-sm font-black {{ $scoreColor }} px-2 py-0.5 rounded-lg min-w-[44px] text-center">
                                    {{ $score > 0 ? $score : '—' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <button wire:click="scoreContact({{ $contact->id }})"
                                wire:loading.attr="disabled"
                                class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-3 py-1.5 hover:bg-brand-primary/5 transition-colors disabled:opacity-50">
                                @if($scoring && $scoringId === $contact->id)
                                    <span class="flex items-center gap-1">
                                        <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Scoring...
                                    </span>
                                @else
                                    ✨ Re-score
                                @endif
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-12 text-center text-sm text-text-secondary">No active contacts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @else
    <!-- Deal Momentum Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-sunken/50 border-b border-border-default/40">
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Deal / Contact</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Stage</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Value</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Activities</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Momentum</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/40">
                    @forelse($deals as $deal)
                    @php
                        $score = $scores["deal_{$deal->id}"] ?? $deal->momentum_score ?? 0;
                        $label = $score >= 70 ? 'Hot' : ($score >= 40 ? 'Warm' : 'Cold');
                        $labelClass = $score >= 70 ? 'bg-danger-100 text-danger-700' : ($score >= 40 ? 'bg-warning-100 text-warning-700' : 'bg-info-100 text-info-700');
                        $barColor = $score >= 70 ? 'bg-danger-500' : ($score >= 40 ? 'bg-warning-500' : 'bg-info-400');
                    @endphp
                    <tr class="hover:bg-surface-raised/20 transition-colors">
                        <td class="py-4 px-5">
                            <p class="text-sm font-bold text-text-primary">{{ $deal->contact?->full_name ?? 'Unknown' }}</p>
                            <p class="text-xs text-text-secondary">{{ $deal->listing?->property?->address_line_1 ?? 'No listing' }}</p>
                        </td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-surface-sunken text-text-secondary">
                                {{ $deal->stage?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="py-4 px-5 text-sm font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($deal->value ?? 0) }}</td>
                        <td class="py-4 px-5 text-sm text-text-secondary">{{ $deal->activities->count() }}</td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div class="flex-1 bg-surface-raised rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $score }}%"></div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $labelClass }}">
                                    {{ $score > 0 ? $label . ' ' . $score : '—' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <button wire:click="scoreDeal({{ $deal->id }})"
                                wire:loading.attr="disabled"
                                class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-3 py-1.5 hover:bg-brand-primary/5 transition-colors disabled:opacity-50">
                                @if($scoring && $scoringId === $deal->id)
                                    <span class="flex items-center gap-1">
                                        <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Scoring...
                                    </span>
                                @else
                                    ✨ Re-score
                                @endif
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-12 text-center text-sm text-text-secondary">No active deals in the pipeline.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- How it works note -->
    <div class="mt-6 p-4 bg-surface-sunken/40 rounded-2xl border border-border-default/40 flex items-start gap-3">
        <div class="h-6 w-6 bg-brand-primary rounded-lg flex items-center justify-center text-white text-xs shrink-0 mt-0.5">AI</div>
        <div>
            <p class="text-xs font-bold text-text-primary mb-1">How scoring works</p>
            <p class="text-xs text-text-secondary">Each score is generated by OpenAI based on contact/deal attributes — type, status, activity frequency, days since last contact, budget presence, and existing signals. Scores are saved back to the record and update the pipeline heat indicators.</p>
        </div>
    </div>
</div>
