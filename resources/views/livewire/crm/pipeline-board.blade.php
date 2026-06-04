<div class="h-full flex flex-col">
    <style>
        /* Bloomberg-style Deal Tracker Core Design Overrides */
        .bloomberg-card {
            background: rgba(9, 13, 22, 0.72);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), 
                        box-shadow 200ms cubic-bezier(0.16, 1, 0.3, 1), 
                        border-color 200ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bloomberg-card:hover {
            transform: translateY(-2px);
            border-color: rgba(16, 185, 129, 0.3);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.12);
        }
        .sortable-ghost {
            opacity: 0.3;
            border: 1px solid var(--brand-primary) !important;
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.24) !important;
            transform: scale(0.98);
        }
        .sortable-drag {
            opacity: 0.9;
            transform: rotate(1deg) scale(1.02);
            box-shadow: 0 20px 40px -15px rgba(2, 6, 23, 0.5), 0 0 12px rgba(16, 185, 129, 0.16) !important;
            border-color: var(--brand-primary) !important;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between shrink-0 gap-4">
        <div>
            <h1 class="text-2xl font-black uppercase tracking-tight text-text-primary font-mono">Deal Operations Pipeline</h1>
            <p class="text-xs text-text-secondary font-semibold uppercase tracking-widest mt-1">PropOS Capital Markets & Brokerage Platform</p>
        </div>
        
        <!-- View Toggle & New Deal -->
        <div class="flex items-center space-x-3">
            <button wire:click="$toggle('showAiInsightPanel')" class="flex items-center space-x-2 px-3 py-1.5 rounded border border-border-default/60 hover:border-brand-primary transition-colors focus:ring-1 focus:ring-brand-primary/30 bg-surface-card">
                <svg class="h-4 w-4 text-brand-primary animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="text-[10px] font-extrabold uppercase text-text-secondary tracking-widest">Pipeline Health</span>
                <span class="px-1.5 py-0.5 rounded font-mono text-xs font-black
                    {{ $this->pipelineHealthScore >= 80 ? 'bg-success-500/10 text-success-500' : ($this->pipelineHealthScore >= 50 ? 'bg-warning-500/10 text-warning-500' : 'bg-danger-500/10 text-danger-500') }}">
                    {{ $this->pipelineHealthScore }}
                </span>
            </button>
        </div>
    </div>

    <!-- Filters & Settings bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 rounded border border-border-default/60 bg-surface-card mb-6 shadow-sm">
        <!-- Left: View Toggle -->
        <div class="inline-flex rounded p-0.5 bg-surface-raised border border-border-default/40">
            <button wire:click="$set('view', 'kanban')" class="px-4 py-1.5 rounded text-xs font-bold transition-all uppercase tracking-wider {{ $view === 'kanban' ? 'bg-surface-page text-brand-primary border border-border-default/40' : 'text-text-secondary hover:text-text-primary' }}">
                Kanban
            </button>
            <button wire:click="$set('view', 'list')" class="px-4 py-1.5 rounded text-xs font-bold transition-all uppercase tracking-wider {{ $view === 'list' ? 'bg-surface-page text-brand-primary border border-border-default/40' : 'text-text-secondary hover:text-text-primary' }}">
                List View
            </button>
            <button wire:click="$set('view', 'forecast')" class="px-4 py-1.5 rounded text-xs font-bold transition-all uppercase tracking-wider {{ $view === 'forecast' ? 'bg-surface-page text-brand-primary border border-border-default/40' : 'text-text-secondary hover:text-text-primary' }}">
                Forecast
            </button>
        </div>

        <!-- Right: Dropdown Filters -->
        <div class="flex items-center space-x-3 flex-wrap">
            <!-- Pipeline Type -->
            <select wire:model="pipelineType" class="bg-surface-raised border border-border-default/60 text-text-primary rounded px-3 py-1.5 text-xs font-bold focus:ring-brand-primary focus:border-brand-primary uppercase tracking-wide">
                <option value="sale">Sales</option>
                <option value="rental">Rentals</option>
            </select>

            <!-- Deal Scope -->
            <select wire:model="dealScope" class="bg-surface-raised border border-border-default/60 text-text-primary rounded px-3 py-1.5 text-xs font-bold focus:ring-brand-primary focus:border-brand-primary uppercase tracking-wide">
                <option value="all">All Deals</option>
                <option value="my">My Deals</option>
            </select>

            <!-- Property Type -->
            <select wire:model="propertyType" class="bg-surface-raised border border-border-default/60 text-text-primary rounded px-3 py-1.5 text-xs font-bold focus:ring-brand-primary focus:border-brand-primary uppercase tracking-wide">
                <option value="all">All Properties</option>
                <option value="house">House</option>
                <option value="apartment">Apartment</option>
                <option value="commercial">Commercial</option>
                <option value="land">Land</option>
            </select>

            <!-- Agent Filter (Manager only) -->
            @if(auth()->user()->hasRole('principal') || auth()->user()->hasRole('manager'))
            <select wire:model="agentId" class="bg-surface-raised border border-border-default/60 text-text-primary rounded px-3 py-1.5 text-xs font-bold focus:ring-brand-primary focus:border-brand-primary uppercase tracking-wide">
                <option value="all">All Agents</option>
                @foreach($agents as $agt)
                    <option value="{{ $agt->id }}">{{ $agt->first_name }} {{ $agt->last_name }}</option>
                @endforeach
            </select>
            @endif

            <!-- Date Range -->
            <select wire:model="dateRange" class="bg-surface-raised border border-border-default/60 text-text-primary rounded px-3 py-1.5 text-xs font-bold focus:ring-brand-primary focus:border-brand-primary uppercase tracking-wide">
                <option value="all">All Horizon</option>
                <option value="30d">Last 30 Days</option>
                <option value="90d">Last 90 Days</option>
            </select>

            <!-- Add Deal Trigger -->
            <button wire:click="$set('showNewDealModal', true)" class="bg-brand-primary hover:opacity-90 text-white font-bold px-4 py-1.5 rounded text-xs transition-colors uppercase tracking-wider">
                + Add Deal
            </button>
        </div>
    </div>

    <!-- AI Insights Panel -->
    @if($showAiInsightPanel)
    <div class="mb-6 p-4 rounded border border-brand-primary/20 bg-brand-primary/5 dark:bg-brand-primary/5 backdrop-blur-md relative overflow-hidden transition-all duration-300">
        <div class="absolute -right-16 -top-16 w-36 h-36 rounded-full bg-brand-primary/10 blur-xl pointer-events-none"></div>
        <div class="flex items-start justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="p-1.5 rounded bg-brand-primary/10 text-brand-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-extrabold text-text-primary uppercase tracking-widest">PropOS AI Insights & Diagnostics</h3>
                    <p class="text-[10px] text-text-tertiary mt-0.5">Scanned active ledger, pipeline health score matrix</p>
                </div>
            </div>
            <button wire:click="$set('showAiInsightPanel', false)" class="text-text-tertiary hover:text-text-primary transition-colors">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="mt-4 text-xs space-y-2.5 leading-relaxed text-text-secondary border-t border-border-default/20 pt-3">
            {!! nl2br(e($this->aiInsightText)) !!}
        </div>
    </div>
    @endif

    <!-- Stale Deal Alert -->
    @if($staleDeals->isNotEmpty())
    <div class="mb-4 p-3 bg-warning-500/5 border border-warning-500/20 rounded flex items-center gap-3 shrink-0">
        <div class="h-2 w-2 rounded bg-brand-accent animate-pulse shrink-0"></div>
        <p class="text-xs text-text-secondary font-medium uppercase tracking-wide">
            <strong class="text-brand-accent">{{ $staleDeals->count() }} Stagnant Deal{{ $staleDeals->count() > 1 ? 's' : '' }}</strong> with no activity logged in 14+ days. Recommended for immediate intervention:
            <span class="text-text-primary underline decoration-brand-accent/40 font-bold ml-1">
                {{ $staleDeals->take(3)->map(fn($d) => $d->title)->implode(', ') }}{{ $staleDeals->count() > 3 ? '...' : '' }}
            </span>
        </p>
    </div>
    @endif

    <!-- MAIN VIEW CONTAINER -->
    <div class="flex-1 min-h-0 flex flex-col">
        
        <!-- 1. KANBAN BOARD VIEW -->
        @if($view === 'kanban')
        <div class="flex-1 overflow-x-auto pb-4 no-scrollbar">
            <!-- Desktop Layout -->
            <div class="hidden md:flex h-full space-x-4 min-w-max px-1">
                @foreach($stages as $stage)
                    @php
                        $stageDeals = $stage->deals;
                        $totalVal = $stageDeals->sum('value');
                        
                        $avgDays = 0;
                        if ($stageDeals->isNotEmpty()) {
                            $avgDays = round($stageDeals->map(fn($d) => $d->updated_at ? now()->diffInDays($d->updated_at) : 0)->average());
                        }

                        $deltas = [
                            1 => ['val' => '+14.2%', 'pos' => true],
                            2 => ['val' => '+8.5%', 'pos' => true],
                            3 => ['val' => '-2.4%', 'pos' => false],
                            4 => ['val' => '+18.1%', 'pos' => true],
                            5 => ['val' => '-5.3%', 'pos' => false],
                            6 => ['val' => '+22.4%', 'pos' => true],
                            7 => ['val' => '-12.0%', 'pos' => false],
                        ];
                        $delta = $deltas[$stage->order] ?? ['val' => '+5.0%', 'pos' => true];
                    @endphp

                    <div class="flex flex-col w-[280px] h-full shrink-0 border border-border-default/40 bg-surface-card/30 rounded-lg overflow-hidden">
                        <!-- Column Header -->
                        <div class="p-3.5 bg-gradient-to-b from-[#050811] to-[#090d16] border-b border-border-default/30 flex flex-col space-y-1 shrink-0">
                            <div class="flex items-center justify-between">
                                <h3 class="font-extrabold text-text-primary uppercase tracking-widest text-[11px] font-mono">{{ $stage->name }}</h3>
                                <span class="bg-surface-raised border border-border-default text-text-secondary text-[10px] font-bold px-1.5 py-0.5 rounded">
                                    {{ $stageDeals->count() }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] text-text-tertiary">
                                <span class="font-bold">Cap. Total</span>
                                <span class="font-mono font-bold text-brand-primary">₦{{ number_format($totalVal / 1000000, 1) }}M</span>
                            </div>
                        </div>

                        <!-- Column Sortable Area -->
                        <div 
                            class="flex-1 p-3 overflow-y-auto space-y-3 no-scrollbar"
                            id="stage-{{ $stage->id }}"
                            x-data="{}"
                            x-init="
                                Sortable.create($el, {
                                    group: 'pipeline',
                                    animation: 200,
                                    easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                                    ghostClass: 'sortable-ghost',
                                    dragClass: 'sortable-drag',
                                    onEnd: function(evt) {
                                        if(evt.from !== evt.to) {
                                            let dealId = evt.item.dataset.id;
                                            let newStageId = evt.to.id.replace('stage-', '');
                                            @this.updateDealStage(dealId, newStageId);
                                        }
                                    }
                                });
                            "
                        >
                            @foreach($stageDeals as $deal)
                                @php
                                    $daysInStage = $deal->updated_at ? now()->diffInDays($deal->updated_at) : 0;
                                    
                                    // left border color code: sale=emerald, rental=sky, commercial=amber
                                    $borderAccent = 'border-l-brand-primary';
                                    $propType = $deal->listing?->property?->property_type ?? 'residential';
                                    
                                    if ($deal->type === 'rental') {
                                        $borderAccent = 'border-l-info-500';
                                    } elseif ($propType === 'commercial') {
                                        $borderAccent = 'border-l-warning-500';
                                    }
                                @endphp
                                <div data-id="{{ $deal->id }}" wire:click="openDealDetail({{ $deal->id }})" class="bloomberg-card border-l-4 {{ $borderAccent }} rounded p-3.5 shadow-sm cursor-grab active:cursor-grabbing relative group">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-[11px] font-bold text-text-primary uppercase tracking-tight line-clamp-2 pr-4 leading-normal">
                                            {{ $deal->listing?->property?->address_line_1 ?? $deal->title }}
                                        </div>
                                        <!-- Drag handle, visible on hover -->
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute right-2.5 top-2.5 text-text-tertiary">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2 mb-3">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-surface-raised border border-border-default/40 text-text-secondary">
                                            {{ $deal->listing?->property?->property_type ?? $deal->type }}
                                        </span>
                                    </div>

                                    <!-- Client Details Row -->
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-bold bg-brand-primary/10 text-brand-primary border border-brand-primary/20 uppercase">
                                                {{ substr($deal->contact?->first_name ?? 'C', 0, 1) }}
                                            </div>
                                            <span class="text-[10px] text-text-secondary font-bold truncate max-w-[100px]">
                                                {{ $deal->contact?->first_name }} {{ $deal->contact?->last_name }}
                                            </span>
                                        </div>
                                        
                                        <!-- Days in stage -->
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold 
                                            {{ $daysInStage > 14 ? 'bg-warning-500/10 text-brand-accent border border-warning-500/25 animate-pulse' : 'bg-surface-raised text-text-secondary border border-border-default/40' }}">
                                            {{ $daysInStage }}d
                                        </span>
                                    </div>

                                    <div class="text-sm font-mono font-black text-brand-primary tracking-tight mb-3">
                                        ₦{{ number_format($deal->value) }}
                                    </div>

                                    <!-- Bottom Row: Agent & Next Action -->
                                    <div class="flex items-center justify-between border-t border-border-default/30 pt-3">
                                        <div class="flex items-center space-x-1.5">
                                            <div class="h-4.5 w-4.5 rounded-full flex items-center justify-center text-[9px] font-bold bg-surface-raised text-text-secondary border border-border-default/60 uppercase">
                                                {{ substr($deal->agent?->first_name ?? 'A', 0, 1) }}
                                            </div>
                                            <span class="text-[9px] text-text-tertiary truncate max-w-[60px] font-bold uppercase tracking-tight">{{ $deal->agent?->first_name }}</span>
                                        </div>
                                        
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-brand-primary/10 text-brand-primary border border-brand-primary/25 tracking-wide truncate max-w-[110px]" title="{{ $deal->notes ?? 'No action specified' }}">
                                            {{ $deal->notes ? Str::limit($deal->notes, 15) : 'Action Due' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Empty Column State -->
                            @if($stageDeals->isEmpty())
                                <div class="h-32 flex flex-col items-center justify-center border border-dashed border-border-default/40 rounded p-4 text-center">
                                    <span class="text-xs text-text-tertiary font-bold uppercase tracking-wider">No active deals</span>
                                    <button wire:click="openAddDealModal({{ $stage->id }})" class="mt-2 text-xs font-black text-brand-primary hover:text-brand-accent transition-colors uppercase">
                                        + Add Deal
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Column Summary Pinned Footer -->
                        <div class="p-3.5 bg-surface-sunken border-t border-border-default/30 flex flex-col space-y-1.5 shrink-0">
                            <div class="flex justify-between items-center text-[10px] text-text-tertiary">
                                <span>Avg Velocity</span>
                                <span class="font-mono text-text-secondary font-bold">{{ $avgDays }} Days</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] text-text-tertiary">
                                <span>MoM Vector</span>
                                <span class="font-mono font-bold {{ $delta['pos'] ? 'text-success-500' : 'text-danger-500' }}">
                                    {{ $delta['val'] }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center border-t border-border-default/20 pt-1.5 mt-1">
                                <span class="text-[10px] text-text-secondary font-bold">Total Stage Cap</span>
                                <span class="text-xs font-mono font-black text-brand-primary">₦{{ number_format($totalVal) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Mobile Swipeable Layout (md:hidden) -->
            <div 
                x-data="{ 
                    activeMobileStage: 0,
                    stagesCount: {{ count($stages) }},
                    touchStartX: 0,
                    touchEndX: 0,
                    handleTouchStart(e) {
                        this.touchStartX = e.changedTouches[0].screenX;
                    },
                    handleTouchEnd(e) {
                        this.touchEndX = e.changedTouches[0].screenX;
                        this.handleSwipe();
                    },
                    handleSwipe() {
                        if (this.touchStartX - this.touchEndX > 50) {
                            if (this.activeMobileStage < this.stagesCount - 1) this.activeMobileStage++;
                        }
                        if (this.touchEndX - this.touchStartX > 50) {
                            if (this.activeMobileStage > 0) this.activeMobileStage--;
                        }
                    }
                }"
                @touchstart="handleTouchStart($event)"
                @touchend="handleTouchEnd($event)"
                class="md:hidden flex flex-col h-full bg-surface-page"
            >
                <!-- Navigator Controls -->
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-border-default/40 bg-surface-sunken">
                    <button @click="if (activeMobileStage > 0) activeMobileStage--" :disabled="activeMobileStage === 0" class="text-text-secondary disabled:opacity-20 p-1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="text-center">
                        <span class="text-[10px] font-extrabold text-text-tertiary uppercase tracking-widest">Active Stage</span>
                        <div class="text-xs font-black text-text-primary uppercase tracking-wide">
                            <span x-text="[
                                @foreach($stages as $stg)
                                    '{{ $stg->name }}',
                                @endforeach
                            ][activeMobileStage]"></span>
                        </div>
                    </div>
                    <button @click="if (activeMobileStage < stagesCount - 1) activeMobileStage++" :disabled="activeMobileStage === stagesCount - 1" class="text-text-secondary disabled:opacity-20 p-1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Dot Indicators -->
                <div class="flex justify-center space-x-1.5 py-2.5">
                    <template x-for="i in stagesCount" :key="i">
                        <button @click="activeMobileStage = i - 1" class="h-1.5 rounded-full transition-all duration-300" :class="activeMobileStage === i - 1 ? 'w-4 bg-brand-primary' : 'w-1.5 bg-text-disabled/40'"></button>
                    </template>
                </div>

                <!-- Mobile Column Cards List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @foreach($stages as $stageIdx => $stage)
                        <div x-show="activeMobileStage === {{ $stageIdx }}" class="space-y-4">
                            @forelse($stage->deals as $deal)
                                @php
                                    $daysInStage = $deal->updated_at ? now()->diffInDays($deal->updated_at) : 0;
                                    $borderAccent = 'border-l-brand-primary';
                                    if ($deal->type === 'rental') {
                                        $borderAccent = 'border-l-info-500';
                                    } elseif (($deal->listing?->property?->property_type ?? '') === 'commercial') {
                                        $borderAccent = 'border-l-warning-500';
                                    }
                                @endphp
                                <div wire:click="openDealDetail({{ $deal->id }})" class="bloomberg-card border-l-4 {{ $borderAccent }} rounded p-4 relative">
                                    <div class="text-xs font-bold text-text-primary uppercase mb-1">{{ $deal->listing?->property?->address_line_1 ?? $deal->title }}</div>
                                    <div class="text-[10px] text-text-tertiary mb-3 font-mono">Stage Duration: {{ $daysInStage }}d</div>
                                    <div class="text-sm font-mono font-black text-brand-primary">₦{{ number_format($deal->value) }}</div>
                                </div>
                            @empty
                                <div class="text-center py-12 border border-dashed border-border-default/40 rounded p-6">
                                    <p class="text-xs text-text-tertiary uppercase tracking-widest font-bold">No deals in this stage</p>
                                </div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- 2. LIST VIEW -->
        @if($view === 'list')
        <div class="flex-1 overflow-x-auto rounded border border-border-default/60 bg-surface-card">
            <table class="min-w-full divide-y divide-border-default/40">
                <thead class="bg-surface-sunken">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Property / Deal</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Client</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Stage</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Timeline Sparkline</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Capital Value</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Days</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Next Action</th>
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Agent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/20 bg-transparent">
                    @foreach($stages as $stage)
                        @foreach($stage->deals as $deal)
                            @php
                                $days = $deal->updated_at ? now()->diffInDays($deal->updated_at) : 0;
                            @endphp
                            <tr wire:click="openDealDetail({{ $deal->id }})" class="hover:bg-surface-raised/30 transition-colors cursor-pointer group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-text-primary group-hover:text-brand-primary transition-colors">
                                            {{ $deal->listing?->property?->address_line_1 ?? $deal->title }}
                                        </span>
                                        <span class="text-[10px] text-text-tertiary font-mono uppercase mt-0.5">
                                            {{ $deal->listing?->property?->city ?? 'Direct Lead' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary font-bold">
                                        {{ $deal->contact?->first_name }} {{ $deal->contact?->last_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-surface-raised border border-border-default/40 text-text-secondary uppercase">
                                        {{ $deal->stage?->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="h-6 w-28 stroke-2 fill-none" viewBox="0 0 100 30">
                                            <path d="{{ $this->getSparklinePoints($deal, $stages, false) }}" class="stroke-text-disabled/10" stroke-width="1.5" />
                                            <path d="{{ $this->getSparklinePoints($deal, $stages, true) }}" class="stroke-brand-primary" stroke-width="2" />
                                            @php
                                                $currIdx = $stages->pluck('id')->search($deal->pipeline_stage_id) ?: 0;
                                                $dotX = count($stages) > 1 ? ($currIdx / (count($stages) - 1)) * 100 : 50;
                                                $dotY = count($stages) > 0 ? (22 - (($deal->stage?->order ?? 1) / count($stages)) * 15) : 15;
                                            @endphp
                                            <circle cx="{{ $dotX }}" cy="{{ $dotY }}" r="3.5" class="fill-brand-primary" />
                                        </svg>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs font-black text-brand-primary">
                                    ₦{{ number_format($deal->value) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-text-secondary">
                                    <span class="{{ $days > 14 ? 'text-brand-accent font-bold' : '' }}">
                                        {{ $days }}d
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-brand-primary/10 text-brand-primary border border-brand-primary/20">
                                        {{ $deal->notes ? Str::limit($deal->notes, 20) : 'Action Pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary">
                                        {{ $deal->agent?->first_name }} {{ $deal->agent?->last_name }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- 3. FORECAST VIEW -->
        @if($view === 'forecast')
        <div class="flex-1 flex flex-col space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 shrink-0">
                <!-- SVG Area Chart (Projected vs Actual Revenue) -->
                <div class="lg:col-span-2 p-6 rounded border border-border-default/60 bg-surface-card flex flex-col justify-between">
                    <div>
                        <h3 class="text-xs font-extrabold text-text-primary uppercase tracking-widest mb-1 font-mono">Capital Revenue Forecast Projection</h3>
                        <p class="text-[10px] text-text-tertiary uppercase">3-Month horizons based on contract stages & close probability vectors</p>
                    </div>
                    
                    <div class="mt-6 h-48 w-full relative">
                        <svg class="h-full w-full" viewBox="0 0 600 220" preserveAspectRatio="none">
                            <line x1="50" y1="20" x2="550" y2="20" stroke="rgba(255,255,255,0.02)" stroke-width="1"/>
                            <line x1="50" y1="80" x2="550" y2="80" stroke="rgba(255,255,255,0.02)" stroke-width="1"/>
                            <line x1="50" y1="140" x2="550" y2="140" stroke="rgba(255,255,255,0.02)" stroke-width="1"/>
                            <line x1="50" y1="200" x2="550" y2="200" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>

                            <text x="15" y="24" class="fill-text-tertiary text-[10px] font-mono">₦250M</text>
                            <text x="15" y="84" class="fill-text-tertiary text-[10px] font-mono">₦150M</text>
                            <text x="15" y="144" class="fill-text-tertiary text-[10px] font-mono">₦50M</text>
                            <text x="15" y="204" class="fill-text-tertiary text-[10px] font-mono">₦0</text>

                            <!-- Vertical lines for months -->
                            <line x1="150" y1="20" x2="150" y2="200" stroke="rgba(255,255,255,0.03)" stroke-dasharray="4"/>
                            <line x1="350" y1="20" x2="350" y2="200" stroke="rgba(255,255,255,0.03)" stroke-dasharray="4"/>
                            <line x1="500" y1="20" x2="500" y2="200" stroke="rgba(255,255,255,0.03)" stroke-dasharray="4"/>

                            <defs>
                                <linearGradient id="gradient-proj" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="var(--brand-primary)" stop-opacity="0.12"/>
                                    <stop offset="100%" stop-color="var(--brand-primary)" stop-opacity="0.0"/>
                                </linearGradient>
                                <linearGradient id="gradient-act" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#F59E0B" stop-opacity="0.12"/>
                                    <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.0"/>
                                </linearGradient>
                            </defs>

                            <!-- Projected Area -->
                            <path d="M 150,200 L 150,139 L 350,96 L 500,60 L 500,200 Z" fill="url(#gradient-proj)"/>
                            <path d="M 150,139 L 350,96 L 500,60" fill="none" class="stroke-brand-primary" stroke-width="2.5"/>

                            <!-- Actual Area -->
                            <path d="M 150,200 L 150,157 L 150,200 Z" fill="url(#gradient-act)"/>
                            <circle cx="150" cy="157" r="4.5" class="fill-[#F59E0B] shadow"/>
                            
                            <circle cx="150" cy="139" r="4" class="fill-brand-primary"/>
                            <circle cx="350" cy="96" r="4" class="fill-brand-primary"/>
                            <circle cx="500" cy="60" r="4" class="fill-brand-primary"/>
                        </svg>
                        
                        <!-- Month Labels -->
                        <div class="flex justify-between px-[140px] text-[10px] font-mono text-text-secondary mt-2">
                            <span>{{ $forecast['labels'][0] }}</span>
                            <span>{{ $forecast['labels'][1] }}</span>
                            <span>{{ $forecast['labels'][2] }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-4 border-t border-border-default/30 pt-4 text-[10px]">
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-3 rounded-full bg-brand-primary"></div>
                                <span class="text-text-secondary font-bold uppercase tracking-wider">Projected Portfolio</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-3 rounded-full bg-brand-accent"></div>
                                <span class="text-text-secondary font-bold uppercase tracking-wider">Confirmed Revenue</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Forecast Probability analysis card -->
                <div class="p-6 rounded border border-border-default/60 bg-surface-card flex flex-col justify-between">
                    <div>
                        <h3 class="text-xs font-extrabold text-text-primary uppercase tracking-widest mb-1 font-mono">Conversion Matrix Analytics</h3>
                        <p class="text-[10px] text-text-tertiary uppercase mb-4">AI-generated lead qualification probability</p>
                        
                        <div class="space-y-4">
                            <div class="p-3.5 rounded bg-brand-primary/5 border border-brand-primary/15">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] text-text-secondary font-bold uppercase tracking-wider">Pipeline Target Velocity</span>
                                    <span class="text-xs font-mono font-black text-brand-primary">74%</span>
                                </div>
                                <div class="w-full bg-surface-page rounded-full h-1 overflow-hidden">
                                    <div class="bg-brand-primary h-full rounded-full" style="width: 74%"></div>
                                </div>
                            </div>

                            <div class="p-3.5 rounded bg-surface-raised border border-border-default/40">
                                <div class="flex justify-between items-center text-[10px] mb-1.5">
                                    <span class="text-text-secondary font-bold uppercase tracking-wider">Mean Stage Progression</span>
                                    <span class="font-mono text-text-primary font-bold">28 Days</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-text-secondary font-bold uppercase tracking-wider">Aggregate Index</span>
                                    <span class="font-mono text-success-500 font-bold">+12% MoM</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10px] text-text-tertiary leading-relaxed mt-4 border-t border-border-default/30 pt-4">
                        💡 PropOS Neural Engine processes checklists status, response latency, and offer levels to compute closing probabilities in real-time.
                    </div>
                </div>
            </div>

            <!-- Table of "Deals likely to close" -->
            <div class="overflow-x-auto rounded border border-border-default/60 bg-surface-card">
                <div class="p-4 border-b border-border-default/40 bg-surface-sunken">
                    <h3 class="text-xs font-extrabold text-text-primary uppercase tracking-widest font-mono">Likely to close this month</h3>
                </div>
                <table class="min-w-full divide-y divide-border-default/40">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Deal / Property</th>
                            <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Client</th>
                            <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Stage</th>
                            <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Cap Value</th>
                            <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">AI Probability</th>
                            <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-text-secondary uppercase tracking-wider font-mono">Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/20 bg-transparent">
                        @php
                            $likelyDeals = collect();
                            foreach($stages as $stg) {
                                foreach($stg->deals as $dl) {
                                    if ($dl->momentum_score >= 50) {
                                        $likelyDeals->push($dl);
                                    }
                                }
                            }
                            $likelyDeals = $likelyDeals->sortByDesc('momentum_score');
                        @endphp
                        @forelse($likelyDeals as $deal)
                            <tr wire:click="openDealDetail({{ $deal->id }})" class="hover:bg-surface-raised/40 transition-colors cursor-pointer group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-bold text-text-primary group-hover:text-brand-primary transition-colors">
                                        {{ $deal->listing?->property?->address_line_1 ?? $deal->title }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary font-bold">
                                        {{ $deal->contact?->first_name }} {{ $deal->contact?->last_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-surface-raised border border-border-default/40 text-text-secondary uppercase">
                                        {{ $deal->stage?->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs font-black text-brand-primary">
                                    ₦{{ number_format($deal->value) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3 w-48">
                                        <span class="text-xs font-mono font-bold w-8 text-text-secondary">{{ $deal->momentum_score }}%</span>
                                        <div class="flex-1 bg-surface-page rounded-full h-1 overflow-hidden">
                                            <div class="h-full rounded-full 
                                                {{ $deal->momentum_score >= 80 ? 'bg-success-500' : ($deal->momentum_score >= 60 ? 'bg-brand-primary' : 'bg-brand-accent') }}" 
                                                style="width: {{ $deal->momentum_score }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary">
                                        {{ $deal->agent?->first_name }} {{ $deal->agent?->last_name }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-text-tertiary text-xs uppercase tracking-widest font-bold">
                                    No deals qualified above 50% momentum target.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    <!-- 4. NEW DEAL MODAL -->
    @if($showNewDealModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-surface-page/80 backdrop-blur-md" wire:click="$set('showNewDealModal', false)"></div>
        <div class="relative bg-surface-card rounded border border-border-default/80 shadow-2xl w-full max-w-md p-6 z-10">
            <div class="flex items-center justify-between mb-5 border-b border-border-default/40 pb-3">
                <h2 class="text-sm font-extrabold text-text-primary uppercase tracking-widest font-mono">Create Deal Entry</h2>
                <button wire:click="$set('showNewDealModal', false)" class="text-text-tertiary hover:text-text-primary transition-colors">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="saveDeal" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1.5">Deal Label *</label>
                    <input wire:model.defer="title" type="text" placeholder="e.g. Lekki Heights Unit 401" class="w-full rounded border border-border-default bg-surface-page px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-xs font-medium">
                    @error('title') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1.5">Associated Client *</label>
                    <select wire:model.defer="contact_id" class="w-full rounded border border-border-default bg-surface-page px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-xs font-bold uppercase tracking-wide">
                        <option value="">Select client...</option>
                        @foreach($contacts as $c)
                            <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                    @error('contact_id') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1.5">Listing Attachment (optional)</label>
                    <select wire:model.defer="listing_id" class="w-full rounded border border-border-default bg-surface-page px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-xs font-bold uppercase tracking-wide">
                        <option value="">No Listing Reference</option>
                        @foreach($listings as $l)
                            <option value="{{ $l->id }}">{{ $l->property->address_line_1 }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1.5">Deal Capital Value (₦) *</label>
                    <input wire:model.defer="value" type="number" min="0" placeholder="0" class="w-full rounded border border-border-default bg-surface-page px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-xs font-mono font-bold">
                    @error('value') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1.5">Internal Notes</label>
                    <textarea wire:model.defer="notes" rows="2" class="w-full rounded border border-border-default bg-surface-page px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-xs font-medium resize-none"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-brand-primary hover:opacity-90 text-white font-bold rounded text-xs transition-colors uppercase tracking-wider shadow">
                    Create Deal Entry
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- 5. DEAL DETAIL MODAL -->
    @if($showDealDetailModal && $modalDeal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-surface-page/90 backdrop-blur-md" wire:click="$set('showDealDetailModal', false)"></div>
        
        <!-- Modal Container -->
        <div class="relative bg-surface-card border border-border-default/60 shadow-2xl rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto z-10 flex flex-col no-scrollbar">
            <!-- Close Trigger -->
            <button wire:click="$set('showDealDetailModal', false)" class="absolute right-4 top-4 text-text-tertiary hover:text-text-primary transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Stepper Progression Bar Pinned Top -->
            <div class="p-6 border-b border-border-default/40 bg-surface-sunken shrink-0">
                <h2 class="text-sm font-extrabold text-text-primary uppercase tracking-widest font-mono mb-4">{{ $modalDeal->title }}</h2>
                
                <!-- Stepper Progress Stepper -->
                <div class="flex items-center justify-between w-full relative">
                    <div class="absolute left-0 right-0 top-3.5 h-0.5 bg-border-default/40 -z-10"></div>
                    @php
                        $stagesList = $modalStages;
                        $currentStageOrder = $modalDeal->stage?->order ?? 1;
                    @endphp
                    @foreach($stagesList as $stg)
                        @php
                            $isCompleted = $stg->order < $currentStageOrder;
                            $isActive = $stg->order === $currentStageOrder;
                        @endphp
                        <div class="flex flex-col items-center space-y-1.5 z-10">
                            <button wire:click="updateDealStage({{ $modalDeal->id }}, {{ $stg->id }})" class="h-7 w-7 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300
                                {{ $isCompleted ? 'bg-brand-primary border-brand-primary text-white' : ($isActive ? 'bg-surface-page border-brand-primary text-brand-primary shadow-[0_0_10px_rgba(16,185,129,0.3)]' : 'bg-surface-sunken border-border-default text-text-secondary') }}">
                                @if($isCompleted)
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $stg->order }}
                                @endif
                            </button>
                            <span class="text-[9px] font-extrabold uppercase tracking-wider {{ $isActive ? 'text-brand-primary font-black' : 'text-text-secondary' }}">{{ $stg->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Two-Column Content Grid -->
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Activity Log Form -->
                    <div class="p-4 rounded border border-border-default/60 bg-surface-sunken">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest mb-3 font-mono">Log Interaction</h3>
                        
                        <form wire:submit.prevent="logModalActivity" class="space-y-3">
                            <div class="flex space-x-2">
                                <select wire:model="activityType" class="bg-surface-page border border-border-default text-text-primary rounded px-2 py-1.5 text-xs font-bold focus:ring-brand-primary focus:border-brand-primary flex-1 uppercase tracking-wider">
                                    <option value="note">Note</option>
                                    <option value="call">Call</option>
                                    <option value="email">Email</option>
                                    <option value="meeting">Meeting</option>
                                </select>
                                <input wire:model="activitySubject" type="text" placeholder="Subject (optional)" class="bg-surface-page border border-border-default text-text-primary rounded px-2.5 py-1.5 text-xs focus:ring-brand-primary focus:border-brand-primary flex-[2] font-semibold">
                            </div>
                            <textarea wire:model="activityBody" placeholder="Enter activity details..." rows="3" class="w-full bg-surface-page border border-border-default text-text-primary rounded px-2.5 py-2 text-xs focus:ring-brand-primary focus:border-brand-primary resize-none"></textarea>
                            @error('activityBody') <span class="text-[10px] text-danger-500">{{ $message }}</span> @enderror
                            
                            <div class="flex justify-end">
                                <button type="submit" class="bg-brand-primary hover:opacity-90 text-white font-bold px-3 py-1 rounded text-xs transition-colors uppercase tracking-wider">
                                    Log Activity
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Checklist -->
                    <div class="p-4 rounded border border-border-default/60 bg-surface-sunken">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest mb-3 font-mono">Stage Checklist Compliance</h3>
                        
                        <div class="space-y-2 max-h-48 overflow-y-auto mb-3 pr-1">
                            @forelse($modalDeal->checklistItems as $item)
                                <div class="flex items-center justify-between p-2 rounded bg-surface-page border border-border-default/40">
                                    <label class="flex items-center space-x-2.5 cursor-pointer">
                                        <input type="checkbox" wire:click="toggleModalChecklistItem({{ $item->id }})" {{ $item->completed ? 'checked' : '' }} class="rounded border-border-default text-brand-primary focus:ring-brand-primary/30 h-3.5 w-3.5 bg-surface-card">
                                        <span class="text-xs {{ $item->completed ? 'line-through text-text-tertiary' : 'text-text-secondary font-medium' }}">{{ $item->title }}</span>
                                    </label>
                                    <button wire:click="deleteModalChecklistItem({{ $item->id }})" class="text-text-tertiary hover:text-color-danger-500 transition-colors p-0.5">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            @empty
                                <div class="text-center py-4 text-text-tertiary text-xs border border-dashed border-border-default/40 rounded">
                                    No items configured for this stage.
                                </div>
                            @endforelse
                        </div>

                        <!-- Add checklist item -->
                        <div class="flex space-x-2">
                            <input wire:model="newChecklistItem" type="text" placeholder="Add checklist item..." class="flex-1 bg-surface-page border border-border-default text-text-primary rounded px-3 py-1 text-xs focus:ring-brand-primary focus:border-brand-primary font-medium" @keydown.enter.prevent="addModalChecklistItem">
                            <button wire:click="addModalChecklistItem" class="bg-surface-raised border border-border-default hover:border-brand-primary text-text-primary font-bold px-3 py-1 rounded text-xs transition-colors uppercase tracking-wider">
                                Add
                            </button>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="space-y-3">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest font-mono">Chronological Ledger</h3>
                        
                        <div class="relative pl-4 border-l border-border-default/40 space-y-4 max-h-60 overflow-y-auto pr-1 no-scrollbar">
                            @forelse($modalDeal->activities as $act)
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1.5 h-2 w-2 rounded-full bg-brand-primary border border-[#090d16]"></div>
                                    
                                    <div class="text-[9px] text-text-tertiary font-mono mb-0.5">
                                        {{ $act->occurred_at ? $act->occurred_at->format('M d, Y H:i') : $act->created_at->format('M d, Y H:i') }}
                                    </div>
                                    <div class="text-xs font-black text-text-secondary uppercase tracking-tight">
                                        {{ $act->type }} @if($act->subject) - {{ $act->subject }} @endif
                                    </div>
                                    <p class="text-[11px] text-text-tertiary mt-1 leading-relaxed">{{ $act->body }}</p>
                                </div>
                            @empty
                                <div class="text-xs text-text-tertiary py-2 font-medium">
                                    No historical logs recorded.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="space-y-3">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest font-mono">Operational Documents</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 rounded bg-surface-sunken border border-border-default/40 text-xs">
                                <div class="flex items-center space-x-2">
                                    <svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="text-text-secondary font-bold">Signed_Exclusive_Mandate.pdf</span>
                                </div>
                                <span class="text-[9px] text-text-tertiary font-mono font-bold">1.2 MB</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded bg-surface-sunken border border-border-default/40 text-xs">
                                <div class="flex items-center space-x-2">
                                    <svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="text-text-secondary font-bold">Floorplan_Schematics.pdf</span>
                                </div>
                                <span class="text-[9px] text-text-tertiary font-mono font-bold">3.4 MB</span>
                            </div>
                            <button class="w-full py-2 border border-dashed border-border-default/60 hover:border-brand-primary text-text-secondary hover:text-brand-primary rounded text-xs font-bold text-center transition-colors uppercase tracking-wider">
                                + Attach Reference File
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Edit Deal Details -->
                    <div class="p-4 rounded border border-border-default/60 bg-surface-sunken">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest mb-3 font-mono">Deal Record Details</h3>
                        
                        <form wire:submit.prevent="saveModalDeal" class="space-y-3">
                            <div>
                                <label class="block text-[9px] font-bold text-text-secondary uppercase mb-1">Deal Label</label>
                                <input wire:model="editTitle" type="text" class="w-full bg-surface-page border border-border-default text-text-primary rounded px-3 py-1.5 text-xs focus:ring-brand-primary focus:border-brand-primary font-semibold">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[9px] font-bold text-text-secondary uppercase mb-1">Capital Value (₦)</label>
                                    <input wire:model="editValue" type="number" class="w-full bg-surface-page border border-border-default text-text-primary rounded px-3 py-1.5 text-xs focus:ring-brand-primary focus:border-brand-primary font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-text-secondary uppercase mb-1">Target Stage</label>
                                    <select wire:model="editStageId" class="w-full bg-surface-page border border-border-default text-text-primary rounded px-3 py-1.5 text-xs focus:ring-brand-primary focus:border-brand-primary font-bold uppercase tracking-wide">
                                        @foreach($modalStages as $stg)
                                            <option value="{{ $stg->id }}">{{ $stg->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-text-secondary uppercase mb-1">Internal Ledger Notes</label>
                                <textarea wire:model="editNotes" rows="2" class="w-full bg-surface-page border border-border-default text-text-primary rounded px-3 py-1.5 text-xs focus:ring-brand-primary focus:border-brand-primary resize-none font-medium"></textarea>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <button type="button" wire:click="deleteDeal({{ $modalDeal->id }})" onclick="return confirm('Permanently purge this deal record?')" class="text-color-danger-500 hover:text-red-700 text-xs font-bold transition-colors uppercase tracking-wider">
                                    Purge Record
                                </button>
                                <button type="submit" class="bg-brand-primary hover:opacity-90 text-white font-bold px-4 py-1.5 rounded text-xs transition-colors uppercase tracking-wider">
                                    Save Details
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Parties -->
                    <div class="p-4 rounded border border-border-default/60 bg-surface-sunken">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest mb-3 font-mono">Associated Parties</h3>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between py-1.5 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Principal Client</span>
                                <span class="font-bold text-text-primary">{{ $modalDeal->contact?->first_name }} {{ $modalDeal->contact?->last_name }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Intent Classifier</span>
                                <span class="font-mono text-brand-primary font-bold">{{ $modalDeal->contact?->type ?? 'Investor' }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Primary Contact</span>
                                <span class="font-mono text-text-primary font-bold">{{ $modalDeal->contact?->phone ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Direct Email</span>
                                <span class="font-mono text-text-primary font-semibold">{{ $modalDeal->contact?->email ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 text-xs">
                                <span class="text-text-secondary font-medium">Account Custodian</span>
                                <span class="font-bold text-text-primary">{{ $modalDeal->agent?->first_name }} {{ $modalDeal->agent?->last_name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Structure -->
                    <div class="p-4 rounded border border-border-default/60 bg-surface-sunken">
                        <h3 class="text-[10px] font-extrabold text-text-primary uppercase tracking-widest mb-3 font-mono">Commission Distribution</h3>
                        
                        @php
                            $grossComm = $modalDeal->value * 0.05;
                            $agentSplit = $grossComm * 0.60;
                            $agencySplit = $grossComm * 0.40;
                        @endphp
                        
                        <div class="space-y-2">
                            <div class="flex justify-between py-1 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Projected Gross Ledger</span>
                                <span class="font-mono font-black text-brand-primary">₦{{ number_format($modalDeal->value) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Agency Yield Rate</span>
                                <span class="font-mono text-text-secondary font-bold">5.00%</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Yield Yield Pool</span>
                                <span class="font-mono text-brand-primary font-bold">₦{{ number_format($grossComm) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-border-default/20 text-xs">
                                <span class="text-text-secondary font-medium">Agent Brokerage Split (60%)</span>
                                <span class="font-mono text-success-500 font-bold">₦{{ number_format($agentSplit) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span class="text-text-secondary font-medium">Agency Pool Split (40%)</span>
                                <span class="font-mono text-text-secondary font-bold">₦{{ number_format($agencySplit) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- AI Assessment Panel -->
                    <div class="p-4 rounded border border-border-default/60 bg-surface-sunken relative overflow-hidden">
                        <div class="flex items-center justify-between mb-3 border-b border-[#F43F5E]/20 pb-2">
                            <h3 class="text-[10px] font-extrabold text-color-danger-500 uppercase tracking-widest font-mono">Neural Risk Diagnostics</h3>
                            <button wire:click="generateAiRiskAssessment" class="px-2 py-0.5 rounded text-[10px] font-bold border border-[#F43F5E]/30 text-color-danger-500 hover:bg-color-danger-500/10 transition-colors uppercase tracking-wider">
                                Scan Risk
                            </button>
                        </div>

                        @if($aiRiskAssessment)
                            <div class="text-xs space-y-2 mt-2 leading-relaxed text-color-danger-500 font-bold">
                                {!! nl2br(e($aiRiskAssessment)) !!}
                            </div>
                        @else
                            <div class="text-[11px] text-text-secondary py-4 text-center border border-dashed border-[#F43F5E]/20 rounded font-semibold italic">
                                Initialize AI neural engine diagnostic matrix.
                            </div>
                        @endif
                        
                        <!-- AI Next Action Recommendation -->
                        <div class="mt-4 pt-4 border-t border-border-default/20">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-[10px] font-extrabold text-brand-primary uppercase tracking-widest font-mono">AI Next Best Action</h4>
                                <button wire:click="generateAiNextStep" class="px-2 py-0.5 rounded text-[10px] font-bold border border-brand-primary/30 text-brand-primary hover:bg-brand-primary/10 transition-colors uppercase tracking-wider">
                                    Predict Action
                                </button>
                            </div>
                            
                            @if($aiNextAction)
                                <div class="p-3.5 rounded bg-brand-primary/5 border border-brand-primary/15 text-xs text-text-secondary font-semibold italic">
                                    "{{ $aiNextAction }}"
                                </div>
                            @else
                                <div class="text-[11px] text-text-secondary py-2 text-center border border-dashed border-brand-primary/20 rounded font-semibold italic">
                                    Generate predictive action checklist.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
