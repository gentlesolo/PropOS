<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Deposit Management</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track security deposits, deductions, and refunds</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-surface-card rounded-2xl border border-brand-200 p-4 text-center">
            <div class="text-xl font-bold text-brand-600">R{{ number_format($stats['total_held']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Total Deposits Held</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-success-200 p-4 text-center">
            <div class="text-2xl font-bold text-success-600">{{ $stats['refunded'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Deposits Refunded</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-warning-200 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['pending_count'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Pending Refunds</div>
        </div>
    </div>

    @if($showRefundForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Process Deposit Refund</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Refund Date *</label>
                <input wire:model="refund_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('refund_date') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Deductions -->
        <div class="mb-4">
            <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Deductions</p>
            @foreach($deductions as $i => $ded)
            <div class="flex items-center gap-3 mb-2 p-2 bg-surface-hover/30 rounded-lg">
                <span class="text-sm text-text-primary flex-1">{{ $ded['description'] }}</span>
                <span class="text-sm font-medium text-text-primary">R{{ number_format($ded['amount'], 2) }}</span>
                <button type="button" wire:click="removeDeduction({{ $i }})" class="text-danger-600 hover:text-danger-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endforeach
            <div class="grid grid-cols-2 gap-3 mt-2">
                <input wire:model="deduction_description" type="text" placeholder="Description…" class="rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                <div class="flex gap-2">
                    <input wire:model="deduction_amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="flex-1 rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <button type="button" wire:click="addDeduction" class="px-3 py-2 bg-surface-hover border border-border-default rounded-lg text-xs text-text-secondary hover:bg-surface-hover/80">Add</button>
                </div>
            </div>
            @if(count($deductions) > 0)
            <div class="mt-2 text-sm text-text-secondary text-right">
                Total deductions: <span class="font-bold text-text-primary">R{{ number_format(collect($deductions)->sum('amount'), 2) }}</span>
            </div>
            @endif
        </div>

        <div class="flex gap-3">
            <button wire:click="processRefund" wire:loading.attr="disabled" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Confirm Refund</button>
            <button wire:click="$set('showRefundForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
        </div>
    </div>
    @endif

    <!-- Search -->
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tenant…"
            class="w-full md:w-72 rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
    </div>

    <!-- Deposit Table -->
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tenant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Deposit</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Lease Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Refund Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($leases as $lease)
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $lease->tenant?->contact?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $lease->listing?->property?->address ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-text-primary">R{{ number_format($lease->deposit_amount) }}</td>
                    <td class="px-4 py-3">
                        @php $sc = match($lease->status){ 'active'=>'success','terminated'=>'danger','renewed'=>'brand',default=>'secondary' }; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst($lease->status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($lease->deposit_refunded_at)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-success-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-xs text-success-700">Refunded {{ $lease->deposit_refunded_at->format('d M Y') }}</span>
                        </div>
                        @if($lease->deposit_deductions && count($lease->deposit_deductions))
                        <div class="text-xs text-text-tertiary mt-0.5">Deductions: R{{ number_format(collect($lease->deposit_deductions)->sum('amount'), 2) }}</div>
                        @endif
                        @else
                        <span class="text-xs text-text-tertiary">Held</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if(!$lease->deposit_refunded_at && in_array($lease->status, ['terminated', 'vacated', 'expired']))
                        <button wire:click="openRefundForm({{ $lease->id }})" class="text-xs px-2.5 py-1 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100 transition-colors">Process Refund</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">No deposits found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">{{ $leases->links() }}</div>
    </div>
</div>



