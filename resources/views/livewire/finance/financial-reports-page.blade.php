<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Financial Reports</h1>
            <p class="text-sm text-text-secondary mt-0.5">P&amp;L, AR aging, cash flow, and tax deductibles</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('finance.reports.export.pdf', ['report' => $activeReport, 'month' => $periodMonth, 'year' => $periodYear, 'property_id' => $propertyId]) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-danger-50 text-danger-700 border border-danger-200 rounded-xl text-xs font-semibold hover:bg-danger-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            @if(in_array($activeReport, ['pl','tax']))
            <a href="{{ route('finance.reports.export.csv', ['report' => $activeReport, 'month' => $periodMonth, 'year' => $periodYear, 'property_id' => $propertyId]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-success-50 text-success-700 border border-success-200 rounded-xl text-xs font-semibold hover:bg-success-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                CSV
            </a>
            @endif
        </div>
    </div>

    <!-- Report Tabs -->
    <div class="flex gap-1 mb-6 bg-surface-hover/40 rounded-xl p-1 w-fit flex-wrap">
        @foreach(['pl'=>'P&L Summary','aging'=>'AR Aging','cashflow'=>'Cash Flow','tax'=>'Tax Deductibles'] as $report => $label)
        <button wire:click="$set('activeReport','{{ $report }}')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $activeReport === $report ? 'bg-surface-card text-text-primary shadow-sm' : 'text-text-secondary hover:text-text-primary' }}">{!! $label !!}</button>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
        <select wire:model.live="periodMonth" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            @foreach(range(1,12) as $m)
                <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}">{{ \Carbon\Carbon::create(null,$m,1)->format('F') }}</option>
            @endforeach
        </select>
        <select wire:model.live="periodYear" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            @foreach([now()->year-2, now()->year-1, now()->year] as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>
        <select wire:model.live="propertyId" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Properties</option>
            @foreach($properties as $prop)
                <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
            @endforeach
        </select>
    </div>

    <!-- P&L Report -->
    @if($activeReport === 'pl' && !empty($plData))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-surface-card rounded-2xl border border-success-200 p-5">
            <h3 class="font-semibold text-text-primary mb-4">Income</h3>
            <div class="space-y-2">
                @foreach($plData['incomeRows'] as $label => $amount)
                <div class="flex justify-between text-sm"><span class="text-text-secondary">{{ $label }}</span><span class="font-medium text-text-primary">R{{ number_format($amount) }}</span></div>
                @endforeach
                <div class="border-t border-success-200 pt-2 flex justify-between font-bold text-success-700"><span>Total Income</span><span>R{{ number_format($plData['totalIncome']) }}</span></div>
            </div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-danger-200 p-5">
            <h3 class="font-semibold text-text-primary mb-4">Expenses</h3>
            <div class="space-y-2">
                @foreach($plData['expenseRows'] as $cat => $amount)
                <div class="flex justify-between text-sm"><span class="text-text-secondary capitalize">{{ str_replace('_',' ',$cat) }}</span><span class="font-medium text-text-primary">R{{ number_format($amount) }}</span></div>
                @endforeach
                <div class="border-t border-danger-200 pt-2 flex justify-between font-bold text-danger-700"><span>Total Expenses</span><span>R{{ number_format($plData['totalExpenses']) }}</span></div>
            </div>
        </div>
        <div class="md:col-span-2 bg-surface-card rounded-2xl border border-brand-200 p-5">
            @php $net = $plData['totalIncome'] - $plData['totalExpenses']; @endphp
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-text-primary">Net Operating Income</span>
                <span class="text-2xl font-bold {{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }}">R{{ number_format($net) }}</span>
            </div>
        </div>
    </div>
    @elseif($activeReport === 'pl')
    <div class="bg-surface-card rounded-2xl border border-border-default p-12 text-center text-text-tertiary">No data for selected period.</div>
    @endif

    <!-- AR Aging -->
    @if($activeReport === 'aging' && !empty($agingData))
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @php $agingColors = ['Current'=>'success','1-30 days'=>'warning','31-60 days'=>'warning','61-90 days'=>'danger','90+ days'=>'danger']; @endphp
        @foreach($agingData['buckets'] as $bucket => $amount)
        @php $c = $agingColors[$bucket] ?? 'brand'; @endphp
        <div class="bg-surface-card rounded-2xl border border-{{ $c }}-200 p-4 text-center">
            <div class="text-lg font-bold text-{{ $c }}-600">R{{ number_format($amount) }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $bucket }}</div>
        </div>
        @endforeach
    </div>
    <div class="bg-surface-card rounded-2xl border border-border-default p-4">
        <div class="flex justify-between text-sm font-bold text-text-primary">
            <span>Total Outstanding AR</span>
            <span>R{{ number_format($agingData['total']) }}</span>
        </div>
    </div>
    @elseif($activeReport === 'aging')
    <div class="bg-surface-card rounded-2xl border border-border-default p-12 text-center text-text-tertiary">No outstanding invoices.</div>
    @endif

    <!-- Cash Flow -->
    @if($activeReport === 'cashflow')
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Month</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Income</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Expenses</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Net</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Outstanding AR</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($cashFlowData as $row)
                <tr class="hover:bg-surface-hover/30">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ \Carbon\Carbon::create($row['year'], $row['month'], 1)->format('M Y') }}</td>
                    <td class="px-4 py-3 text-right text-success-600">R{{ number_format($row['income']) }}</td>
                    <td class="px-4 py-3 text-right text-danger-600">R{{ number_format($row['expenses']) }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $row['net'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">R{{ number_format($row['net']) }}</td>
                    <td class="px-4 py-3 text-right text-warning-600">R{{ number_format($row['outstanding_ar']) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-text-tertiary">No cash flow data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    <!-- Tax Deductibles -->
    @if($activeReport === 'tax')
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Property</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($taxData as $expense)
                <tr class="hover:bg-surface-hover/30">
                    <td class="px-4 py-3 font-mono text-xs text-text-primary">{{ $expense->reference }}</td>
                    <td class="px-4 py-3 text-text-primary">{{ $expense->vendor_name }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs capitalize">{{ str_replace('_', ' ', $expense->category) }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $expense->property?->address_line_1 ?? 'Portfolio' }}</td>
                    <td class="px-4 py-3 text-right font-medium text-text-primary">R{{ number_format($expense->amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-text-tertiary">No tax-deductible expenses for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($taxData->count() > 0)
        <div class="px-4 py-3 border-t border-border-default flex justify-between text-sm font-bold text-text-primary">
            <span>Total Deductible</span>
            <span>R{{ number_format($taxData->sum('amount')) }}</span>
        </div>
        @endif
    </div>
    @endif
</div>

