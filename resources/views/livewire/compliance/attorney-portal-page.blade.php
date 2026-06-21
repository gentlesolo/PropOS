<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Attorney Portal</h1>
            <p class="mt-2 text-text-secondary">Assign conveyancing attorneys to transactions and track their engagement.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-success-600">{{ $stats['with_attorney'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">Attorney Assigned</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-warning-600">{{ $stats['without_attorney'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">No Attorney Yet</p>
        </div>
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-brand-primary">{{ $stats['in_conveyancing'] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">In Conveyancing</p>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-4">
        <input wire:model.debounce.300ms="search" type="text" placeholder="Search transactions by deal title..."
            class="w-full max-w-sm px-3 py-2 border border-border-strong rounded-xl bg-white/50 focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page text-sm">
    </div>

    <!-- Transaction List -->
    <div class="space-y-4">
        @forelse($transactions as $transaction)
        <div class="bg-surface-card rounded-2xl border border-border-default p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-base font-bold text-text-primary truncate">{{ $transaction->deal?->title }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase shrink-0
                            @if($transaction->status === 'completed') bg-success-100 text-success-700
                            @elseif($transaction->status === 'conveyancing') bg-brand-primary/10 text-brand-primary
                            @elseif($transaction->status === 'registration') bg-brand-primary/10 text-brand-primary
                            @else bg-warning-100 text-warning-700 @endif">
                            {{ str_replace('_', ' ', $transaction->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-text-secondary">
                        <span>{{ $transaction->reference }}</span>
                        @if($transaction->contact)
                        <span>Client: {{ $transaction->contact->first_name }} {{ $transaction->contact->last_name }}</span>
                        @endif
                        <span>Sale: {{ $currencySymbol }}{{ number_format($transaction->sale_price / 1000000, 1) }}M</span>
                        <span>FICA: {{ $transaction->ficaProgress }}%</span>
                    </div>
                </div>

                <!-- Attorney Info or Assign -->
                <div class="shrink-0">
                    @if($transaction->attorney)
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-sm font-bold text-text-primary">{{ $transaction->attorney->first_name }} {{ $transaction->attorney->last_name }}</p>
                            <p class="text-xs text-text-secondary">{{ $transaction->attorney->email }}</p>
                            @if($transaction->attorney->job_title)
                            <p class="text-xs text-text-secondary">{{ $transaction->attorney->job_title }}</p>
                            @endif
                        </div>
                        <button wire:click="removeAttorney({{ $transaction->id }})"
                            wire:confirm="Remove this attorney from the transaction?"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-danger-500 border border-danger-200 rounded-lg px-2 py-1 hover:bg-danger-50 transition-colors font-medium" wire:loading.attr="disabled" wire:target="removeAttorney">
                <span wire:loading.remove wire:target="removeAttorney">Remove</span>
                <span wire:loading wire:target="removeAttorney" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <a href="{{ route('compliance.transaction.detail', $transaction) }}"
                            class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2 py-1 hover:bg-brand-primary/5 transition-colors font-medium">
                            View TXN
                        </a>
                    </div>
                    @elseif($assigningTransactionId === $transaction->id)
                    <form wire:submit.prevent="assignAttorney({{ $transaction->id }})" class="space-y-2 w-72">
                        <input wire:model.defer="attorney_name" type="text" placeholder="Full Name *"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @error('attorney_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        <input wire:model.defer="attorney_email" type="email" placeholder="Email *"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <input wire:model.defer="attorney_firm" type="text" placeholder="Law Firm"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <input wire:model.defer="attorney_phone" type="text" placeholder="Phone"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-bold hover:bg-brand-secondary transition-colors">
                                <span wire:loading.remove wire:target="assignAttorney">Assign</span>
                                <span wire:loading wire:target="assignAttorney">...</span>
                            </button>
                            <button type="button" wire:click="$set('assigningTransactionId', null)" class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-1.5 border border-border-default text-text-secondary rounded-lg text-xs font-medium hover:bg-surface-sunken transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>
                    </form>
                    @else
                    <div class="flex gap-2">
                        <button wire:click="$set('assigningTransactionId', {{ $transaction->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-bold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">+ Assign Attorney</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <a href="{{ route('compliance.transaction.detail', $transaction) }}"
                            class="px-3 py-1.5 border border-border-default text-text-secondary rounded-lg text-xs font-medium hover:bg-surface-sunken transition-colors">
                            View TXN
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-14 bg-surface-card rounded-2xl border border-border-default">
            <div class="h-14 w-14 bg-brand-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">??</div>
            <p class="text-sm font-medium text-text-primary">No active transactions found.</p>
            <p class="text-xs text-text-secondary mt-1">Initiate transactions from the <a href="{{ route('compliance.transactions') }}" class="text-brand-primary underline">Transaction Center</a>.</p>
        </div>
        @endforelse
    </div>
</div>



