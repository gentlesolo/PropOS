<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Lease Management</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track leases, renewals, and rent collection</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="$toggle('showPaymentForm')" class="inline-flex items-center gap-2 px-4 py-2 border border-success-300 text-success-700 rounded-xl text-sm font-medium hover:bg-success-50 transition-colors">
                Record Payment
            </button>
            <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Lease
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-panel rounded-2xl border border-success-200 p-4 text-center">
            <div class="text-2xl font-bold text-success-600">{{ $stats['active'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Active Leases</div>
        </div>
        <div class="glass-panel rounded-2xl border border-warning-200 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['expiring'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Expiring in 60 Days</div>
        </div>
        <div class="glass-panel rounded-2xl border border-danger-200 p-4 text-center">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['overdue_payments'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Overdue Payments</div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-lg font-bold text-text-primary">R{{ number_format($stats['total_rent_due']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Total Rent Due</div>
        </div>
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Create New Lease</h2>
        <form wire:submit.prevent="createLease" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Tenant *</label>
                <select wire:model="tenant_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select tenant…</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t->id }}">{{ $t->contact?->full_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Property *</label>
                <select wire:model="listing_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select property…</option>
                    @foreach($listings as $l)
                    <option value="{{ $l->id }}">{{ $l->property?->address ?? 'Listing #'.$l->id }}</option>
                    @endforeach
                </select>
                @error('listing_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Start Date *</label>
                <input wire:model="start_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('start_date') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">End Date *</label>
                <input wire:model="end_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('end_date') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Monthly Rent *</label>
                <input wire:model="monthly_rent" type="number" min="1" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('monthly_rent') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Deposit</label>
                <input wire:model="deposit_amount" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Annual Escalation (%)</label>
                <input wire:model="escalation_percent" type="number" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Payment Day of Month</label>
                <select wire:model="payment_day" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['1','2','3','5','7','10','15','25','28','30'] as $d)
                    <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Create Lease + Generate Schedule</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    @if($showPaymentForm)
    <div class="glass-panel rounded-2xl border border-success-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Record Rent Payment</h2>
        <form wire:submit.prevent="recordPayment" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Lease *</label>
                <select wire:model="payment_lease_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select lease…</option>
                    @foreach($leases->getCollection() as $l)
                    <option value="{{ $l->id }}">{{ $l->tenant?->contact?->full_name }} — R{{ number_format($l->monthly_rent) }}/mo</option>
                    @endforeach
                </select>
                @error('payment_lease_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Amount Paid *</label>
                <input wire:model="amount_paid" type="number" min="0.01" step="0.01" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Date Paid *</label>
                <input wire:model="paid_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Payment Method</label>
                <select wire:model="payment_method" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="eft">EFT / Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors">Record Payment</button>
                <button type="button" wire:click="$set('showPaymentForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    @if($showTerminateForm)
    <div class="glass-panel rounded-2xl border border-danger-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Terminate Lease</h2>
        <form wire:submit.prevent="terminateLease" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Termination Date *</label>
                <input wire:model="termination_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('termination_date') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Reason *</label>
                <textarea wire:model="termination_reason" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="State reason for termination…"></textarea>
                @error('termination_reason') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-danger-600 text-white rounded-xl text-sm font-medium hover:bg-danger-700 transition-colors">Confirm Termination</button>
                <button type="button" wire:click="$set('showTerminateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Leases Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <div class="px-4 py-3 border-b border-border-default flex flex-wrap gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tenant…"
                class="flex-1 min-w-[200px] rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Statuses</option>
                @foreach(['active','expiring_soon','renewed','terminated','expired'] as $s)
                <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tenant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Monthly Rent</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Expires</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($leases as $lease)
                @php $daysLeft = $lease->daysUntilExpiry; @endphp
                <tr class="hover:bg-surface-hover/30 transition-colors cursor-pointer" wire:click="selectLease({{ $lease->id }})">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $lease->tenant?->contact?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $lease->listing?->property?->address ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-text-primary">R{{ number_format($lease->monthly_rent) }}</td>
                    <td class="px-4 py-3">
                        @php $sc = match($lease->status){ 'active'=>'success','expiring_soon'=>'warning','terminated'=>'danger','renewed'=>'brand',default=>'secondary' }; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst(str_replace('_',' ',$lease->status)) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs text-text-secondary">{{ $lease->end_date->format('d M Y') }}</div>
                        @if($daysLeft > 0 && $daysLeft <= 60)
                        <div class="text-xs text-warning-600 font-medium">{{ $daysLeft }}d left</div>
                        @elseif($daysLeft < 0)
                        <div class="text-xs text-danger-600 font-medium">Expired</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5" wire:click.stop>
                            @if(in_array($lease->status, ['active','renewed']) && $daysLeft <= 60)
                            <button wire:click="renewLease({{ $lease->id }})" class="text-xs px-2.5 py-1 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100 transition-colors">Renew</button>
                            <button wire:click="sendRenewalOffer({{ $lease->id }})" class="text-xs px-2.5 py-1 bg-surface-hover text-text-secondary border border-border-default rounded-lg hover:bg-surface-hover/80 transition-colors">Offer</button>
                            @endif
                            @if(in_array($lease->status, ['active','renewed']))
                            <button wire:click="openTerminateForm({{ $lease->id }})" class="text-xs px-2.5 py-1 bg-danger-50 text-danger-700 border border-danger-200 rounded-lg hover:bg-danger-100 transition-colors">Terminate</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">No leases found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">{{ $leases->links() }}</div>
    </div>

    <!-- Lease Detail Side Panel -->
    @if($selectedLease)
    <div class="fixed inset-y-0 right-0 w-96 bg-surface-primary border-l border-border-default shadow-2xl z-40 overflow-y-auto" wire:click.stop>
        <div class="p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-semibold text-text-primary">{{ $selectedLease->reference }}</h3>
                    <p class="text-xs text-text-secondary mt-0.5">{{ $selectedLease->tenant?->contact?->full_name }}</p>
                </div>
                <button wire:click="$set('selectedLeaseId', null)" class="p-1.5 rounded-lg hover:bg-surface-hover text-text-tertiary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-5">
                @foreach(['overview' => 'Overview', 'payments' => 'Payments'] as $tab => $label)
                <button wire:click="$set('detailTab','{{ $tab }}')" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $detailTab === $tab ? 'bg-brand-primary text-white' : 'text-text-secondary hover:bg-surface-hover' }}">{{ $label }}</button>
                @endforeach
            </div>

            @if($detailTab === 'overview')
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-text-secondary">Property</dt><dd class="font-medium text-text-primary text-right max-w-[180px]">{{ $selectedLease->listing?->property?->address ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">Monthly Rent</dt><dd class="font-bold text-text-primary">R{{ number_format($selectedLease->monthly_rent) }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">Deposit</dt><dd class="font-medium text-text-primary">{{ $selectedLease->deposit_amount ? 'R'.number_format($selectedLease->deposit_amount) : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">Escalation</dt><dd class="font-medium text-text-primary">{{ $selectedLease->escalation_percent }}% p.a.</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">Payment Day</dt><dd class="font-medium text-text-primary">{{ $selectedLease->payment_day }} of month</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">Start</dt><dd class="font-medium text-text-primary">{{ $selectedLease->start_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">End</dt><dd class="font-medium text-text-primary">{{ $selectedLease->end_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-secondary">Outstanding</dt><dd class="font-bold {{ $selectedLease->outstandingBalance > 0 ? 'text-danger-600' : 'text-success-600' }}">R{{ number_format($selectedLease->outstandingBalance) }}</dd></div>
            </dl>
            @if($selectedLease->special_conditions)
            <div class="mt-4 p-3 bg-surface-hover/30 rounded-xl">
                <p class="text-xs font-semibold text-text-secondary mb-1">Special Conditions</p>
                <p class="text-sm text-text-primary">{{ $selectedLease->special_conditions }}</p>
            </div>
            @endif

            @elseif($detailTab === 'payments')
            <div class="space-y-2">
                @forelse($selectedLease->rentPayments->sortByDesc('due_date') as $payment)
                @php $pc = match($payment->status){ 'paid'=>'success','overdue'=>'danger','partial'=>'warning',default=>'secondary' }; @endphp
                <div class="flex items-center justify-between p-3 bg-surface-hover/30 rounded-xl">
                    <div>
                        <div class="text-xs font-mono text-text-tertiary">{{ $payment->reference }}</div>
                        <div class="text-sm font-medium text-text-primary">R{{ number_format($payment->amount_due) }}</div>
                        <div class="text-xs text-text-secondary">Due {{ $payment->due_date->format('d M Y') }}</div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($payment->status) }}</span>
                        @if($payment->amount_paid)
                        <div class="text-xs text-text-secondary mt-1">Paid R{{ number_format($payment->amount_paid) }}</div>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-6">No payment records.</p>
                @endforelse
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
