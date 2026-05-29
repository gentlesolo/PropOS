<div>
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Dashboard</h1>
            <p class="mt-2 text-text-secondary">Welcome back, <span class="font-semibold text-brand-primary">{{ $user->first_name }}</span>. Here is your agency overview.</p>
        </div>
        <div class="hidden sm:block">
            <button class="glass-panel px-4 py-2 text-sm font-semibold text-text-primary hover:text-brand-primary transition-colors hover-spring flex items-center space-x-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span>Sync Data</span>
            </button>
        </div>
    </div>

    <!-- KPI Metrics -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Metric 1: Pipeline -->
        <div class="glass-panel p-6 rounded-3xl hover-spring cursor-pointer group relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-brand-primary/10 rounded-full blur-2xl group-hover:bg-brand-primary/20 transition-colors"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold tracking-wider uppercase text-text-secondary">Pipeline Value</p>
                    <p class="mt-2 text-3xl font-black text-text-primary tracking-tight">₦{{ number_format($metrics['total_pipeline'] / 1000000, 1) }}M</p>
                </div>
                <div class="p-3 bg-surface-raised rounded-2xl group-hover:bg-brand-primary/10 border border-border-default/60 transition-colors shadow-sm">
                    <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="relative z-10 mt-5 flex items-center text-xs font-semibold">
                <span class="text-success-500 bg-success-500/10 px-2 py-0.5 rounded-md flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    12.4%
                </span>
                <span class="ml-2 text-text-tertiary">vs last month</span>
            </div>
        </div>

        <!-- Metric 2: Active Listings -->
        <div class="glass-panel p-6 rounded-3xl hover-spring cursor-pointer group relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-info-500/10 rounded-full blur-2xl group-hover:bg-info-500/20 transition-colors"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold tracking-wider uppercase text-text-secondary">Active Listings</p>
                    <p class="mt-2 text-3xl font-black text-text-primary tracking-tight">{{ $metrics['active_listings'] }}</p>
                </div>
                <div class="p-3 bg-surface-raised rounded-2xl group-hover:bg-info-500/10 border border-border-default/60 transition-colors shadow-sm">
                    <svg class="w-6 h-6 text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
            </div>
        </div>

        <!-- Metric 3: New Leads -->
        <div class="glass-panel p-6 rounded-3xl hover-spring cursor-pointer group relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-brand-primary/10 rounded-full blur-2xl group-hover:bg-brand-primary/20 transition-colors"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold tracking-wider uppercase text-text-secondary">New Leads</p>
                    <p class="mt-2 text-3xl font-black text-text-primary tracking-tight">{{ $metrics['new_leads'] }}</p>
                </div>
                <div class="p-3 bg-surface-raised rounded-2xl group-hover:bg-brand-primary/10 border border-border-default/60 transition-colors shadow-sm">
                    <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Metric 4: Hot Buyers -->
        <div class="glass-panel p-6 rounded-3xl hover-spring cursor-pointer group relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-warning-500/10 rounded-full blur-2xl group-hover:bg-warning-500/20 transition-colors"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold tracking-wider uppercase text-text-secondary">Hot Buyers</p>
                    <p class="mt-2 text-3xl font-black text-text-primary tracking-tight">{{ $metrics['hot_buyers'] }}</p>
                </div>
                <div class="p-3 bg-surface-raised rounded-2xl group-hover:bg-warning-500/10 border border-border-default/60 transition-colors shadow-sm">
                    <svg class="w-6 h-6 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Main Views -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- AI Copilot Intelligence Feed (Stunning Visual Variant) -->
            <div class="rounded-3xl border border-border-default/60 overflow-hidden relative shadow-md group transition-all duration-300 hover:shadow-brand-lg">
                <!-- Background Gradient (Subtle shifting) -->
                <div class="absolute inset-0 bg-gradient-subtle dark:bg-gradient-hero opacity-20 pointer-events-none transition-opacity group-hover:opacity-30"></div>
                
                <!-- Noise/Texture Overlay -->
                <div class="absolute inset-0 opacity-[0.03] mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

                <div class="relative z-10 glass-panel border-none rounded-3xl backdrop-blur-3xl h-full">
                    <div class="px-8 py-5 border-b border-border-default/40 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="h-3 w-3 rounded-full bg-success-500 shadow-[0_0_10px_rgba(16,185,129,0.8)] animate-pulse"></div>
                            <h2 class="text-sm font-black uppercase tracking-widest text-text-primary">PropOS Copilot</h2>
                        </div>
                        <span class="text-[10px] font-mono text-text-tertiary font-bold px-2 py-1 bg-surface-raised rounded-md border border-border-subtle">LIVE STREAM</span>
                    </div>
                    
                    <div class="p-8 space-y-5">
                        <div class="relative pl-8 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border-default/60">
                            <!-- Suggestion 1 -->
                            <div class="relative mb-8">
                                <div class="absolute -left-8 top-1 h-7 w-7 rounded-full bg-brand-primary/10 border border-brand-primary/30 flex items-center justify-center backdrop-blur-sm z-10">
                                    <svg class="h-3.5 w-3.5 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div class="p-5 rounded-2xl bg-surface-card border border-border-default/60 shadow-sm hover:shadow transition-shadow">
                                    <h4 class="text-sm font-bold text-text-primary mb-1">High Intent Lead Matched</h4>
                                    <p class="text-sm text-text-secondary leading-relaxed">A new buyer lead matches 95% of criteria for your new "Victoria Island" mandate.</p>
                                    <div class="mt-4 flex space-x-3">
                                        <button class="px-4 py-2 text-xs font-bold rounded-xl bg-brand-primary text-white hover:bg-brand-secondary transition-colors hover-spring shadow-sm">Draft Introduction</button>
                                        <button class="px-4 py-2 text-xs font-bold rounded-xl bg-surface-raised text-text-secondary hover:text-text-primary border border-border-default/60 transition-colors">Dismiss</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Suggestion 2 -->
                            <div class="relative">
                                <div class="absolute -left-8 top-1 h-7 w-7 rounded-full bg-warning-500/10 border border-warning-500/30 flex items-center justify-center backdrop-blur-sm z-10">
                                    <svg class="h-3.5 w-3.5 text-warning-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div class="p-5 rounded-2xl bg-surface-card border border-border-default/60 shadow-sm hover:shadow transition-shadow">
                                    <h4 class="text-sm font-bold text-text-primary mb-1">Price Reduction Recommended</h4>
                                    <p class="text-sm text-text-secondary leading-relaxed">Listing <span class="font-semibold text-text-primary">"Ikoyi Premium Villa"</span> has been on the market for 45 days. AI suggests a 5% reduction.</p>
                                    <div class="mt-4 flex space-x-3">
                                        <button class="px-4 py-2 text-xs font-bold rounded-xl bg-surface-raised text-text-primary border border-border-default/60 hover:border-brand-primary/50 transition-colors">Review Pricing</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Listings -->
            <div class="glass-panel rounded-3xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/60 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary">Portfolio Activity</h2>
                    <a href="{{ route('listing.index') }}" class="text-sm font-bold text-brand-primary hover:text-brand-secondary transition-colors">View All &rarr;</a>
                </div>
                <div class="divide-y divide-border-default/40">
                    @forelse($recentListings as $listing)
                        <div class="px-8 py-5 flex items-center justify-between hover:bg-surface-sunken/30 transition-colors group">
                            <div class="flex items-center space-x-5">
                                <div class="h-14 w-14 rounded-2xl bg-surface-raised border border-border-default/60 flex items-center justify-center text-text-tertiary flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-text-primary group-hover:text-brand-primary transition-colors">{{ $listing->property->address_line_1 }}</h4>
                                    <p class="text-sm text-text-secondary mt-0.5">{{ $listing->property->city }} • ₦{{ number_format($listing->listing_price) }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-black tracking-wider bg-surface-raised border border-border-default/60 text-text-primary uppercase shadow-sm">
                                {{ $listing->status }}
                            </span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-text-tertiary">
                            No active portfolio data.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right Column: Contacts & Activity -->
        <div class="space-y-8">
            
            <!-- Recent Contacts -->
            <div class="glass-panel rounded-3xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/60 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary">Recent Leads</h2>
                    <a href="{{ route('crm.contacts') }}" class="text-sm font-bold text-text-tertiary hover:text-text-primary transition-colors">See all</a>
                </div>
                <ul class="divide-y divide-border-default/40">
                    @forelse($recentContacts as $contact)
                        <li class="px-8 py-5 hover:bg-surface-sunken/30 transition-colors group cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <div class="h-12 w-12 rounded-full bg-surface-raised border border-border-default/60 flex items-center justify-center text-text-primary font-black text-sm group-hover:bg-brand-primary/10 group-hover:border-brand-primary/30 group-hover:text-brand-primary transition-all duration-300">
                                        {{ substr($contact->first_name, 0, 1) }}{{ substr($contact->last_name, 0, 1) }}
                                    </div>
                                    @if($contact->intent_score >= 80)
                                        <div class="absolute -top-1 -right-1 h-4 w-4 bg-warning-500 rounded-full border-2 border-surface-card flex items-center justify-center">
                                            <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-text-primary truncate group-hover:text-brand-primary transition-colors">
                                        {{ $contact->first_name }} {{ $contact->last_name }}
                                    </p>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-text-tertiary">{{ $contact->type }}</span>
                                        <span class="h-1 w-1 rounded-full bg-border-default"></span>
                                        <span class="text-xs font-semibold {{ $contact->intent_score >= 80 ? 'text-warning-500' : 'text-text-secondary' }}">{{ $contact->intent_score }}% Intent</span>
                                    </div>
                                </div>
                                <div>
                                    <svg class="h-5 w-5 text-text-tertiary group-hover:text-brand-primary transition-colors transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-text-tertiary">
                            No recent contacts.
                        </li>
                    @endforelse
                </ul>
            </div>

            <!-- Task List Shell -->
            <div class="glass-panel rounded-3xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-8 py-6 border-b border-border-default/60">
                    <h2 class="text-lg font-bold text-text-primary">Upcoming Tasks</h2>
                </div>
                <div class="p-8 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-surface-raised border border-border-default/60 rounded-2xl flex items-center justify-center mb-4 shadow-sm">
                        <svg class="w-8 h-8 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <p class="text-sm font-semibold text-text-primary">You're all caught up!</p>
                    <p class="text-xs text-text-secondary mt-1">No pending mandate tasks.</p>
                </div>
            </div>

        </div>
    </div>
</div>
