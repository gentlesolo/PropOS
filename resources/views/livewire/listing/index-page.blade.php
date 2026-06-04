<div class="space-y-6 text-text-primary">
    <!-- Header Block -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-border-default/40 pb-6">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                <span>Property Intelligence Terminal</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-brand-primary/10 text-brand-primary font-mono border border-brand-primary/20">Active Node</span>
            </h1>
            <p class="mt-2 text-text-secondary text-sm">
                Track real estate assets, mandate states, and live portal syndication performance.
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <!-- View Mode Toggles -->
            <div class="flex bg-surface-sunken border border-border-default/60 p-0.5 rounded-lg">
                <button wire:click="setViewMode('grid')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $viewMode === 'grid' ? 'bg-surface-raised text-white shadow-brand-sm' : 'text-text-secondary hover:text-white' }}">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Grid
                </button>
                <button wire:click="setViewMode('list')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $viewMode === 'list' ? 'bg-surface-raised text-white shadow-brand-sm' : 'text-text-secondary hover:text-white' }}">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    List
                </button>
                <button wire:click="setViewMode('map')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $viewMode === 'map' ? 'bg-surface-raised text-white shadow-brand-sm' : 'text-text-secondary hover:text-white' }}">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Map
                </button>
            </div>

            <button wire:click="$set('showCreateModal', true)" class="flex items-center gap-1.5 px-4 py-2 bg-gradient-to-br from-amber-500 to-amber-600 text-black shadow-brand-sm font-semibold text-sm rounded-lg hover:from-amber-400 hover:to-amber-500 transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Listing
            </button>
        </div>
    </div>

    <!-- Quick Stats Terminal Header -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-[#090d16]/80 backdrop-blur-md p-4 rounded-xl border border-border-default/60 shadow-brand hover:border-brand-primary/40 transition-all duration-300">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Active Assets</p>
            <p class="mt-2 text-2xl font-bold text-white font-mono tracking-tight">{{ $activeCount }}</p>
        </div>
        <div class="bg-[#090d16]/80 backdrop-blur-md p-4 rounded-xl border border-border-default/60 shadow-brand hover:border-amber-500/40 transition-all duration-300">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Under Offer</p>
            <p class="mt-2 text-2xl font-bold text-amber-500 font-mono tracking-tight">{{ $underOfferCount }}</p>
        </div>
        <div class="bg-[#090d16]/80 backdrop-blur-md p-4 rounded-xl border border-border-default/60 shadow-brand hover:border-brand-primary/40 transition-all duration-300">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Portfolio Market Value</p>
            <p class="mt-2 text-xl font-bold text-brand-primary font-mono tracking-tight">{{ $currencySymbol }}{{ number_format($totalValue) }}</p>
        </div>
        <div class="bg-[#090d16]/80 backdrop-blur-md p-4 rounded-xl border border-border-default/60 shadow-brand hover:border-rose-500/40 transition-all duration-300">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Avg Days on Market</p>
            <p class="mt-2 text-2xl font-bold font-mono tracking-tight {{ $avgDom > 60 ? 'text-rose-500' : ($avgDom > 30 ? 'text-amber-500' : 'text-emerald-500') }}">
                {{ $avgDom ?: '—' }}d
            </p>
        </div>
    </div>

    <!-- Search & Filters Panel -->
    <div class="bg-[#090d16]/40 backdrop-blur-md border border-border-default/60 rounded-xl p-4 space-y-4">
        <!-- Search bar -->
        <div class="relative w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-text-tertiary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.debounce.300ms="search" type="text"
                placeholder="Search address, suburb, ref number..."
                class="w-full pl-9 pr-4 py-2.5 bg-surface-input border border-border-default/60 rounded-lg text-sm text-white placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:outline-none transition-all">
        </div>

        <!-- Filter bar (All | For Sale | To Let | Sold | Off Market) -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border-default/40 pt-4">
            <div class="flex flex-wrap gap-1">
                @foreach([
                    'all' => 'All Listings',
                    'sale' => 'For Sale',
                    'rental' => 'To Let',
                    'sold' => 'Sold',
                    'off_market' => 'Off Market'
                ] as $val => $label)
                <button wire:click="$set('filterBar', '{{ $val }}')"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-full transition-all border {{ $filterBar === $val ? 'bg-brand-primary/10 text-brand-primary border-brand-primary/30 shadow-[0_0_8px_rgba(16,185,129,0.15)]' : 'bg-transparent text-text-secondary border-transparent hover:text-white' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            <!-- Sub Filters toggler or dropdowns -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Suburb -->
                <select wire:model="suburb" class="px-2 py-1.5 bg-surface-input border border-border-default/60 rounded-md text-xs text-text-secondary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:outline-none">
                    <option value="">All Suburbs</option>
                    @foreach($suburbs as $sub)
                    <option value="{{ $sub }}">{{ $sub }}</option>
                    @endforeach
                </select>

                <!-- Price Range -->
                <div class="flex items-center gap-1">
                    <input wire:model.debounce.300ms="minPrice" type="number" placeholder="Min Price" class="w-20 px-2 py-1.5 bg-surface-input border border-border-default/60 rounded-md text-xs text-text-secondary focus:border-brand-primary focus:outline-none">
                    <span class="text-text-tertiary text-xs">-</span>
                    <input wire:model.debounce.300ms="maxPrice" type="number" placeholder="Max Price" class="w-20 px-2 py-1.5 bg-surface-input border border-border-default/60 rounded-md text-xs text-text-secondary focus:border-brand-primary focus:outline-none">
                </div>

                <!-- Bedrooms -->
                <select wire:model="bedroomsFilter" class="px-2 py-1.5 bg-surface-input border border-border-default/60 rounded-md text-xs text-text-secondary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:outline-none">
                    <option value="">Bedrooms</option>
                    @for($i = 1; $i <= 8; $i++)
                    <option value="{{ $i }}">{{ $i }}+ beds</option>
                    @endfor
                </select>

                <!-- Agent -->
                <select wire:model="agentFilter" class="px-2 py-1.5 bg-surface-input border border-border-default/60 rounded-md text-xs text-text-secondary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:outline-none">
                    <option value="">All Agents</option>
                    @foreach($agents as $agt)
                    <option value="{{ $agt->id }}">{{ $agt->first_name }} {{ $agt->last_name }}</option>
                    @endforeach
                </select>

                <!-- Portal status -->
                <select wire:model="portalStatusFilter" class="px-2 py-1.5 bg-surface-input border border-border-default/60 rounded-md text-xs text-text-secondary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:outline-none">
                    <option value="">Portal Status</option>
                    <option value="synced">Synced (Live)</option>
                    <option value="error">Sync Error</option>
                    <option value="not_synced">Not Synced</option>
                </select>

                @if($search || $filterBar !== 'all' || $suburb || $minPrice || $maxPrice || $bedroomsFilter || $agentFilter || $portalStatusFilter)
                <button wire:click="clearFilters" class="px-2.5 py-1.5 text-xs text-rose-400 hover:text-rose-300 font-medium hover:bg-rose-500/10 rounded-md transition-all">
                    Reset
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- MAIN RENDER AREA (Grid / List / Map) -->
    <div wire:loading.class="opacity-60 pointer-events-none" class="transition-opacity duration-200">
        @if($viewMode === 'grid')
            <!-- 3-4 Column Grid Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($listings as $listing)
                @php
                    $isDraft = $listing->status === 'draft';
                    $dom = $listing->days_on_market ?? ($listing->mandate_start_date ? $listing->mandate_start_date->diffInDays(now()) : 0);
                @endphp
                <div class="group bg-[#090d16] border border-border-default/50 hover:border-brand-primary/30 rounded-xl overflow-hidden shadow-brand flex flex-col justify-between hover:shadow-[0_0_15px_rgba(16,185,129,0.08)] transition-all duration-300 {{ $isDraft ? 'opacity-85' : '' }}">
                    <!-- Photo Header Container -->
                    <div class="relative aspect-[4/3] w-full overflow-hidden bg-[#111827]">
                        <!-- Cover image -->
                        @if($listing->coverPhoto)
                        <img src="{{ asset('storage/' . $listing->coverPhoto->file_path) }}"
                             alt="Property photo"
                             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                        <div class="h-full w-full flex flex-col items-center justify-center text-text-tertiary">
                            <svg class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M21 12l-5.25-5.25L12 9.75"/></svg>
                            <span class="text-xs">No Cover Photo</span>
                        </div>
                        @endif

                        <!-- Draft Watermark -->
                        @if($isDraft)
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center pointer-events-none select-none">
                            <span class="text-white/30 text-3xl font-black tracking-widest uppercase border-4 border-white/20 px-4 py-1 rotate-[-12deg]">DRAFT</span>
                        </div>
                        @endif

                        <!-- Status Badge (Top-left of photo) -->
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                @switch($listing->status)
                                    @case('active') bg-emerald-500/10 text-emerald-400 border-emerald-500/20 @break
                                    @case('under_offer') bg-amber-500/10 text-amber-400 border-amber-500/20 @break
                                    @case('sold') bg-zinc-500/10 text-zinc-400 border-zinc-500/20 @break
                                    @case('draft') bg-amber-500/10 text-amber-400 border-amber-500/20 @break
                                    @default bg-zinc-500/10 text-zinc-400 border-zinc-500/20
                                @endswitch">
                                {{ str_replace('_', ' ', $listing->status) }}
                            </span>
                        </div>

                        <!-- Portal Sync Icons (Top-right of photo) -->
                        <div class="absolute top-3 right-3 flex items-center gap-1.5 bg-black/60 backdrop-blur-sm px-2 py-1 rounded-full border border-white/10">
                            @php
                                $portalSyncs = $listing->portalSyncs;
                            @endphp
                            @forelse($portalSyncs as $sync)
                                <div class="relative group/portal" title="{{ $sync->portal->name }}: {{ $sync->status }}">
                                    <div class="h-2 w-2 rounded-full
                                        @if($sync->status === 'synced') bg-emerald-500
                                        @elseif($sync->status === 'failed') bg-rose-500
                                        @else bg-amber-500 @endif"></div>
                                </div>
                            @empty
                                <span class="text-[9px] text-text-tertiary">No Sync</span>
                            @endforelse
                        </div>

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                            <button wire:click="openListing({{ $listing->id }})" class="px-4 py-2 bg-brand-primary text-black font-semibold text-xs rounded-md shadow-brand hover:bg-brand-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>

                    <!-- Details Area -->
                    <div class="p-4 space-y-3 flex-1 flex flex-col justify-between">
                        <div>
                            <!-- Address and suburb -->
                            <h3 class="text-sm font-bold text-white line-clamp-1 group-hover:text-brand-primary transition-colors cursor-pointer" wire:click="openListing({{ $listing->id }})">
                                {{ $listing->property->address_line_1 }}
                            </h3>
                            <p class="text-xs text-text-tertiary mt-0.5">{{ $listing->property->city }}, {{ $listing->property->state_province }}</p>

                            <!-- Size/Bed/Bath Chips -->
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                @if($listing->property->bedrooms)
                                <span class="inline-flex items-center gap-1 text-[11px] text-text-secondary bg-[#111827] px-2 py-0.5 rounded border border-border-default/40">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    {{ $listing->property->bedrooms }} Beds
                                </span>
                                @endif
                                @if($listing->property->bathrooms)
                                <span class="inline-flex items-center gap-1 text-[11px] text-text-secondary bg-[#111827] px-2 py-0.5 rounded border border-border-default/40">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                    {{ $listing->property->bathrooms }} Baths
                                </span>
                                @endif
                                @if($listing->property->floor_area_sqm)
                                <span class="inline-flex items-center gap-1 text-[11px] text-text-secondary bg-[#111827] px-2 py-0.5 rounded border border-border-default/40">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/></svg>
                                    {{ (int)$listing->property->floor_area_sqm }} sqm
                                </span>
                                @endif
                            </div>
                        </div>

                        <!-- Price & Bottom Info Row -->
                        <div class="border-t border-border-default/40 pt-3 flex items-center justify-between gap-2 mt-2">
                            <!-- Price -->
                            <div>
                                <p class="text-xs text-text-tertiary uppercase tracking-widest text-[9px] font-semibold">Listing Price</p>
                                <span class="text-base font-bold font-mono text-emerald-400">
                                    {{ $currencySymbol }}{{ number_format($listing->listing_price) }}
                                </span>
                            </div>

                            <!-- Agent & Days on Market -->
                            <div class="flex items-center gap-2 text-right">
                                <div>
                                    <p class="text-[10px] text-text-secondary font-medium leading-none">{{ $listing->agent?->first_name ?? 'Agent' }}</p>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full inline-block mt-1 font-semibold border
                                        @if($dom > 60) bg-rose-500/10 text-rose-400 border-rose-500/20
                                        @elseif($dom > 30) bg-amber-500/10 text-amber-400 border-amber-500/20
                                        @else bg-zinc-500/10 text-zinc-400 border-zinc-500/20 @endif">
                                        {{ $dom }} DOM
                                    </span>
                                </div>
                                @if($listing->agent?->profile_photo_url)
                                <img src="{{ $listing->agent->profile_photo_url }}" class="h-7 w-7 rounded-full object-cover border border-border-default/60">
                                @else
                                <div class="h-7 w-7 rounded-full bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-[10px] text-brand-primary font-bold">
                                    {{ substr($listing->agent?->first_name ?? 'A', 0, 1) }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-16 bg-[#090d16]/40 border border-dashed border-border-default/60 rounded-xl text-center">
                    <x-ui.empty-state 
                        icon="search" 
                        title="No Listings Found" 
                        description="Try modifying your active filters or clear them to view all database records." 
                        actionText="Create Listing"
                        actionClick="$set('showCreateModal', true)"
                    />
                </div>
                @endforelse
            </div>

        @elseif($viewMode === 'list')
            <!-- List View Table -->
            <div class="bg-[#090d16]/80 border border-border-default/60 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-default/60">
                        <thead class="bg-surface-sunken/40">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Photo</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Address & suburb</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Price</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Beds/Baths</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">DOM</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Portals</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-text-tertiary uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-default/30 bg-[#090d16]/20">
                            @forelse($listings as $listing)
                            @php
                                $dom = $listing->days_on_market ?? ($listing->mandate_start_date ? $listing->mandate_start_date->diffInDays(now()) : 0);
                            @endphp
                            <tr class="hover:bg-surface-sunken/10 transition-colors group cursor-pointer" wire:click="openListing({{ $listing->id }})">
                                <!-- Photo -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="h-12 w-16 bg-[#111827] rounded-lg overflow-hidden border border-border-default/45">
                                        @if($listing->coverPhoto)
                                        <img src="{{ asset('storage/' . $listing->coverPhoto->file_path) }}" class="h-full w-full object-cover">
                                        @else
                                        <div class="h-full w-full flex items-center justify-center text-text-tertiary">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Address -->
                                <td class="px-6 py-3">
                                    <p class="text-sm font-bold text-white group-hover:text-brand-primary transition-colors truncate max-w-xs">{{ $listing->property->address_line_1 }}</p>
                                    <p class="text-xs text-text-tertiary">{{ $listing->property->city }}, {{ $listing->property->state_province }}</p>
                                </td>

                                <!-- Type -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary capitalize font-medium">{{ str_replace('_', ' ', $listing->mandate_type) }}</span>
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="text-sm font-bold font-mono text-emerald-400">{{ $currencySymbol }}{{ number_format($listing->listing_price) }}</span>
                                </td>

                                <!-- Beds -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary font-mono">{{ $listing->property->bedrooms ?: '—' }} Bds / {{ $listing->property->bathrooms ?: '—' }} Bths</span>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                        @switch($listing->status)
                                            @case('active') bg-emerald-500/10 text-emerald-400 border-emerald-500/20 @break
                                            @case('under_offer') bg-amber-500/10 text-amber-400 border-amber-500/20 @break
                                            @case('sold') bg-zinc-500/10 text-zinc-400 border-zinc-500/20 @break
                                            @case('draft') bg-amber-500/10 text-amber-400 border-amber-500/20 @break
                                            @default bg-zinc-500/10 text-zinc-400 border-zinc-500/20
                                        @endswitch">
                                        {{ str_replace('_', ' ', $listing->status) }}
                                    </span>
                                </td>

                                <!-- Agent -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="text-xs text-text-secondary">{{ $listing->agent?->first_name ?? '—' }}</span>
                                </td>

                                <!-- DOM -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="text-xs font-semibold {{ $dom > 60 ? 'text-rose-400' : ($dom > 30 ? 'text-amber-400' : 'text-emerald-400') }}">
                                        {{ $dom }}d
                                    </span>
                                </td>

                                <!-- Portals -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        @forelse($listing->portalSyncs as $sync)
                                        <div class="h-2 w-2 rounded-full cursor-pointer" title="{{ $sync->portal->name }}: {{ $sync->status }}"
                                            @class([
                                                'bg-emerald-500' => $sync->status === 'synced',
                                                'bg-rose-500' => $sync->status === 'failed',
                                                'bg-amber-500' => !in_array($sync->status, ['synced', 'failed'])
                                            ])></div>
                                        @empty
                                        <span class="text-[10px] text-text-tertiary">—</span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-3 whitespace-nowrap text-right" wire:click.stop>
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="openListing({{ $listing->id }})" class="px-2.5 py-1 bg-brand-primary/10 border border-brand-primary/20 text-brand-primary text-xs font-semibold rounded hover:bg-brand-primary hover:text-black transition-all">
                                            Manage
                                        </button>
                                        @if(in_array($listing->status, ['draft', 'withdrawn', 'expired']))
                                        <button wire:click="deleteListing({{ $listing->id }})" onclick="return confirm('Confirm delete?')" class="p-1 text-text-tertiary hover:text-rose-500 rounded transition-all">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="py-12 text-center text-text-tertiary">No listings found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif($viewMode === 'map')
            <!-- Map View Layout -->
            <div class="flex flex-col lg:flex-row bg-[#090d16] border border-border-default/60 rounded-xl overflow-hidden h-[550px]">
                <!-- Simulated High-Fidelity Dark Map Panel -->
                <div class="flex-1 relative bg-[#02050b] overflow-hidden" x-data="{ activePin: null }">
                    <!-- Map background grid/radar style -->
                    <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(circle, #10b981 1px, transparent 1px); background-size: 24px 24px;"></div>
                    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: linear-gradient(to right, #111827 1px, transparent 1px), linear-gradient(to bottom, #111827 1px, transparent 1px); background-size: 120px 120px;"></div>

                    <!-- Ambient glowing city centers -->
                    <div class="absolute top-[25%] left-[30%] h-40 w-40 rounded-full bg-brand-primary/10 blur-[80px]"></div>
                    <div class="absolute bottom-[20%] right-[25%] h-56 w-56 rounded-full bg-brand-primary/5 blur-[100px]"></div>

                    <!-- Simulated street lines -->
                    <svg class="absolute inset-0 w-full h-full text-[#111827] pointer-events-none stroke-current" fill="none">
                        <path d="M 0 100 L 1000 120 M 200 0 L 250 600 M 0 450 L 1000 400 M 700 0 L 800 600 M 0 250 Q 500 300 1000 220" stroke-width="1" />
                    </svg>

                    <!-- Property Pins on Map -->
                    @php
                        // Deterministic visual offsets for mock positions
                        $pinIndex = 0;
                    @endphp
                    @foreach($listings as $listing)
                        @php
                            $pinIndex++;
                            // Simple coordinate maps within container
                            $top = (($pinIndex * 67) % 70) + 15;
                            $left = (($pinIndex * 113) % 75) + 10;
                            $priceText = $currencySymbol . (round($listing->listing_price / 1000) . 'k');
                        @endphp
                        <!-- Pin dot -->
                        <div class="absolute" style="top: {{ $top }}%; left: {{ $left }}%;"
                             @mouseenter="activePin = {{ $listing->id }}"
                             @mouseleave="activePin = null">
                            <div class="relative cursor-pointer group/pin">
                                <!-- Ping animation -->
                                <div class="absolute -inset-1 rounded-full bg-brand-primary/45 animate-ping opacity-75"></div>
                                <!-- Pin container -->
                                <div class="relative flex items-center gap-1.5 bg-[#030712] border-2 border-brand-primary hover:border-white px-2.5 py-1 rounded-full shadow-[0_0_12px_rgba(16,185,129,0.3)] transition-all">
                                    <div class="h-2 w-2 rounded-full bg-brand-primary"></div>
                                    <span class="text-[10px] font-bold font-mono text-white">{{ $priceText }}</span>
                                </div>

                                <!-- Mini Card Popup on Pin Hover -->
                                <div x-show="activePin === {{ $listing->id }}"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 bg-[#090d16] border border-border-default rounded-xl p-3 shadow-2xl z-20 pointer-events-auto">
                                    <div class="relative rounded-lg overflow-hidden h-28 bg-[#111827] mb-2">
                                        @if($listing->coverPhoto)
                                        <img src="{{ asset('storage/' . $listing->coverPhoto->file_path) }}" class="h-full w-full object-cover">
                                        @else
                                        <div class="h-full w-full flex items-center justify-center text-text-tertiary">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        @endif
                                        <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-bold uppercase rounded bg-brand-primary/10 text-brand-primary border border-brand-primary/20">{{ $listing->status }}</span>
                                    </div>
                                    <h4 class="text-xs font-bold text-white truncate">{{ $listing->property->address_line_1 }}</h4>
                                    <p class="text-[10px] text-text-tertiary">{{ $listing->property->city }}</p>
                                    <div class="flex items-center justify-between border-t border-border-default/40 pt-2 mt-2">
                                        <span class="text-xs font-bold font-mono text-emerald-400">{{ $currencySymbol }}{{ number_format($listing->listing_price) }}</span>
                                        <span wire:click="openListing({{ $listing->id }})" class="text-[10px] text-brand-primary hover:underline font-semibold cursor-pointer">Manage &rarr;</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Map Overlay controls -->
                    <div class="absolute bottom-4 left-4 flex flex-col gap-1 bg-[#090d16]/80 backdrop-blur-sm border border-border-default/50 p-1.5 rounded-lg">
                        <button class="w-7 h-7 text-xs font-bold text-text-secondary hover:text-white bg-surface-raised rounded flex items-center justify-center">+</button>
                        <button class="w-7 h-7 text-xs font-bold text-text-secondary hover:text-white bg-surface-raised rounded flex items-center justify-center">-</button>
                    </div>
                </div>

                <!-- Right Viewport Sidebar (320px) -->
                <div class="w-full lg:w-[320px] bg-[#090d16] border-l border-border-default/50 flex flex-col h-full">
                    <div class="p-4 border-b border-border-default/50">
                        <h3 class="text-sm font-bold text-white">Viewport Results</h3>
                        <p class="text-xs text-text-tertiary mt-0.5">Showing listings in map region</p>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        @forelse($listings as $listing)
                        <div wire:click="openListing({{ $listing->id }})" class="flex items-center gap-3 p-2 rounded-lg bg-surface-raised/40 hover:bg-surface-raised border border-border-default/30 transition-all cursor-pointer group">
                            <div class="h-12 w-12 rounded overflow-hidden bg-[#111827] shrink-0">
                                @if($listing->coverPhoto)
                                <img src="{{ asset('storage/' . $listing->coverPhoto->file_path) }}" class="h-full w-full object-cover">
                                @else
                                <div class="h-full w-full flex items-center justify-center text-text-tertiary">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-white group-hover:text-brand-primary truncate">{{ $listing->property->address_line_1 }}</h4>
                                <p class="text-[10px] text-text-tertiary truncate">{{ $listing->property->city }}</p>
                                <span class="text-xs font-bold font-mono text-emerald-400 mt-1 block">{{ $currencySymbol }}{{ number_format($listing->listing_price) }}</span>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs text-text-tertiary text-center py-8">No viewport assets found.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Pagination controls -->
    @if($listings->hasPages() && $viewMode !== 'map')
    <div class="border-t border-border-default/40 pt-4 mt-6">
        {{ $listings->links() }}
    </div>
    @endif

    <!-- Create Listing Slide-over -->
    @if($showCreateModal)
    <div class="relative z-50" role="dialog" aria-modal="true" x-data="{}">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-lg">
                        <div class="flex h-full flex-col overflow-y-scroll bg-[#090d16] border-l border-border-default shadow-2xl">
                            <!-- Slide-over Header -->
                            <div class="px-6 py-5 border-b border-border-default/60 flex items-center justify-between bg-[#111827]">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-brand-primary"></span>
                                    Register New Asset
                                </h2>
                                <button wire:click="$set('showCreateModal', false)" class="rounded-lg p-1.5 text-text-secondary hover:text-white hover:bg-surface-sunken transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- Slide-over Form Content -->
                            <div class="flex-1 px-6 py-6 space-y-6">
                                <form wire:submit.prevent="saveListing" class="space-y-5">
                                    <div class="space-y-3">
                                        <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-widest border-b border-border-default/40 pb-2">Property Details</p>

                                        <div>
                                            <x-ui.floating-input id="address_line_1" label="Street Address *" model="address_line_1" defer="true" />
                                            @error('address_line_1') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <x-ui.floating-input id="city" label="City *" model="city" defer="true" />
                                                @error('city') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <x-ui.floating-input id="state_province" label="State *" model="state_province" defer="true" />
                                                @error('state_province') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-text-secondary mb-1.5 uppercase tracking-wide">Property Type *</label>
                                            <select wire:model.defer="property_type"
                                                class="w-full rounded-md border border-border-default/60 bg-surface-input px-3 py-2.5 text-sm text-white focus:border-brand-primary focus:outline-none transition-all">
                                                <option value="house">House</option>
                                                <option value="apartment">Apartment</option>
                                                <option value="townhouse">Townhouse</option>
                                                <option value="penthouse">Penthouse</option>
                                                <option value="land">Land</option>
                                                <option value="commercial">Commercial</option>
                                                <option value="office">Office</option>
                                                <option value="warehouse">Warehouse</option>
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <x-ui.floating-input id="bedrooms" type="number" label="Bedrooms" model="bedrooms" defer="true" />
                                            </div>
                                            <div>
                                                <x-ui.floating-input id="bathrooms" type="number" label="Bathrooms" model="bathrooms" defer="true" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3 pt-4">
                                        <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-widest border-b border-border-default/40 pb-2">Mandate & Financials</p>

                                        <div>
                                            <x-ui.floating-input id="listing_price" type="number" label="Listing Price ({{ $currencySymbol }}) *" model="listing_price" defer="true" />
                                            @error('listing_price') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-text-secondary mb-1.5 uppercase tracking-wide">Mandate Type *</label>
                                            <select wire:model.defer="mandate_type"
                                                class="w-full rounded-md border border-border-default/60 bg-surface-input px-3 py-2.5 text-sm text-white focus:border-brand-primary focus:outline-none transition-all">
                                                <option value="sole">Sole Mandate (Sale)</option>
                                                <option value="open">Open Mandate (Sale)</option>
                                                <option value="rental">Rental Mandate</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-border-default/40">
                                        <button type="submit"
                                            class="w-full py-3 bg-gradient-to-br from-amber-500 to-amber-600 text-black shadow-brand-sm font-semibold rounded-md hover:from-amber-400 hover:to-amber-500 transition-all text-sm">
                                            <span wire:loading.remove wire:target="saveListing">Create & Open Listing</span>
                                            <span wire:loading wire:target="saveListing">Registering Asset...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
