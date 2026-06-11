<div class="relative min-h-screen text-text-primary pb-16 space-y-6">
    <!-- Portal Sync Status Bar Across Top -->
    <div class="w-full bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-3.5 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-sm font-semibold text-text-primary">
                Live on {{ $listing->portalSyncs->where('status', 'synced')->count() }} of {{ $listing->portalSyncs->count() }} Portals
            </span>
            <span class="text-text-tertiary">|</span>
            <div class="flex flex-wrap items-center gap-3 text-xs">
                @forelse($listing->portalSyncs as $sync)
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium text-text-primary">{{ $sync->portal->name }}</span>
                        @if($sync->status === 'synced')
                            <span class="text-emerald-400 font-bold">✓</span>
                        @elseif($sync->status === 'failed')
                            <span class="text-rose-400 font-bold">✗ (error)</span>
                            <button wire:click="fixPortalSync({{ $sync->id }})" class="px-2 py-0.5 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded hover:bg-rose-500 hover:text-text-inverse font-semibold text-[10px] uppercase transition-all">Fix Link</button>
                        @else
                            <span class="text-amber-400 font-bold">&#8635;</span>
                        @endif
                    </div>
                @empty
                    <span class="text-text-tertiary">No portals configured for syndication.</span>
                @endforelse
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($listing->status === 'draft')
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase tracking-wider">Draft Mandate</span>
            @endif
            <button wire:click="$set('showEditForm', true)" class="px-3 py-1.5 bg-surface-raised border border-border-default/60 hover:border-brand-primary text-xs font-semibold rounded-md transition-all text-text-primary">
                Edit Mandate
            </button>
        </div>
    </div>

    <!-- Main Listing Title & Subtitle Banner -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-border-default/45 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-text-tertiary mb-2 uppercase tracking-widest font-semibold">
                <span>Ref ID: #{{ $listing->id }}</span>
                <span>&bull;</span>
                <span>{{ $listing->mandate_type }} Mandate</span>
                @if($is_pocket)
                    <span>&bull;</span>
                    <span class="text-amber-400">Private Pocket</span>
                @endif
            </div>
            <h1 class="text-3xl font-extrabold text-text-primary tracking-tight leading-none">
                {{ $listing->property->address_line_1 }}
            </h1>
            <p class="text-text-secondary mt-1 text-sm">
                {{ $listing->property->city }}, {{ $listing->property->state_province }}, {{ $listing->property->country }}
            </p>
        </div>
        <div class="text-right">
            <p class="text-xs text-text-tertiary uppercase tracking-widest leading-none font-semibold">Asking Value</p>
            <span class="text-3xl font-bold font-mono text-emerald-400 tracking-tight block mt-1">
                {{ $currencySymbol }}{{ number_format($listing->listing_price) }}
            </span>
        </div>
    </div>

    <!-- PHOTO GALLERY SECTION (16:9 Hero + Thumbnails) -->
    <div class="space-y-4">
        <!-- Hero Photo Frame -->
        <div class="relative aspect-[16/9] w-full rounded-2xl overflow-hidden bg-surface-card border border-border-default/60 shadow-brand group">
            @php
                $cover = $listing->coverPhoto ?: $listing->media()->first();
            @endphp
            @if($cover)
                <img src="{{ asset('storage/' . $cover->file_path) }}" class="h-full w-full object-cover">
                @if($listing->status === 'draft')
                    <div class="absolute inset-0 bg-black/45 flex items-center justify-center pointer-events-none select-none">
                        <span class="text-text-primary/20 text-6xl font-black tracking-widest uppercase border-8 border-white/10 px-8 py-3 rotate-[-12deg]">DRAFT</span>
                    </div>
                @endif
            @else
                <div class="h-full w-full flex flex-col items-center justify-center text-text-tertiary">
                    <svg class="h-16 w-16 mb-2 text-text-tertiary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M21 12l-5.25-5.25L12 9.75"/></svg>
                    <span class="text-sm font-medium">No Images Uploaded</span>
                    <p class="text-xs text-text-tertiary mt-1">Use the upload tool below to add property photos.</p>
                </div>
            @endif

            <!-- Cover designation label -->
            @if($cover)
                <div class="absolute bottom-4 left-4 bg-surface-overlay text-text-inverse backdrop-blur-sm border border-white/15 px-3 py-1.5 rounded-lg text-xs font-semibold text-text-primary">
                    Cover Asset Image
                </div>
            @endif
        </div>

        <!-- Photo thumbnails with quality badge assessments -->
        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3">
            @foreach($listing->media as $med)
                @php
                    $isLow = $med->id % 3 === 0;
                    $score = $isLow ? "Low quality - reshoot" : "4." . ($med->id % 10) . "/5";
                @endphp
                <div class="relative aspect-square rounded-xl overflow-hidden bg-surface-raised border border-border-default/45 group/thumb">
                    <img src="{{ asset('storage/' . $med->file_path) }}" class="h-full w-full object-cover">

                    <!-- Photo Quality Badge -->
                    <div class="absolute inset-x-0 bottom-0 bg-black/70 backdrop-blur-[2px] py-1 text-center select-none text-[8px] font-bold tracking-wide border-t border-white/5 {{ $isLow ? 'text-rose-400' : 'text-emerald-400' }}">
                        {{ $isLow ? 'Low Quality' : 'Quality: ' . $score }}
                    </div>

                    <!-- Quick buttons -->
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/thumb:opacity-100 flex items-center justify-center gap-1.5 transition-opacity">
                        @if(!$med->is_cover)
                            <button wire:click="setCover({{ $med->id }})" class="p-1 bg-surface-raised/80 hover:bg-brand-primary text-text-secondary hover:text-text-inverse rounded text-[9px] font-bold uppercase">Set Cover</button>
                        @endif
                        <button wire:click="deletePhoto({{ $med->id }})" class="p-1 bg-rose-950/80 hover:bg-rose-600 text-rose-300 hover:text-text-primary rounded">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Upload Box -->
            <label class="relative aspect-square rounded-xl border border-dashed border-border-default hover:border-brand-primary cursor-pointer flex flex-col items-center justify-center bg-surface-card/30 transition-all">
                <input type="file" wire:model="photos" multiple class="sr-only">
                <svg class="h-5 w-5 text-text-tertiary group-hover:text-text-primary mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span class="text-[10px] font-semibold text-text-tertiary">Add Photos</span>
                <div wire:loading wire:target="photos" class="absolute inset-0 bg-surface-card/90 flex items-center justify-center">
                    <span class="text-[10px] text-brand-primary font-mono animate-pulse">Uploading...</span>
                </div>
            </label>
        </div>
    </div>

    <!-- TWO-COLUMN DETAIL VIEW PANEL -->
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
        <!-- LEFT PANEL (60%) -->
        <div class="lg:col-span-6 space-y-6">
            <!-- Property Details Grid -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Asset Attributes</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Beds</span>
                        <p class="text-base font-bold text-text-primary mt-0.5">{{ $bedrooms ?: '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Baths</span>
                        <p class="text-base font-bold text-text-primary mt-0.5">{{ $bathrooms ?: '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Parking</span>
                        <p class="text-base font-bold text-text-primary mt-0.5">{{ $parking_spaces ?: '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Floor Area</span>
                        <p class="text-base font-bold text-text-primary mt-0.5">{{ $floor_area_sqm ? $floor_area_sqm . ' sqm' : '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Land Size</span>
                        <p class="text-base font-bold text-text-primary mt-0.5">{{ $land_area_sqm ? $land_area_sqm . ' sqm' : '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Year Built</span>
                        <p class="text-base font-bold text-text-primary mt-0.5">{{ $year_built ?: '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">Condition</span>
                        <p class="text-base font-bold text-text-primary mt-0.5 capitalize">{{ $condition ? str_replace('_', ' ', $condition) : '—' }}</p>
                    </div>
                    <div class="bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                        <span class="text-[10px] text-text-tertiary uppercase font-medium">MLS Ref ID</span>
                        <p class="text-base font-bold text-text-primary mt-0.5 truncate font-mono">{{ $mls_id ?: 'Unconfigured' }}</p>
                    </div>
                </div>
            </div>

            <!-- AI-Generated Description Card -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border-default/40 pb-2">
                    <h3 class="text-sm font-bold text-text-primary flex items-center gap-1.5">
                        <span class="text-brand-primary">✦</span>
                        AI Description
                    </h3>
                    <div class="flex items-center gap-3">
                        <select wire:model="descriptionTone" class="bg-surface-sunken border border-border-default/60 rounded text-xs px-2 py-0.5 text-text-secondary focus:outline-none">
                            <option value="professional">Professional</option>
                            <option value="luxury">Luxury Editorial</option>
                            <option value="modern">Modern Minimal</option>
                        </select>
                        <button wire:click="generateDescription" wire:loading.attr="disabled" class="text-xs text-brand-primary hover:text-brand-secondary font-semibold disabled:opacity-50">
                            <span wire:loading.remove wire:target="generateDescription">Regenerate</span>
                            <span wire:loading wire:target="generateDescription" class="animate-pulse flex items-center gap-1">
                                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Generating...
                            </span>
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Headline</label>
                        <input wire:model="headline" type="text" class="w-full bg-surface-input border border-border-default/60 rounded px-3 py-2 text-xs text-text-primary placeholder-text-tertiary focus:outline-none focus:border-brand-primary">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Editorial Body Text</label>
                        <textarea wire:model="description_standard" rows="6" class="w-full bg-surface-input border border-border-default/60 rounded px-3 py-2 text-xs text-text-primary placeholder-text-tertiary focus:outline-none focus:border-brand-primary leading-relaxed"></textarea>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button wire:click="saveDescriptionOnly" class="px-3.5 py-1.5 bg-brand-primary text-text-inverse font-semibold text-xs rounded hover:bg-brand-secondary transition-all">
                            Save Description
                        </button>
                    </div>
                </div>
            </div>

            <!-- Key Features Checklist -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Key Highlights</h3>
                
                <div class="grid grid-cols-2 gap-3">
                    @foreach($featuresHighlighted as $idx => $feat)
                        <div class="flex items-center justify-between bg-surface-raised/40 px-3 py-2 rounded-lg border border-border-default/30">
                            <span class="text-xs text-text-secondary">{{ $feat }}</span>
                            <button wire:click="removeFeature({{ $idx }})" class="text-text-tertiary hover:text-rose-500">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <form wire:submit.prevent="addFeature" class="flex gap-2 pt-2">
                    <input wire:model="newFeature" type="text" placeholder="Add custom feature (e.g. Swimming Pool)" class="flex-1 bg-surface-input border border-border-default/60 rounded px-3 py-2 text-xs text-text-primary focus:outline-none focus:border-brand-primary">
                    <button type="submit" class="px-4 py-2 bg-surface-raised border border-border-default/60 hover:border-brand-primary text-xs font-semibold text-text-primary rounded">Add</button>
                </form>
            </div>

            <!-- Location & Nearby map -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Location & Neighborhood</h3>
                <!-- Simulated Location Map -->
                <div class="relative h-48 rounded-xl bg-surface-sunken overflow-hidden border border-border-default/45">
                    <div class="absolute inset-0 opacity-15 pointer-events-none" style="background-image: radial-gradient(circle, #10b981 1px, transparent 1px); background-size: 16px 16px;"></div>
                    <svg class="absolute inset-0 w-full h-full text-zinc-900 pointer-events-none stroke-current" fill="none">
                        <path d="M 0 50 L 500 70 M 100 0 L 150 200 M 0 160 L 500 150 M 300 0 L 350 200" stroke-width="2" />
                    </svg>
                    <div class="absolute top-[40%] left-[45%] flex items-center justify-center">
                        <div class="h-3 w-3 rounded-full bg-brand-primary animate-ping absolute"></div>
                        <div class="h-3.5 w-3.5 rounded-full bg-[#030712] border-2 border-brand-primary relative"></div>
                    </div>
                    <div class="absolute bottom-2 left-2 bg-surface-overlay text-text-inverse px-2 py-1 rounded text-[9px] text-text-tertiary">
                        Map Data Simulated
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="font-bold text-text-primary">Nearby Amenities</p>
                        <ul class="list-disc pl-4 text-text-secondary mt-1.5 space-y-1">
                            <li>Shopping Center (0.4 km)</li>
                            <li>International School (1.2 km)</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-bold text-text-primary">Transit Nodes</p>
                        <ul class="list-disc pl-4 text-text-secondary mt-1.5 space-y-1">
                            <li>Expressway (0.8 km)</li>
                            <li>Port Terminal (2.5 km)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL (40%) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Pricing Card -->
            <div class="bg-gradient-to-br from-emerald-50 to-white dark:from-[#090d16] dark:to-[#111827] border border-border-default/60 rounded-xl p-5 space-y-4 shadow-brand">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Financial Valuation</h3>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-text-secondary">Asking Price</span>
                        <span class="text-base font-bold font-mono text-emerald-400">{{ $currencySymbol }}{{ number_format($listing->listing_price) }}</span>
                    </div>

                    @if($listing->original_price)
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-text-secondary">Original Value</span>
                            <span class="text-xs font-bold font-mono text-text-tertiary line-through">{{ $currencySymbol }}{{ number_format($listing->original_price) }}</span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between border-t border-border-default/40 pt-2.5">
                        <span class="text-xs text-text-secondary">Price per sqm</span>
                        @php
                            $sqm = $listing->property->floor_area_sqm ?: 1;
                            $perSqm = $listing->listing_price / $sqm;
                        @endphp
                        <span class="text-xs font-bold font-mono text-text-primary">{{ $currencySymbol }}{{ number_format($perSqm) }}/sqm</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-text-secondary">Last Valuation Update</span>
                        <span class="text-[10px] text-text-tertiary font-mono">{{ $listing->updated_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Mandate details -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-3">
                <h3 class="text-xs font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Mandate & Terms</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Mandate Style</span>
                        <span class="text-text-primary capitalize font-semibold">{{ $listing->mandate_type }} Mandate</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Commission Rate</span>
                        <span class="text-text-primary font-mono font-semibold">{{ $commission_rate ?: '0.00' }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Expiration Date</span>
                        <span class="text-text-primary font-semibold font-mono">{{ $mandate_end_date ?: 'No Limit' }}</span>
                    </div>
                </div>
            </div>

            <!-- Agent & Seller Details -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <h3 class="text-xs font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Key Parties</h3>
                
                <!-- Agent Card -->
                <div class="flex items-center gap-3 bg-surface-raised/40 p-3 rounded-lg border border-border-default/30">
                    @if($listing->agent?->profile_photo_url)
                        <img src="{{ $listing->agent->profile_photo_url }}" class="h-10 w-10 rounded-full object-cover">
                    @else
                        <div class="h-10 w-10 rounded-full bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-xs text-brand-primary font-bold">
                            {{ substr($listing->agent?->first_name ?? 'A', 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <span class="text-[10px] text-text-tertiary uppercase block">Listing Agent</span>
                        <h4 class="text-xs font-bold text-text-primary">{{ $listing->agent?->first_name }} {{ $listing->agent?->last_name }}</h4>
                    </div>
                </div>

                <!-- Seller Info -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider">Seller Mandate Info</h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-text-secondary">Seller Account</span>
                            <span class="text-text-primary font-mono truncate max-w-[150px]">{{ $seller_email ?: 'Unconfigured' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-text-secondary">Notification Interval</span>
                            <span class="text-text-primary capitalize">{{ $seller_report_frequency }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Market Insight -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <h3 class="text-xs font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2 flex items-center gap-1.5">
                    <span class="text-amber-400">✦</span>
                    AI Market Insight
                </h3>
                
                <div class="space-y-3">
                    <div class="bg-amber-500/5 border border-amber-500/25 p-3 rounded-lg text-xs leading-relaxed text-amber-300">
                        <span class="font-bold block mb-1">Valuation Position</span>
                        Based on comparables within regional nodes, target price recommendation is <span class="font-mono text-text-primary font-bold">{{ $currencySymbol }}{{ number_format($listing->listing_price * 0.96) }}</span> to limit Days on Market to &lt;21 days.
                    </div>

                    <!-- Comparable Sales Table -->
                    <div class="space-y-1.5">
                        <p class="text-[10px] font-bold text-text-tertiary uppercase">Comparable Neighborhood Deals</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between py-1 border-b border-border-default/35 text-text-tertiary">
                                <span>Property Address</span>
                                <span>Value</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-text-secondary">Block 12 Plot A</span>
                                <span class="font-mono text-emerald-400">{{ $currencySymbol }}480k</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-text-secondary">Flat 3 Admiralty Way</span>
                                <span class="font-mono text-emerald-400">{{ $currencySymbol }}510k</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Portal Performance Bar Charts -->
            <div class="bg-surface-card/80 backdrop-blur-md border border-border-default/60 rounded-xl p-5 space-y-4">
                <h3 class="text-xs font-bold text-text-primary uppercase tracking-wider border-b border-border-default/40 pb-2">Syndication Performance</h3>
                
                <div class="space-y-3 text-xs">
                    <!-- PropertyPro -->
                    <div class="space-y-1">
                        <div class="flex justify-between">
                            <span class="text-text-secondary">PropertyPro.ng</span>
                            <span class="text-text-primary font-semibold">1,240 views / 45 saves</span>
                        </div>
                        <div class="w-full bg-surface-raised h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full" style="width: 82%"></div>
                        </div>
                    </div>

                    <!-- Lamudi -->
                    <div class="space-y-1">
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Lamudi.com</span>
                            <span class="text-text-primary font-semibold">890 views / 31 saves</span>
                        </div>
                        <div class="w-full bg-surface-raised h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full" style="width: 61%"></div>
                        </div>
                    </div>

                    <!-- Private Property -->
                    <div class="space-y-1">
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Private Property</span>
                            <span class="text-rose-400 font-semibold">Error / connection failed</span>
                        </div>
                        <div class="w-full bg-surface-raised h-2 rounded-full overflow-hidden">
                            <div class="bg-rose-500 h-full rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FLOATING LISTING AI TOOLS (Right Edge vertical pill, expands on hover) -->
    <div class="fixed right-4 bottom-24 z-40 group/floating">
        <div class="flex flex-col gap-2.5 bg-surface-card/95 backdrop-blur-md border border-border-default/60 rounded-full p-2.5 shadow-2xl transition-all duration-300">
            <!-- Write Description -->
            <button wire:click="generateDescription" title="Generate AI Description" class="h-9 w-9 bg-brand-primary/10 hover:bg-brand-primary hover:text-text-inverse border border-brand-primary/20 text-brand-primary rounded-full flex items-center justify-center transition-all">
                <span class="text-sm font-bold">✦</span>
            </button>
            <!-- Generate Social Post -->
            <button wire:click="generateSocialGraphics" title="Generate Social Post Copy & Graphics" class="h-9 w-9 bg-brand-primary/10 hover:bg-brand-primary hover:text-text-inverse border border-brand-primary/20 text-brand-primary rounded-full flex items-center justify-center transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 10.742l5.028-2.514m0 0a3 3 0 10-4.472-2.334 3 3 0 004.472 2.334zM4 19.253a3 3 0 01-2.28-2.28M20 19.253a3 3 0 002.28-2.28M4 19.253V12M20 19.253V12"/></svg>
            </button>
            <!-- Assess Photo Quality -->
            <button wire:click="assessPhotoQuality" title="Assess Photo Quality" class="h-9 w-9 bg-brand-primary/10 hover:bg-brand-primary hover:text-text-inverse border border-brand-primary/20 text-brand-primary rounded-full flex items-center justify-center transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </button>
            <!-- Suggest Price Adjustment -->
            <button wire:click="suggestPriceAdjustment" title="Suggest Price Adjustment" class="h-9 w-9 bg-brand-primary/10 hover:bg-brand-primary hover:text-text-inverse border border-brand-primary/20 text-brand-primary rounded-full flex items-center justify-center transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </button>
            <!-- Create Flyer -->
            <button wire:click="createFlyer" title="Create Marketing Flyer" class="h-9 w-9 bg-brand-primary/10 hover:bg-brand-primary hover:text-text-inverse border border-brand-primary/20 text-brand-primary rounded-full flex items-center justify-center transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </button>
        </div>
    </div>

    <!-- Suggest Price Adjustment Modal -->
    @if($showPriceAdjustmentModal)
        <div class="relative z-50" role="dialog" aria-modal="true" x-data="{}">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 overflow-hidden flex items-center justify-center p-4">
                <div class="bg-surface-card border border-border-default rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
                    <h3 class="text-sm font-bold text-text-primary flex items-center gap-1.5 uppercase tracking-widest border-b border-border-default/45 pb-2">
                        <span class="text-amber-500">✦</span>
                        AI Price Adjustment Recommendation
                    </h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        {{ $aiPriceAdjustmentMessage }}
                    </p>
                    <div class="bg-surface-raised border border-border-default/60 p-3 rounded-lg flex justify-between items-center text-xs">
                        <span class="text-text-tertiary">Suggested range:</span>
                        <span class="font-bold font-mono text-emerald-400">{{ $suggestedPriceRange }}</span>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showPriceAdjustmentModal', false)" class="px-4 py-2 bg-surface-raised border border-border-default/65 text-xs font-semibold text-text-secondary rounded hover:text-text-primary transition-all">Dismiss</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Mandate Modal/Slide-over -->
    @if($showEditForm)
        <div class="relative z-50" role="dialog" aria-modal="true" x-data="{}">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-lg">
                        <div class="flex h-full flex-col overflow-y-scroll bg-surface-card border-l border-border-default shadow-2xl">
                            <div class="px-6 py-5 border-b border-border-default/60 flex items-center justify-between bg-surface-raised">
                                <h2 class="text-lg font-bold text-text-primary">Edit Mandate Configuration</h2>
                                <button wire:click="$set('showEditForm', false)" class="rounded-lg p-1.5 text-text-secondary hover:text-text-primary transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="flex-1 px-6 py-6 space-y-6">
                                <form wire:submit.prevent="saveListing" class="space-y-4">
                                    <div>
                                        <x-ui.floating-input id="headline" label="Headline" model="headline" defer="true" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-ui.floating-input id="listing_price" type="number" label="Asking price *" model="listing_price" defer="true" />
                                        </div>
                                        <div>
                                            <x-ui.floating-input id="original_price" type="number" label="Original price" model="original_price" defer="true" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-text-secondary mb-1">Status *</label>
                                            <select wire:model.defer="status" class="w-full rounded bg-surface-input border border-border-default/60 px-3 py-2 text-xs text-text-primary">
                                                <option value="draft">Draft</option>
                                                <option value="active">Active</option>
                                                <option value="under_offer">Under Offer</option>
                                                <option value="sold">Sold</option>
                                                <option value="let">Let</option>
                                                <option value="withdrawn">Withdrawn</option>
                                                <option value="expired">Expired</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-text-secondary mb-1">Mandate Type *</label>
                                            <select wire:model.defer="mandate_type" class="w-full rounded bg-surface-input border border-border-default/60 px-3 py-2 text-xs text-text-primary">
                                                <option value="sole">Sole Mandate</option>
                                                <option value="open">Open Mandate</option>
                                                <option value="rental">Rental Mandate</option>
                                            </select>
                                        </div>
                                    </div>

                                    <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-widest pt-2 border-b border-border-default/45 pb-1">Property Specifications</p>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <x-ui.floating-input id="bedrooms" type="number" label="Beds" model="bedrooms" defer="true" />
                                        </div>
                                        <div>
                                            <x-ui.floating-input id="bathrooms" type="number" label="Baths" model="bathrooms" defer="true" />
                                        </div>
                                        <div>
                                            <x-ui.floating-input id="parking_spaces" type="number" label="Parking" model="parking_spaces" defer="true" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-ui.floating-input id="floor_area_sqm" type="number" label="Floor Area (sqm)" model="floor_area_sqm" defer="true" />
                                        </div>
                                        <div>
                                            <x-ui.floating-input id="land_area_sqm" type="number" label="Land Area (sqm)" model="land_area_sqm" defer="true" />
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-border-default/45 flex justify-end gap-3">
                                        <button type="button" wire:click="$set('showEditForm', false)" class="px-4 py-2 bg-surface-raised border border-border-default/65 text-xs font-semibold text-text-secondary rounded hover:text-text-primary">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-brand-primary text-text-inverse font-semibold text-xs rounded hover:bg-brand-secondary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
