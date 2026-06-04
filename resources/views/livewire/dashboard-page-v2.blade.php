<div>
    <!-- Header -->
    <div class="mb-10 flex items-end justify-between">
        <div>
            <p class="text-sm font-bold tracking-widest uppercase text-brand-primary mb-1">PropOS Command Center</p>
            <h1 class="text-4xl font-black tracking-tight text-text-primary">Good Morning, {{ $user->first_name }}.</h1>
        </div>
        <div class="hidden sm:flex space-x-3">
            <button class="px-5 py-2.5 rounded-xl bg-surface-raised border border-border-default text-sm font-bold text-text-primary hover:border-brand-primary/50 hover:text-brand-primary transition-colors shadow-sm flex items-center space-x-2 hover-spring active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span>New Mandate</span>
            </button>
            <button class="px-5 py-2.5 rounded-xl bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-border-strong text-sm font-bold hover:bg-brand-secondary transition-colors shadow-brand-md flex items-center space-x-2 hover-spring active:scale-95 hover:shadow-brand-lg">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span>Sync</span>
            </button>
        </div>
    </div>

    <!-- KPI Metrics - Bento Box Style -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-10">
        <!-- Metric 1: Pipeline (Hero Style) -->
        <div class="p-6 rounded-2xl bg-gradient-hero text-text-primary shadow-2xl relative overflow-hidden group hover:scale-[1.02] hover:shadow-brand-lg transition-all duration-300">
            <div class="absolute inset-0 bg-brand-primary opacity-30 mix-blend-overlay pointer-events-none"></div>
            <!-- Glowing accent in card -->
            <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full bg-brand-accent/25 blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-8">
                    <p class="text-xs font-bold tracking-widest uppercase opacity-80">Pipeline Value</p>
                    <div class="p-2.5 bg-white/10 rounded-xl backdrop-blur-sm">
                        <svg class="w-5 h-5 text-text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div>
                    <p class="text-4xl font-black tracking-tighter">{{ $currencySymbol }}{{ number_format($metrics['total_pipeline'] / 1000000, 1) }}M</p>
                    <div class="mt-3 flex items-center text-xs font-bold">
                        <span class="text-brand-accent bg-black/20 px-2 py-1 rounded-lg flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            12.4%
                        </span>
                        <span class="ml-2 opacity-70">vs last month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Active Listings -->
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-info-500/40 hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-info-500/10 blur-xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="flex items-center justify-between mb-8">
                <p class="text-xs font-bold tracking-widest uppercase text-text-secondary">Listings</p>
                <div class="p-2.5 bg-info-500/10 rounded-xl text-info-500 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
            </div>
            <div>
                <p class="text-4xl font-black text-text-primary tracking-tighter">{{ $metrics['active_listings'] }}</p>
                <p class="mt-3 text-xs font-semibold text-text-tertiary">3 closing this week</p>
            </div>
        </div>

        <!-- Metric 3: New Leads -->
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-brand-primary/40 hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-brand-primary/10 blur-xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="flex items-center justify-between mb-8">
                <p class="text-xs font-bold tracking-widest uppercase text-text-secondary">New Leads</p>
                <div class="p-2.5 bg-brand-primary/10 rounded-xl text-brand-primary group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div>
                <p class="text-4xl font-black text-text-primary tracking-tighter">{{ $metrics['new_leads'] }}</p>
                <p class="mt-3 text-xs font-semibold text-text-tertiary">From all campaigns</p>
            </div>
        </div>

        <!-- Metric 4: Hot Buyers -->
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-warning-500/40 hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-warning-500/10 blur-xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="flex items-center justify-between mb-8">
                <p class="text-xs font-bold tracking-widest uppercase text-text-secondary">Hot Buyers</p>
                <div class="p-2.5 bg-warning-500/10 rounded-xl text-warning-500 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path></svg>
                </div>
            </div>
            <div>
                <p class="text-4xl font-black text-text-primary tracking-tighter">{{ $metrics['hot_buyers'] }}</p>
                <p class="mt-3 text-xs font-semibold text-text-tertiary">> 80% AI Intent Score</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Data Tables -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Recent Listings -->
            <div class="bg-surface-card rounded-2xl border border-border-default shadow-md overflow-hidden transition-all duration-300 hover:border-border-strong/50">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between bg-surface-sunken/40">
                    <h2 class="text-lg font-bold text-text-primary flex items-center">
                        <svg class="w-5 h-5 mr-2 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Portfolio Activity
                    </h2>
                    <a href="{{ route('listing.index') }}" class="text-sm font-bold text-text-tertiary hover:text-brand-primary transition-colors">View All &rarr;</a>
                </div>
                <div class="divide-y divide-border-default/40">
                    @forelse($recentListings as $listing)
                        <div class="px-8 py-5 flex items-center justify-between hover:bg-brand-primary/5 border-l-2 border-transparent hover:border-brand-primary transition-all duration-300 group cursor-pointer relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-brand-primary/0 to-brand-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                            <div class="flex items-center space-x-5">
                                <div class="h-12 w-12 rounded-xl bg-surface-raised border border-border-default flex items-center justify-center text-text-tertiary flex-shrink-0 group-hover:text-brand-primary transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-text-primary group-hover:text-brand-primary transition-colors">{{ $listing->property->address_line_1 }}</h4>
                                    <p class="text-xs font-semibold text-text-secondary mt-1">{{ $listing->property->city }} � {{ $currencySymbol }}{{ number_format($listing->listing_price) }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-black tracking-wider bg-surface-raised border border-border-default text-text-primary uppercase shadow-sm">
                                {{ $listing->status }}
                            </span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm font-semibold text-text-tertiary">
                             No active portfolio data.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Contacts -->
            <div class="bg-surface-card rounded-2xl border border-border-default shadow-md overflow-hidden transition-all duration-300 hover:border-border-strong/50">
                <div class="px-8 py-6 border-b border-border-default/40 flex items-center justify-between bg-surface-sunken/40">
                    <h2 class="text-lg font-bold text-text-primary flex items-center">
                        <svg class="w-5 h-5 mr-2 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Recent Leads
                    </h2>
                    <a href="{{ route('crm.contacts') }}" class="text-sm font-bold text-text-tertiary hover:text-brand-primary transition-colors">See all &rarr;</a>
                </div>
                <div class="divide-y divide-border-default/40">
                    @forelse($recentContacts as $contact)
                        <div class="px-8 py-5 flex items-center justify-between hover:bg-brand-primary/5 border-l-2 border-transparent hover:border-brand-primary transition-all duration-300 group cursor-pointer relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-brand-primary/0 to-brand-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                            <div class="flex items-center space-x-4">
                                <div class="h-10 w-10 rounded-full bg-brand-primary/10 text-brand-primary font-black text-xs flex items-center justify-center">
                                    {{ substr($contact->first_name, 0, 1) }}{{ substr($contact->last_name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-text-primary group-hover:text-brand-primary transition-colors">{{ $contact->first_name }} {{ $contact->last_name }}</h4>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-text-tertiary">{{ $contact->type }}</span>
                                        <span class="h-1 w-1 rounded-full bg-border-default"></span>
                                        <span class="text-[10px] font-black uppercase tracking-wider {{ $contact->intent_score >= 80 ? 'text-warning-500' : 'text-text-secondary' }}">{{ $contact->intent_score }}% Intent</span>
                                    </div>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-text-tertiary group-hover:text-brand-primary transition-colors transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm font-semibold text-text-tertiary">
                            No recent contacts.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right Column: AI Copilot -->
        <div class="space-y-8">
            
            <!-- AI Copilot Intelligence Feed -->
            <div class="rounded-2xl bg-surface-card border border-border-default shadow-brand-md relative overflow-hidden flex flex-col h-[600px] transition-all duration-300 hover:shadow-brand-lg hover:border-brand-primary/30">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-primary via-info-500 to-brand-primary"></div>
                <div class="px-6 py-5 border-b border-border-default/40 flex items-center justify-between bg-surface-sunken/40">
                    <div class="flex items-center space-x-2">
                        <div class="relative flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                        </div>
                        <h2 class="text-sm font-black uppercase tracking-widest text-text-primary">Copilot Feed</h2>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <!-- Suggestion 1 -->
                    <div class="p-5 rounded-2xl bg-surface-sunken/30 border border-border-default/50 hover:bg-surface-card/60 hover:border-brand-primary/30 hover:shadow-brand-sm transition-all duration-300 hover-spring active:scale-95 cursor-pointer group">
                        <div class="flex items-center space-x-2 mb-3">
                            <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span class="text-[10px] font-black uppercase tracking-widest text-brand-primary">Lead Match</span>
                        </div>
                        <h4 class="text-sm font-bold text-text-primary mb-2">High Intent Buyer</h4>
                        <p class="text-xs font-medium text-text-secondary leading-relaxed mb-4">A new buyer lead matches 95% of criteria for your new "Victoria Island" mandate.</p>
                        <button class="w-full py-2.5 text-xs font-bold rounded-xl bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-border-strong hover:bg-brand-secondary transition-colors shadow-brand-sm">Draft Introduction</button>
                    </div>
                    
                    <!-- Suggestion 2 -->
                    <div class="p-5 rounded-2xl bg-surface-sunken/30 border border-border-default/50 hover:bg-surface-card/60 hover:border-warning-500/30 hover:shadow-brand-sm transition-all duration-300 hover-spring active:scale-95 cursor-pointer group">
                        <div class="flex items-center space-x-2 mb-3">
                            <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            <span class="text-[10px] font-black uppercase tracking-widest text-warning-500">Optimization</span>
                        </div>
                        <h4 class="text-sm font-bold text-text-primary mb-2">Price Reduction</h4>
                        <p class="text-xs font-medium text-text-secondary leading-relaxed mb-4">"Ikoyi Premium Villa" has been on the market for 45 days. AI suggests a 5% reduction.</p>
                        <button class="w-full py-2.5 text-xs font-bold rounded-xl bg-surface-raised border border-border-default text-text-primary hover:border-warning-500 transition-colors">Review Pricing</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>



