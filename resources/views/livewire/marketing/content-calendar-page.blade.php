<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Content Calendar</h1>
            <p class="mt-2 text-text-secondary">Visualise all scheduled marketing content across channels.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('marketing.campaigns') }}" class="px-4 py-2 border border-border-default text-text-primary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">
                All Campaigns
            </a>
            <a href="{{ route('marketing.campaign.new') }}" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                + New Campaign
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Calendar -->
        <div class="xl:col-span-2">
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">

                <!-- Month Navigator -->
                <div class="px-6 py-4 border-b border-border-default/60 flex items-center justify-between bg-surface-sunken/30">
                    <button wire:click="previousMonth" class="h-8 w-8 rounded-full bg-surface-card border border-border-default/60 flex items-center justify-center hover:bg-surface-sunken transition-colors">
                        <svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <h2 class="text-base font-bold text-text-primary">
                        {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}
                    </h2>
                    <button wire:click="nextMonth" class="h-8 w-8 rounded-full bg-surface-card border border-border-default/60 flex items-center justify-center hover:bg-surface-sunken transition-colors">
                        <svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Day Headers -->
                <div class="grid grid-cols-7 border-b border-border-default/40">
                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                    <div class="py-2 text-center text-[10px] font-bold uppercase tracking-wider text-text-secondary">{{ $d }}</div>
                    @endforeach
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-px bg-border-default/20">
                    @foreach($calendarDays as $dayData)
                    @if($dayData === null)
                    <div class="bg-surface-sunken/30 min-h-[80px] p-1"></div>
                    @else
                    @php $isToday = $dayData['date']->isToday(); @endphp
                    <div class="bg-surface-card min-h-[80px] p-1.5 relative hover:bg-surface-sunken/30 transition-colors">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold {{ $isToday ? 'h-5 w-5 bg-brand-primary text-white rounded-full flex items-center justify-center' : 'text-text-secondary' }}">
                                {{ $dayData['day'] }}
                            </span>
                        </div>
                        <div class="space-y-0.5">
                            @foreach($dayData['contents']->take(3) as $content)
                            <button wire:click="selectContent({{ $content->id }})"
                                class="w-full text-left px-1.5 py-0.5 rounded text-[9px] font-bold truncate transition-colors
                                @if($selectedContentId === $content->id) ring-1 ring-brand-primary @endif
                                @switch($content->channel)
                                    @case('instagram') bg-brand-primary/10 text-brand-primary @break
                                    @case('facebook') bg-brand-secondary/10 text-brand-secondary @break
                                    @case('linkedin') bg-info-100 text-info-700 @break
                                    @case('email') bg-success-100 text-success-700 @break
                                    @default bg-surface-sunken text-text-secondary
                                @endswitch">
                                {{ strtoupper(substr($content->channel, 0, 2)) }} · {{ $content->campaign?->name }}
                            </button>
                            @endforeach
                            @if($dayData['contents']->count() > 3)
                            <div class="text-[9px] text-text-secondary px-1">+{{ $dayData['contents']->count() - 3 }} more</div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            <!-- Channel Legend -->
            <div class="mt-4 flex gap-3 flex-wrap">
                @foreach(['instagram' => ['bg-brand-primary/10 text-brand-primary', 'Instagram'], 'facebook' => ['bg-brand-secondary/10 text-brand-secondary', 'Facebook'], 'linkedin' => ['bg-info-100 text-info-700', 'LinkedIn'], 'email' => ['bg-success-100 text-success-700', 'Email']] as $channel => [$classes, $label])
                <div class="flex items-center gap-1.5">
                    <div class="h-3 w-3 rounded {{ $classes }} inline-block"></div>
                    <span class="text-xs text-text-secondary font-medium">{{ $label }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Content Detail Panel -->
        <div class="xl:col-span-1">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5 sticky top-6">
                @if($selectedContent)
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-text-primary">Content Preview</h3>
                        <button wire:click="$set('selectedContentId', null)" class="text-text-secondary hover:text-text-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-medium text-text-secondary">Campaign</p>
                            <p class="font-semibold text-text-primary">{{ $selectedContent->campaign?->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-text-secondary">Channel</p>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                @switch($selectedContent->channel)
                                    @case('instagram') bg-brand-primary/10 text-brand-primary @break
                                    @case('facebook') bg-brand-secondary/10 text-brand-secondary @break
                                    @case('linkedin') bg-info-100 text-info-700 @break
                                    @case('email') bg-success-100 text-success-700 @break
                                    @default bg-surface-sunken text-text-secondary
                                @endswitch">
                                {{ $selectedContent->channel }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-text-secondary">Scheduled</p>
                            <p class="text-text-primary">{{ \Carbon\Carbon::parse($selectedContent->scheduled_at)->format('d M Y, H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-text-secondary mb-1">Content</p>
                            <div class="p-3 bg-surface-sunken/40 rounded-xl border border-border-default/40">
                                <p class="text-xs text-text-primary leading-relaxed whitespace-pre-line">{{ $selectedContent->content_body }}</p>
                            </div>
                        </div>
                        @if($selectedContent->campaign?->listing)
                        <div>
                            <p class="text-xs font-medium text-text-secondary">Property</p>
                            <p class="text-text-primary">{{ $selectedContent->campaign->listing->property->address_line_1 }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="h-12 w-12 bg-brand-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-3 text-xl">📅</div>
                    <p class="text-sm font-medium text-text-primary">Select a content block</p>
                    <p class="text-xs text-text-secondary mt-1">Click any calendar item to preview its content.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
