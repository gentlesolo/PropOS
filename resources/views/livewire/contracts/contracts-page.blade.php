<div class="flex gap-0 h-full">

    {{-- ══ Main column ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Contracts</h1>
                <p class="text-sm text-text-secondary mt-0.5">Manage sale agreements, leases, mandates and MOUs</p>
            </div>
            <button wire:click="openCreateForm"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="openCreateForm">
                <span wire:loading.remove wire:target="openCreateForm"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Contract</span>
                <span wire:loading wire:target="openCreateForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach([['label'=>'Drafts','val'=>$stats['draft'],'c'=>'secondary'],['label'=>'Awaiting Sign','val'=>$stats['sent'],'c'=>'warning'],['label'=>'Fully Signed','val'=>$stats['signed'],'c'=>'success'],['label'=>'Total','val'=>$stats['total'],'c'=>'brand']] as $s)
            <div class="bg-surface-card rounded-2xl border border-{{ $s['c'] }}-200 p-4 text-center">
                <div class="text-2xl font-bold text-{{ $s['c'] }}-600">{{ $s['val'] }}</div>
                <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Create form --}}
        @if($showCreateForm)
        <div class="bg-surface-card rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Create New Contract</h2>
                <button wire:click="$set('showCreateForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">&times;</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            <form wire:submit.prevent="createContract" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                    <input wire:model="title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page" placeholder="e.g. Sale Agreement — 12 Elm Street">
                    @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Generate From Template</label>
                    <select wire:model.live="selectedTemplate" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="">Manual Entry (No Template)</option>
                        <option value="sale_agreement">Standard Sale Agreement</option>
                        <option value="lease_agreement">Residential Lease Agreement</option>
                        <option value="mandate">Exclusive Seller Mandate</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Type *</label>
                    <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
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
                    <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="">None</option>
                        @foreach($deals as $deal)
                        <option value="{{ $deal->id }}">{{ $deal->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Contact</label>
                    <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="">None</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Valid From</label>
                    <input wire:model="valid_from" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Valid Until</label>
                    <input wire:model="valid_until" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Contract Body</label>
                    <textarea wire:model="body" rows="8" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none font-mono" placeholder="Paste or type contract text here…"></textarea>
                </div>
                <div class="md:col-span-2 flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                        <span wire:loading.remove wire:target="createContract">Create Draft</span>
                        <span wire:loading wire:target="createContract">Creating…</span>
                    </button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Edit form (draft only) --}}
        @if($showEditForm)
        <div class="bg-surface-card rounded-2xl border border-warning-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Edit Contract (Draft)</h2>
                <button wire:click="cancelEdit" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none" wire:loading.attr="disabled" wire:target="cancelEdit">
                <span wire:loading.remove wire:target="cancelEdit">&times;</span>
                <span wire:loading wire:target="cancelEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            <form wire:submit.prevent="saveEdit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                    <input wire:model="edit_title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('edit_title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Valid From</label>
                    <input wire:model="edit_valid_from" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Valid Until</label>
                    <input wire:model="edit_valid_until" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Contract Body</label>
                    <textarea wire:model="edit_body" rows="8" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none font-mono"></textarea>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Save Changes</button>
                    <button type="button" wire:click="cancelEdit" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="cancelEdit">
                <span wire:loading.remove wire:target="cancelEdit">Cancel</span>
                <span wire:loading wire:target="cancelEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Filters --}}
        <div class="flex flex-wrap gap-3 mb-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by title or reference…"
                class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
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

        {{-- Table --}}
        <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-hover/50 border-b border-border-default">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Valid Until</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @forelse($contracts as $contract)
                    @php
                        $sc     = $contract->statusColor;
                        $active = $detailContractId === $contract->id && $showDetail;
                    @endphp
                    <tr wire:click="openDetail({{ $contract->id }})"
                        class="cursor-pointer transition-colors {{ $active ? 'bg-brand-50/30' : 'hover:bg-surface-hover/30' }}">
                        <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ $contract->reference }}</td>
                        <td class="px-4 py-3 font-medium text-text-primary">{{ $contract->title }}</td>
                        <td class="px-4 py-3 text-text-secondary text-xs">{{ ucwords(str_replace('_', ' ', $contract->type)) }}</td>
                        <td class="px-4 py-3 text-text-secondary text-sm">{{ $contract->contact?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">
                                {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-text-secondary text-xs">{{ $contract->valid_until?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3" wire:click.stop>
                            <div class="flex items-center gap-1 justify-end">
                                @if($contract->status === 'draft')
                                <button wire:click="sendForSignature({{ $contract->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-2.5 py-1 text-xs bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="sendForSignature">
                <span wire:loading.remove wire:target="sendForSignature">Send eSign</span>
                <span wire:loading wire:target="sendForSignature" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                                @endif
                                @if(in_array($contract->status, ['draft','cancelled']))
                                <button wire:click="deleteContract({{ $contract->id }})" onclick="return confirm('Delete this contract?')" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-2.5 py-1 text-xs text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="deleteContract">
                <span wire:loading.remove wire:target="deleteContract">Del</span>
                <span wire:loading wire:target="deleteContract" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
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

    {{-- ══ Detail / Preview panel ═══════════════════════════════════════════════ --}}
    @if($showDetail && $detailContract)
    <div class="border-l border-border-default bg-surface-card overflow-y-auto flex-shrink-0" style="width:24rem">
        <div class="p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="min-w-0">
                    <div class="font-semibold text-text-primary text-sm leading-snug">{{ $detailContract->title }}</div>
                    <div class="text-xs text-text-tertiary font-mono mt-0.5">{{ $detailContract->reference }}</div>
                </div>
                <button wire:click="closeDetail" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none ml-2 shrink-0" wire:loading.attr="disabled" wire:target="closeDetail">
                <span wire:loading.remove wire:target="closeDetail">&times;</span>
                <span wire:loading wire:target="closeDetail" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>

            @php $sc = $detailContract->statusColor; @endphp

            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">
                    {{ ucwords(str_replace('_', ' ', $detailContract->status)) }}
                </span>
                <span class="text-xs text-text-tertiary">{{ ucwords(str_replace('_', ' ', $detailContract->type)) }}</span>
            </div>

            {{-- Details --}}
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Details</div>
                <div class="space-y-1.5 text-xs">
                    @if($detailContract->contact)
                    <div class="flex justify-between"><span class="text-text-secondary">Contact</span><span class="text-text-primary font-medium">{{ $detailContract->contact->full_name }}</span></div>
                    @endif
                    @if($detailContract->deal)
                    <div class="flex justify-between"><span class="text-text-secondary">Deal</span><span class="text-text-primary truncate max-w-40 text-right">{{ $detailContract->deal->title }}</span></div>
                    @endif
                    @if($detailContract->listing?->property)
                    <div class="flex justify-between"><span class="text-text-secondary">Property</span><span class="text-text-primary truncate max-w-40 text-right">{{ $detailContract->listing->property->address_line_1 }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-text-secondary">Valid From</span><span class="text-text-primary">{{ $detailContract->valid_from?->format('d M Y') ?? '—' }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Valid Until</span>
                        <span class="{{ $detailContract->valid_until?->isPast() ? 'text-danger-600 font-semibold' : 'text-text-primary' }}">
                            {{ $detailContract->valid_until?->format('d M Y') ?? '—' }}
                        </span>
                    </div>
                    @if($detailContract->createdBy)
                    <div class="flex justify-between"><span class="text-text-secondary">Created by</span><span class="text-text-primary">{{ $detailContract->createdBy->first_name ?? $detailContract->createdBy->name }}</span></div>
                    @endif
                </div>
            </div>

            {{-- Signatories --}}
            @if(!empty($detailContract->signatories))
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Signatories</div>
                <div class="space-y-2">
                    @foreach($detailContract->signatories as $sig)
                        @if(is_array($sig))
                        <div class="flex items-center justify-between text-xs">
                            <div>
                                <span class="text-text-primary font-medium">{{ $sig['name'] ?? '—' }}</span>
                                <span class="ml-1 text-text-tertiary capitalize">({{ $sig['role'] ?? '' }})</span>
                            </div>
                            @if(!empty($sig['sent_at']))
                            <span class="text-success-600 font-medium">Sent</span>
                            @endif
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Body preview --}}
            @if($detailContract->body)
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Contract Body</div>
                <pre class="text-xs text-text-secondary leading-relaxed whitespace-pre-wrap font-mono overflow-auto max-h-72">{{ $detailContract->body }}</pre>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-2 mt-4">
                @if($detailContract->status === 'draft')
                    <button wire:click="sendForSignature({{ $detailContract->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="sendForSignature">
                <span wire:loading.remove wire:target="sendForSignature">Send for eSignature</span>
                <span wire:loading wire:target="sendForSignature" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    <button wire:click="openEditForm({{ $detailContract->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="openEditForm">
                <span wire:loading.remove wire:target="openEditForm">Edit Contract</span>
                <span wire:loading wire:target="openEditForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    <button wire:click="deleteContract({{ $detailContract->id }})" onclick="return confirm('Delete this contract?')" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="deleteContract">
                <span wire:loading.remove wire:target="deleteContract">Delete</span>
                <span wire:loading wire:target="deleteContract" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @elseif(in_array($detailContract->status, ['sent','viewed']))
                    <a href="{{ route('contracts.sign', $detailContract->reference) }}" target="_blank"
                        class="block w-full text-center py-2 border border-brand-primary text-brand-primary rounded-xl text-sm font-medium hover:bg-brand-primary/10 transition-colors">
                        Open Sign Page
                    </a>
                @elseif($detailContract->status === 'cancelled')
                    <button wire:click="deleteContract({{ $detailContract->id }})" onclick="return confirm('Delete this contract?')" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="deleteContract">
                <span wire:loading.remove wire:target="deleteContract">Delete</span>
                <span wire:loading wire:target="deleteContract" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endif

                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Update Status</label>
                    <select wire:change="updateStatus({{ $detailContract->id }}, $event.target.value)"
                        class="w-full text-xs rounded-lg border border-border-default bg-surface-input px-3 py-2 text-text-secondary">
                        @foreach(['draft','sent','viewed','signed_buyer','signed_seller','fully_signed','cancelled'] as $s)
                        <option value="{{ $s }}" @selected($detailContract->status === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>



