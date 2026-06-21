<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Marketing Campaigns</h1>
            <p class="mt-2 text-text-secondary">Manage AI-generated campaigns across all channels.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('marketing.calendar') }}" class="px-4 py-2 border border-border-default text-text-primary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">
                Content Calendar
            </a>
            <a href="{{ route('marketing.campaign.new') }}" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                + New Campaign
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        @foreach(['total' => 'Total', 'active' => 'Active', 'scheduled' => 'Scheduled', 'completed' => 'Completed'] as $key => $label)
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-extrabold text-text-primary">{{ $stats[$key] }}</p>
            <p class="text-xs font-medium text-text-secondary mt-1">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden shadow-sm mb-2">
        <div class="px-6 py-4 border-b border-border-default flex items-center justify-between bg-surface-sunken/30 gap-4">
            <div class="w-1/3">
                <input wire:model.debounce.300ms="search" type="text" placeholder="Search campaigns..."
                    class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white/50 focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page text-sm">
            </div>
            <div class="flex gap-2">
                @foreach(['' => 'All', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'active' => 'Active', 'completed' => 'Completed'] as $val => $label)
                <button wire:click="$set('filterStatus', '{{ $val }}')"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                    {{ $filterStatus === $val ? 'bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10' : 'bg-surface-card border border-border-default text-text-secondary hover:bg-surface-sunken' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endforeach
            </div>
        </div>

        <!-- Campaign Grid -->
        <div class="p-6">
            @forelse($campaigns as $campaign)
            <div class="mb-4 p-5 bg-surface-card rounded-2xl border border-border-default hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-text-primary truncate">{{ $campaign->name }}</h3>
                        <p class="text-xs text-text-secondary mt-0.5">
                            {{ $campaign->listing?->property?->address_line_1 ?? 'No listing' }}
                            · Goal: <span class="capitalize">{{ str_replace('_', ' ', $campaign->goal) }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 ml-4 shrink-0">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                            @if($campaign->status === 'active') bg-success-100 text-success-700
                            @elseif($campaign->status === 'scheduled') bg-info-100 text-info-700
                            @elseif($campaign->status === 'draft') bg-surface-sunken text-text-secondary
                            @else bg-brand-primary/10 text-brand-primary @endif">
                            {{ $campaign->status }}
                        </span>
                    </div>
                </div>

                <!-- Channel pills -->
                <div class="flex gap-2 mb-4 flex-wrap">
                    @foreach($campaign->contents as $content)
                    <span class="px-2 py-0.5 bg-brand-primary/10 text-brand-primary rounded-full text-[10px] font-bold uppercase tracking-wider">
                        {{ $content->channel }}
                    </span>
                    @endforeach
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-border-default/40">
                    <p class="text-xs text-text-secondary">
                        @if($campaign->scheduled_at) Scheduled: {{ $campaign->scheduled_at->format('d M Y') }}
                        @else Created: {{ $campaign->created_at->format('d M Y') }} @endif
                    </p>
                    <div class="flex items-center gap-2">
                        <select wire:change="updateStatus({{ $campaign->id }}, $event.target.value)"
                            class="text-xs border border-border-default rounded-lg px-2 py-1 bg-surface-input text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            <option value="draft" {{ $campaign->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="scheduled" {{ $campaign->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="active" {{ $campaign->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ $campaign->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        <button wire:click="deleteCampaign({{ $campaign->id }})"
                            wire:confirm="Delete this campaign and all its content?"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-danger-500 hover:text-danger-700 font-medium border border-danger-200 rounded-lg px-2 py-1 hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="deleteCampaign">
                <span wire:loading.remove wire:target="deleteCampaign">Delete</span>
                <span wire:loading wire:target="deleteCampaign" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="py-16 text-center">
                <div class="h-14 w-14 bg-brand-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">📣</div>
                <p class="text-sm font-medium text-text-primary">No campaigns yet.</p>
                <a href="{{ route('marketing.campaign.new') }}" class="mt-3 inline-block px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                    Create First Campaign
                </a>
            </div>
            @endforelse
        </div>

        @if($campaigns->hasPages())
        <div class="px-6 py-3 border-t border-border-default">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>
</div>



