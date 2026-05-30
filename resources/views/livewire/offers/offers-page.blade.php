<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Offers</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track and manage all property offers</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Offer
        </button>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([['label'=>'Total','val'=>$stats['total'],'color'=>'brand'],['label'=>'Pending','val'=>$stats['pending'],'color'=>'warning'],['label'=>'Accepted','val'=>$stats['accepted'],'color'=>'success'],['label'=>'Countered','val'=>$stats['countered'],'color'=>'brand']] as $stat)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $stat['val'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    <!-- Create Form -->
    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Submit New Offer</h2>
        <form wire:submit.prevent="createOffer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Deal *</label>
                <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select deal…</option>
                    @foreach($deals as $deal)
                    <option value="{{ $deal->id }}">{{ $deal->title }}</option>
                    @endforeach
                </select>
                @error('deal_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Contact *</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select contact…</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
                @error('contact_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Offer Amount (₦) *</label>
                <input wire:model="amount" type="number" min="1" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('amount') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Type</label>
                <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="sale">Sale</option>
                    <option value="rental">Rental</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Expiry Date</label>
                <input wire:model="expiry_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Deposit Amount (₦)</label>
                <input wire:model="deposit_amount" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Conditions</label>
                <textarea wire:model="conditions" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none" placeholder="Subject to bond approval, inspection, etc."></textarea>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="createOffer">Submit Offer</span>
                    <span wire:loading wire:target="createOffer">Submitting…</span>
                </button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by contact name…"
            class="flex-1 min-w-[200px] rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['pending','countered','accepted','rejected','expired','withdrawn'] as $s)
            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    <!-- Offers Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Deal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Expiry</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($offers as $offer)
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $offer->contact?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $offer->deal?->title ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-text-primary">₦{{ number_format($offer->amount) }}</td>
                    <td class="px-4 py-3">
                        @php $colors = ['accepted'=>'success','rejected'=>'danger','countered'=>'warning','pending'=>'brand','expired'=>'secondary','withdrawn'=>'secondary']; $c = $colors[$offer->status] ?? 'secondary'; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">
                            {{ ucfirst($offer->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">{{ $offer->expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($offer->status === 'pending')
                        <div class="flex gap-2">
                            <button wire:click="acceptOffer({{ $offer->id }})" class="text-xs px-2.5 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100 transition-colors">Accept</button>
                            <button wire:click="rejectOffer({{ $offer->id }})" class="text-xs px-2.5 py-1 bg-danger-50 text-danger-700 border border-danger-200 rounded-lg hover:bg-danger-100 transition-colors">Reject</button>
                        </div>
                        @else
                        <span class="text-xs text-text-tertiary">{{ $offer->responded_at?->diffForHumans() ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">No offers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">
            {{ $offers->links() }}
        </div>
    </div>
</div>
