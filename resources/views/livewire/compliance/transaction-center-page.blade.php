<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Transaction Center</h1>
            <p class="mt-2 text-text-secondary">Manage FICA compliance, document checklists, and transaction deadlines.</p>
        </div>
    </div>

    <!-- Stats (real) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5 flex items-center gap-4">
            <div class="h-10 w-10 rounded-xl bg-warning-100 text-warning-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-text-secondary uppercase tracking-wider">Action Required</p>
                <p class="text-2xl font-black text-text-primary">{{ $stats['action_required'] }}</p>
            </div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5 flex items-center gap-4">
            <div class="h-10 w-10 rounded-xl bg-danger-100 text-danger-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-text-secondary uppercase tracking-wider">Overdue</p>
                <p class="text-2xl font-black text-text-primary">{{ $stats['overdue'] }}</p>
            </div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5 flex items-center gap-4">
            <div class="h-10 w-10 rounded-xl bg-info-100 text-info-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-text-secondary uppercase tracking-wider">FICA Verified</p>
                <p class="text-2xl font-black text-text-primary">{{ $stats['compliant'] }}</p>
            </div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5 flex items-center gap-4">
            <div class="h-10 w-10 rounded-xl bg-success-100 text-success-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-text-secondary uppercase tracking-wider">Completed</p>
                <p class="text-2xl font-black text-text-primary">{{ $stats['completed'] }}</p>
            </div>
        </div>
    </div>

    <!-- Eligible Deals (no transaction yet) -->
    @if($eligibleDeals->isNotEmpty())
    <div class="glass-panel rounded-2xl border border-warning-200 bg-warning-50/50 p-5 mb-6">
        <h3 class="text-sm font-bold text-warning-800 mb-3">Deals ready to initiate transactions ({{ $eligibleDeals->count() }})</h3>
        <div class="space-y-2">
            @foreach($eligibleDeals as $deal)
            <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-warning-100">
                <div>
                    <p class="text-sm font-semibold text-text-primary">{{ $deal->title }}</p>
                    <p class="text-xs text-text-secondary">{{ $deal->contact?->first_name }} {{ $deal->contact?->last_name }} · ₦{{ number_format($deal->value) }} · {{ $deal->stage?->name }}</p>
                </div>
                <button wire:click="initiateTransaction({{ $deal->id }})"
                    class="px-3 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-bold hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="initiateTransaction({{ $deal->id }})">Initiate →</span>
                    <span wire:loading wire:target="initiateTransaction({{ $deal->id }})">...</span>
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Tabs -->
    <div class="flex border-b border-border-default/60 mb-6 gap-1">
        @foreach(['pending' => 'Pending Action', 'approved' => 'FICA Verified', 'completed' => 'Completed'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-5 py-2.5 border-b-2 font-bold text-sm transition-colors
            {{ $activeTab === $tab ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <!-- Transaction List (real data) -->
    <div class="space-y-4">
        @forelse($transactions as $transaction)
        <div class="glass-panel border border-border-default/60 rounded-2xl p-5 shadow-sm hover:shadow transition-shadow">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                <!-- Deal Info -->
                <div class="lg:col-span-4">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-base font-bold text-text-primary">{{ $transaction->deal?->title }}</h3>
                        @if($transaction->isOverdue)
                        <span class="px-1.5 py-0.5 bg-danger-100 text-danger-700 rounded text-[10px] font-bold uppercase">Overdue</span>
                        @endif
                    </div>
                    <p class="text-xs text-text-secondary">{{ $transaction->contact?->first_name }} {{ $transaction->contact?->last_name }}</p>
                    <p class="text-sm font-bold text-text-primary mt-1">₦{{ number_format($transaction->sale_price) }}</p>
                </div>

                <!-- Status + Deadline -->
                <div class="lg:col-span-3">
                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase mb-2
                        @if($transaction->status === 'fica_verified') bg-info-100 text-info-700
                        @elseif($transaction->status === 'completed') bg-success-100 text-success-700
                        @elseif($transaction->status === 'cancelled') bg-danger-100 text-danger-700
                        @else bg-warning-100 text-warning-700 @endif">
                        {{ str_replace('_', ' ', $transaction->status) }}
                    </span>
                    @if($transaction->deadline)
                    <p class="text-xs {{ $transaction->isOverdue ? 'text-danger-600 font-semibold' : 'text-text-secondary' }}">
                        Deadline: {{ $transaction->deadline->format('d M Y') }}
                    </p>
                    @endif
                </div>

                <!-- FICA Progress -->
                <div class="lg:col-span-3">
                    <div class="flex justify-between text-xs font-medium mb-1.5">
                        <span class="text-text-secondary">FICA Progress</span>
                        <span class="text-text-primary">{{ $transaction->ficaProgress }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all
                            @if($transaction->ficaProgress >= 100) bg-success-500
                            @elseif($transaction->ficaProgress >= 60) bg-warning-500
                            @else bg-danger-400 @endif"
                            style="width: {{ $transaction->ficaProgress }}%"></div>
                    </div>
                    <p class="text-[10px] text-text-secondary mt-1">{{ $transaction->documents->where('status', 'approved')->count() }} / {{ $transaction->documents->count() }} documents approved</p>
                </div>

                <!-- Actions -->
                <div class="lg:col-span-2 text-right">
                    <a href="{{ route('compliance.transaction.detail', $transaction) }}"
                        class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors hover-spring inline-block">
                        Manage
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-14 glass-panel rounded-2xl border border-border-default/60">
            <div class="h-14 w-14 bg-surface-raised rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="h-7 w-7 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-base font-bold text-text-primary mb-1">No transactions in this view</h3>
            <p class="text-sm text-text-secondary">Initiate a transaction from a won deal above to begin tracking FICA compliance.</p>
        </div>
        @endforelse
    </div>
</div>
