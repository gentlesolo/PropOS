<div class="min-h-screen bg-slate-950 text-slate-100 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Badge -->
        <div class="mb-6 flex justify-between items-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-brand-primary/20 text-brand-primary border border-brand-primary/30">
                🔒 Private Pocket Listing
            </span>
            <span class="text-xs text-slate-400">Exclusive off-market opportunity</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Media & Description -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header -->
                <div class="p-6 bg-slate-900/60 rounded-3xl border border-slate-800 backdrop-blur-md">
                    <h1 class="text-3xl font-extrabold tracking-tight text-white mb-2">
                        {{ $listing->headline ?? $listing->property->address_line_1 }}
                    </h1>
                    <p class="text-slate-400 mb-4">{{ $listing->property->city }}, {{ $listing->property->state_province }}</p>
                    
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white">{{ $currencySymbol }}{{ number_format($listing->listing_price) }}</span>
                    </div>
                </div>

                <!-- Media Gallery -->
                @if($listing->media->count() > 0)
                <div class="p-6 bg-slate-900/60 rounded-3xl border border-slate-800 backdrop-blur-md space-y-4">
                    <h3 class="text-lg font-semibold text-white">Gallery</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($listing->media as $media)
                        <div class="relative rounded-2xl overflow-hidden aspect-video bg-slate-950 border border-slate-800">
                            <img src="{{ asset('storage/' . $media->file_path) }}" alt="{{ $media->alt_text ?? 'Listing Photo' }}" class="w-full h-full object-cover">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Virtual Tour Embed -->
                @if($listing->virtual_tour_url && $listing->virtual_tour_type)
                <div class="p-6 bg-slate-900/60 rounded-3xl border border-slate-800 backdrop-blur-md space-y-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 00-2 2z"/></svg>
                        Virtual Tour Integration
                    </h3>
                    <div class="relative rounded-2xl overflow-hidden aspect-video bg-slate-950 border border-slate-800">
                        @if($listing->virtual_tour_type === 'youtube')
                            @php
                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|win/|[^/]+/[^/]+/|watch\?v(?:-nocookie)?=)|youtu\.be/)([^"&?/ ]{11})%i', $listing->virtual_tour_url, $match);
                                $youtubeId = $match[1] ?? null;
                            @endphp
                            @if($youtubeId)
                                <iframe class="absolute inset-0 w-full h-full" src="https://www.youtube.com/embed/{{ $youtubeId }}" frameborder="0" allowfullscreen></iframe>
                            @endif
                        @elseif($listing->virtual_tour_type === 'matterport')
                            @php
                                $matterportEmbed = $listing->virtual_tour_url;
                                if (!str_contains($matterportEmbed, '/embed/')) {
                                    $matterportEmbed = str_replace('.com/show/?m=', '.com/embed?m=', $matterportEmbed);
                                }
                            @endphp
                            <iframe class="absolute inset-0 w-full h-full" src="{{ $matterportEmbed }}" frameborder="0" allowfullscreen allow="xr-spatial-tracking"></iframe>
                        @else
                            <iframe class="absolute inset-0 w-full h-full" src="{{ $listing->virtual_tour_url }}" frameborder="0" allowfullscreen></iframe>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Specs & Description -->
                <div class="p-6 bg-slate-900/60 rounded-3xl border border-slate-800 backdrop-blur-md space-y-6">
                    <h3 class="text-lg font-semibold text-white">Property Specifications</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @if($listing->property->bedrooms)
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                            <p class="text-2xl font-extrabold text-white">{{ $listing->property->bedrooms }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Bedrooms</p>
                        </div>
                        @endif
                        @if($listing->property->bathrooms)
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                            <p class="text-2xl font-extrabold text-white">{{ $listing->property->bathrooms }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Bathrooms</p>
                        </div>
                        @endif
                        @if($listing->property->floor_area_sqm)
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                            <p class="text-2xl font-extrabold text-white">{{ number_format($listing->property->floor_area_sqm) }} m²</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Floor Area</p>
                        </div>
                        @endif
                        @if($listing->property->year_built)
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                            <p class="text-2xl font-extrabold text-white">{{ $listing->property->year_built }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Year Built</p>
                        </div>
                        @endif
                    </div>

                    @if($listing->description_long || $listing->description_standard)
                    <div class="border-t border-slate-800 pt-6">
                        <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-3">About the Property</h4>
                        <div class="text-slate-300 leading-relaxed space-y-4">
                            {!! nl2br(e($listing->description_long ?: $listing->description_standard)) !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right: Agent Profile & Private Viewing Request -->
            <div class="space-y-6">
                <!-- Agent -->
                @if($listing->agent)
                <div class="p-6 bg-slate-900/60 rounded-3xl border border-slate-800 backdrop-blur-md text-center">
                    <div class="w-20 h-20 bg-brand-primary/10 border border-brand-primary/30 rounded-full flex items-center justify-center text-brand-primary text-2xl font-extrabold mx-auto mb-4">
                        {{ substr($listing->agent->first_name, 0, 1) }}{{ substr($listing->agent->last_name, 0, 1) }}
                    </div>
                    <h3 class="text-lg font-bold text-white">{{ $listing->agent->first_name }} {{ $listing->agent->last_name }}</h3>
                    <p class="text-xs text-slate-400 mb-2">{{ $listing->agent->job_title ?? 'Mandated Agent' }}</p>
                    <div class="border-t border-slate-800 mt-4 pt-4 flex flex-col gap-2">
                        <a href="mailto:{{ $listing->agent->email }}" class="text-sm text-brand-primary hover:underline">{{ $listing->agent->email }}</a>
                        @if($listing->agent->phone)
                        <span class="text-sm text-slate-300">{{ $listing->agent->phone }}</span>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Public Booking Portal Redirection / Booking Card -->
                <div class="p-6 bg-gradient-to-br from-brand-primary/20 to-slate-900 rounded-3xl border border-brand-primary/30 text-center space-y-4">
                    <h3 class="text-lg font-bold text-white">Private Tour Scheduler</h3>
                    <p class="text-xs text-slate-300">Book an exclusive in-person walkthrough directly using our calendar.</p>
                    <a href="{{ route('viewing.book', $listing) }}" class="block w-full py-3 bg-brand-primary text-white text-sm font-semibold rounded-xl hover:bg-brand-secondary transition">
                        Select Viewing Slot
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
