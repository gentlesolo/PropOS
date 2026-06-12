@php
    $statusClasses = [
        'active'        => 'bg-success-500/10 text-success-600 border border-success-500/20',
        'expiring_soon' => 'bg-warning-500/10 text-warning-600 border border-warning-500/20',
        'terminated'    => 'bg-danger-500/10  text-danger-600  border border-danger-500/20',
        'renewed'       => 'bg-brand-primary/10 text-brand-primary border border-brand-primary/20',
        'expired'       => 'bg-surface-raised text-text-tertiary border border-border-default',
    ];
    $paymentClasses = [
        'paid'    => 'bg-success-500/10 text-success-600 border border-success-500/20',
        'overdue' => 'bg-danger-500/10  text-danger-600  border border-danger-500/20',
        'partial' => 'bg-warning-500/10 text-warning-600 border border-warning-500/20',
        'pending' => 'bg-surface-raised text-text-secondary border border-border-default',
        'waived'  => 'bg-surface-raised text-text-tertiary border border-border-default',
    ];
@endphp

<div>
    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Lease Management</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track leases, renewals, and rent collection</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="$toggle('showPaymentForm')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-success-500/30 text-success-600 text-sm font-semibold hover:bg-success-500/5 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Record Payment
            </button>
            <button wire:click="$toggle('showCreateForm')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-primary text-white text-sm font-semibold hover:bg-brand-secondary transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Lease
            </button>
        </div>
    </div>

    {{-- ── Stats ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-surface-card rounded-2xl border border-success-500/20 p-4 text-center">
            <div class="text-2xl font-bold text-success-600">{{ $stats['active'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Active Leases</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-warning-500/20 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['expiring'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Expiring in 90 Days</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-danger-500/20 p-4 text-center">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['overdue_payments'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Overdue Payments</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
            <div class="text-lg font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($stats['total_rent_due']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Total Rent Due</div>
        </div>
    </div>

    {{-- ── Create Lease Form ───────────────────────────────────────────── --}}
    @if($showCreateForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-text-primary">Create New Lease</h2>
            <button wire:click="$set('showCreateForm', false)" class="p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form wire:submit.prevent="createLease" class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Tenant & Property --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Tenant *</label>
                <select wire:model="tenant_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                    <option value="">Select tenant…</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t->id }}">{{ $t->contact?->full_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Property *</label>
                <select wire:model="listing_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                    <option value="">Select property…</option>
                    @foreach($listings as $l)
                    <option value="{{ $l->id }}">{{ $l->property?->address ?? 'Listing #'.$l->id }}</option>
                    @endforeach
                </select>
                @error('listing_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Dates --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Start Date *</label>
                <input wire:model="start_date" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                @error('start_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">End Date *</label>
                <input wire:model="end_date" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                @error('end_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Payment frequency (prominent — Nigerian default is yearly) --}}
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Payment Frequency *</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach(['yearly' => 'Yearly (Annual)', 'bi_yearly' => 'Bi-Yearly (6 months)', 'quarterly' => 'Quarterly (3 months)', 'monthly' => 'Monthly'] as $val => $lbl)
                    <label class="flex items-center gap-2 px-3 py-2.5 rounded-xl border cursor-pointer transition-colors
                        {{ $payment_frequency === $val ? 'border-brand-primary bg-brand-primary/5 text-brand-primary' : 'border-border-default text-text-secondary hover:bg-surface-raised' }}">
                        <input wire:model.live="payment_frequency" type="radio" name="payment_frequency" value="{{ $val }}" class="sr-only">
                        <span class="text-xs font-semibold">{{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>
                @error('payment_frequency') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Rent — label and hint are driven by payment_frequency --}}
            @php
                $rentLabel    = match($payment_frequency) {
                    'quarterly' => 'Quarterly Rent',
                    'bi_yearly' => 'Bi-Yearly Rent (6 months)',
                    'yearly'    => 'Annual Rent',
                    default     => 'Monthly Rent',
                };
                $rentPeriodMonths = match($payment_frequency) { 'quarterly' => 3, 'bi_yearly' => 6, 'yearly' => 12, default => 1 };
                $rentMonthlyEq = ($rent_input && is_numeric($rent_input) && $rentPeriodMonths > 1)
                    ? round((float) $rent_input / $rentPeriodMonths, 2)
                    : null;
            @endphp
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">{{ $rentLabel }} ({{ $currencySymbol }}) *</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-sm text-text-tertiary">{{ $currencySymbol }}</span>
                    <input wire:model.live="rent_input" type="number" min="1"
                        placeholder="{{ $payment_frequency === 'yearly' ? 'e.g. 6000000' : '' }}"
                        class="w-full rounded-xl border border-border-default bg-surface-input pl-6 pr-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                </div>
                @if($rentMonthlyEq)
                <p class="text-xs text-text-tertiary mt-1">≈ {{ $currencySymbol }}{{ number_format($rentMonthlyEq) }}/month equivalent</p>
                @endif
                @error('rent_input') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Caution Deposit --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Caution Deposit</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-sm text-text-tertiary">{{ $currencySymbol }}</span>
                    <input wire:model="deposit_amount" type="number" min="0" class="w-full rounded-xl border border-border-default bg-surface-input pl-6 pr-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                </div>
            </div>

            {{-- Agency fee & Legal fee --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Agency Fee</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-sm text-text-tertiary">{{ $currencySymbol }}</span>
                    <input wire:model="agency_fee" type="number" min="0" placeholder="e.g. 10% of annual rent"
                        class="w-full rounded-xl border border-border-default bg-surface-input pl-6 pr-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Legal / Agreement Fee</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-sm text-text-tertiary">{{ $currencySymbol }}</span>
                    <input wire:model="legal_fee" type="number" min="0" placeholder="e.g. 5% of annual rent"
                        class="w-full rounded-xl border border-border-default bg-surface-input pl-6 pr-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                </div>
            </div>

            {{-- Service charge & Escalation --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Annual Service Charge</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-sm text-text-tertiary">{{ $currencySymbol }}</span>
                    <input wire:model="service_charge" type="number" min="0" placeholder="Estate / facility charge"
                        class="w-full rounded-xl border border-border-default bg-surface-input pl-6 pr-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Annual Rent Escalation (%)</label>
                <input wire:model="escalation_percent" type="number" min="0" max="100"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                <p class="text-xs text-text-tertiary mt-1">Applied to rent on each renewal</p>
            </div>

            {{-- Payment day (only relevant for monthly/quarterly/bi-yearly) --}}
            @if($payment_frequency !== 'yearly')
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Payment Day of Month</label>
                <select wire:model="payment_day" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                    @foreach(['1','2','3','5','7','10','15','25','28','30'] as $d)
                    <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="{{ $payment_frequency !== 'yearly' ? '' : 'md:col-span-2' }}">
                <label class="block text-xs font-medium text-text-secondary mb-1">Special Conditions</label>
                <textarea wire:model="special_conditions" rows="2" placeholder="Any special conditions…"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50"></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Payment Instructions</label>
                <textarea wire:model="bank_account" rows="3" placeholder="e.g. Bank: FNB&#10;Account No: 1234567890&#10;Branch Code: 250655&#10;Reference: Use your lease reference"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary font-mono focus:outline-none focus:border-brand-primary/50"></textarea>
                <p class="text-xs text-text-tertiary mt-1">Bank account details shown to the tenant on their portal.</p>
            </div>

            <div class="md:col-span-2 flex gap-3 pt-1">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-semibold hover:bg-brand-secondary transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="createLease">Creating…</span>
                    <span wire:loading.remove wire:target="createLease">Create Lease + Generate Schedule</span>
                </button>
                <button type="button" wire:click="$set('showCreateForm', false)"
                    class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-raised transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Record Payment Form ─────────────────────────────────────────── --}}
    @if($showPaymentForm)
    <div class="bg-surface-card rounded-2xl border border-success-500/20 p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-text-primary">Record Rent Payment</h2>
            <button wire:click="$set('showPaymentForm', false)" class="p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form wire:submit.prevent="recordPayment" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Lease *</label>
                <select wire:model="payment_lease_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                    <option value="">Select lease…</option>
                    @foreach($leases->getCollection() as $l)
                    <option value="{{ $l->id }}">{{ $l->tenant?->contact?->full_name }} — {{ $currencySymbol }}{{ number_format($l->monthly_rent) }}/mo</option>
                    @endforeach
                </select>
                @error('payment_lease_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Amount Paid *</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-sm text-text-tertiary">{{ $currencySymbol }}</span>
                    <input wire:model="amount_paid" type="number" min="0.01" step="0.01"
                        class="w-full rounded-xl border border-border-default bg-surface-input pl-7 pr-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                </div>
                @error('amount_paid') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Date Paid *</label>
                <input wire:model="paid_date" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                @error('paid_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Payment Method</label>
                <select wire:model="payment_method" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                    <option value="eft">EFT / Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                <input wire:model="payment_notes" type="text" placeholder="Optional payment note…"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
            </div>
            <div class="md:col-span-2 flex gap-3 pt-1">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-success-600 text-white rounded-xl text-sm font-semibold hover:bg-success-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="recordPayment">Recording…</span>
                    <span wire:loading.remove wire:target="recordPayment">Record Payment</span>
                </button>
                <button type="button" wire:click="$set('showPaymentForm', false)"
                    class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-raised transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Terminate Lease Form ────────────────────────────────────────── --}}
    @if($showTerminateForm)
    @php $terminatingLease = $leases->getCollection()->find((int)$terminate_lease_id); @endphp
    <div class="bg-surface-card rounded-2xl border border-danger-500/20 p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-semibold text-text-primary">Terminate Lease</h2>
                @if($terminatingLease)
                <p class="text-xs text-text-secondary mt-0.5">
                    {{ $terminatingLease->tenant?->contact?->full_name }} — {{ $terminatingLease->reference }}
                </p>
                @endif
            </div>
            <button wire:click="$set('showTerminateForm', false)" class="p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form wire:submit.prevent="terminateLease" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Termination Date *</label>
                <input wire:model="termination_date" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                @error('termination_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Reason *</label>
                <textarea wire:model="termination_reason" rows="2" placeholder="State reason for termination…"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50"></textarea>
                @error('termination_reason') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 flex gap-3 pt-1">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-danger-600 text-white rounded-xl text-sm font-semibold hover:bg-danger-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="terminateLease">Terminating…</span>
                    <span wire:loading.remove wire:target="terminateLease">Confirm Termination</span>
                </button>
                <button type="button" wire:click="$set('showTerminateForm', false)"
                    class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-raised transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Leases Table ────────────────────────────────────────────────── --}}
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
        <div class="px-5 py-4 border-b border-border-default flex flex-wrap gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by tenant name…"
                class="flex-1 min-w-[200px] rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
            <select wire:model.live="statusFilter"
                class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                <option value="">All Statuses</option>
                @foreach(['active','expiring_soon','renewed','terminated','expired'] as $s)
                <option value="{{ $s }}">{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-surface-raised/40 border-b border-border-default">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tenant</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Rent</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Expires</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default/60">
                @forelse($leases as $lease)
                @php
                    $daysLeft      = $lease->daysUntilExpiry;
                    $expiryThresh  = ($lease->payment_frequency ?? 'monthly') === 'yearly' ? 90 : 60;
                @endphp
                <tr wire:key="lease-{{ $lease->id }}"
                    class="hover:bg-surface-raised/20 transition-colors cursor-pointer"
                    wire:click="selectLease({{ $lease->id }})">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-text-primary">{{ $lease->tenant?->contact?->full_name ?? '—' }}</div>
                        <div class="text-xs text-text-tertiary font-mono mt-0.5">{{ $lease->reference }}</div>
                    </td>
                    <td class="px-5 py-3.5 text-text-secondary text-xs max-w-[180px] truncate">
                        {{ $lease->listing?->property?->address ?? '—' }}
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="font-bold text-text-primary">
                            {{ $currencySymbol }}{{ number_format($lease->periodRent) }}
                            <span class="text-xs font-normal text-text-tertiary">{{ $lease->rentSuffix }}</span>
                        </div>
                        @if($lease->payment_frequency !== 'monthly')
                        <div class="text-xs text-text-tertiary">{{ $currencySymbol }}{{ number_format($lease->monthly_rent) }}/mo</div>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses[$lease->status] ?? $statusClasses['expired'] }}">
                            {{ ucfirst(str_replace('_', ' ', $lease->status)) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="text-xs text-text-secondary">{{ $lease->end_date->format('d M Y') }}</div>
                        @if($daysLeft > 0 && $daysLeft <= $expiryThresh)
                            <div class="text-xs text-warning-600 font-semibold mt-0.5">{{ $daysLeft }}d left</div>
                        @elseif($daysLeft < 0 && $lease->status !== 'terminated')
                            <div class="text-xs text-danger-600 font-semibold mt-0.5">Expired</div>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-1.5" wire:click.stop>
                            @if(in_array($lease->status, ['active','renewed']) && $daysLeft <= $expiryThresh)
                            <button wire:click="renewLease({{ $lease->id }})" wire:loading.attr="disabled"
                                class="text-xs px-2.5 py-1 rounded-lg border border-brand-primary/30 text-brand-primary hover:bg-brand-primary/5 transition-colors font-semibold">
                                Renew
                            </button>
                            <button wire:click="sendRenewalOffer({{ $lease->id }})" wire:loading.attr="disabled"
                                class="text-xs px-2.5 py-1 rounded-lg border border-border-default text-text-secondary hover:bg-surface-raised transition-colors font-semibold">
                                Send Offer
                            </button>
                            @endif
                            @if(in_array($lease->status, ['active','renewed']))
                            <button wire:click="openTerminateForm({{ $lease->id }})"
                                class="text-xs px-2.5 py-1 rounded-lg border border-danger-500/30 text-danger-600 hover:bg-danger-500/5 transition-colors font-semibold">
                                Terminate
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <div class="w-14 h-14 bg-surface-raised border border-border-default rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-text-primary">No leases found</p>
                        <p class="text-xs text-text-secondary mt-1">Try adjusting your search or filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-5 py-3 border-t border-border-default">
            {{ $leases->links() }}
        </div>
    </div>

    {{-- ── Lease Detail Side Panel ─────────────────────────────────────── --}}
    @if($selectedLease)
    {{-- Backdrop --}}
    <div class="fixed inset-0 z-30 bg-black/30 backdrop-blur-sm"
         wire:click="$set('selectedLeaseId', null)"></div>

    {{-- Panel --}}
    <div class="fixed inset-y-0 right-0 w-[420px] bg-surface-card border-l border-border-default shadow-2xl z-40 overflow-y-auto flex flex-col"
         wire:click.stop>

        {{-- Panel header --}}
        <div class="px-6 py-5 border-b border-border-default flex items-start justify-between flex-shrink-0">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono text-text-tertiary">{{ $selectedLease->reference }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses[$selectedLease->status] ?? $statusClasses['expired'] }}">
                        {{ ucfirst(str_replace('_', ' ', $selectedLease->status)) }}
                    </span>
                </div>
                <h3 class="font-bold text-text-primary text-base">{{ $selectedLease->tenant?->contact?->full_name }}</h3>
                <p class="text-xs text-text-secondary mt-0.5">{{ $selectedLease->listing?->property?->address ?? '—' }}</p>
            </div>
            <button wire:click="$set('selectedLeaseId', null)"
                class="p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary transition-colors flex-shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 px-6 pt-4 pb-2 border-b border-border-default flex-shrink-0">
            @foreach(['overview' => 'Overview', 'payments' => 'Payments'] as $tab => $label)
            <button wire:click="$set('detailTab','{{ $tab }}')"
                class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-colors
                    {{ $detailTab === $tab ? 'bg-brand-primary text-white shadow-sm' : 'text-text-secondary hover:bg-surface-raised' }}">
                {{ $label }}
                @if($tab === 'payments')
                    <span class="ml-1 text-[10px] opacity-70">({{ $selectedLease->rentPayments->count() }})</span>
                @endif
            </button>
            @endforeach
        </div>

        {{-- Tab content --}}
        <div class="flex-1 overflow-y-auto p-6">
            @if($detailTab === 'overview')

            {{-- Key figures --}}
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default">
                    <p class="text-xs text-text-secondary">{{ $selectedLease->paymentFrequencyLabel }} Rent</p>
                    <p class="text-base font-bold text-text-primary mt-0.5">{{ $currencySymbol }}{{ number_format($selectedLease->periodRent) }}</p>
                    @if($selectedLease->payment_frequency !== 'monthly')
                    <p class="text-xs text-text-tertiary mt-0.5">{{ $currencySymbol }}{{ number_format($selectedLease->monthly_rent) }}/mo</p>
                    @endif
                </div>
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default">
                    <p class="text-xs text-text-secondary">Outstanding</p>
                    <p class="text-base font-bold mt-0.5 {{ $selectedLease->outstandingBalance > 0 ? 'text-danger-600' : 'text-success-600' }}">
                        {{ $currencySymbol }}{{ number_format($selectedLease->outstandingBalance) }}
                    </p>
                </div>
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default">
                    <p class="text-xs text-text-secondary">Caution Deposit</p>
                    <p class="text-base font-bold text-text-primary mt-0.5">
                        {{ $selectedLease->deposit_amount ? $currencySymbol.number_format($selectedLease->deposit_amount) : '—' }}
                    </p>
                </div>
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default">
                    <p class="text-xs text-text-secondary">Annual Rent</p>
                    <p class="text-base font-bold text-text-primary mt-0.5">{{ $currencySymbol }}{{ number_format($selectedLease->annualRent) }}</p>
                </div>
            </div>

            {{-- Details list --}}
            <dl class="space-y-2.5 text-sm mb-5">
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Payment Frequency</dt>
                    <dd class="font-semibold text-brand-primary">{{ $selectedLease->paymentFrequencyLabel }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Start Date</dt>
                    <dd class="font-medium text-text-primary">{{ $selectedLease->start_date->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-secondary">End Date</dt>
                    <dd class="font-medium text-text-primary">{{ $selectedLease->end_date->format('d M Y') }}</dd>
                </div>
                @if($selectedLease->payment_frequency !== 'yearly')
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Payment Day</dt>
                    <dd class="font-medium text-text-primary">{{ $selectedLease->payment_day }}{{ match((int)$selectedLease->payment_day) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of month</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Escalation</dt>
                    <dd class="font-medium text-text-primary">{{ $selectedLease->escalation_percent }}% p.a.</dd>
                </div>
                @if($selectedLease->agency_fee)
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Agency Fee</dt>
                    <dd class="font-medium text-text-primary">{{ $currencySymbol }}{{ number_format($selectedLease->agency_fee) }}</dd>
                </div>
                @endif
                @if($selectedLease->legal_fee)
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Legal / Agreement Fee</dt>
                    <dd class="font-medium text-text-primary">{{ $currencySymbol }}{{ number_format($selectedLease->legal_fee) }}</dd>
                </div>
                @endif
                @if($selectedLease->service_charge)
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Annual Service Charge</dt>
                    <dd class="font-medium text-text-primary">{{ $currencySymbol }}{{ number_format($selectedLease->service_charge) }}</dd>
                </div>
                @endif
                @php $dl = $selectedLease->daysUntilExpiry; $thresh = ($selectedLease->payment_frequency ?? 'monthly') === 'yearly' ? 90 : 60; @endphp
                @if($dl > 0)
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Days Remaining</dt>
                    <dd class="font-semibold {{ $dl <= $thresh ? 'text-warning-600' : 'text-text-primary' }}">{{ $dl }} days</dd>
                </div>
                @endif
            </dl>

            @if($selectedLease->special_conditions)
            <div class="p-3 bg-surface-raised/40 rounded-xl border border-border-default mb-5">
                <p class="text-xs font-semibold text-text-secondary mb-1">Special Conditions</p>
                <p class="text-sm text-text-primary">{{ $selectedLease->special_conditions }}</p>
            </div>
            @endif

            {{-- Payment Instructions --}}
            <div class="mb-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wide">Payment Instructions</p>
                    @if(!$editingPaymentInstructions)
                    <button wire:click="openPaymentInstructionsEdit"
                        class="text-[10px] font-semibold text-brand-primary hover:underline">
                        {{ $selectedLease->bank_account ? 'Edit' : 'Add' }}
                    </button>
                    @endif
                </div>
                @if($editingPaymentInstructions)
                <div>
                    <textarea wire:model="edit_bank_account" rows="4"
                        placeholder="e.g. Bank: FNB&#10;Account No: 1234567890&#10;Branch Code: 250655&#10;Reference: Use your lease reference"
                        class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary font-mono focus:outline-none focus:border-brand-primary/50"></textarea>
                    <div class="flex gap-2 mt-2">
                        <button wire:click="savePaymentInstructions"
                            class="px-3 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-semibold hover:opacity-90 transition-opacity">
                            Save
                        </button>
                        <button wire:click="$set('editingPaymentInstructions', false)"
                            class="px-3 py-1.5 border border-border-default rounded-lg text-xs text-text-secondary hover:bg-surface-raised transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
                @elseif($selectedLease->bank_account)
                <div class="p-3 bg-surface-raised/40 rounded-xl border border-border-default">
                    <p class="text-xs text-text-primary font-mono leading-relaxed whitespace-pre-wrap">{{ $selectedLease->bank_account }}</p>
                </div>
                @else
                <p class="text-xs text-text-tertiary italic">No payment instructions set. Click Add to provide bank details for the tenant.</p>
                @endif
            </div>

            {{-- Quick actions --}}
            @if(in_array($selectedLease->status, ['active','renewed']))
            @php $panelThresh = ($selectedLease->payment_frequency ?? 'monthly') === 'yearly' ? 90 : 60; @endphp
            <div class="flex flex-wrap gap-2">
                @if($selectedLease->daysUntilExpiry <= $panelThresh)
                <button wire:click="renewLease({{ $selectedLease->id }})"
                    class="flex-1 py-2 text-xs font-bold rounded-xl border border-brand-primary/30 text-brand-primary hover:bg-brand-primary/5 transition-colors">
                    Renew Lease
                </button>
                <button wire:click="sendRenewalOffer({{ $selectedLease->id }})"
                    class="flex-1 py-2 text-xs font-bold rounded-xl border border-border-default text-text-secondary hover:bg-surface-raised transition-colors">
                    Send Offer
                </button>
                @endif
                <button wire:click="openTerminateForm({{ $selectedLease->id }})"
                    class="flex-1 py-2 text-xs font-bold rounded-xl border border-danger-500/30 text-danger-600 hover:bg-danger-500/5 transition-colors">
                    Terminate
                </button>
            </div>
            @endif

            @elseif($detailTab === 'payments')

            @php
                $payments = $selectedLease->rentPayments->sortByDesc('due_date');
                $paidCount   = $payments->where('status','paid')->count();
                $overdueCount = $payments->where('status','overdue')->count();
            @endphp

            {{-- Payment summary --}}
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default text-center">
                    <p class="text-lg font-bold text-success-600">{{ $paidCount }}</p>
                    <p class="text-[10px] text-text-secondary uppercase tracking-wider mt-0.5">Paid</p>
                </div>
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default text-center">
                    <p class="text-lg font-bold text-danger-600">{{ $overdueCount }}</p>
                    <p class="text-[10px] text-text-secondary uppercase tracking-wider mt-0.5">Overdue</p>
                </div>
                <div class="bg-surface-raised/40 rounded-xl p-3 border border-border-default text-center">
                    <p class="text-lg font-bold text-text-primary">{{ $payments->count() }}</p>
                    <p class="text-[10px] text-text-secondary uppercase tracking-wider mt-0.5">Total</p>
                </div>
            </div>

            {{-- Payment list --}}
            <div class="space-y-2">
                @forelse($payments as $payment)
                <div class="flex items-center justify-between p-3.5 bg-surface-raised/30 rounded-xl border border-border-default/60">
                    <div class="min-w-0">
                        <div class="text-xs font-mono text-text-tertiary truncate">{{ $payment->reference }}</div>
                        <div class="text-sm font-semibold text-text-primary mt-0.5">{{ $currencySymbol }}{{ number_format($payment->amount_due) }}</div>
                        <div class="text-xs text-text-secondary">Due {{ $payment->due_date->format('d M Y') }}</div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $paymentClasses[$payment->status] ?? $paymentClasses['pending'] }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                        @if($payment->amount_paid)
                        <div class="text-xs text-text-secondary mt-1">Paid {{ $currencySymbol }}{{ number_format($payment->amount_paid) }}</div>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">No payment records.</p>
                @endforelse
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
