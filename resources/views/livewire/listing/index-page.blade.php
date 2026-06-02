<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary dark:text-white">Properties & Listings</h1>
            <p class="mt-2 text-text-secondary dark:text-text-tertiary">Manage mandates, listings, syndication status, and property performance.</p>
        </div>
        <button wire:click="$set('showCreateModal', true)" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg hover:bg-brand-secondary font-medium text-sm transition-colors">
            + New Listing
        </button>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default shadow-sm hover:border-brand-primary/40 transition-colors">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Active Listings</p>
            <p class="mt-2 text-3xl font-bold text-text-primary tabular-nums">{{ $activeCount }}</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default shadow-sm hover:border-info-500/40 transition-colors">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Under Offer</p>
            <p class="mt-2 text-3xl font-bold text-info-600 tabular-nums">{{ $underOfferCount }}</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default shadow-sm hover:border-brand-primary/40 transition-colors">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Active Portfolio Value</p>
            <p class="mt-2 text-2xl font-bold text-text-primary tabular-nums">{{ $currencySymbol }}{{ number_format($totalValue) }}</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default shadow-sm hover:border-success-500/40 transition-colors">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">Avg. Days on Market</p>
            <p class="mt-2 text-3xl font-bold tabular-nums {{ $avgDom > 60 ? 'text-danger-600' : ($avgDom > 30 ? 'text-warning-600' : 'text-success-600') }}">
                {{ $avgDom ?: '—' }}
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-surface-card rounded-2xl overflow-hidden border border-border-default shadow-sm">
        <div class="px-5 py-4 border-b border-border-default flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-surface-sunken/30">
            <div class="relative flex-1 w-full sm:max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.debounce.300ms="search" type="text"
                    placeholder="Search address, city, area..."
                    class="w-full pl-9 pr-3 py-2 border border-border-default bg-surface-input rounded-lg text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select wire:model="filterType" class="px-3 py-2 border border-border-default bg-surface-input rounded-lg text-sm text-text-secondary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="">All Types</option>
                    <option value="sale">Sales</option>
                    <option value="rental">Rentals</option>
                </select>
                <select wire:model="filterStatus" class="px-3 py-2 border border-border-default bg-surface-input rounded-lg text-sm text-text-secondary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="under_offer">Under Offer</option>
                    <option value="sold">Sold</option>
                    <option value="let">Let</option>
                    <option value="withdrawn">Withdrawn</option>
                    <option value="expired">Expired</option>
                </select>
                @if($search || $filterStatus || $filterType)
                <button wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterType', '')"
                    class="text-xs text-text-secondary hover:text-danger-600 border border-border-default rounded-lg px-3 py-2 transition-colors">
                    Clear
                </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-default/60">
                <thead class="bg-surface-sunken/20">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Property</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Price</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider hidden md:table-cell">DOM</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider hidden lg:table-cell">Health</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider hidden lg:table-cell">Agent</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-text-tertiary uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50 pointer-events-none" class="divide-y divide-border-default/60 bg-white/10 transition-opacity duration-200">
                    @forelse($listings as $listing)
                    <tr wire:click="openListing({{ $listing->id }})"
                        class="hover:bg-surface-sunken/20 transition-colors group cursor-pointer">
                        <!-- Property -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 flex-shrink-0 rounded-xl overflow-hidden bg-surface-raised">
                                    @if($listing->coverPhoto)
                                    <img src="{{ asset('storage/' . $listing->coverPhoto->file_path) }}"
                                         alt="cover"
                                         class="h-full w-full object-cover">
                                    @else
                                    <div class="h-full w-full flex items-center justify-center text-text-tertiary">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-text-primary group-hover:text-brand-primary transition-colors truncate">{{ $listing->property->address_line_1 }}</p>
                                    <p class="text-xs text-text-tertiary">{{ $listing->property->city }}, {{ $listing->property->state_province }}</p>
                                    <p class="text-xs text-text-tertiary capitalize mt-0.5">
                                        {{ $listing->property->property_type }}
                                        @if($listing->property->bedrooms) · {{ $listing->property->bedrooms }}bd @endif
                                        @if($listing->property->bathrooms) · {{ $listing->property->bathrooms }}ba @endif
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Price -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="text-sm font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($listing->listing_price) }}</p>
                            <p class="text-xs text-text-tertiary capitalize">{{ str_replace('_', ' ', $listing->mandate_type) }}</p>
                        </td>

                        <!-- Status -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                                @switch($listing->status)
                                    @case('active') bg-success-100 text-success-800 @break
                                    @case('under_offer') bg-info-100 text-info-800 @break
                                    @case('sold') @case('let') bg-brand-primary/10 text-brand-primary @break
                                    @case('draft') bg-surface-sunken text-text-secondary @break
                                    @default bg-surface-sunken text-text-secondary
                                @endswitch">
                                {{ str_replace('_', ' ', $listing->status) }}
                            </span>
                        </td>

                        <!-- DOM -->
                        <td class="px-5 py-4 whitespace-nowrap hidden md:table-cell">
                            @php
                                $dom = $listing->days_on_market ?? ($listing->mandate_start_date ? $listing->mandate_start_date->diffInDays(now()) : null);
                            @endphp
                            @if($dom !== null)
                            <span class="text-sm font-medium {{ $dom > 60 ? 'text-danger-600' : ($dom > 30 ? 'text-warning-600' : 'text-text-primary') }}">
                                {{ $dom }}d
                            </span>
                            @else
                            <span class="text-sm text-text-tertiary">—</span>
                            @endif
                        </td>

                        <!-- Health Score -->
                        <td class="px-5 py-4 whitespace-nowrap hidden lg:table-cell">
                            @if($listing->health_score !== null)
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-surface-raised rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $listing->health_score >= 70 ? 'bg-success-500' : ($listing->health_score >= 40 ? 'bg-warning-500' : 'bg-danger-500') }}"
                                         style="width: {{ $listing->health_score }}%"></div>
                                </div>
                                <span class="text-xs font-medium {{ $listing->health_score >= 70 ? 'text-success-700' : ($listing->health_score >= 40 ? 'text-warning-700' : 'text-danger-700') }}">
                                    {{ $listing->health_score }}
                                </span>
                            </div>
                            @else
                            <span class="text-xs text-text-tertiary">—</span>
                            @endif
                        </td>

                        <!-- Agent -->
                        <td class="px-5 py-4 whitespace-nowrap hidden lg:table-cell">
                            <span class="text-sm text-text-secondary">{{ $listing->agent?->first_name ?? 'Unassigned' }}</span>
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-4 whitespace-nowrap text-right" wire:click.stop>
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openListing({{ $listing->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-brand-primary/30 text-xs font-medium text-brand-primary hover:bg-brand-primary hover:text-white transition-colors">
                                    <span wire:loading.remove wire:target="openListing({{ $listing->id }})">
                                        Manage
                                        <svg class="h-3.5 w-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <span wire:loading wire:target="openListing({{ $listing->id }})">...</span>
                                </button>
                                @if(in_array($listing->status, ['draft', 'withdrawn', 'expired']))
                                <button wire:click="deleteListing({{ $listing->id }})"
                                    onclick="return confirm('Delete this listing? This cannot be undone.')"
                                    class="p-1.5 text-text-tertiary hover:text-danger-600 rounded-lg hover:bg-danger-50 transition-colors" title="Delete listing">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8">
                            <x-ui.empty-state 
                                icon="search" 
                                title="{{ ($search || $filterStatus || $filterType) ? 'No listings match your filters' : 'No listings yet' }}" 
                                description="{{ ($search || $filterStatus || $filterType) ? 'Try adjusting your search or clearing filters.' : 'Get started by creating your first property listing.' }}"
                                actionText="{{ (!$search && !$filterStatus && !$filterType) ? 'Add First Listing' : null }}"
                                actionClick="$set('showCreateModal', true)"
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($listings->hasPages())
        <div class="px-5 py-3 border-t border-border-default bg-surface-sunken/10">
            {{ $listings->links() }}
        </div>
        @endif
    </div>

    <!-- Create Listing Slide-over -->
    @if($showCreateModal)
    <div class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-surface-overlay backdrop-blur-sm"></div>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-lg">
                        <div class="flex h-full flex-col overflow-y-scroll bg-surface-page shadow-xl border-l border-border-default">

                            <div class="px-6 py-5 border-b border-border-default flex items-center justify-between bg-surface-card">
                                <h2 class="text-lg font-bold text-text-primary">Create New Listing</h2>
                                <button wire:click="$set('showCreateModal', false)" class="rounded-lg p-1.5 text-text-secondary hover:text-text-primary hover:bg-surface-sunken transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="flex-1 px-6 py-6">
                                <form wire:submit.prevent="saveListing" class="space-y-5">

                                    <p class="text-xs font-semibold text-text-tertiary uppercase tracking-wider border-b border-border-default pb-2">Property Details</p>

                                    <div>
                                        <x-ui.floating-input id="address_line_1" label="Street Address *" model="address_line_1" defer="true" />
                                        @error('address_line_1') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-ui.floating-input id="city" label="City *" model="city" defer="true" />
                                            @error('city') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <x-ui.floating-input id="state_province" label="State *" model="state_province" defer="true" />
                                            @error('state_province') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Property Type *</label>
                                        <select wire:model.defer="property_type"
                                            class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
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

                                    <p class="text-xs font-semibold text-text-tertiary uppercase tracking-wider border-b border-border-default pb-2 pt-2">Mandate Details</p>

                                    <div>
                                        <x-ui.floating-input id="listing_price" type="number" label="Listing Price ({{ $currencySymbol }}) *" model="listing_price" defer="true" />
                                        @error('listing_price') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Mandate Type *</label>
                                        <select wire:model.defer="mandate_type"
                                            class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                                            <option value="sole">Sole Mandate (Sale)</option>
                                            <option value="open">Open Mandate (Sale)</option>
                                            <option value="rental">Rental Mandate</option>
                                        </select>
                                    </div>

                                    <div class="pt-4 border-t border-border-default">
                                        <button type="submit"
                                            class="w-full py-2.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl font-medium text-sm hover:bg-brand-secondary transition-colors">
                                            <span wire:loading.remove wire:target="saveListing">Create & Open Listing</span>
                                            <span wire:loading wire:target="saveListing">Creating...</span>
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



