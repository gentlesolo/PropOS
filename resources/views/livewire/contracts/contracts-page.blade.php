<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Contracts</h1>
            <p class="text-sm text-text-secondary mt-0.5">Manage sale agreements, leases, mandates and MOUs</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Contract
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([['label'=>'Drafts','val'=>$stats['draft'],'color'=>'secondary'],['label'=>'Awaiting Signature','val'=>$stats['sent'],'color'=>'warning'],['label'=>'Fully Signed','val'=>$stats['signed'],'color'=>'success'],['label'=>'Total','val'=>$stats['total'],'color'=>'brand']] as $s)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $s['val'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Create New Contract</h2>
        <form wire:submit.prevent="createContract" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                <input wire:model="title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="e.g. Sale Agreement — 12 Elm Street">
                @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Generate From Template</label>
                <select wire:model.live="selectedTemplate" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Manual Entry (No Template)</option>
                    <option value="sale_agreement">Standard Sale Agreement</option>
                    <option value="lease_agreement">Residential Lease Agreement</option>
                    <option value="mandate">Exclusive Seller Mandate</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Type *</label>
                <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="sale_agreement">Sale Agreement</option>
                    <option value="lease_agreement">Lease Agreement</option>
                    <option value="mou">MOU</option>
                    <option value="mandate">Mandate</option>
                    <option value="offer_to_purchase">Offer to Purchase</option>
                    <option value="addendum">Addendum</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Linked Deal</label>
                <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None</option>
                    @foreach($deals as $deal)
                    <option value="{{ $deal->id }}">{{ $deal->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Contact</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Valid From</label>
                <input wire:model="valid_from" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Valid Until</label>
                <input wire:model="valid_until" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Contract Body</label>
                <textarea wire:model="body" rows="6" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none font-mono" placeholder="Paste or type contract text here…"></textarea>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="createContract">Create Draft</span>
                    <span wire:loading wire:target="createContract">Creating…</span>
                </button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by title or reference…"
            class="flex-1 min-w-[200px] rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['draft','sent','viewed','signed_buyer','signed_seller','fully_signed','cancelled','expired'] as $s)
            <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <select wire:model.live="typeFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Types</option>
            @foreach(['sale_agreement','lease_agreement','mou','mandate','offer_to_purchase','addendum','other'] as $t)
            <option value="{{ $t }}">{{ ucwords(str_replace('_', ' ', $t)) }}</option>
            @endforeach
        </select>
    </div>

    <!-- Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Valid Until</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($contracts as $contract)
                @php $sc = $contract->statusColor; @endphp
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ $contract->reference }}</td>
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $contract->title }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ ucwords(str_replace('_', ' ', $contract->type)) }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $contract->contact?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">
                            {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $contract->valid_until?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <select wire:change="updateStatus({{ $contract->id }}, $event.target.value)" class="text-xs rounded-lg border border-border-default bg-surface-input px-2 py-1 text-text-secondary">
                                @foreach(['draft','sent','viewed','signed_buyer','signed_seller','fully_signed','cancelled'] as $s)
                                <option value="{{ $s }}" @selected($contract->status === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                            
                            @if($contract->status === 'draft')
                            <button wire:click="sendForSignature({{ $contract->id }})" class="px-2.5 py-1 text-xs bg-brand-primary text-white rounded-lg hover:bg-brand-secondary transition-colors" title="Send eSignature Request">
                                Send eSign
                            </button>
                            @endif

                            @if(in_array($contract->status, ['sent', 'viewed']))
                            <a href="{{ route('contracts.sign', $contract->reference) }}" target="_blank" class="px-2.5 py-1 text-xs border border-brand-primary text-brand-primary hover:bg-brand-primary/10 rounded-lg transition-colors" title="Copy Sign Link">
                                Sign Page
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-text-tertiary text-sm">No contracts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">{{ $contracts->links() }}</div>
    </div>
</div>
