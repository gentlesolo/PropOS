<div>
    <!-- Breadcrumb -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('listing.index') }}" class="text-text-tertiary hover:text-brand-primary text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Listings
        </a>
        <span class="text-text-tertiary">/</span>
        <span class="text-sm text-text-secondary font-medium truncate">{{ $listing->property->address_line_1 }}</span>
    </div>

    <!-- Alert Banners -->
    @if($mandateExpired)
    <div class="mb-5 flex items-center gap-3 p-4 rounded-2xl border border-danger-300 bg-danger-50">
        <svg class="h-5 w-5 text-danger-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-medium text-danger-800">Mandate expired on {{ $listing->mandate_end_date->format('d M Y') }}. Contact the seller to renew.</p>
    </div>
    @elseif($mandateExpiringSoon)
    <div class="mb-5 flex items-center gap-3 p-4 rounded-2xl border border-warning-300 bg-warning-50">
        <svg class="h-5 w-5 text-warning-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-medium text-warning-800">Mandate expires {{ $listing->mandate_end_date->diffForHumans() }} ({{ $listing->mandate_end_date->format('d M Y') }}). Consider renewal.</p>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- ── Left: Main Content ───────────────────────────────────── -->
        <div class="xl:col-span-2 space-y-5">

            <!-- Header Card -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="min-w-0 flex-1 pr-4">
                        <h1 class="text-2xl font-extrabold text-text-primary leading-tight">{{ $listing->property->address_line_1 }}</h1>
                        <p class="text-sm text-text-secondary mt-0.5">{{ $listing->property->city }}, {{ $listing->property->state_province }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                                @switch($listing->status)
                                    @case('active') bg-success-100 text-success-800 @break
                                    @case('under_offer') bg-info-100 text-info-800 @break
                                    @case('sold') @case('let') bg-brand-primary/10 text-brand-primary @break
                                    @default bg-surface-sunken text-text-secondary
                                @endswitch">
                                {{ str_replace('_', ' ', $listing->status) }}
                            </span>
                            <span class="text-xs text-text-secondary capitalize">{{ $listing->mandate_type }} mandate</span>
                            @if($listing->health_score !== null)
                            <a href="{{ route('analytics.listing-health') }}" class="text-xs font-medium
                                {{ $listing->health_score >= 70 ? 'text-success-700' : ($listing->health_score >= 40 ? 'text-warning-700' : 'text-danger-700') }}">
                                Health: {{ $listing->health_score }}/100
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-2xl font-bold text-text-primary">₦{{ number_format($listing->listing_price) }}</p>
                        @if($priceReduced)
                        <p class="text-xs text-danger-600 line-through">₦{{ number_format($listing->original_price) }}</p>
                        <p class="text-xs text-success-600 font-medium">Price reduced {{ round((1 - $listing->listing_price / $listing->original_price) * 100) }}%</p>
                        @endif
                        <button wire:click="$toggle('showEditForm')" class="mt-2 text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-3 py-1.5 hover:bg-brand-primary/5 transition-colors">
                            {{ $showEditForm ? 'Cancel' : 'Edit Listing' }}
                        </button>
                    </div>
                </div>

                @if($showEditForm)
                <form wire:submit.prevent="saveListing" class="space-y-4 border-t border-border-default/60 pt-4">
                    <!-- Price & Status -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Listing Price (₦) *</label>
                            <input wire:model.defer="listing_price" type="number" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            @error('listing_price') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Original Price (₦)</label>
                            <input wire:model.defer="original_price" type="number" placeholder="Leave blank if no reduction" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                            <select wire:model.defer="status" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
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
                            <label class="block text-xs font-medium text-text-secondary mb-1">Mandate Type</label>
                            <select wire:model.defer="mandate_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                <option value="sole">Sole (Sale)</option>
                                <option value="open">Open (Sale)</option>
                                <option value="rental">Rental</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Commission (%)</label>
                            <input wire:model.defer="commission_rate" type="number" step="0.1" min="0" max="100" placeholder="5.0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Mandate End Date</label>
                        <input wire:model.defer="mandate_end_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <!-- Property specs -->
                    <p class="text-xs font-semibold text-text-tertiary uppercase tracking-wider pt-1">Property Specifications</p>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Beds</label>
                            <input wire:model.defer="bedrooms" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Baths</label>
                            <input wire:model.defer="bathrooms" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Parking</label>
                            <input wire:model.defer="parking_spaces" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Floor (sqm)</label>
                            <input wire:model.defer="floor_area_sqm" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Land (sqm)</label>
                            <input wire:model.defer="land_area_sqm" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Year Built</label>
                            <input wire:model.defer="year_built" type="number" min="1800" max="2100" placeholder="2020" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Condition</label>
                        <select wire:model.defer="condition" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="">— Select —</option>
                            <option value="new">New</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="needs_work">Needs Work</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveListing">Save Changes</span>
                            <span wire:loading wire:target="saveListing">Saving...</span>
                        </button>
                    </div>
                </form>
                @else
                <!-- Property Specs Grid -->
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mt-2">
                    @if($listing->property->bedrooms)
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-lg font-bold text-text-primary">{{ $listing->property->bedrooms }}</p>
                        <p class="text-[10px] text-text-secondary uppercase tracking-wider">Beds</p>
                    </div>
                    @endif
                    @if($listing->property->bathrooms)
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-lg font-bold text-text-primary">{{ $listing->property->bathrooms }}</p>
                        <p class="text-[10px] text-text-secondary uppercase tracking-wider">Baths</p>
                    </div>
                    @endif
                    @if($listing->property->parking_spaces)
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-lg font-bold text-text-primary">{{ $listing->property->parking_spaces }}</p>
                        <p class="text-[10px] text-text-secondary uppercase tracking-wider">Parking</p>
                    </div>
                    @endif
                    @if($listing->property->floor_area_sqm)
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-lg font-bold text-text-primary">{{ number_format($listing->property->floor_area_sqm) }}</p>
                        <p class="text-[10px] text-text-secondary uppercase tracking-wider">Floor m²</p>
                    </div>
                    @endif
                    @if($listing->property->land_area_sqm)
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-lg font-bold text-text-primary">{{ number_format($listing->property->land_area_sqm) }}</p>
                        <p class="text-[10px] text-text-secondary uppercase tracking-wider">Land m²</p>
                    </div>
                    @endif
                    @if($listing->property->year_built)
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-lg font-bold text-text-primary">{{ $listing->property->year_built }}</p>
                        <p class="text-[10px] text-text-secondary uppercase tracking-wider">Built</p>
                    </div>
                    @endif
                </div>
                @if($listing->property->condition)
                <p class="mt-3 text-xs text-text-secondary">Condition: <span class="font-medium text-text-primary capitalize">{{ str_replace('_', ' ', $listing->property->condition) }}</span></p>
                @endif
                @endif
            </div>

            <!-- Photo Gallery -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-text-primary">Photos <span class="text-text-tertiary font-normal">({{ $listing->media->count() }})</span></h3>
                </div>

                @if($listing->media->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                    @foreach($listing->media as $media)
                    <div class="relative group rounded-xl overflow-hidden aspect-video bg-surface-sunken">
                        <img src="{{ asset('storage/' . $media->file_path) }}"
                             alt="{{ $media->alt_text ?? $media->file_name }}"
                             class="w-full h-full object-cover">
                        @if($media->is_cover)
                        <div class="absolute top-2 left-2">
                            <span class="px-1.5 py-0.5 bg-brand-primary text-white text-[10px] rounded-full font-semibold">Cover</span>
                        </div>
                        @endif
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            @if(!$media->is_cover)
                            <button wire:click="setCover({{ $media->id }})" class="px-2 py-1 bg-white/90 text-text-primary rounded text-xs font-medium hover:bg-white">Cover</button>
                            @endif
                            <button wire:click="deletePhoto({{ $media->id }})" wire:confirm="Remove this photo?"
                                class="px-2 py-1 bg-danger-600 text-white rounded text-xs font-medium hover:bg-danger-700">Remove</button>
                        </div>
                        @if($media->width && $media->height)
                        <span class="absolute bottom-1 right-1 text-[9px] bg-black/50 text-white px-1 py-0.5 rounded">{{ $media->width }}×{{ $media->height }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="border-2 border-dashed border-border-default rounded-xl p-6 text-center">
                    <input wire:model="photos" type="file" id="photo-upload" multiple accept="image/*" class="hidden">
                    <label for="photo-upload" class="cursor-pointer flex flex-col items-center gap-2">
                        <svg class="h-9 w-9 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-text-secondary">Click to upload photos <span class="text-text-tertiary">(JPG, PNG · max 10 MB each)</span></p>
                    </label>
                    <div wire:loading wire:target="photos" class="mt-2 text-xs text-brand-primary">Processing...</div>
                </div>

                @if(count($photos) > 0)
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-sm text-text-secondary">{{ count($photos) }} photo(s) ready</span>
                    <button wire:click="uploadPhotos" class="px-4 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="uploadPhotos">Upload</span>
                        <span wire:loading wire:target="uploadPhotos">Uploading...</span>
                    </button>
                </div>
                @endif
            </div>

            <!-- Features Highlighted -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Key Features</h3>
                </div>

                @if(count($featuresHighlighted) > 0)
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach($featuresHighlighted as $i => $feature)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-primary/10 text-brand-primary text-xs font-medium">
                        {{ $feature }}
                        <button wire:click="removeFeature({{ $i }})" class="hover:text-danger-600 transition-colors">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                    @endforeach
                </div>
                @endif

                <form wire:submit.prevent="addFeature" class="flex gap-2">
                    <input wire:model.defer="newFeature" type="text"
                        placeholder="e.g. Swimming Pool, Generator, Smart Home..."
                        class="flex-1 rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <button type="submit" class="px-3 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors whitespace-nowrap">
                        + Add
                    </button>
                </form>
                @error('newFeature') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- AI Description Generator -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-text-primary">Property Description</h3>
                    <div class="flex items-center gap-2">
                        <select wire:model="descriptionTone" class="text-xs border border-border-default rounded-lg px-2 py-1.5 bg-surface-input text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="professional">Professional</option>
                            <option value="luxury">Luxury</option>
                            <option value="friendly">Friendly</option>
                            <option value="investment">Investment-focused</option>
                        </select>
                        <button wire:click="generateDescription"
                            wire:loading.attr="disabled"
                            wire:target="generateDescription"
                            class="px-3 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors disabled:opacity-60">
                            <span wire:loading.remove wire:target="generateDescription">✦ Generate with AI</span>
                            <span wire:loading wire:target="generateDescription">Generating...</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Headline</label>
                        <input wire:model.defer="headline" type="text" placeholder="e.g. Stunning 3-bed apartment with city views"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Short Description <span class="text-text-tertiary font-normal">(~50 words · portals)</span></label>
                        <textarea wire:model.defer="description_short" rows="2"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Standard Description <span class="text-text-tertiary font-normal">(~100 words)</span></label>
                        <textarea wire:model.defer="description_standard" rows="4"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Full Description <span class="text-text-tertiary font-normal">(~180 words · seller reports)</span></label>
                        <textarea wire:model.defer="description_long" rows="6"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="saveListing" class="px-4 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveListing">Save Description</span>
                            <span wire:loading wire:target="saveListing">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Matched Buyers -->
            @if($matchedBuyers->isNotEmpty())
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-text-primary">Matched Buyers <span class="ml-1 px-2 py-0.5 rounded-full bg-brand-primary/10 text-brand-primary text-xs font-bold">{{ $matchedBuyers->count() }}</span></h3>
                    <p class="text-xs text-text-tertiary">Contacts scored against this listing's criteria</p>
                </div>
                <div class="space-y-2">
                    @foreach($matchedBuyers->take(8) as $match)
                    @php $contact = $match['contact']; @endphp
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-surface-sunken/30 border border-border-default/30 hover:border-brand-primary/30 transition-colors group">
                        <div class="h-9 w-9 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary text-sm font-bold shrink-0">
                            {{ $contact->initials }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('crm.contact.detail', $contact) }}" class="text-sm font-medium text-text-primary group-hover:text-brand-primary transition-colors">
                                {{ $contact->full_name }}
                            </a>
                            <p class="text-xs text-text-tertiary truncate">{{ implode(' · ', $match['reasons']) }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="text-sm font-bold {{ $match['score'] >= 70 ? 'text-success-700' : ($match['score'] >= 50 ? 'text-warning-700' : 'text-text-secondary') }}">
                                {{ $match['score'] }}%
                            </span>
                            <p class="text-[10px] text-text-tertiary">match</p>
                        </div>
                    </div>
                    @endforeach
                    @if($matchedBuyers->count() > 8)
                    <p class="text-xs text-center text-text-tertiary pt-1">+ {{ $matchedBuyers->count() - 8 }} more matched buyers</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- ── Right: Sidebar ───────────────────────────────────────── -->
        <div class="xl:col-span-1 space-y-5">

            <!-- Listing Info -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Listing Info</h3>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Property Type</dt>
                        <dd class="font-medium text-text-primary capitalize">{{ $listing->property->property_type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Days on Market</dt>
                        <dd class="font-medium {{ $dom > 60 ? 'text-danger-600' : ($dom > 30 ? 'text-warning-600' : 'text-text-primary') }}">
                            {{ $dom !== null ? $dom . ' days' : '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Viewings</dt>
                        <dd class="font-medium text-text-primary">{{ $viewingsCount }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Offers</dt>
                        <dd class="font-medium text-text-primary">{{ $offersCount }}</dd>
                    </div>
                    @if($listing->commission_rate)
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Commission</dt>
                        <dd class="font-medium text-text-primary">{{ $listing->commission_rate }}%
                            <span class="text-xs text-text-tertiary">(₦{{ number_format($listing->listing_price * $listing->commission_rate / 100) }})</span>
                        </dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Mandate Start</dt>
                        <dd class="font-medium text-text-primary">{{ $listing->mandate_start_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Mandate End</dt>
                        <dd class="font-medium {{ $mandateExpired ? 'text-danger-600' : ($mandateExpiringSoon ? 'text-warning-600' : 'text-text-primary') }}">
                            {{ $listing->mandate_end_date?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Health Score</dt>
                        <dd class="font-medium {{ ($listing->health_score ?? 0) >= 70 ? 'text-success-600' : (($listing->health_score ?? 0) >= 40 ? 'text-warning-600' : 'text-danger-600') }}">
                            {{ $listing->health_score ? $listing->health_score . '/100' : '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Agent</dt>
                        <dd class="font-medium text-text-primary">{{ $listing->agent?->first_name ?? 'Unassigned' }}</dd>
                    </div>
                </dl>

                <div class="mt-4 pt-4 border-t border-border-default/60">
                    <a href="{{ route('reports.seller-pdf', $listing) }}" target="_blank"
                        class="flex items-center justify-center gap-2 w-full py-2 rounded-lg border border-border-default/60 text-xs font-medium text-text-secondary hover:border-brand-primary hover:text-brand-primary transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download Seller Report (PDF)
                    </a>
                </div>
            </div>

            <!-- Seller Info -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Seller / Landlord</h3>
                <form wire:submit.prevent="saveSellerInfo" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Seller Email</label>
                        <input wire:model.defer="seller_email" type="email" placeholder="seller@email.com"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('seller_email') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Report Frequency</label>
                        <select wire:model.defer="seller_report_frequency"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2 bg-surface-sunken border border-border-default rounded-lg text-xs font-medium text-text-secondary hover:border-brand-primary hover:text-brand-primary transition-colors">
                        <span wire:loading.remove wire:target="saveSellerInfo">Save Seller Info</span>
                        <span wire:loading wire:target="saveSellerInfo">Saving...</span>
                    </button>
                </form>
            </div>

            <!-- Portal Syndication -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-4">Portal Syndication</h3>

                @forelse($portals as $portal)
                @php $sync = $listing->portalSyncs->firstWhere('portal_id', $portal->id); @endphp
                <div class="flex items-center justify-between py-3 border-b border-border-default/40 last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text-primary">{{ $portal->name }}</p>
                        @if($sync)
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <div class="h-2 w-2 rounded-full shrink-0
                                @if($sync->status === 'synced') bg-success-500
                                @elseif($sync->status === 'failed') bg-danger-500
                                @elseif(in_array($sync->status, ['pending', 'syncing'])) bg-warning-500
                                @else bg-text-tertiary @endif"></div>
                            <span class="text-xs text-text-secondary capitalize">{{ $sync->status }}</span>
                            @if($sync->last_synced_at)
                            <span class="text-xs text-text-tertiary">· {{ $sync->last_synced_at->diffForHumans() }}</span>
                            @endif
                        </div>
                        @else
                        <span class="text-xs text-text-tertiary">Not published</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 ml-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="portalSelections.{{ $portal->id }}" class="sr-only peer" value="1">
                            <div class="w-9 h-5 bg-surface-raised peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-primary"></div>
                        </label>
                        <button wire:click="syncPortal({{ $portal->id }})" class="text-xs text-brand-primary hover:text-brand-secondary font-medium">
                            <span wire:loading.remove wire:target="syncPortal({{ $portal->id }})">Sync</span>
                            <span wire:loading wire:target="syncPortal({{ $portal->id }})">...</span>
                        </button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-secondary text-center py-4">No portals configured. Add portals in Settings.</p>
                @endforelse
            </div>

            <!-- Social Media Graphics -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-text-primary">Social Media Graphics</h3>
                        <p class="text-xs text-text-tertiary mt-0.5">Instagram · Facebook · Story</p>
                    </div>
                    <button wire:click="generateSocialGraphics" wire:loading.attr="disabled" wire:target="generateSocialGraphics"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-brand-primary text-white text-xs font-semibold rounded-lg hover:bg-brand-secondary transition disabled:opacity-60">
                        <span wire:loading.remove wire:target="generateSocialGraphics">{{ $graphics->isEmpty() ? 'Generate' : 'Regenerate' }}</span>
                        <span wire:loading wire:target="generateSocialGraphics">Working…</span>
                    </button>
                </div>

                @if($graphics->isEmpty())
                <div class="rounded-xl bg-surface-sunken/40 border border-dashed border-border-default/60 p-6 text-center">
                    <svg class="w-8 h-8 text-text-tertiary mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M21 12l-5.25-5.25L12 9.75"/></svg>
                    <p class="text-xs text-text-tertiary">Generate branded social graphics with AI captions.</p>
                </div>
                @else
                <div class="grid grid-cols-3 gap-2">
                    @foreach($graphics as $graphic)
                    <div class="relative rounded-xl overflow-hidden group border border-border-default/40 bg-surface-raised">
                        <img src="{{ asset('storage/'.$graphic->file_path) }}" alt="{{ $graphic->format }}" class="w-full aspect-square object-cover">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="{{ asset('storage/'.$graphic->file_path) }}" download class="p-1.5 bg-white rounded-lg">
                                <svg class="w-4 h-4 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            <button wire:click="deleteSocialGraphic({{ $graphic->id }})" class="p-1.5 bg-red-600 rounded-lg">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        <span class="absolute bottom-1 left-1 text-[9px] font-bold bg-black/60 text-white px-1.5 py-0.5 rounded uppercase">{{ $graphic->format }}</span>
                    </div>
                    @endforeach
                </div>
                @if($graphics->isNotEmpty() && $graphics->first()->post_copy)
                @php $copy = $graphics->first()->post_copy; @endphp
                <div class="mt-3 p-3 rounded-xl bg-surface-sunken/30 border border-border-default/30">
                    <p class="text-xs font-medium text-text-secondary mb-1">AI Caption</p>
                    <p class="text-xs text-text-primary leading-relaxed">{{ $copy['caption'] ?? '' }}</p>
                    @if(!empty($copy['hashtags']))
                    <p class="text-[10px] text-brand-primary mt-1.5 break-words">#{{ implode(' #', array_slice($copy['hashtags'], 0, 8)) }}</p>
                    @endif
                </div>
                @endif
                <a href="{{ route('marketing.social') }}" class="block mt-3 text-center text-xs text-brand-primary hover:underline">Open Social Studio →</a>
                @endif
            </div>
        </div>
    </div>
</div>
