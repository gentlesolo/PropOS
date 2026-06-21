<div wire:init="generateBrief">
    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="mb-8 flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">AI Daily Planner</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black tracking-widest bg-brand-primary/10 text-brand-primary uppercase">AI</span>
                @if($brief && $brief->focus_score)
                    @php
                        $score = $brief->focus_score;
                        $scoreColor = $score >= 70 ? 'bg-success-500/10 text-success-500 border-success-500/20'
                                    : ($score >= 40 ? 'bg-warning-500/10 text-warning-500 border-warning-500/20'
                                    : 'bg-danger-500/10 text-danger-500 border-danger-500/20');
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {{ $scoreColor }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $score >= 70 ? 'bg-success-500' : ($score >= 40 ? 'bg-warning-500' : 'bg-danger-500') }}"></span>
                        Focus {{ $score }}/100
                    </span>
                @endif
            </div>

            {{-- Date navigation --}}
            <div class="flex items-center space-x-2">
                <button wire:click="previousDay" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1.5 rounded-lg hover:bg-surface-raised transition-colors text-text-secondary hover:text-text-primary" wire:loading.attr="disabled" wire:target="previousDay">
                <span wire:loading.remove wire:target="previousDay"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></span>
                <span wire:loading wire:target="previousDay" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <p class="text-sm font-semibold text-text-secondary">
                    @if(\Carbon\Carbon::parse($selectedDate)->isToday())
                        <span class="text-brand-primary font-bold">Today</span> —
                    @endif
                    {{ \Carbon\Carbon::parse($selectedDate)->format('l, F jS Y') }}
                </p>
                <button wire:click="nextDay"
                    @if(\Carbon\Carbon::parse($selectedDate)- wire:loading.attr="disabled" wire:target="nextDay">
                <span wire:loading.remove wire:target="nextDay">isToday()) disabled @endif
                    class="p-1.5 rounded-lg hover:bg-surface-raised transition-colors text-text-secondary hover:text-text-primary disabled:opacity-30 disabled:cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
                <span wire:loading wire:target="nextDay" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>

        @if(\Carbon\Carbon::parse($selectedDate)->isToday())
        <button wire:click="regenerateBrief" wire:loading.attr="disabled"
            class="flex-shrink-0 flex items-center space-x-2 px-5 py-2.5 rounded-xl border border-border-default bg-surface-card text-sm font-semibold text-text-primary hover:text-brand-primary hover:border-brand-primary/30 transition-all duration-200 hover-spring active:scale-95 disabled:opacity-50">
            <svg wire:loading wire:target="regenerateBrief" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
            <svg wire:loading.remove wire:target="regenerateBrief" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
            <span wire:loading.remove wire:target="regenerateBrief">Regenerate Brief</span>
            <span wire:loading wire:target="regenerateBrief">Generating...</span>
        </button>
        @endif
    </div>

    {{-- ── Generating skeleton ─────────────────────────────────────────── --}}
    @if($generating)
        <div class="animate-pulse space-y-8">
            <div class="p-6 bg-brand-primary/5 rounded-3xl border border-brand-primary/20 h-24"></div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-surface-card rounded-3xl border border-border-default h-48"></div>
                    <div class="bg-surface-card rounded-3xl border border-border-default h-64"></div>
                </div>
                <div class="space-y-8">
                    <div class="bg-surface-card rounded-3xl border border-border-default h-48"></div>
                    <div class="bg-surface-card rounded-3xl border border-border-default h-48"></div>
                </div>
            </div>
            <p class="text-center text-sm text-text-tertiary pt-2">Generating your daily brief with AI...</p>
        </div>

    {{-- ── No brief for past date ──────────────────────────────────────── --}}
    @elseif(!$brief)
        <div class="text-center py-32">
            <div class="w-20 h-20 bg-surface-raised border border-border-default rounded-3xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-text-primary mb-2">No brief for this date</h3>
            <p class="text-sm text-text-secondary">Briefs are generated for today only. Navigate back to today to see your plan.</p>
            <button wire:click="$set('selectedDate', '{{ now()->toDateString() }}')" wire:then="loadBrief"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative mt-6 inline-flex items-center px-5 py-2.5 rounded-xl bg-brand-primary text-white text-sm font-bold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Back to Today</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>

    @else

    {{-- ── AI Executive Summary ────────────────────────────────────────── --}}
    @if($brief->ai_summary)
    <div class="mb-8 p-6 bg-brand-primary/5 rounded-3xl border border-brand-primary/20 relative overflow-hidden group">
        <div class="absolute -right-10 -top-10 w-44 h-44 bg-brand-primary/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex items-start space-x-4">
            <div class="mt-0.5 h-10 w-10 rounded-2xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-brand-primary mb-1.5">AI Daily Briefing</h2>
                <p class="text-sm font-medium text-text-primary leading-relaxed max-w-4xl">{{ $brief->ai_summary }}</p>
            </div>
        </div>
    </div>
    @elseif($brief->market_snapshot)
    <div class="mb-8 p-6 bg-brand-primary/5 rounded-3xl border border-brand-primary/20 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-44 h-44 bg-brand-primary/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex items-start space-x-4">
            <div class="mt-0.5 h-10 w-10 rounded-2xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-brand-primary mb-1.5">Morning Market Snapshot</h2>
                <p class="text-sm font-medium text-text-primary leading-relaxed max-w-4xl">{{ $brief->market_snapshot }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Main grid ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- ── Left column (2/3) ──────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Daily Goals --}}
            <div class="bg-surface-card rounded-3xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-text-primary">Daily Goals</h2>
                        @php
                            $goals = $brief->goals ?? [];
                            $doneGoals = collect($goals)->where('completed', true)->count();
                        @endphp
                        @if(count($goals))
                            <p class="text-xs text-text-secondary mt-0.5">{{ $doneGoals }} of {{ count($goals) }} complete</p>
                        @endif
                    </div>
                    @if(\Carbon\Carbon::parse($selectedDate)->isToday())
                    <button wire:click="$set('showGoalModal', true)"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-brand-primary border border-brand-primary/30 hover:bg-brand-primary/5 transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Goal</span></span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                </div>

                <div class="p-8 bg-surface-sunken/20">
                    @if(empty($goals))
                        <div class="text-center py-8">
                            <div class="w-14 h-14 bg-surface-raised border border-border-default rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="text-sm font-bold text-text-primary">No goals yet</p>
                            <p class="text-xs text-text-secondary mt-1">Regenerate your brief to get AI-suggested goals, or add your own.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($goals as $gi => $goal)
                            @php $pct = $goal['target'] > 0 ? min(100, round(($goal['current'] / $goal['target']) * 100)) : 0; @endphp
                            <div class="p-4 rounded-2xl border transition-all duration-200 {{ $goal['completed'] ? 'bg-success-500/5 border-success-500/20' : 'bg-surface-card border-border-default' }}">
                                <div class="flex items-center justify-between mb-2.5">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <button wire:click="toggleGoal({{ $gi }})"
                                            class="disabled:opacity-70 disabled:cursor-not-allowed relative h-6 w-6 rounded-lg border flex items-center justify-center transition-colors flex-shrink-0 {{ $goal['completed'] ? 'bg-success-500 border-success-500 text-white' : 'border-border-default hover:border-success-500 text-transparent hover:text-success-500' }}" wire:loading.attr="disabled" wire:target="toggleGoal">
                <span wire:loading.remove wire:target="toggleGoal"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                <span wire:loading wire:target="toggleGoal" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                                        <span class="text-sm font-bold text-text-primary truncate {{ $goal['completed'] ? 'line-through text-text-tertiary' : '' }}">{{ $goal['title'] }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2 flex-shrink-0 ml-3">
                                        <span class="text-xs font-semibold text-text-secondary">{{ $goal['current'] }}/{{ $goal['target'] }} {{ $goal['unit'] }}</span>
                                        @if(!$goal['completed'] && \Carbon\Carbon::parse($selectedDate)->isToday())
                                        <button wire:click="incrementGoal({{ $gi }})"
                                            class="disabled:opacity-70 disabled:cursor-not-allowed relative h-6 w-6 rounded-lg border border-brand-primary/30 flex items-center justify-center text-brand-primary hover:bg-brand-primary/10 transition-colors" wire:loading.attr="disabled" wire:target="incrementGoal">
                <span wire:loading.remove wire:target="incrementGoal"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></span>
                <span wire:loading wire:target="incrementGoal" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                                        @endif
                                        @if(\Carbon\Carbon::parse($selectedDate)->isToday())
                                        <button wire:click="removeGoal({{ $gi }})"
                                            class="disabled:opacity-70 disabled:cursor-not-allowed relative h-6 w-6 rounded-lg flex items-center justify-center text-text-tertiary hover:text-danger-500 hover:bg-danger-500/10 transition-colors" wire:loading.attr="disabled" wire:target="removeGoal">
                <span wire:loading.remove wire:target="removeGoal"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="removeGoal" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="h-1.5 bg-surface-raised rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $goal['completed'] ? 'bg-success-500' : 'bg-brand-primary' }}"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Add Goal inline form --}}
                    @if($showGoalModal)
                    <div class="mt-6 p-5 rounded-2xl border border-brand-primary/20 bg-brand-primary/5">
                        <h4 class="text-sm font-bold text-text-primary mb-4">Add New Goal</h4>
                        <div class="space-y-3">
                            <input wire:model="newGoalTitle" type="text"
                                placeholder="Goal title (e.g. Follow-up calls)"
                                class="w-full px-4 py-2.5 rounded-xl border border-border-default bg-surface-card text-sm text-text-primary placeholder-text-tertiary focus:outline-none focus:border-brand-primary/50">
                            <div class="flex space-x-3">
                                <input wire:model.number="newGoalTarget" type="number" min="1"
                                    placeholder="Target"
                                    class="w-24 px-4 py-2.5 rounded-xl border border-border-default bg-surface-card text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                                <select wire:model="newGoalUnit"
                                    class="flex-1 px-4 py-2.5 rounded-xl border border-border-default bg-surface-card text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                                    <option value="calls">calls</option>
                                    <option value="emails">emails</option>
                                    <option value="deals">deals</option>
                                    <option value="contacts">contacts</option>
                                    <option value="tasks">tasks</option>
                                    <option value="viewings">viewings</option>
                                    <option value="items">items</option>
                                </select>
                            </div>
                            <div class="flex space-x-3">
                                <button wire:click="addGoal"
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-2.5 rounded-xl bg-brand-primary text-white text-sm font-bold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="addGoal">
                <span wire:loading.remove wire:target="addGoal">Add Goal</span>
                <span wire:loading wire:target="addGoal" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                                <button wire:click="$set('showGoalModal', false)"
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-2.5 rounded-xl border border-border-default text-sm font-semibold text-text-secondary hover:bg-surface-raised transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Priority Actions --}}
            <div class="bg-surface-card rounded-3xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary">Priority Actions</h2>
                    @php
                        $allActions    = collect($brief->priority_actions ?? []);
                        $activeActions = $allActions->filter(fn($a) => !($a['snoozed'] ?? false));
                        $doneActions   = $activeActions->where('completed', true)->count();
                    @endphp
                    <span class="text-xs font-semibold text-text-secondary">{{ $doneActions }} / {{ $activeActions->count() }} complete</span>
                </div>

                <div class="p-8 space-y-4 bg-surface-sunken/20">
                    @forelse($brief->priority_actions ?? [] as $ai => $action)

                    {{-- Snoozed --}}
                    @if($action['snoozed'] ?? false)
                    <div class="flex items-center space-x-3 p-4 rounded-2xl border border-border-default/30 bg-surface-raised/20 opacity-40">
                        <svg class="h-4 w-4 text-text-tertiary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm italic text-text-tertiary line-through">{{ $action['title'] }}</span>
                        <span class="text-xs text-text-tertiary">— snoozed to tomorrow</span>
                    </div>

                    @else
                    @php
                        $isOverdue  = $action['is_overdue'] ?? false;
                        $isDone     = $action['completed'] ?? false;
                        $priority   = $action['priority'] ?? 'medium';
                        $cardClass  = $isDone    ? 'bg-surface-raised/40 border-border-default/40 opacity-60'
                                    : ($isOverdue ? 'bg-danger-500/5 border-danger-500/30 hover:border-danger-500/50'
                                    : 'bg-surface-card border-border-default hover:border-brand-primary/30 hover:shadow-md');
                        $badgeColors = [
                            'urgent' => 'bg-danger-500/10 text-danger-500 border-danger-500/20',
                            'high'   => 'bg-warning-500/10 text-warning-500 border-warning-500/20',
                            'medium' => 'bg-brand-primary/10 text-brand-primary border-brand-primary/20',
                            'low'    => 'bg-surface-raised text-text-tertiary border-border-default',
                        ];
                        $badgeColor = $badgeColors[$priority] ?? $badgeColors['medium'];
                    @endphp
                    <div class="p-5 rounded-2xl border transition-all duration-200 {{ $cardClass }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start space-x-4 flex-1 min-w-0">
                                {{-- Checkbox --}}
                                <button wire:click="completeAction({{ $ai }})" @if($isDone) disabled @endif
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative mt-0.5 h-6 w-6 rounded-lg border flex items-center justify-center transition-colors flex-shrink-0
                                        {{ $isDone ? 'bg-success-500 border-success-500 text-white' : 'border-border-default hover:border-brand-primary text-transparent hover:text-brand-primary' }}" wire:loading.attr="disabled" wire:target="completeAction">
                <span wire:loading.remove wire:target="completeAction"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                <span wire:loading wire:target="completeAction" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h3 class="text-base font-bold text-text-primary {{ $isDone ? 'line-through text-text-tertiary' : '' }}">{{ $action['title'] }}</h3>
                                        @if(!$isDone)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $badgeColor }}">{{ $priority }}</span>
                                            @if($isOverdue)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-danger-500/10 text-danger-500 border border-danger-500/20">Overdue</span>
                                            @endif
                                            @if($action['task_created'] ?? false)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-success-500/10 text-success-500 border border-success-500/20">Task Created</span>
                                            @endif
                                        @endif
                                    </div>
                                    <p class="text-sm text-text-secondary leading-relaxed">{{ $action['context'] }}</p>
                                </div>
                            </div>

                            @if(!$isDone)
                            <div class="text-xs font-bold text-text-tertiary bg-surface-raised px-3 py-1.5 rounded-lg border border-border-default flex-shrink-0 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($action['due_at'])->format('g:i A') }}
                            </div>
                            @endif
                        </div>

                        @if(!$isDone && \Carbon\Carbon::parse($selectedDate)->isToday())
                        <div class="mt-4 ml-10 flex flex-wrap gap-2">
                            @if(in_array($action['type'], ['email', 'call', 'follow_up']))
                            <button wire:click="draftWithCopilot({{ $ai }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-sm hover-spring active:scale-95" wire:loading.attr="disabled" wire:target="draftWithCopilot">
                <span wire:loading.remove wire:target="draftWithCopilot"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Draft with Copilot</span>
                <span wire:loading wire:target="draftWithCopilot" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            @endif

                            @if(!($action['task_created'] ?? false) && !isset($action['task_id']))
                            <button wire:click="createTaskFromAction({{ $ai }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl border border-border-default bg-surface-card text-text-secondary hover:border-brand-primary/30 hover:text-brand-primary transition-colors hover-spring active:scale-95" wire:loading.attr="disabled" wire:target="createTaskFromAction">
                <span wire:loading.remove wire:target="createTaskFromAction"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                Create Task</span>
                <span wire:loading wire:target="createTaskFromAction" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            @endif

                            <button wire:click="snoozeAction({{ $ai }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl border border-border-default bg-surface-card text-text-tertiary hover:text-text-secondary transition-colors hover-spring active:scale-95" wire:loading.attr="disabled" wire:target="snoozeAction">
                <span wire:loading.remove wire:target="snoozeAction"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Snooze</span>
                <span wire:loading wire:target="snoozeAction" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>
                        @endif
                    </div>
                    @endif

                    @empty
                    <div class="text-center py-10">
                        <div class="w-14 h-14 bg-success-500/10 border border-success-500/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-text-primary">All clear!</p>
                        <p class="text-xs text-text-secondary mt-1">No priority actions for today.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Deal Alerts --}}
            @if(!empty($brief->deal_alerts))
            <div class="bg-surface-card rounded-3xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40">
                    <h2 class="text-lg font-bold text-text-primary">Deal Alerts</h2>
                    <p class="text-xs text-text-secondary mt-0.5">{{ count($brief->deal_alerts) }} deal{{ count($brief->deal_alerts) !== 1 ? 's' : '' }} need attention</p>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-5 bg-surface-sunken/20">
                    @foreach($brief->deal_alerts as $di => $alert)
                    @php
                        $isCritical = ($alert['severity'] ?? '') === 'critical';
                        $isWarning  = ($alert['severity'] ?? '') === 'warning';
                    @endphp
                    <div class="p-5 rounded-2xl bg-surface-card border {{ $isCritical ? 'border-danger-500/30' : 'border-border-default' }} relative group hover-spring">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="h-2 w-2 rounded-full flex-shrink-0 {{ $isCritical ? 'bg-danger-500 animate-pulse' : ($isWarning ? 'bg-warning-500 animate-pulse' : 'bg-success-500') }}"></div>
                                <h4 class="text-sm font-bold {{ $isCritical ? 'text-danger-500' : 'text-text-primary' }}">{{ $alert['title'] }}</h4>
                            </div>
                            @if(\Carbon\Carbon::parse($selectedDate)->isToday())
                            <button wire:click="dismissAlert({{ $di }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative opacity-0 group-hover:opacity-100 p-1 rounded-lg text-text-tertiary hover:text-danger-500 hover:bg-danger-500/10 transition-all flex-shrink-0 ml-2" wire:loading.attr="disabled" wire:target="dismissAlert">
                <span wire:loading.remove wire:target="dismissAlert"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="dismissAlert" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            @endif
                        </div>
                        <p class="text-xs font-semibold text-text-secondary mb-1.5 truncate">{{ $alert['property'] }}</p>
                        @if(isset($alert['value']) && $alert['value'])
                        <p class="text-xs text-text-tertiary mb-2">Value: ${{ number_format($alert['value']) }}</p>
                        @endif
                        <p class="text-sm text-text-primary leading-relaxed opacity-80">{{ $alert['message'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- end left column --}}

        {{-- ── Right column (1/3) ─────────────────────────────────────── --}}
        <div class="space-y-8">

            {{-- Today's Viewings --}}
            <div class="bg-surface-card rounded-3xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary">Today's Viewings</h2>
                    <a href="{{ route('viewing.day') }}" class="text-sm font-bold text-brand-primary hover:text-brand-secondary transition-colors">
                        View all &rarr;
                    </a>
                </div>
                <div class="p-8">
                    @if(empty($brief->viewing_schedule))
                    <div class="text-center py-8">
                        <div class="w-14 h-14 bg-surface-raised border border-border-default rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-text-primary">No viewings today.</p>
                        <p class="text-xs text-text-secondary mt-1">Time to prospect for new leads.</p>
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach($brief->viewing_schedule as $viewing)
                        <div class="flex items-start space-x-4 p-4 rounded-2xl border border-border-default hover:border-brand-primary/20 hover:bg-surface-raised/20 transition-all">
                            <div class="text-sm font-black text-brand-primary flex-shrink-0 w-12 pt-0.5">{{ $viewing['time'] }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-text-primary truncate">{{ $viewing['client'] }}</p>
                                <p class="text-xs text-text-secondary truncate mt-0.5">{{ $viewing['property'] }}</p>
                                @if(isset($viewing['status']))
                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider
                                    {{ $viewing['status'] === 'completed' ? 'bg-success-500/10 text-success-500' : 'bg-surface-raised text-text-tertiary border border-border-default' }}">
                                    {{ $viewing['status'] }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- AI Coaching Tips --}}
            @if(!empty($brief->coaching_tips))
            <div class="bg-surface-card rounded-3xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40">
                    <h2 class="text-lg font-bold text-text-primary">Coaching Tips</h2>
                    <p class="text-xs text-text-secondary mt-0.5">Personalised by AI for your pipeline</p>
                </div>
                <div class="p-8 space-y-4">
                    @foreach($brief->coaching_tips as $tip)
                    <div class="p-4 rounded-2xl bg-surface-sunken/40 border border-border-default/50">
                        <div class="flex items-start space-x-3">
                            <span class="text-xl flex-shrink-0 mt-0.5">{{ $tip['icon'] ?? '💡' }}</span>
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-text-tertiary block mb-1">
                                    {{ ucfirst(str_replace('_', ' ', $tip['category'] ?? 'tip')) }}
                                </span>
                                <p class="text-sm text-text-primary leading-relaxed">{{ $tip['tip'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Market Snapshot (right column when ai_summary is main banner) --}}
            @if($brief->ai_summary && $brief->market_snapshot)
            <div class="bg-surface-card rounded-3xl border border-border-default overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/40">
                    <h2 class="text-lg font-bold text-text-primary">Market Snapshot</h2>
                </div>
                <div class="p-8">
                    <div class="flex items-start space-x-3">
                        <div class="h-8 w-8 rounded-xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary flex-shrink-0 mt-0.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <p class="text-sm text-text-primary leading-relaxed">{{ $brief->market_snapshot }}</p>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- end right column --}}
    </div>{{-- end grid --}}
    @endif
</div>
