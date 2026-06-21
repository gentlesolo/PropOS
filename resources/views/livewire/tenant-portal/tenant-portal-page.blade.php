<div x-data="{ tab: '{{ $activeQuitNotice ? 'notices' : 'lease' }}' }">

    {{-- ── Welcome Header ─────────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <p class="text-xs font-semibold text-brand-primary uppercase tracking-wider mb-1">Tenant Portal</p>
        <h1 class="text-2xl font-bold text-text-primary">
            Welcome back, {{ $tenant->contact?->first_name ?? 'Tenant' }}
        </h1>
        @if($tenant->listing?->property)
        <p class="text-sm text-text-secondary mt-1 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-text-tertiary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
            </svg>
            {{ $tenant->listing->property->address_line_1 }}@if($tenant->listing->property->city), {{ $tenant->listing->property->city }}@endif
        </p>
        @endif
    </div>

    {{-- ── Outstanding Balance Banner ──────────────────────────────────────────── --}}
    @if($outstandingBalance > 0)
    <div class="mb-6 flex items-start gap-3 p-4 bg-danger-50 border border-danger-200 rounded-2xl">
        <svg class="w-5 h-5 text-danger-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-danger-800">Outstanding balance: {{ $currencySymbol }}{{ number_format($outstandingBalance, 2) }}</p>
            <p class="text-xs text-danger-700 mt-0.5">Please make payment as soon as possible to avoid penalties. See payment instructions on the Lease tab.</p>
        </div>
    </div>
    @endif

    {{-- ── Quit Notice Alert ───────────────────────────────────────────────────── --}}
    @if($activeQuitNotice)
    <div class="mb-6 rounded-2xl border-2 border-danger-400 bg-danger-50 overflow-hidden">
        <div class="flex items-center gap-2 px-4 py-2 bg-danger-600">
            <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span class="text-sm font-bold text-white tracking-wide uppercase">Important Notice</span>
            <span class="ml-auto text-xs text-danger-100 font-mono">{{ $activeQuitNotice->reference }}</span>
        </div>
        <div class="px-4 py-4">
            <p class="text-sm font-semibold text-danger-900">You have received a Quit Notice for this property.</p>
            <p class="text-xs text-danger-800 mt-1">
                You are required to vacate the premises by
                <span class="font-bold">{{ $activeQuitNotice->vacate_by_date->format('d F Y') }}</span>.
                Please review the full notice in the <button @click="tab = 'notices'" class="underline font-semibold">Notices tab</button> and contact your property manager if you have questions.
            </p>
        </div>
    </div>
    @endif

    {{-- ── Lease Expiry Warning ─────────────────────────────────────────────────── --}}
    @if($lease && $lease->daysUntilExpiry > 0 && $lease->daysUntilExpiry <= 60)
    <div class="mb-6 flex items-start gap-3 p-4 bg-warning-50 border border-warning-200 rounded-2xl">
        <svg class="w-5 h-5 text-warning-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-warning-800">Your lease expires in {{ $lease->daysUntilExpiry }} day{{ $lease->daysUntilExpiry === 1 ? '' : 's' }}</p>
            <p class="text-xs text-warning-700 mt-0.5">Please contact your property manager to discuss renewal options.</p>
        </div>
    </div>
    @endif

    {{-- ── Quick Stats ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-surface-card rounded-2xl border border-border-default p-4">
            <p class="text-xs text-text-secondary mb-1">Monthly Rent</p>
            <p class="text-lg font-bold text-text-primary">{{ $lease ? $currencySymbol.number_format($lease->monthly_rent) : '—' }}</p>
        </div>
        <div class="bg-surface-card rounded-2xl border border-border-default p-4">
            <p class="text-xs text-text-secondary mb-1">Next Due</p>
            <p class="text-lg font-bold {{ $nextPayment?->isOverdue ? 'text-danger-600' : 'text-text-primary' }}">
                {{ $nextPayment ? $nextPayment->due_date->format('d M') : '—' }}
            </p>
        </div>
        <div class="bg-surface-card rounded-2xl border border-border-default p-4">
            <p class="text-xs text-text-secondary mb-1">Outstanding</p>
            <p class="text-lg font-bold {{ $outstandingBalance > 0 ? 'text-danger-600' : 'text-success-600' }}">
                {{ $outstandingBalance > 0 ? $currencySymbol.number_format($outstandingBalance) : $currencySymbol.'0' }}
            </p>
        </div>
        <div class="bg-surface-card rounded-2xl border border-border-default p-4">
            <p class="text-xs text-text-secondary mb-1">Open Requests</p>
            <p class="text-lg font-bold {{ $openMaintenanceCount > 0 ? 'text-warning-600' : 'text-text-primary' }}">{{ $openMaintenanceCount }}</p>
        </div>
    </div>

    {{-- ── Tab Navigation ──────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 mb-6 p-1 bg-surface-hover/40 rounded-xl w-fit overflow-x-auto">
        @foreach(['lease' => 'My Lease', 'payments' => 'Payments', 'maintenance' => 'Maintenance', 'documents' => 'Documents'] as $key => $label)
        <button @click="tab = '{{ $key }}'"
            :class="tab === '{{ $key }}' ? 'bg-surface-card text-brand-primary shadow-sm' : 'text-text-secondary hover:text-text-primary'"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
            {{ $label }}
        </button>
        @endforeach
        @if($quitNotices->isNotEmpty())
        <button @click="tab = 'notices'"
            :class="tab === 'notices' ? 'bg-danger-600 text-white shadow-sm' : 'text-danger-600 hover:bg-danger-50'"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            Notices
            <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-bold rounded-full"
                :class="tab === 'notices' ? 'bg-white text-danger-600' : 'bg-danger-600 text-white'">
                {{ $quitNotices->count() }}
            </span>
        </button>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- LEASE TAB                                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'lease'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        @if($lease)
        <div class="space-y-4">

            {{-- Lease Summary --}}
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-text-primary">Lease Details</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Active</span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Reference</dt>
                        <dd class="font-mono font-medium text-text-primary">{{ $lease->reference }}</dd>
                    </div>
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Monthly Rent</dt>
                        <dd class="text-xl font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($lease->monthly_rent, 2) }}</dd>
                    </div>
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Payment Due</dt>
                        <dd class="font-medium text-text-primary">
                            {{ $lease->payment_day }}{{ match((int)$lease->payment_day) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of each month
                        </dd>
                    </div>
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Security Deposit</dt>
                        <dd class="font-medium text-text-primary">{{ $lease->deposit_amount ? $currencySymbol.number_format($lease->deposit_amount, 2) : '—' }}</dd>
                    </div>
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Lease Start</dt>
                        <dd class="font-medium text-text-primary">{{ $lease->start_date->format('d M Y') }}</dd>
                    </div>
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Lease End</dt>
                        <dd class="font-medium {{ $lease->daysUntilExpiry <= 60 && $lease->daysUntilExpiry > 0 ? 'text-warning-600' : 'text-text-primary' }}">
                            {{ $lease->end_date->format('d M Y') }}
                            @if($lease->daysUntilExpiry > 0 && $lease->daysUntilExpiry <= 60)
                            <span class="text-xs ml-1 text-warning-500">({{ $lease->daysUntilExpiry }}d)</span>
                            @endif
                        </dd>
                    </div>
                    @if($lease->escalation_percent)
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Annual Escalation</dt>
                        <dd class="font-medium text-text-primary">{{ $lease->escalation_percent }}%</dd>
                    </div>
                    @endif
                    @if($lease->payment_frequency && $lease->payment_frequency !== 'monthly')
                    <div class="p-3 bg-surface-hover/30 rounded-xl">
                        <dt class="text-xs text-text-secondary mb-1">Payment Frequency</dt>
                        <dd class="font-medium text-text-primary">{{ $lease->paymentFrequencyLabel }}</dd>
                    </div>
                    @endif
                </dl>
                @if($lease->special_conditions)
                <div class="mt-3 p-3 bg-surface-hover/30 rounded-xl">
                    <dt class="text-xs text-text-secondary mb-1">Special Conditions</dt>
                    <dd class="text-sm text-text-primary leading-relaxed">{{ $lease->special_conditions }}</dd>
                </div>
                @endif
            </div>

            {{-- Payment Instructions --}}
            @if($lease->bank_account)
            <div class="bg-surface-card rounded-2xl border border-brand-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-brand-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-text-primary">Payment Instructions</h3>
                </div>
                <p class="text-xs text-text-secondary mb-3">Use the following banking details to make your monthly rental payment:</p>
                <div class="p-3 bg-brand-50/50 rounded-xl border border-brand-100">
                    <p class="text-sm text-text-primary font-mono leading-relaxed whitespace-pre-wrap">{{ $lease->bank_account }}</p>
                </div>
                <p class="text-xs text-text-tertiary mt-2">Use your lease reference <span class="font-mono font-semibold">{{ $lease->reference }}</span> as the payment reference.</p>
            </div>
            @endif

            {{-- Property Details --}}
            @if($tenant->listing?->property)
            @php $prop = $tenant->listing->property; @endphp
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Property Details</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                    @if($prop->bedrooms)
                    <div class="text-center p-3 bg-surface-hover/30 rounded-xl">
                        <p class="text-xl font-bold text-text-primary">{{ $prop->bedrooms }}</p>
                        <p class="text-xs text-text-secondary">Bedrooms</p>
                    </div>
                    @endif
                    @if($prop->bathrooms)
                    <div class="text-center p-3 bg-surface-hover/30 rounded-xl">
                        <p class="text-xl font-bold text-text-primary">{{ $prop->bathrooms }}</p>
                        <p class="text-xs text-text-secondary">Bathrooms</p>
                    </div>
                    @endif
                    @if($prop->floor_area_sqm)
                    <div class="text-center p-3 bg-surface-hover/30 rounded-xl">
                        <p class="text-xl font-bold text-text-primary">{{ number_format($prop->floor_area_sqm) }}</p>
                        <p class="text-xs text-text-secondary">m² Floor Area</p>
                    </div>
                    @endif
                    @if($prop->parking_spaces)
                    <div class="text-center p-3 bg-surface-hover/30 rounded-xl">
                        <p class="text-xl font-bold text-text-primary">{{ $prop->parking_spaces }}</p>
                        <p class="text-xs text-text-secondary">Parking</p>
                    </div>
                    @endif
                    @if($prop->year_built)
                    <div class="text-center p-3 bg-surface-hover/30 rounded-xl">
                        <p class="text-xl font-bold text-text-primary">{{ $prop->year_built }}</p>
                        <p class="text-xs text-text-secondary">Year Built</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
        @else
        <div class="bg-surface-card rounded-2xl border border-border-default p-10 text-center">
            <svg class="w-10 h-10 text-text-tertiary mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <p class="text-sm font-semibold text-text-primary mb-1">No active lease</p>
            <p class="text-xs text-text-secondary">Contact your property manager for details.</p>
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- PAYMENTS TAB                                                               --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'payments'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display:none">
        <div x-data="{ openReceipt: null }" class="space-y-4">

            {{-- Payment Summary --}}
            @if($lease)
            @php
                $payments = $lease->rentPayments;
                $totalPaid  = $payments->whereIn('status', ['paid', 'partial'])->sum('amount_paid');
                $totalDue   = $payments->sum('amount_due');
                $overdueCount = $payments->filter(fn($p) => $p->isOverdue)->count();
            @endphp
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                    <p class="text-xs text-text-secondary mb-1">Total Paid</p>
                    <p class="text-base font-bold text-success-600">{{ $currencySymbol }}{{ number_format($totalPaid) }}</p>
                </div>
                <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                    <p class="text-xs text-text-secondary mb-1">Outstanding</p>
                    <p class="text-base font-bold {{ $outstandingBalance > 0 ? 'text-danger-600' : 'text-success-600' }}">{{ $currencySymbol }}{{ number_format($outstandingBalance) }}</p>
                </div>
                <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                    <p class="text-xs text-text-secondary mb-1">Overdue</p>
                    <p class="text-base font-bold {{ $overdueCount > 0 ? 'text-danger-600' : 'text-text-primary' }}">{{ $overdueCount }}</p>
                </div>
            </div>
            @endif

            {{-- Payment History --}}
            <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
                <div class="px-5 py-4 border-b border-border-default">
                    <h2 class="text-base font-semibold text-text-primary">Payment History</h2>
                </div>
                @if($lease && $lease->rentPayments->isNotEmpty())
                <div class="divide-y divide-border-default">
                    @foreach($lease->rentPayments->sortByDesc('due_date') as $payment)
                    @php
                        $pc      = match($payment->status){ 'paid'=>'success','overdue'=>'danger','partial'=>'warning',default=>'secondary' };
                        $hasPaid = in_array($payment->status, ['paid', 'partial']) && $payment->amount_paid;
                        $balance = round((float)$payment->amount_due - (float)$payment->amount_paid, 2);
                    @endphp
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            {{-- Reference + date --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-xs text-text-tertiary">{{ $payment->reference }}</span>
                                    <span class="text-xs text-text-secondary">{{ $payment->due_date->format('d M Y') }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </div>
                            </div>
                            {{-- Amounts --}}
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($payment->amount_due, 2) }}</p>
                                @if($payment->amount_paid)
                                <p class="text-xs text-text-secondary">Paid: {{ $currencySymbol }}{{ number_format($payment->amount_paid, 2) }}</p>
                                @endif
                            </div>
                            {{-- Receipt button for paid / partial --}}
                            @if($hasPaid)
                            <button @click="openReceipt = openReceipt === {{ $payment->id }} ? null : {{ $payment->id }}"
                                class="flex-shrink-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 border border-success-200 text-success-700 rounded-lg bg-success-50 hover:bg-success-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0Z"/></svg>
                                <span x-text="openReceipt === {{ $payment->id }} ? 'Hide Receipt' : 'View Receipt'">View Receipt</span>
                            </button>
                            @endif
                            {{-- Proof upload action --}}
                            @if(!in_array($payment->status, ['paid', 'waived']))
                            @if($payment->proof_of_payment)
                            <span class="flex-shrink-0 inline-flex items-center gap-1 text-xs text-success-600 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Proof submitted
                            </span>
                            @else
                            <button wire:click="openProofUpload({{ $payment->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-shrink-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 border border-brand-200 text-brand-700 rounded-lg bg-brand-50 hover:bg-brand-100 transition-colors" wire:loading.attr="disabled" wire:target="openProofUpload">
                <span wire:loading.remove wire:target="openProofUpload"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                Upload Proof</span>
                <span wire:loading wire:target="openProofUpload" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            @endif
                            @endif
                        </div>

                        {{-- Inline receipt --}}
                        @if($hasPaid)
                        <div x-show="openReceipt === {{ $payment->id }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             style="display:none"
                             class="mt-3">
                            <div class="border border-success-200 rounded-xl overflow-hidden">
                                {{-- Receipt header --}}
                                <div class="bg-success-50 px-4 py-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185Z"/></svg>
                                        <span class="text-xs font-bold text-success-800 uppercase tracking-wide">
                                            {{ $payment->status === 'paid' ? 'Payment Receipt' : 'Partial Payment Receipt' }}
                                        </span>
                                    </div>
                                    <span class="font-mono text-xs text-success-700">{{ $payment->reference }}</span>
                                </div>
                                {{-- Receipt body --}}
                                <div class="bg-white px-4 py-4">
                                    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                                        <div>
                                            <dt class="text-text-tertiary">Period</dt>
                                            <dd class="font-medium text-text-primary mt-0.5">{{ $payment->due_date->format('F Y') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-text-tertiary">Date Paid</dt>
                                            <dd class="font-medium text-text-primary mt-0.5">
                                                {{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('d M Y') : '—' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-text-tertiary">Amount Due</dt>
                                            <dd class="font-medium text-text-primary mt-0.5">{{ $currencySymbol }}{{ number_format($payment->amount_due, 2) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-text-tertiary">Amount Paid</dt>
                                            <dd class="font-bold text-success-700 mt-0.5">{{ $currencySymbol }}{{ number_format($payment->amount_paid, 2) }}</dd>
                                        </div>
                                        @if($payment->payment_method)
                                        <div>
                                            <dt class="text-text-tertiary">Method</dt>
                                            <dd class="font-medium text-text-primary mt-0.5">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                                        </div>
                                        @endif
                                        @if($payment->status === 'partial' && $balance > 0)
                                        <div>
                                            <dt class="text-text-tertiary">Balance Due</dt>
                                            <dd class="font-bold text-warning-700 mt-0.5">{{ $currencySymbol }}{{ number_format($balance, 2) }}</dd>
                                        </div>
                                        @endif
                                    </dl>
                                    @if($payment->status === 'paid')
                                    <div class="mt-3 pt-3 border-t border-success-100 flex items-center gap-1.5 text-xs text-success-700 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Account is up to date for this period
                                    </div>
                                    @elseif($payment->status === 'partial')
                                    <div class="mt-3 pt-3 border-t border-warning-100 flex items-center gap-1.5 text-xs text-warning-700 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                        Partial payment recorded — outstanding balance remains
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Inline proof upload form --}}
                        @if($uploadingPaymentId === $payment->id)
                        <div class="mt-3 p-4 bg-brand-50/50 border border-brand-200 rounded-xl">
                            <p class="text-xs font-semibold text-text-primary mb-3">Upload proof of payment (JPG, PNG, or PDF — max 5 MB)</p>
                            <form wire:submit.prevent="submitProof" class="flex items-center gap-3 flex-wrap">
                                <input wire:model="proofFile" type="file" accept=".jpg,.jpeg,.png,.pdf"
                                    class="text-xs text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-brand-200 file:text-xs file:font-medium file:text-brand-700 file:bg-white file:cursor-pointer hover:file:bg-brand-50 transition-all">
                                @error('proofFile') <p class="w-full text-xs text-danger-600">{{ $message }}</p> @enderror
                                <div class="flex gap-2">
                                    <button type="submit" wire:loading.attr="disabled" wire:target="submitProof"
                                        class="px-4 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-semibold hover:opacity-90 disabled:opacity-60 transition-opacity">
                                        <span wire:loading wire:target="submitProof">Uploading…</span>
                                        <span wire:loading.remove wire:target="submitProof">Submit</span>
                                    </button>
                                    <button type="button" wire:click="cancelProofUpload"
                                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 border border-border-default rounded-lg text-xs text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="cancelProofUpload">
                <span wire:loading.remove wire:target="cancelProofUpload">Cancel</span>
                <span wire:loading wire:target="cancelProofUpload" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-10 text-center text-text-secondary text-sm">
                    <svg class="w-8 h-8 text-text-tertiary mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                    </svg>
                    No payment records available yet.
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- MAINTENANCE TAB                                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'maintenance'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display:none">
        {{-- Alpine controls show/hide only; wire:model still binds field values --}}
        <div x-data="{ showForm: false }" x-on:maintenance-submitted.window="showForm = false" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-text-primary">Maintenance Requests</h2>
                <button @click="showForm = true" x-show="!showForm"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white rounded-xl text-sm font-medium shadow-sm hover:opacity-90 transition-opacity">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Request
                </button>
            </div>

            <div x-show="showForm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display:none">
                <div class="bg-surface-card rounded-2xl border border-brand-200 p-5">
                    <h3 class="text-sm font-semibold text-text-primary mb-4">Submit a Maintenance Request</h3>
                    <form wire:submit.prevent="submitMaintenance" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Issue Title <span class="text-danger-500">*</span></label>
                            <input wire:model="maintenance_title" type="text" placeholder="e.g. Leaking tap in bathroom"
                                class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20 outline-none transition-all">
                            @error('maintenance_title') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Description <span class="text-danger-500">*</span></label>
                            <textarea wire:model="maintenance_description" rows="4" placeholder="Describe the issue in detail, including its location and how long it has been occurring…"
                                class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20 outline-none transition-all resize-none"></textarea>
                            @error('maintenance_description') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Priority</label>
                            <select wire:model="maintenance_priority"
                                class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2.5 text-sm text-text-primary focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20 outline-none transition-all">
                                <option value="low">Low — can wait</option>
                                <option value="medium">Medium — within a week</option>
                                <option value="high">High — within 48 hours</option>
                                <option value="urgent">Urgent — needs immediate attention</option>
                            </select>
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-5 py-2.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition-opacity disabled:opacity-60">
                                <span wire:loading wire:target="submitMaintenance">Submitting…</span>
                                <span wire:loading.remove wire:target="submitMaintenance">Submit Request</span>
                            </button>
                            <button type="button" @click="showForm = false"
                                class="px-4 py-2.5 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($maintenanceRequests->isEmpty())
            <div class="bg-surface-card rounded-2xl border border-border-default p-10 text-center">
                <svg class="w-10 h-10 text-text-tertiary mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                </svg>
                <p class="text-sm font-semibold text-text-primary mb-1">No requests yet</p>
                <p class="text-xs text-text-secondary">Use the button above to report an issue.</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($maintenanceRequests as $req)
                @php
                    $pc = match($req->priority){ 'urgent'=>'danger','high'=>'warning','medium'=>'brand',default=>'secondary' };
                    $sc = match($req->status){ 'resolved'=>'success','in_progress'=>'brand','closed'=>'secondary',default=>'warning' };
                @endphp
                <div class="bg-surface-card rounded-2xl border border-border-default p-4">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="font-medium text-text-primary leading-snug">{{ $req->title }}</h3>
                        <div class="flex gap-1.5 flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">
                                {{ ucfirst($req->priority) }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">
                                {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-text-secondary leading-relaxed">{{ $req->description }}</p>
                    <p class="text-xs text-text-tertiary mt-2">Submitted {{ $req->created_at->format('d M Y') }}</p>
                    @if($req->resolution_notes)
                    <div class="mt-3 p-3 bg-success-50 rounded-xl border border-success-200">
                        <p class="text-xs font-semibold text-success-700 mb-1">Resolution Notes</p>
                        <p class="text-sm text-success-800 leading-relaxed">{{ $req->resolution_notes }}</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- DOCUMENTS TAB                                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'documents'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display:none">
        <div class="space-y-4">
            <h2 class="text-base font-semibold text-text-primary">Your Documents</h2>

            {{-- Lease Agreement --}}
            @if($lease?->contract)
            <div class="bg-surface-card rounded-2xl border border-border-default p-4 flex items-center gap-4">
                <div class="w-10 h-10 bg-brand-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-text-primary">Lease Agreement</p>
                    <p class="text-xs text-text-secondary">{{ $lease->reference }} &mdash; {{ ucfirst($lease->contract->status ?? 'signed') }}</p>
                </div>
                @if($lease->contract->file_path)
                <a href="{{ Storage::url($lease->contract->file_path) }}" target="_blank"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Download
                </a>
                @else
                <span class="text-xs text-text-tertiary">Not available</span>
                @endif
            </div>
            @endif

            {{-- FICA Documents --}}
            @if($tenant->fica_documents && count($tenant->fica_documents) > 0)
            <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
                <div class="px-5 py-4 border-b border-border-default flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-text-primary">FICA Documents</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Verified</span>
                </div>
                <div class="divide-y divide-border-default">
                    @foreach($tenant->fica_documents as $doc)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 bg-success-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-text-primary truncate">{{ $doc['name'] ?? 'FICA Document' }}</p>
                            @if(!empty($doc['uploaded_at']))
                            <p class="text-xs text-text-tertiary">Submitted {{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y') }}</p>
                            @endif
                        </div>
                        <span class="flex-shrink-0 text-xs text-success-600 font-medium">On file</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!$lease?->contract && (!$tenant->fica_documents || count($tenant->fica_documents) === 0))
            <div class="bg-surface-card rounded-2xl border border-border-default p-10 text-center">
                <svg class="w-10 h-10 text-text-tertiary mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                </svg>
                <p class="text-sm font-semibold text-text-primary mb-1">No documents available</p>
                <p class="text-xs text-text-secondary">Please contact your property manager.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- NOTICES TAB                                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    @if($quitNotices->isNotEmpty())
    <div x-show="tab === 'notices'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display:none">
        <div class="space-y-4">
            <div>
                <h2 class="text-base font-semibold text-text-primary">Quit Notices</h2>
                <p class="text-xs text-text-secondary mt-0.5">Formal notices issued by your property manager. Please read carefully.</p>
            </div>

            @foreach($quitNotices as $notice)
            @php
                $isActive = in_array($notice->status, ['sent', 'acknowledged']);
            @endphp
            <div class="bg-surface-card rounded-2xl border {{ $isActive ? 'border-danger-300' : 'border-border-default' }} overflow-hidden">

                {{-- Notice header --}}
                <div class="flex items-center justify-between px-5 py-3 {{ $isActive ? 'bg-danger-50' : 'bg-surface-hover/40' }} border-b {{ $isActive ? 'border-danger-200' : 'border-border-default' }}">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $isActive ? 'text-danger-600' : 'text-text-tertiary' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span class="text-sm font-semibold {{ $isActive ? 'text-danger-800' : 'text-text-secondary' }}">Quit Notice</span>
                        <span class="font-mono text-xs text-text-tertiary">{{ $notice->reference }}</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        @if($notice->status === 'sent') bg-brand-primary/10 text-brand-primary border border-brand-primary/20
                        @elseif($notice->status === 'acknowledged') bg-warning-500/10 text-warning-700 border border-warning-500/20
                        @elseif($notice->status === 'disputed') bg-danger-500/10 text-danger-700 border border-danger-500/20
                        @else bg-surface-raised text-text-tertiary border border-border-default @endif">
                        {{ ucfirst($notice->status) }}
                    </span>
                </div>

                {{-- Vacate date highlight --}}
                @if($isActive)
                <div class="px-5 py-3 bg-danger-600 flex items-center justify-between">
                    <span class="text-xs font-semibold text-danger-100 uppercase tracking-wider">You must vacate by</span>
                    <span class="text-lg font-black text-white">{{ $notice->vacate_by_date->format('d F Y') }}</span>
                </div>
                @endif

                {{-- Meta --}}
                <div class="px-5 py-3 border-b border-border-default grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <p class="text-text-tertiary">Issue Date</p>
                        <p class="font-medium text-text-primary mt-0.5">{{ $notice->issue_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-text-tertiary">Reason</p>
                        <p class="font-medium text-text-primary mt-0.5">{{ $notice->reason }}</p>
                    </div>
                    <div>
                        <p class="text-text-tertiary">Sent Via</p>
                        <p class="font-medium text-text-primary mt-0.5">{{ ucfirst(str_replace('_', ' ', $notice->delivery_method)) }}</p>
                    </div>
                </div>

                {{-- Notice body --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider mb-2">Notice Content</p>
                    <div class="bg-surface-raised rounded-xl border border-border-default p-4 text-sm text-text-primary leading-relaxed whitespace-pre-wrap font-mono">{{ $notice->notice_body }}</div>
                </div>

                {{-- Issued by --}}
                @if($notice->issuedBy)
                <div class="px-5 pb-4 text-xs text-text-tertiary">
                    Issued by {{ $notice->issuedBy->name }}
                    @if($notice->sent_at) on {{ $notice->sent_at->format('d M Y') }}@endif
                </div>
                @endif

            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Agency Contact Card ──────────────────────────────────────────────────── --}}
    @if($tenant->agency)
    @php $agency = $tenant->agency; @endphp
    <div class="mt-8 bg-surface-card rounded-2xl border border-border-default p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-3">Need help? Contact your Property Manager</h3>
        <div class="flex flex-wrap gap-3">
            @if($agency->phone)
            <a href="tel:{{ $agency->phone }}" class="inline-flex items-center gap-2 text-sm text-text-secondary hover:text-text-primary transition-colors">
                <svg class="w-4 h-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                </svg>
                {{ $agency->phone }}
            </a>
            @endif
            @if($agency->email)
            <a href="mailto:{{ $agency->email }}" class="inline-flex items-center gap-2 text-sm text-text-secondary hover:text-text-primary transition-colors">
                <svg class="w-4 h-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                {{ $agency->email }}
            </a>
            @endif
            @if($agency->address)
            <span class="inline-flex items-center gap-2 text-sm text-text-secondary">
                <svg class="w-4 h-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                {{ $agency->address }}
            </span>
            @endif
        </div>
    </div>
    @endif

</div>
