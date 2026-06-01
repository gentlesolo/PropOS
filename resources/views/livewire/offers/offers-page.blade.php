<div class="flex gap-0 h-full">

    {{-- ══ Main column ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Offers</h1>
                <p class="text-sm text-text-secondary mt-0.5">Track, counter, and respond to all property offers</p>
            </div>
            <button wire:click="openCreateForm"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Offer
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach([['label'=>'Total','val'=>$stats['total'],'c'=>'brand'],['label'=>'Pending','val'=>$stats['pending'],'c'=>'warning'],['label'=>'Accepted','val'=>$stats['accepted'],'c'=>'success'],['label'=>'Countered','val'=>$stats['countered'],'c'=>'brand']] as $s)
            <div class="glass-panel rounded-2xl border border-{{ $s['c'] }}-200 p-4 text-center">
                <div class="text-2xl font-bold text-{{ $s['c'] }}-600">{{ $s['val'] }}</div>
                <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Create form --}}
        @if($showCreateForm)
        <div class="glass-panel rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Submit New Offer</h2>
                <button wire:click="$set('showCreateForm', false)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="createOffer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Deal *</label>
                    <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="">Select deal…</option>
                        @foreach($deals as $d)
                        <option value="{{ $d->id }}">{{ $d->title }}</option>
                        @endforeach
                    </select>
                    @error('deal_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Contact (Buyer) *</label>
                    <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="">Select contact…</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                    @error('contact_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Offer Amount ({{ $currencySymbol }}) *</label>
                    <input wire:model="amount" type="number" min="1" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @error('amount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Type</label>
                    <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        <option value="sale">Sale</option>
                        <option value="rental">Rental</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Expiry Date</label>
                    <input wire:model="expiry_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Deposit Amount ({{ $currencySymbol }})</label>
                    <input wire:model="deposit_amount" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Proposed Occupation Date</label>
                    <input wire:model="proposed_occupation_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Conditions</label>
                    <textarea wire:model="conditions" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none" placeholder="Subject to bond approval, inspection, etc."></textarea>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Submit Offer</button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Edit form --}}
        @if($showEditForm)
        <div class="glass-panel rounded-2xl border border-warning-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Edit Offer</h2>
                <button wire:click="cancelEdit" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="saveEdit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Offer Amount ({{ $currencySymbol }}) *</label>
                    <input wire:model="edit_amount" type="number" min="1" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @error('edit_amount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Deposit ({{ $currencySymbol }})</label>
                    <input wire:model="edit_deposit_amount" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Expiry Date</label>
                    <input wire:model="edit_expiry_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Proposed Occupation</label>
                    <input wire:model="edit_proposed_occupation_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Conditions</label>
                    <textarea wire:model="edit_conditions" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Save Changes</button>
                    <button type="button" wire:click="cancelEdit" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Counter form --}}
        @if($showCounterForm)
        <div class="glass-panel rounded-2xl border border-warning-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Submit Counter Offer</h2>
                <button wire:click="$set('showCounterForm', false)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="submitCounter" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Counter Amount ({{ $currencySymbol }}) *</label>
                    <input wire:model="counter_amount" type="number" min="1" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @error('counter_amount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Counter Notes</label>
                    <input wire:model="counter_notes" type="text" placeholder="e.g. No subject-to-sale clause" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Submit Counter</button>
                    <button type="button" wire:click="$set('showCounterForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Filters --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by contact…"
                class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Statuses</option>
                @foreach(['pending','countered','accepted','rejected','expired','withdrawn'] as $s)
                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select wire:model.live="typeFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">Sale &amp; Rental</option>
                <option value="sale">Sale</option>
                <option value="rental">Rental</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-hover/50 border-b border-border-default">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Deal / Property</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Expiry</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @forelse($offers as $offer)
                    @php
                        $colors = ['accepted'=>'success','rejected'=>'danger','countered'=>'warning','pending'=>'brand','expired'=>'secondary','withdrawn'=>'secondary'];
                        $c      = $colors[$offer->status] ?? 'secondary';
                        $active = $detailOfferId === $offer->id && $showDetail;
                    @endphp
                    <tr wire:click="openDetail({{ $offer->id }})"
                        class="cursor-pointer transition-colors {{ $active ? 'bg-brand-50/30' : 'hover:bg-surface-hover/30' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-text-primary">{{ $offer->contact?->full_name ?? '—' }}</div>
                            <div class="text-xs text-text-tertiary capitalize">{{ $offer->type }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-text-secondary">{{ $offer->deal?->title ?? '—' }}</div>
                            <div class="text-xs text-text-tertiary">{{ $offer->listing?->property?->address_line_1 ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($offer->amount) }}</div>
                            @if($offer->counter_amount)
                            <div class="text-xs text-warning-600">Counter: {{ $currencySymbol }}{{ number_format($offer->counter_amount) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">
                                {{ ucfirst($offer->status) }}
                            </span>
                            @if($offer->contract)
                            <div class="text-xs text-success-600 mt-0.5">✓ Contract</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-text-secondary text-xs">{{ $offer->expiry_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3" wire:click.stop>
                            <div class="flex gap-1 justify-end">
                                @if($offer->status === 'pending')
                                    <button wire:click="acceptOffer({{ $offer->id }})" class="text-xs px-2 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Accept</button>
                                    <button wire:click="openCounterForm({{ $offer->id }})" class="text-xs px-2 py-1 bg-warning-50 text-warning-700 border border-warning-200 rounded-lg hover:bg-warning-100">Counter</button>
                                    <button wire:click="rejectOffer({{ $offer->id }})" class="text-xs px-2 py-1 bg-danger-50 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-100">Reject</button>
                                @elseif($offer->status === 'countered')
                                    <button wire:click="acceptOffer({{ $offer->id }})" class="text-xs px-2 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Accept</button>
                                    <button wire:click="rejectOffer({{ $offer->id }})" class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">
                            No offers found.
                            @if($statusFilter || $typeFilter || $search)
                            <button wire:click="$set('statusFilter',''); $set('typeFilter',''); $set('search','')" class="ml-2 text-brand-600 underline text-xs">Clear filters</button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-border-default">{{ $offers->links() }}</div>
        </div>
    </div>

    {{-- ══ Detail panel ═════════════════════════════════════════════════════════ --}}
    @if($showDetail && $detailOffer)
    <div class="w-88 border-l border-border-default bg-surface-card overflow-y-auto flex-shrink-0" style="width:22rem">
        <div class="p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="font-semibold text-text-primary text-sm">{{ $detailOffer->contact?->full_name }}</div>
                    <div class="text-xs text-text-tertiary capitalize mt-0.5">{{ $detailOffer->type }} offer</div>
                </div>
                <button wire:click="closeDetail" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>

            @php $c = ['accepted'=>'success','rejected'=>'danger','countered'=>'warning','pending'=>'brand','expired'=>'secondary','withdrawn'=>'secondary'][$detailOffer->status] ?? 'secondary'; @endphp

            {{-- Amount card --}}
            <div class="glass-panel rounded-2xl border border-{{ $c }}-200 p-4 mb-4 text-center">
                <div class="text-3xl font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($detailOffer->amount) }}</div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200 mt-2">
                    {{ ucfirst($detailOffer->status) }}
                </span>
                @if($detailOffer->counter_amount)
                <div class="text-sm font-semibold text-warning-600 mt-2">Counter: {{ $currencySymbol }}{{ number_format($detailOffer->counter_amount) }}</div>
                @endif
            </div>

            {{-- Details --}}
            <div class="glass-panel rounded-xl border border-border-default/60 p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Details</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between"><span class="text-text-secondary">Deal</span><span class="text-text-primary font-medium text-right max-w-36 truncate">{{ $detailOffer->deal?->title ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Property</span><span class="text-text-primary text-right max-w-36 truncate">{{ $detailOffer->listing?->property?->address_line_1 ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Deposit</span><span class="text-text-primary font-medium">{{ $detailOffer->deposit_amount ? '{{ $currencySymbol }}'.number_format($detailOffer->deposit_amount) : '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Occupation</span><span class="text-text-primary">{{ $detailOffer->proposed_occupation_date?->format('d M Y') ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Expires</span>
                        <span class="{{ $detailOffer->expiry_date?->isPast() ? 'text-danger-600 font-semibold' : 'text-text-primary' }}">
                            {{ $detailOffer->expiry_date?->format('d M Y') ?? '—' }}
                        </span>
                    </div>
                    @if($detailOffer->responded_at)
                    <div class="flex justify-between"><span class="text-text-secondary">Responded</span><span class="text-text-primary">{{ $detailOffer->responded_at->format('d M Y') }}</span></div>
                    @endif
                    @if($detailOffer->submittedBy)
                    <div class="flex justify-between"><span class="text-text-secondary">Submitted by</span><span class="text-text-primary">{{ $detailOffer->submittedBy->first_name }}</span></div>
                    @endif
                </div>
            </div>

            @if($detailOffer->conditions)
            <div class="glass-panel rounded-xl border border-border-default/60 p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Conditions</div>
                <p class="text-xs text-text-secondary leading-relaxed">{{ $detailOffer->conditions }}</p>
            </div>
            @endif

            @if($detailOffer->counter_notes)
            <div class="glass-panel rounded-xl border border-warning-200 p-3 mb-3">
                <div class="text-xs font-semibold text-warning-700 uppercase tracking-wider mb-2">Counter Notes</div>
                <p class="text-xs text-text-secondary">{{ $detailOffer->counter_notes }}</p>
            </div>
            @endif

            @if($detailOffer->notes)
            <div class="glass-panel rounded-xl border border-border-default/60 p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Internal Notes</div>
                <p class="text-xs text-text-secondary">{{ $detailOffer->notes }}</p>
            </div>
            @endif

            @if($detailOffer->contract)
            <div class="glass-panel rounded-xl border border-success-200 p-3 mb-3">
                <div class="text-xs font-semibold text-success-700 uppercase tracking-wider mb-2">Linked Contract</div>
                <div class="text-xs text-text-primary font-mono">{{ $detailOffer->contract->reference }}</div>
                <div class="text-xs text-text-secondary mt-0.5">{{ ucwords(str_replace('_',' ',$detailOffer->contract->type)) }} · {{ ucwords(str_replace('_',' ',$detailOffer->contract->status)) }}</div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-2 mt-4">
                @if($detailOffer->status === 'pending')
                    <button wire:click="acceptOffer({{ $detailOffer->id }})" class="w-full py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors">Accept Offer</button>
                    <button wire:click="openCounterForm({{ $detailOffer->id }})" class="w-full py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Counter Offer</button>
                    <button wire:click="rejectOffer({{ $detailOffer->id }})" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Reject</button>
                    <button wire:click="openEditForm({{ $detailOffer->id }})" class="w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Edit Offer</button>
                    <button wire:click="withdrawOffer({{ $detailOffer->id }})" onclick="return confirm('Withdraw this offer?')" class="w-full py-2 border border-border-default text-text-tertiary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Withdraw</button>
                @elseif($detailOffer->status === 'countered')
                    <button wire:click="acceptOffer({{ $detailOffer->id }})" class="w-full py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors">Accept Counter</button>
                    <button wire:click="openCounterForm({{ $detailOffer->id }})" class="w-full py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Re-Counter</button>
                    <button wire:click="rejectOffer({{ $detailOffer->id }})" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Reject</button>
                    <button wire:click="withdrawOffer({{ $detailOffer->id }})" onclick="return confirm('Withdraw this offer?')" class="w-full py-2 border border-border-default text-text-tertiary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Withdraw</button>
                @else
                    @if(in_array($detailOffer->status, ['pending','expired','withdrawn']))
                    <button wire:click="deleteOffer({{ $detailOffer->id }})" onclick="return confirm('Delete this offer?')" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Delete</button>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
