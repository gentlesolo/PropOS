<div class="flex gap-6 h-[calc(100vh-7rem)]">

    {{-- ── Left panel: listing picker ─────────────────────────────────────── --}}
    <div class="w-72 shrink-0 flex flex-col gap-3 overflow-y-auto pr-1">
        <div>
            <h1 class="text-xl font-bold text-text-primary">Social Graphics</h1>
            <p class="text-xs text-text-secondary mt-0.5">Auto-generate branded visuals and AI captions for any listing.</p>
        </div>

        @foreach($listings as $listing)
        @php $prop = $listing->property; $hasGraphics = $listing->graphics->isNotEmpty(); @endphp
        <button wire:click="selectListing({{ $listing->id }})"
                class="w-full text-left rounded-xl border p-3 transition
                       {{ $selectedListingId == $listing->id
                           ? 'border-brand-primary bg-brand-primary/5'
                           : 'border-border-default bg-surface-card hover:border-brand-primary/40' }}">
            <div class="flex items-start gap-3">
                {{-- Thumbnail --}}
                @if($listing->graphics->where('format','square')->first())
                <img src="{{ asset('storage/'.$listing->graphics->firstWhere('format','square')->file_path) }}"
                     class="w-12 h-12 rounded-lg object-cover shrink-0" alt="">
                @elseif($listing->media->first())
                <img src="{{ asset('storage/'.$listing->media->first()->file_path) }}"
                     class="w-12 h-12 rounded-lg object-cover shrink-0" alt="">
                @else
                <div class="w-12 h-12 rounded-lg bg-surface-raised flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909"/></svg>
                </div>
                @endif

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-text-primary truncate">{{ $prop->address_line_1 }}</p>
                    <p class="text-xs text-text-secondary truncate">{{ $prop->city }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @if($hasGraphics)
                        <span class="text-[10px] font-medium text-success-600 bg-success-50 px-1.5 py-0.5 rounded">
                            {{ $listing->graphics->count() }} graphics
                        </span>
                        @else
                        <span class="text-[10px] font-medium text-text-tertiary bg-surface-raised px-1.5 py-0.5 rounded">
                            No graphics yet
                        </span>
                        @endif
                        <span class="text-[10px] text-text-tertiary capitalize">{{ $listing->status }}</span>
                    </div>
                </div>
            </div>
        </button>
        @endforeach

        @if($listings->isEmpty())
        <div class="text-center py-10 text-text-tertiary text-sm">
            No active listings found.
        </div>
        @endif
    </div>

    {{-- ── Right panel: graphic studio ────────────────────────────────────── --}}
    <div class="flex-1 overflow-y-auto">

        @if(! $selectedListing)
        <div class="h-full flex items-center justify-center">
            <div class="text-center space-y-3">
                <div class="w-16 h-16 rounded-2xl bg-surface-raised flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M21 12l-5.25-5.25L12 9.75m9 2.25l-9 9"/>
                    </svg>
                </div>
                <p class="text-text-secondary text-sm font-medium">Select a listing to generate social media graphics</p>
                <p class="text-text-tertiary text-xs">Creates branded Instagram, Facebook, and Story formats with AI captions</p>
            </div>
        </div>

        @else
        <div class="space-y-6">

            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-text-primary">
                        {{ $selectedListing->property->address_line_1 }}, {{ $selectedListing->property->city }}
                    </h2>
                    <p class="text-sm text-text-secondary mt-0.5">
                        {{ number_format($selectedListing->listing_price) }}
                        &middot; {{ $selectedListing->property->bedrooms ?? '—' }} beds
                        &middot; {{ ucfirst($selectedListing->mandate_type) }}
                    </p>
                </div>
                <button wire:click="regenerateNow" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 text-sm font-semibold rounded-xl hover:bg-brand-secondary transition disabled:opacity-60 flex items-center gap-2">
                    <svg wire:loading.remove wire:target="regenerateNow" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <svg wire:loading wire:target="regenerateNow" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span wire:loading.remove wire:target="regenerateNow">{{ $graphics->isEmpty() ? 'Generate Graphics' : 'Regenerate All' }}</span>
                    <span wire:loading wire:target="regenerateNow">Generating…</span>
                </button>
            </div>

            @if($graphics->isEmpty())
            <div class="bg-surface-card border border-dashed border-border-default rounded-2xl p-12 text-center">
                <p class="text-text-secondary text-sm">No graphics yet for this listing.</p>
                <p class="text-text-tertiary text-xs mt-1">Click "Generate Graphics" above to create Instagram, Facebook, and Story formats.</p>
            </div>

            @else

            {{-- ── Graphics Grid ──────────────────────────────────────────────── --}}
            <div class="grid grid-cols-3 gap-4">
                @foreach($graphics as $graphic)
                <div class="bg-surface-card border border-border-default rounded-2xl overflow-hidden group">

                    {{-- Preview image --}}
                    <div class="relative aspect-square bg-surface-raised overflow-hidden">
                        <img src="{{ asset('storage/'.$graphic->file_path) }}"
                             alt="{{ $graphic->formatLabel }}"
                             class="w-full h-full object-cover">

                        {{-- Hover overlay --}}
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                            <a href="{{ asset('storage/'.$graphic->file_path) }}"
                               download
                               class="p-2 bg-white rounded-lg hover:bg-gray-100 transition" title="Download">
                                <svg class="w-5 h-5 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            <button wire:click="deleteGraphic({{ $graphic->id }})"
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative p-2 bg-red-600 rounded-lg hover:bg-red-700 transition" title="Delete" wire:loading.attr="disabled" wire:target="deleteGraphic">
                <span wire:loading.remove wire:target="deleteGraphic"><svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>
                <span wire:loading wire:target="deleteGraphic" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>

                        {{-- Format badge --}}
                        <span class="absolute top-2 left-2 text-[10px] font-bold px-2 py-0.5 rounded bg-black/60 text-white uppercase tracking-wide">
                            {{ $graphic->format }}
                        </span>

                        {{-- Dimensions --}}
                        <span class="absolute bottom-2 right-2 text-[10px] text-white/70 bg-black/40 px-1.5 py-0.5 rounded">
                            {{ $graphic->width }}×{{ $graphic->height }}
                        </span>
                    </div>

                    {{-- Caption --}}
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-text-primary capitalize">{{ $graphic->channel }}</span>
                            <button wire:click="regenerateCopy({{ $graphic->id }})"
                                    wire:loading.attr="disabled"
                                    class="text-[10px] text-brand-primary hover:underline disabled:opacity-50">
                                <span wire:loading.remove wire:target="regenerateCopy({{ $graphic->id }})">↻ New copy</span>
                                <span wire:loading wire:target="regenerateCopy({{ $graphic->id }})">Generating…</span>
                            </button>
                        </div>

                        @if($graphic->post_copy)
                        <div x-data="{ copied: false }" class="relative">
                            <p class="text-xs text-text-secondary leading-relaxed line-clamp-4">
                                {{ $graphic->post_copy['caption'] ?? '' }}
                            </p>
                            <button
                                @click="
                                    navigator.clipboard.writeText('{{ addslashes($graphic->post_copy['caption'] ?? '') }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="mt-2 w-full py-1.5 rounded-lg border border-border-default text-xs font-medium text-text-secondary hover:border-brand-primary hover:text-brand-primary transition">
                                <span x-show="!copied">Copy caption</span>
                                <span x-show="copied" x-cloak class="text-success-600">✓ Copied!</span>
                            </button>
                        </div>

                        @if(! empty($graphic->post_copy['hashtags']))
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_slice($graphic->post_copy['hashtags'], 0, 6) as $tag)
                            <span class="text-[10px] text-brand-primary bg-brand-primary/10 px-1.5 py-0.5 rounded">#{{ $tag }}</span>
                            @endforeach
                            @if(count($graphic->post_copy['hashtags']) > 6)
                            <span class="text-[10px] text-text-tertiary">+{{ count($graphic->post_copy['hashtags']) - 6 }} more</span>
                            @endif
                        </div>
                        @endif

                        @else
                        <p class="text-xs text-text-tertiary italic">No caption yet — click "↻ New copy"</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ── All Channel Captions ───────────────────────────────────────── --}}
            @if(! empty($allChannelCopy))
            <div class="bg-surface-card border border-border-default rounded-2xl p-6">
                <h3 class="text-sm font-semibold text-text-primary mb-4">All Channel Captions</h3>

                <div class="space-y-4">
                    @php
                    $channelIcons = [
                        'instagram' => '📸', 'facebook' => '👍', 'linkedin' => '💼',
                        'whatsapp'  => '💬', 'twitter'  => '🐦',
                    ];
                    @endphp

                    @foreach($allChannelCopy as $channel => $copy)
                    <div x-data="{ open: false, copied: false }" class="border border-border-default/40 rounded-xl overflow-hidden">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 hover:bg-surface-raised/50 transition">
                            <div class="flex items-center gap-2">
                                <span>{{ $channelIcons[$channel] ?? '📢' }}</span>
                                <span class="text-sm font-medium text-text-primary capitalize">{{ $channel }}</span>
                                <span class="text-xs text-text-tertiary">({{ $copy['char_count'] ?? 0 }} chars)</span>
                            </div>
                            <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-text-tertiary transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-collapse class="border-t border-border-default/40 px-4 py-4 space-y-3" style="display:none">
                            <p class="text-sm text-text-primary leading-relaxed whitespace-pre-line">{{ $copy['caption'] ?? '' }}</p>

                            @if(! empty($copy['hashtags']))
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach($copy['hashtags'] as $tag)
                                <span class="text-xs text-brand-primary bg-brand-primary/10 px-2 py-0.5 rounded-full">#{{ $tag }}</span>
                                @endforeach
                            </div>
                            @endif

                            <button
                                @click="
                                    navigator.clipboard.writeText('{{ addslashes($copy['caption'] ?? '') }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="px-3 py-1.5 rounded-lg border border-border-default text-xs font-medium text-text-secondary hover:border-brand-primary hover:text-brand-primary transition">
                                <span x-show="!copied">Copy caption</span>
                                <span x-show="copied" x-cloak class="text-success-600">✓ Copied!</span>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @endif {{-- graphics --}}
        </div>
        @endif {{-- selectedListing --}}
    </div>
</div>


