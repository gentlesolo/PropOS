<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <span class="text-3xl">📣</span> Meta Ads Manager
            </h1>
            <p class="mt-2 text-text-secondary">Create and monitor Facebook & Instagram ad campaigns linked to your listings.</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
            {{ $showCreateForm ? 'Cancel' : '+ New Campaign' }}
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
        @foreach(['total_spend' => ['Total Spend', '₦'], 'total_impressions' => ['Impressions', ''], 'total_clicks' => ['Clicks', ''], 'total_leads' => ['Leads', ''], 'active' => ['Active', ''], 'avg_cpl' => ['Avg CPL', '₦']] as $key => [$label, $prefix])
        <div class="glass-panel p-4 rounded-2xl border border-border-default/60 text-center">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">{{ $label }}</p>
            <p class="text-xl font-black text-text-primary">{{ $prefix }}{{ number_format($stats[$key]) }}</p>
        </div>
        @endforeach
    </div>

    <!-- Create Form -->
    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-6 mb-6">
        <h3 class="text-sm font-bold text-text-primary mb-5">New Meta Ad Campaign</h3>
        <form wire:submit.prevent="createCampaign" class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Campaign Name *</label>
                <input wire:model.defer="name" type="text" placeholder="e.g. Lekki Apartment Leads — Q3" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Objective</label>
                <select wire:model.defer="objective" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="lead_generation">Lead Generation</option>
                    <option value="brand_awareness">Brand Awareness</option>
                    <option value="traffic">Traffic</option>
                    <option value="conversions">Conversions</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Daily Budget (₦) *</label>
                <input wire:model.defer="budget_daily" type="number" min="500" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('budget_daily') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Start Date *</label>
                <input wire:model.defer="start_date" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">End Date</label>
                <input wire:model.defer="end_date" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Linked Campaign (optional)</label>
                <select wire:model.defer="campaign_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">No linked campaign</option>
                    @foreach($linkedCampaigns as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} — {{ $c->listing?->property?->address_line_1 ?? 'No listing' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="createCampaign">Create Campaign</span>
                    <span wire:loading wire:target="createCampaign">Creating...</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Campaigns Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-sunken/50 border-b border-border-default/40">
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Campaign</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Budget/Day</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Spend</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Impressions</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Clicks</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Leads</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">CPL</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Status</th>
                        <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/40">
                    @forelse($campaigns as $ad)
                    <tr class="hover:bg-surface-raised/20 transition-colors">
                        <td class="py-4 px-5">
                            <p class="text-sm font-bold text-text-primary">{{ $ad->name }}</p>
                            <p class="text-xs text-text-secondary capitalize">{{ str_replace('_', ' ', $ad->objective) }}</p>
                        </td>
                        <td class="py-4 px-5 text-sm font-medium text-text-primary">₦{{ number_format($ad->budget_daily) }}</td>
                        <td class="py-4 px-5 text-sm font-bold text-text-primary">₦{{ number_format($ad->spend) }}</td>
                        <td class="py-4 px-5 text-sm text-text-primary">{{ number_format($ad->impressions) }}</td>
                        <td class="py-4 px-5 text-sm text-text-primary">{{ number_format($ad->clicks) }}</td>
                        <td class="py-4 px-5 text-sm font-bold text-success-600">{{ $ad->leads }}</td>
                        <td class="py-4 px-5 text-sm text-text-primary">{{ $ad->cpl ? '₦' . number_format($ad->cpl) : '—' }}</td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase
                                @if($ad->status === 'active') bg-success-100 text-success-700
                                @elseif($ad->status === 'paused') bg-warning-100 text-warning-700
                                @elseif($ad->status === 'draft') bg-slate-100 text-slate-600
                                @else bg-slate-100 text-slate-500 @endif">
                                {{ $ad->status }}
                            </span>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2">
                                @if($ad->status === 'draft')
                                <button wire:click="updateStatus({{ $ad->id }}, 'active')" class="text-xs text-success-600 border border-success-200 rounded-lg px-2 py-1 hover:bg-success-50 transition-colors">Activate</button>
                                @elseif($ad->status === 'active')
                                <button wire:click="updateStatus({{ $ad->id }}, 'paused')" class="text-xs text-warning-600 border border-warning-200 rounded-lg px-2 py-1 hover:bg-warning-50 transition-colors">Pause</button>
                                @endif
                                <button wire:click="deleteCampaign({{ $ad->id }})" wire:confirm="Delete this campaign?" class="text-xs text-danger-500 hover:text-danger-700 font-medium">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="py-12 text-center text-text-secondary text-sm">No Meta Ad campaigns yet. Create your first one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
