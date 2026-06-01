<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Commission Ledger</h1>
            <p class="mt-2 text-text-secondary">Track gross commissions, agent splits, and payment statuses.</p>
        </div>
        <div class="flex gap-3">
            <select wire:model="year" class="border border-border-default rounded-xl px-3 py-2 text-sm bg-surface-card text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @foreach([now()->year, now()->year - 1, now()->year - 2] as $y)
                <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            <div class="flex gap-1.5">
                @foreach(['' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'paid' => 'Paid'] as $val => $label)
                <button wire:click="$set('filterStatus', '{{ $val }}')"
                    class="px-3 py-2 rounded-xl text-xs font-bold transition-colors
                    {{ $filterStatus === $val ? 'bg-brand-primary text-white' : 'border border-border-default bg-surface-card text-text-secondary hover:bg-surface-sunken' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <button wire:click="reconcile" class="px-4 py-2 border border-brand-primary/40 text-brand-primary rounded-xl text-sm font-bold hover:bg-brand-primary/5 transition-colors">
                <span wire:loading.remove wire:target="reconcile">↻ Reconcile</span>
                <span wire:loading wire:target="reconcile">Reconciling...</span>
            </button>
        </div>
    </div>

    <!-- Summary Widgets -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5 md:col-span-1">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Gross YTD</p>
            <h3 class="text-2xl font-black text-text-primary">{{ $currencySymbol }}{{ number_format($commissions->sum('gross_commission')) }}</h3>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Agency Net</p>
            <h3 class="text-2xl font-black text-brand-primary">{{ $currencySymbol }}{{ number_format($totalBrokerageRevenue) }}</h3>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Agents Net</p>
            <h3 class="text-2xl font-black text-success-600">{{ $currencySymbol }}{{ number_format($totalAgentPayouts) }}</h3>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Paid Out</p>
            <h3 class="text-2xl font-black text-text-primary">{{ $currencySymbol }}{{ number_format($commissions->where('payment_status', 'paid')->sum('gross_commission')) }}</h3>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <p class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider mb-1">Pending Payouts</p>
            <h3 class="text-2xl font-black text-warning-600">{{ $commissions->whereIn('payment_status', ['pending', 'processing'])->count() }}</h3>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-sunken/50 border-b border-border-default/40">
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Property & Client</th>
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Agent</th>
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Sale Value</th>
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Gross Comm.</th>
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Agency / Agent Split</th>
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Status</th>
                        <th class="py-4 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/40">
                    @forelse($commissions as $commission)
                    <tr class="hover:bg-surface-raised/20 transition-colors">
                        <td class="py-4 px-5">
                            <p class="text-sm font-bold text-text-primary">{{ $commission->deal?->listing?->property?->address_line_1 ?? $commission->deal?->title }}</p>
                            <p class="text-xs text-text-secondary">{{ $commission->deal?->contact?->first_name }} {{ $commission->deal?->contact?->last_name }}</p>
                        </td>
                        <td class="py-4 px-5">
                            @if($commission->agent)
                            <div class="flex items-center gap-2">
                                <div class="h-7 w-7 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ substr($commission->agent->first_name, 0, 1) }}{{ substr($commission->agent->last_name, 0, 1) }}
                                </div>
                                <span class="text-sm font-medium text-text-primary">{{ $commission->agent->first_name }} {{ $commission->agent->last_name }}</span>
                            </div>
                            @else
                            <span class="text-xs text-text-secondary">Unassigned</span>
                            @endif
                        </td>
                        <td class="py-4 px-5">
                            <p class="text-sm font-black text-text-primary">{{ $currencySymbol }}{{ number_format($commission->sale_price) }}</p>
                        </td>
                        <td class="py-4 px-5">
                            <p class="text-sm font-black text-text-primary">{{ $currencySymbol }}{{ number_format($commission->gross_commission) }}</p>
                            <p class="text-[10px] text-text-tertiary">{{ $commission->commission_rate }}% rate</p>
                        </td>
                        <td class="py-4 px-5">
                            <div class="text-sm font-bold space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-brand-primary inline-block"></span>
                                    <span class="text-brand-primary">{{ $currencySymbol }}{{ number_format($commission->agency_commission) }}</span>
                                    <span class="text-text-tertiary text-[10px]">agency</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-success-500 inline-block"></span>
                                    <span class="text-success-600">{{ $currencySymbol }}{{ number_format($commission->agent_commission) }}</span>
                                    <span class="text-text-tertiary text-[10px]">agent ({{ $commission->agent_split_percentage }}%)</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase
                                @if($commission->payment_status === 'paid') bg-success-100 text-success-700
                                @elseif($commission->payment_status === 'processing') bg-warning-100 text-warning-700
                                @elseif($commission->payment_status === 'disputed') bg-danger-100 text-danger-700
                                @else bg-surface-sunken text-text-secondary @endif">
                                {{ str_replace('_', ' ', $commission->payment_status) }}
                            </span>
                            @if($commission->paid_at)
                            <p class="text-[10px] text-text-secondary mt-0.5">{{ $commission->paid_at->format('d M Y') }}</p>
                            @elseif($commission->expected_payment_date)
                            <p class="text-[10px] text-text-secondary mt-0.5">Due {{ $commission->expected_payment_date->format('d M Y') }}</p>
                            @endif
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2">
                                @if($commission->payment_status === 'pending')
                                <button wire:click="markProcessing({{ $commission->id }})" class="text-xs text-warning-600 border border-warning-200 rounded-lg px-2 py-1 hover:bg-warning-50 transition-colors font-medium">Processing</button>
                                @endif
                                @if(in_array($commission->payment_status, ['pending', 'processing']))
                                <button wire:click="markPaid({{ $commission->id }})" class="text-xs text-success-600 border border-success-200 rounded-lg px-2 py-1 hover:bg-success-50 transition-colors font-medium">Mark Paid</button>
                                @endif
                                <button wire:click="selectCommission({{ $commission->id }})" class="text-xs text-brand-primary border border-brand-primary/20 rounded-lg px-2 py-1 hover:bg-brand-primary/5 transition-colors font-medium">Statement</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="h-12 w-12 bg-surface-raised rounded-2xl flex items-center justify-center text-xl">💰</div>
                                <p class="text-sm font-medium text-text-primary">No commission records for {{ $year }}</p>
                                <p class="text-xs text-text-secondary">Click "Reconcile" to generate commission records from closed deals.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Printable Commission Statement Modal -->
    @if($activeCommission)
    <div class="fixed inset-0 bg-surface-overlay/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in print:bg-white print:p-0 print:static print:h-auto">
        <div class="glass-panel w-full max-w-2xl rounded-3xl border border-border-default/60 shadow-2xl p-8 bg-surface-card print:border-none print:shadow-none print:bg-white print:p-0 text-text-primary print:text-black">
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-border-default/30 pb-4 mb-6 print:border-black">
                <div>
                    <h2 class="text-xl font-bold tracking-tight">Commission Statement</h2>
                    <p class="text-xs text-text-secondary mt-1">Transaction Ref: TXN-{{ str_pad($activeCommission->transaction_id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold text-brand-primary print:text-black uppercase tracking-wider">{{ $activeCommission->agency->name ?? 'PropOS Agency' }}</span>
                    <p class="text-[10px] text-text-tertiary mt-0.5">{{ now()->format('d M Y') }}</p>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-6 mb-8 text-sm">
                <div>
                    <span class="block text-xs font-medium text-text-tertiary uppercase mb-1">Agent Details</span>
                    <p class="font-bold">{{ $activeCommission->agent->first_name ?? 'Unassigned' }} {{ $activeCommission->agent->last_name ?? '' }}</p>
                    <p class="text-xs text-text-secondary mt-0.5">{{ $activeCommission->agent->email ?? '' }}</p>
                </div>
                <div>
                    <span class="block text-xs font-medium text-text-tertiary uppercase mb-1">Property Details</span>
                    <p class="font-bold">{{ $activeCommission->deal?->listing?->property?->address_line_1 ?? $activeCommission->deal?->title }}</p>
                    <p class="text-xs text-text-secondary mt-0.5">Sale Price: {{ $currencySymbol }}{{ number_format($activeCommission->sale_price, 2) }}</p>
                </div>
            </div>

            <!-- Calculation Breakdown Table -->
            <div class="border border-border-default/45 rounded-2xl overflow-hidden mb-8 print:border-black">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-surface-sunken/40 border-b border-border-default/45 print:border-black">
                            <th class="py-3 px-4 font-semibold text-text-secondary">Line Item</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary text-right">Calculation</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/30 print:divide-black">
                        <tr>
                            <td class="py-3 px-4 text-text-primary">Gross Commission</td>
                            <td class="py-3 px-4 text-text-secondary text-right">{{ $activeCommission->commission_rate }}% of Sale Price</td>
                            <td class="py-3 px-4 font-semibold text-right">{{ $currencySymbol }}{{ number_format($activeCommission->gross_commission, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-text-primary">Agent Payout Split</td>
                            <td class="py-3 px-4 text-text-secondary text-right">{{ $activeCommission->agent_split_percentage }}% of Gross</td>
                            <td class="py-3 px-4 font-bold text-success-600 text-right print:text-black">{{ $currencySymbol }}{{ number_format($activeCommission->agent_commission, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 text-text-primary">Agency Net Retention</td>
                            <td class="py-3 px-4 text-text-secondary text-right">{{ 100 - $activeCommission->agent_split_percentage }}% of Gross</td>
                            <td class="py-3 px-4 font-semibold text-right">{{ $currencySymbol }}{{ number_format($activeCommission->agency_commission, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer / Signature & Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-border-default/30 print:border-black">
                <div class="text-xs text-text-secondary">
                    Status: <span class="font-bold text-brand-primary uppercase print:text-black">{{ $activeCommission->payment_status }}</span>
                </div>
                <div class="flex gap-3 print:hidden">
                    <button onclick="window.print()" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-semibold hover:bg-brand-secondary transition-colors">
                        Print Statement
                    </button>
                    <button wire:click="$set('selectedCommissionId', null)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
