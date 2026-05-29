<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary dark:text-white">Properties & Listings</h1>
            <p class="mt-2 text-text-secondary dark:text-text-tertiary">Manage mandates, listings, syndication status, and property performance.</p>
        </div>
        <div class="flex space-x-3">
            <button class="px-4 py-2 border border-border-strong rounded-lg bg-white text-text-secondary hover:bg-surface-page font-medium text-sm transition-colors">
                Map View
            </button>
            <button wire:click="$set('showCreateModal', true)" class="px-4 py-2 bg-brand-primary text-white rounded-lg hover:bg-brand-secondary font-medium text-sm transition-colors hover-spring">
                + New Listing
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-text-tertiary dark:text-text-tertiary">Active Listings</h3>
            <p class="mt-2 text-3xl font-bold text-text-primary dark:text-white">{{ $activeCount }}</p>
        </div>
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-text-tertiary dark:text-text-tertiary">Under Offer</h3>
            <p class="mt-2 text-3xl font-bold text-info-600 dark:text-info-400">{{ $underOfferCount }}</p>
        </div>
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-text-tertiary dark:text-text-tertiary">Total Value (Active)</h3>
            <p class="mt-2 text-3xl font-bold text-text-primary dark:text-white">₦{{ number_format($totalValue, 2) }}</p>
        </div>
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-text-tertiary dark:text-text-tertiary">Avg. Days on Market</h3>
            <p class="mt-2 text-3xl font-bold text-text-primary dark:text-white">0</p>
        </div>
    </div>

    <!-- Listings Table Shell -->
    <div class="glass-panel rounded-2xl overflow-hidden border border-border-default/60 shadow-sm">
        <div class="px-6 py-4 border-b border-border-default/60 flex items-center justify-between bg-surface-sunken/30">
            <div class="flex items-center space-x-2 w-1/3">
                <input type="text" placeholder="Search by address, reference, or agent..." class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white/50 focus:ring-2 focus:ring-brand-primary focus:border-brand-primary text-sm">
            </div>
            <div class="flex space-x-2">
                <select class="px-3 py-2 border border-border-strong rounded-lg bg-white text-text-secondary text-sm">
                    <option>All Types</option>
                    <option>Sales</option>
                    <option>Rentals</option>
                </select>
                <select class="px-3 py-2 border border-border-strong rounded-lg bg-white text-text-secondary text-sm">
                    <option>All Statuses</option>
                    <option>Draft</option>
                    <option>Active</option>
                    <option>Under Offer</option>
                    <option>Sold/Let</option>
                </select>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-default/60">
                <thead class="bg-surface-sunken/20">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Property Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Agent</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Portals</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-text-tertiary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60 bg-white/10">
                    @forelse($listings as $listing)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-surface-raised rounded-lg overflow-hidden flex items-center justify-center text-text-tertiary">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-text-primary dark:text-white">{{ $listing->property->address_line_1 }}</div>
                                        <div class="text-sm text-text-tertiary">{{ $listing->property->city }}, {{ $listing->property->state_province }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-text-primary dark:text-white">₦{{ number_format($listing->listing_price) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-surface-sunken text-text-primary uppercase tracking-wider">
                                    {{ $listing->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-tertiary">
                                {{ $listing->agent ? $listing->agent->first_name : 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-text-tertiary border border-border-default rounded px-2 py-1">Internal</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="#" class="text-brand-primary hover:text-brand-secondary">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <!-- Empty State -->
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-12 w-12 rounded-full bg-brand-primary/10 flex items-center justify-center mb-3">
                                        <span class="text-xl">🏠</span>
                                    </div>
                                    <h3 class="text-sm font-medium text-text-primary dark:text-white">No listings found</h3>
                                    <p class="mt-1 text-sm text-text-tertiary">Get started by creating a new property listing.</p>
                                    <button wire:click="$set('showCreateModal', true)" class="mt-4 px-4 py-2 bg-brand-primary text-white rounded-lg hover:bg-brand-secondary font-medium text-sm transition-colors">
                                        + Add First Listing
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-3 border-t border-border-default/60">
            {{ $listings->links() }}
        </div>
    </div>

    <!-- Create Listing Modal Slide-over -->
    @if($showCreateModal)
    <div class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-surface-overlay backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-lg">
                        <div class="flex h-full flex-col overflow-y-scroll bg-surface-page shadow-xl border-l border-border-default/60">
                            <div class="bg-surface-card px-4 py-6 sm:px-6 border-b border-border-default/60 flex items-center justify-between">
                                <h2 class="text-xl font-bold text-text-primary" id="slide-over-title">Create New Listing</h2>
                                <button wire:click="$set('showCreateModal', false)" type="button" class="rounded-md text-text-secondary hover:text-text-primary focus:outline-none">
                                    <span class="sr-only">Close panel</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="relative flex-1 px-4 py-6 sm:px-6">
                                <form wire:submit.prevent="saveListing" class="space-y-5">
                                    
                                    <h3 class="text-md font-semibold text-text-primary border-b border-border-default/60 pb-2">Property Details</h3>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Address Line 1 *</label>
                                        <input wire:model.defer="address_line_1" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                        @error('address_line_1') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-primary mb-1">City *</label>
                                            <input wire:model.defer="city" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            @error('city') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-primary mb-1">State/Province *</label>
                                            <input wire:model.defer="state_province" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            @error('state_province') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Property Type *</label>
                                        <select wire:model.defer="property_type" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            <option value="house">House</option>
                                            <option value="apartment">Apartment</option>
                                            <option value="townhouse">Townhouse</option>
                                            <option value="commercial">Commercial</option>
                                            <option value="land">Land</option>
                                        </select>
                                        @error('property_type') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <h3 class="text-md font-semibold text-text-primary border-b border-border-default/60 pb-2 pt-4">Listing Details</h3>

                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Listing Price (₦) *</label>
                                        <input wire:model.defer="listing_price" type="number" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                        @error('listing_price') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Mandate Type *</label>
                                        <select wire:model.defer="mandate_type" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            <option value="sole">Sole Mandate (Sale)</option>
                                            <option value="open">Open Mandate (Sale)</option>
                                            <option value="rental">Rental Mandate</option>
                                        </select>
                                        @error('mandate_type') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="pt-6 border-t border-border-default/60">
                                        <button type="submit" class="w-full px-4 py-2 bg-brand-primary text-white rounded-lg hover:bg-brand-secondary font-medium transition-colors hover-spring flex justify-center items-center">
                                            <span wire:loading.remove wire:target="saveListing">Save Draft Listing</span>
                                            <span wire:loading wire:target="saveListing">Saving...</span>
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
