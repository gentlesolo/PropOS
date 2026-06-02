<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Portfolio Dashboard</h1>
            <p class="text-sm text-text-secondary mt-0.5">Cross-property occupancy, revenue, expenses, and risk overview</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="year" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                @foreach([now()->year - 2, now()->year - 1, now()->year] as $y)
                <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            <select wire:model.live="riskFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Properties</option>
                <option value="occupied">Occupied</option>
                <option value="vacant">Vacant</option>
                <option value="at_risk">At Risk</option>
            </select>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        @foreach([
            ['label'=>'Properties','val'=>$summary['total_properties'],'suffix'=>'','color'=>'brand'],
            ['label'=>'Occupancy','val'=>$summary['occupancy_rate'],'suffix'=>'%','color'=>'success'],
            ['label'=>'Revenue','val'=>'R'.number_format($summary['total_revenue']),'suffix'=>'','color'=>'success'],
            ['label'=>'Expenses','val'=>'R'.number_format($summary['total_expenses']),'suffix'=>'','color'=>'danger'],
            ['label'=>'NOI','val'=>'R'.number_format($summary['total_noi']),'suffix'=>'','color'=> $summary['total_noi'] >= 0 ? 'success' : 'danger'],
            ['label'=>'At Risk','val'=>$summary['at_risk'],'suffix'=>'','color'=>$summary['at_risk'] > 0 ? 'danger' : 'success'],
        ] as $s)
        <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
            <div class="text-xl font-bold text-{{ $s['color'] }}-600">{{ $s['val'] }}{{ $s['suffix'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Property Rows --}}
    @if($rows->isEmpty())
    <div class="bg-surface-card rounded-2xl border border-border-default p-12 text-center">
        <p class="text-text-secondary text-sm">No properties match the selected filter.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($rows as $row)
        @php
            $prop = $row['property'];
            $hasRisks = count($row['risks']) > 0;
            $maxTrend = max(array_filter($row['trend'], fn($v) => $v > 0)) ?: 1;
            $riskLabels = ['vacant'=>'Vacant','overdue_inspection'=>'Overdue Inspection','expiring_lease'=>'Lease Expiring','compliance'=>'Compliance Issue'];
        @endphp
        <div class="bg-surface-card rounded-2xl border {{ $hasRisks ? 'border-warning-200' : 'border-border-default' }} p-5">
            <div class="flex flex-col lg:flex-row lg:items-start gap-4">

                {{-- Property Info --}}
                <div class="lg:w-56 shrink-0">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-brand-primary/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-sm text-text-primary leading-tight">{{ $prop->address_line_1 }}</div>
                            <div class="text-xs text-text-secondary">{{ $prop->city }}{{ $prop->suburb ? ', '.$prop->suburb : '' }}</div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $row['occupied'] ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-600' }}">
                                    {{ $row['occupied'] ? 'Occupied' : 'Vacant' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @if($hasRisks)
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach($row['risks'] as $risk)
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-warning-50 text-warning-700">
                            &#9888; {{ $riskLabels[$risk] ?? $risk }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Financial KPIs --}}
                <div class="grid grid-cols-3 gap-3 flex-1">
                    <div class="bg-surface-card rounded-xl border border-border-default/40 p-3 text-center">
                        <div class="text-xs text-text-secondary mb-1">Revenue</div>
                        <div class="font-bold text-success-600 text-sm">R{{ number_format($row['revenue']) }}</div>
                    </div>
                    <div class="bg-surface-card rounded-xl border border-border-default/40 p-3 text-center">
                        <div class="text-xs text-text-secondary mb-1">Expenses</div>
                        <div class="font-bold text-danger-600 text-sm">R{{ number_format($row['expenses']) }}</div>
                    </div>
                    <div class="bg-surface-card rounded-xl border border-border-default/40 p-3 text-center">
                        <div class="text-xs text-text-secondary mb-1">NOI</div>
                        <div class="font-bold text-sm {{ $row['noi'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">R{{ number_format($row['noi']) }}</div>
                    </div>
                </div>

                {{-- Monthly Revenue Trend (inline bar chart) --}}
                <div class="lg:w-56 shrink-0">
                    <div class="text-xs text-text-secondary mb-2 font-medium">Monthly Revenue ({{ $year }})</div>
                    <div class="flex items-end gap-0.5 h-10">
                        @foreach($row['trend'] as $m => $val)
                        @php $barHeight = $maxTrend > 0 ? max(2, round(($val / $maxTrend) * 40)) : 2; @endphp
                        <div class="flex-1 flex flex-col items-center gap-0.5 group relative"
                            title="{{ $months[$m-1] }}: R{{ number_format($val) }}">
                            <div class="w-full rounded-sm transition-colors {{ $val > 0 ? 'bg-brand-primary/70 group-hover:bg-brand-primary' : 'bg-surface-hover' }}"
                                style="height: {{ $barHeight }}px; min-height: 2px;"></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[9px] text-text-tertiary">Jan</span>
                        <span class="text-[9px] text-text-tertiary">Dec</span>
                    </div>
                </div>

            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

