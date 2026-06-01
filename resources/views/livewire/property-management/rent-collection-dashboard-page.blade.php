<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Rent Collection</h1>
            <p class="text-sm text-text-secondary mt-0.5">Monitor and record rent payments across all leases</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="glass-panel rounded-2xl border border-success-200 p-4 text-center">
            <div class="text-xl font-bold text-success-600">R{{ number_format($stats['collected']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Collected This Month</div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-xl font-bold text-text-primary">R{{ number_format($stats['total_due']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Total Due This Month</div>
        </div>
        <div class="glass-panel rounded-2xl border border-warning-200 p-4 text-center">
            <div class="text-xl font-bold text-warning-600">R{{ number_format($stats['outstanding']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Outstanding Balance</div>
        </div>
        <div class="glass-panel rounded-2xl border border-danger-200 p-4 text-center">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['overdue_count'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Overdue Payments</div>
        </div>
        <div class="glass-panel rounded-2xl border border-brand-200 p-4 text-center">
            <div class="text-2xl font-bold text-brand-600">{{ $stats['collection_rate'] }}%</div>
            <div class="text-xs text-text-secondary mt-1">Collection Rate</div>
        </div>
    </div>

    @if($showPaymentForm)
    <div class="glass-panel rounded-2xl border border-success-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Quick Record Payment</h2>
        <form wire:submit.prevent="quickPay" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Amount Paid *</label>
                <input wire:model="amount_paid" type="number" min="0.01" step="0.01" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Date Paid *</label>
                <input wire:model="paid_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Method</label>
                <select wire:model="payment_method" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="eft">EFT</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div class="md:col-span-3 flex gap-3">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors">Record Payment</button>
                <button type="button" wire:click="$set('showPaymentForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live="monthFilter" type="month" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['pending'=>'Pending','partial'=>'Partial','paid'=>'Paid','overdue'=>'Overdue','waived'=>'Waived'] as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Payments Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tenant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Due Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount Due</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount Paid</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($payments as $payment)
                @php $pc = match($payment->status){ 'paid'=>'success','overdue'=>'danger','partial'=>'warning','waived'=>'secondary',default=>'brand' }; @endphp
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-medium text-text-primary">{{ $payment->lease?->tenant?->contact?->full_name ?? '—' }}</div>
                        <div class="text-xs font-mono text-text-tertiary">{{ $payment->reference }}</div>
                    </td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $payment->lease?->listing?->property?->address ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $payment->due_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-bold text-text-primary">R{{ number_format($payment->amount_due) }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $payment->amount_paid ? 'R'.number_format($payment->amount_paid) : '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($payment->status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if(in_array($payment->status, ['pending','overdue','partial']))
                        <button wire:click="$set('payment_rent_id', '{{ $payment->id }}'); $set('showPaymentForm', true); $set('paid_date', '{{ now()->toDateString() }}')"
                            class="text-xs px-2.5 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100 transition-colors">
                            Record
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-text-tertiary text-sm">No payments found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">{{ $payments->links() }}</div>
    </div>
</div>
