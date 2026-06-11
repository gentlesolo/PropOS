<div class="space-y-6" x-data="{ transitioning: false }">
    <!-- View Switcher & Title Header -->
    <div class="flex items-center justify-between border-b border-border-default pb-4">
        <div class="flex flex-col">
            <h1 class="text-lg font-black tracking-tight text-text-primary flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-brand-primary animate-pulse"></span>
                <span>VillaCRM Mission Control</span>
            </h1>
            <p class="text-[10px] font-mono uppercase tracking-wider text-text-tertiary mt-0.5">
                Terminal ID: P-{{ strtoupper(auth()->user()->first_name) }}-{{ now()->format('Ymd') }}
            </p>
        </div>

        <!-- Interactive View Toggle -->
        <div class="flex items-center bg-surface-card border border-border-default p-0.5 rounded">
            <button wire:click="setViewMode('agent')" 
                    class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider transition-all duration-200 {{ $viewMode === 'agent' ? 'bg-brand-primary text-[#030712]' : 'text-text-secondary hover:text-text-primary' }}">
                Agent Terminal
            </button>
            <button wire:click="setViewMode('principal')" 
                    class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider transition-all duration-200 {{ $viewMode === 'principal' ? 'bg-brand-primary text-[#030712]' : 'text-text-secondary hover:text-text-primary' }}">
                Principal Room
            </button>
        </div>
    </div>

    <!-- ZONE 1: HERO STRIP (Full Width, ~140px tall) -->
    @if($viewMode === 'agent')
    <!-- Agent Hero Strip: AI Daily Brief -->
    <div class="relative overflow-hidden rounded-xl bg-surface-card border border-border-default p-4 min-h-[140px] flex flex-col md:flex-row justify-between gap-4 shadow-sm shadow-[#10B981]/5">
        <!-- Faint Emerald Grid Texture -->
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#10B981 1px, transparent 0); background-size: 16px 16px;"></div>

        <!-- Left Side: Priorities (Ranked list of 3 items) -->
        <div class="relative z-10 flex-1 flex flex-col justify-between">
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-wider text-brand-primary flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904zm9.193-7.658L18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006z"/>
                    </svg>
                    <span>AI Priority Recommendations</span>
                </h3>
                <div class="mt-2.5 space-y-2">
                    @foreach($priorities as $index => $item)
                    <div class="flex items-center justify-between text-xs py-1 px-2 rounded bg-surface-raised/40 border border-border-default hover:border-border-strong transition-colors">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono text-text-tertiary text-[10px]">0{{ $index + 1 }}.</span>
                            <span class="font-bold text-text-primary">{{ $item->lead }}</span>
                            <span class="text-text-secondary">—</span>
                            <span class="text-text-secondary truncate max-w-xs">{{ $item->action }}</span>
                        </div>
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ $item->badge_color }}">
                            {{ $item->urgency }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side: 3 Sparkline KPIs -->
        <div class="relative z-10 w-full md:w-80 flex flex-col justify-between gap-2.5">
            <!-- Sparkline 1: Active Leads -->
            <div class="flex items-center justify-between bg-surface-raised/60 p-2 rounded border border-border-default">
                <div class="flex flex-col">
                    <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Active Leads</span>
                    <span class="text-xs font-mono font-bold text-text-primary mt-0.5">{{ $activeLeadsCount }}</span>
                </div>
                <svg class="h-6 w-20 text-brand-primary" viewBox="0 0 100 30" fill="none">
                    <defs>
                        <linearGradient id="spark-leads" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10B981" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="#10B981" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,25 C10,22 15,28 25,18 C35,8 45,15 55,10 C65,5 75,20 85,8 L100,5" stroke="#10B981" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M0,25 C10,22 15,28 25,18 C35,8 45,15 55,10 C65,5 75,20 85,8 L100,5 L100,30 L0,30 Z" fill="url(#spark-leads)" />
                </svg>
            </div>

            <!-- Sparkline 2: Viewings Today -->
            <div class="flex items-center justify-between bg-surface-raised/60 p-2 rounded border border-border-default">
                <div class="flex flex-col">
                    <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Viewings Scheduled</span>
                    <span class="text-xs font-mono font-bold text-text-primary mt-0.5">{{ $viewingsCount }}</span>
                </div>
                <svg class="h-6 w-20 text-[#0ea5e9]" viewBox="0 0 100 30" fill="none">
                    <defs>
                        <linearGradient id="spark-viewings" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,28 Q15,10 30,22 T60,8 T80,18 T100,10" stroke="#0ea5e9" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M0,28 Q15,10 30,22 T60,8 T80,18 T100,10 L100,30 L0,30 Z" fill="url(#spark-viewings)" />
                </svg>
            </div>

            <!-- Sparkline 3: Pipeline Value -->
            <div class="flex items-center justify-between bg-surface-raised/60 p-2 rounded border border-border-default">
                <div class="flex flex-col">
                    <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Pipeline Value</span>
                    <span class="text-xs font-mono font-bold text-text-primary mt-0.5">{{ $agency->currency_symbol ?? '$' }}{{ number_format($myPipelineValue / 1000000, 1) }}M</span>
                </div>
                <svg class="h-6 w-20 text-color-warning-500" viewBox="0 0 100 30" fill="none">
                    <defs>
                        <linearGradient id="spark-pipeline" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#F59E0B" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,28 C20,28 30,15 50,22 C70,29 80,10 90,8 L100,5" stroke="#F59E0B" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M0,28 C20,28 30,15 50,22 C70,29 80,10 90,8 L100,5 L100,30 L0,30 Z" fill="url(#spark-pipeline)" />
                </svg>
            </div>
        </div>
    </div>
    @else
    <!-- Principal Room Hero Strip: Agency Snapshot Bar -->
    <div class="relative overflow-hidden rounded-xl bg-surface-card border border-border-default p-5 min-h-[140px] flex flex-col justify-between shadow-sm shadow-[#10B981]/5">
        <!-- Faint Emerald Grid Texture -->
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#10B981 1px, transparent 0); background-size: 16px 16px;"></div>

        <div class="relative z-10 flex items-center justify-between">
            <h3 class="text-[10px] font-black uppercase tracking-wider text-brand-primary flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
                <span>Executive Agency Snapshot</span>
            </h3>
            <span class="text-[9px] font-mono font-bold text-text-tertiary px-1.5 py-0.5 bg-surface-raised border border-border-default rounded">Real-time Performance</span>
        </div>

        <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <div class="bg-surface-raised/40 border border-border-default p-3 rounded flex flex-col justify-center">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Total Agency GCI</span>
                <span class="text-xl font-mono font-bold text-text-primary tracking-tight mt-1">{{ $agency->currency_symbol ?? '$' }}{{ number_format($totalAgencyGci, 2) }}</span>
            </div>
            <div class="bg-surface-raised/40 border border-border-default p-3 rounded flex flex-col justify-center">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Active Listings</span>
                <span class="text-xl font-mono font-bold text-text-primary tracking-tight mt-1">{{ $activeListings }}</span>
            </div>
            <div class="bg-surface-raised/40 border border-border-default p-3 rounded flex flex-col justify-center">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Team Performance Score</span>
                <span class="text-xl font-mono font-bold text-brand-primary tracking-tight mt-1 flex items-center gap-1.5">
                    <span>{{ $teamPerformanceScore }}%</span>
                    <span class="text-[10px] font-bold text-color-success-500 bg-color-success-500/10 px-1 py-0.5 rounded">High</span>
                </span>
            </div>
            <div class="bg-surface-raised/40 border border-border-default p-3 rounded flex flex-col justify-center">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Top Agent This Month</span>
                <span class="text-sm font-bold text-text-primary tracking-tight mt-1 truncate">{{ $topAgentName }}</span>
                <span class="text-[9px] font-mono font-bold text-color-warning-500 mt-0.5">{{ $agency->currency_symbol ?? '$' }}{{ number_format($topAgentVal, 0) }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- ZONE 2: METRICS ROW (4 cards for Agent, 5 cards for Principal) -->
    <div class="grid grid-cols-2 md:grid-cols-4 {{ $viewMode === 'principal' ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
        <!-- Metric 1: My Pipeline Value / Agency Pipeline Value -->
        <div class="bg-surface-card/80 border border-border-default rounded-xl p-4 shadow-sm hover:border-border-focus/30 hover:shadow-md hover:shadow-[#10B981]/5 transition-all duration-300 group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Pipeline Value</span>
                <span class="text-[10px] font-bold text-color-success-500 bg-color-success-500/10 px-1.5 py-0.5 rounded">↑14%</span>
            </div>
            <p class="text-xl font-mono font-bold text-text-primary mt-2.5 tracking-tight">
                {{ $agency->currency_symbol ?? '$' }}{{ number_format($viewMode === 'agent' ? $myPipelineValue : ($myPipelineValue * 2.8), 2) }}
            </p>
            <p class="text-[9px] font-bold text-text-tertiary uppercase tracking-wider mt-1.5">
                {{ $viewMode === 'agent' ? 'My Active Leads' : 'All Agency Pipeline' }}
            </p>
        </div>

        <!-- Metric 2: Active Leads -->
        <div class="bg-surface-card/80 border border-border-default rounded-xl p-4 shadow-sm hover:border-border-focus/30 hover:shadow-md hover:shadow-[#10B981]/5 transition-all duration-300 group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Active Leads</span>
                <span class="text-[10px] font-bold text-color-success-500 bg-color-success-500/10 px-1.5 py-0.5 rounded">↑8%</span>
            </div>
            <p class="text-xl font-mono font-bold text-text-primary mt-2.5 tracking-tight">
                {{ $viewMode === 'agent' ? $activeLeadsCount : ($activeLeadsCount * 4) }}
            </p>
            <p class="text-[9px] font-bold text-text-tertiary uppercase tracking-wider mt-1.5">Requires Action Today</p>
        </div>

        <!-- Metric 3: Viewings This Month -->
        <div class="bg-surface-card/80 border border-border-default rounded-xl p-4 shadow-sm hover:border-[#0ea5e9]/30 hover:shadow-md hover:shadow-[#0ea5e9]/5 transition-all duration-300 group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Viewings Scheduled</span>
                <span class="text-[10px] font-bold text-color-danger-500 bg-color-danger-500/10 px-1.5 py-0.5 rounded">↓3%</span>
            </div>
            <p class="text-xl font-mono font-bold text-text-primary mt-2.5 tracking-tight">
                {{ $viewMode === 'agent' ? $viewingsCount : ($viewingsCount * 3.5) }}
            </p>
            <p class="text-[9px] font-bold text-text-tertiary uppercase tracking-wider mt-1.5">Calendar Booking</p>
        </div>

        <!-- Metric 4: Commission YTD -->
        <div class="bg-surface-card/80 border border-border-default rounded-xl p-4 shadow-sm hover:border-[#F59E0B]/30 hover:shadow-md hover:shadow-[#F59E0B]/5 transition-all duration-300 group cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Commission YTD</span>
                <span class="text-[10px] font-bold text-color-success-500 bg-color-success-500/10 px-1.5 py-0.5 rounded">↑21%</span>
            </div>
            <p class="text-xl font-mono font-bold text-text-primary mt-2.5 tracking-tight">
                {{ $agency->currency_symbol ?? '$' }}{{ number_format($viewMode === 'agent' ? $commissionYtd : ($commissionYtd * 3.2), 2) }}
            </p>
            <p class="text-[9px] font-bold text-text-tertiary uppercase tracking-wider mt-1.5">Net Earned Commission</p>
        </div>

        @if($viewMode === 'principal')
        <!-- Metric 5: Team Headcount (Principal View Only) -->
        <div class="bg-surface-card/80 border border-border-default rounded-xl p-4 shadow-sm hover:border-border-focus/30 hover:shadow-md hover:shadow-[#10B981]/5 transition-all duration-300 group cursor-pointer col-span-2 md:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black uppercase tracking-wider text-text-tertiary">Active Agents</span>
                <span class="text-[10px] font-bold text-brand-primary bg-brand-primary/10 px-1.5 py-0.5 rounded">100%</span>
            </div>
            <p class="text-xl font-mono font-bold text-text-primary mt-2.5 tracking-tight">
                {{ $teamHeadcount }}
            </p>
            <p class="text-[9px] font-bold text-text-tertiary uppercase tracking-wider mt-1.5">Agency Licensed Seats</p>
        </div>
        @endif
    </div>

    <!-- ZONE 3: SPLIT CONTENT (Left 60% / Right 40%) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Deal Pipeline Summary (60%) -->
        <div class="lg:col-span-2 bg-surface-card/80 border border-border-default rounded-xl p-5 flex flex-col justify-between min-h-[360px]">
            <div>
                <div class="flex items-center justify-between border-b border-border-default pb-3">
                    <h2 class="text-xs font-black uppercase tracking-wider text-text-primary flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-3.75-2.25v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-1.5 2.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                        </svg>
                        <span>Deal Pipeline Stages</span>
                    </h2>
                    <span class="text-[9px] font-mono text-text-tertiary">Overview Mode</span>
                </div>

                <!-- Stage Cards Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                    @foreach($stages as $stage)
                    <div class="bg-surface-raised/40 border border-border-default p-3 rounded flex flex-col justify-between min-h-[100px] hover:border-border-strong transition-colors">
                        <div>
                            <span class="text-[9px] font-black uppercase text-text-secondary tracking-wider block truncate" title="{{ $stage->name }}">{{ $stage->name }}</span>
                            <span class="text-[8px] font-mono font-bold text-text-tertiary mt-0.5 block">{{ $stage->deals->count() }} Deals</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <!-- Client Avatar Overlap Stack -->
                            <div class="flex -space-x-1.5 overflow-hidden">
                                @forelse($stage->deals->take(3) as $deal)
                                    @if($deal->contact)
                                    <div class="h-5 w-5 rounded-full bg-surface-page border border-white/15 flex items-center justify-center text-[7px] font-black text-text-primary uppercase shrink-0" 
                                         title="{{ $deal->contact->name }}">
                                        {{ substr($deal->contact->first_name, 0, 1) }}{{ substr($deal->contact->last_name, 0, 1) }}
                                    </div>
                                    @endif
                                @empty
                                    <span class="text-[8px] text-text-tertiary font-semibold italic">Empty</span>
                                @endforelse
                                @if($stage->deals->count() > 3)
                                <div class="h-5 w-5 rounded-full bg-brand-primary/10 border border-border-focus/20 flex items-center justify-center text-[7px] font-black text-brand-primary shrink-0">
                                    +{{ $stage->deals->count() - 3 }}
                                </div>
                                @endif
                            </div>

                            <!-- Stage total value -->
                            <span class="text-[10px] font-mono font-bold text-brand-primary">
                                {{ $agency->currency_symbol ?? '$' }}{{ number_format($stage->deals->sum('value') / 1000, 0) }}k
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- View Full Pipeline Button link -->
            <div class="mt-4 pt-4 border-t border-border-default flex justify-end">
                <a href="{{ route('crm.pipeline') }}" class="text-[10px] font-black uppercase tracking-wider text-brand-primary hover:text-brand-primary/80 transition-colors flex items-center gap-1">
                    <span>View full pipeline</span>
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Right: Action Feed (Agent) OR Leaderboard (Principal) (40%) -->
        <div class="bg-surface-card/80 border border-border-default rounded-xl p-5 flex flex-col justify-between min-h-[360px]">
            @if($viewMode === 'agent')
            <!-- Lead Activity Feed (Agent View) -->
            <div>
                <div class="flex items-center justify-between border-b border-border-default pb-3">
                    <h2 class="text-xs font-black uppercase tracking-wider text-text-primary flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#0ea5e9]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-1.5 3h1.5m-7.5-6h7.5m-7.5 3h7.5m-7.5 3h7.5M3 5.25h18M3 18.75h18m-18-13.5v13.5c0 .621.504 1.125 1.125 1.125h15.75c.621 0 1.125-.504 1.125-1.125V5.25" />
                        </svg>
                        <span>Lead Activity Feed</span>
                    </h2>
                    <span class="text-[9px] font-mono text-text-tertiary">Live Stream</span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach($activities as $act)
                    <div class="flex gap-3 p-2.5 rounded bg-surface-raised/40 border-l-2 {{ $act->border_color }} border-r border-y border-border-default hover:border-border-strong transition-colors">
                        <div class="h-7 w-7 rounded-full bg-surface-page border border-white/15 flex items-center justify-center text-[9px] font-black text-text-secondary uppercase shrink-0 mt-0.5">
                            {{ $act->contact_initials }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold text-text-primary truncate">{{ $act->contact_name }}</p>
                                <span class="text-[8px] font-mono text-text-tertiary shrink-0">{{ $act->time_ago }}</span>
                            </div>
                            <p class="text-[10px] font-bold text-brand-primary mt-0.5">{{ $act->title }}</p>
                            <p class="text-[9px] text-text-secondary mt-0.5 line-clamp-1">{{ $act->description }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <!-- Agent Leaderboard (Principal View) -->
            <div>
                <div class="flex items-center justify-between border-b border-border-default pb-3">
                    <h2 class="text-xs font-black uppercase tracking-wider text-text-primary flex items-center gap-2">
                        <svg class="h-4 w-4 text-color-warning-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.75a1.125 1.125 0 0 1-1.125-1.125V11.25M10.5 5.25a3 3 0 0 0-3 3v.75m6-3.75a3 3 0 0 1 3 3v.75m-9 0h9" />
                        </svg>
                        <span>Agent Leaderboard</span>
                    </h2>
                    <span class="text-[9px] font-mono text-text-tertiary">YTD Earnings</span>
                </div>

                <div class="mt-4 space-y-3.5">
                    @foreach($leaderboard as $index => $agent)
                    <div class="space-y-1.5 p-2 rounded bg-surface-raised/20 border border-border-default">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center space-x-2 min-w-0">
                                <span class="font-mono text-text-tertiary text-[10px]">0{{ $index + 1 }}.</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-text-primary truncate">{{ $agent->first_name }} {{ $agent->last_name }}</p>
                                    <p class="text-[8px] font-black text-text-tertiary uppercase tracking-wider mt-0.5 truncate">{{ $agent->job_title }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-bold text-brand-primary shrink-0">
                                {{ $agency->currency_symbol ?? '$' }}{{ number_format($agent->total_gci, 0) }}
                            </span>
                        </div>
                        <div class="w-full bg-surface-page rounded-full h-1">
                            <div class="bg-gradient-brand-vibrant h-1 rounded-full transition-all duration-300" style="width: {{ $agent->percentage }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Bottom meta -->
            <div class="mt-4 pt-4 border-t border-border-default flex items-center justify-between text-[9px] text-text-tertiary font-bold uppercase tracking-wider">
                <span>Update: Auto 5m</span>
                <span class="text-brand-primary">Terminal Synced</span>
            </div>
        </div>
    </div>
</div>
