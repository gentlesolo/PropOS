<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Budgeting & Forecasting</h1>
            <p class="text-sm text-text-secondary mt-0.5">Plan, track, and forecast financial performance</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-surface-hover/40 rounded-xl p-1 w-fit">
        @foreach(['setup'=>'Budget Setup','variance'=>'Variance Analysis','forecast'=>'Forecast'] as $tab => $label)
        <button wire:click="$set('activeTab','{{ $tab }}')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $activeTab === $tab ? 'bg-surface-card text-text-primary shadow-sm' : 'text-text-secondary hover:text-text-primary' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if($activeTab === 'setup')
    <!-- Budget Setup -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editBudgetId ? 'Edit Budget' : 'New Budget' }}</h2>
                <form wire:submit.prevent="saveBudget" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Budget Name *</label>
                        <input wire:model="budgetName" type="text" placeholder="e.g. FY2026 Portfolio" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('budgetName') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Year *</label>
                        <select wire:model="budgetYear" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                            @foreach([now()->year-1, now()->year, now()->year+1, now()->year+2] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Property (optional)</label>
                        <select wire:model="budgetPropertyId" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                            <option value="">Portfolio (All Properties)</option>
                            @foreach($properties as $prop)
                                <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Vacancy Rate Assumption (%)</label>
                        <input wire:model="vacancyRate" type="number" step="0.1" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Escalation Assumption (%)</label>
                        <input wire:model="escalation" type="number" step="0.1" min="0" max="100" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    </div>
                    <button type="submit" class="w-full py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Save Budget</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-4">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Monthly Targets (R)</h3>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($months as $i => $m)
                    <div>
                        <label class="block text-xs text-text-secondary mb-1">{{ \Carbon\Carbon::create(null,$m,1)->format('M') }}</label>
                        <input wire:model="monthlyIncomeTargets.{{ $i }}" type="number" step="100" min="0" placeholder="Income" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary mb-1">
                        <input wire:model="monthlyExpenseTargets.{{ $i }}" type="number" step="100" min="0" placeholder="Expenses" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary">
                    </div>
                    @endforeach
                </div>
                <div class="flex gap-4 mt-3 text-xs text-text-tertiary">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 bg-success-400 rounded-full"></span>Income</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 bg-danger-400 rounded-full"></span>Expenses</span>
                </div>
            </div>

            <!-- Existing Budgets -->
            <div class="space-y-3">
                @foreach($budgets as $budget)
                @php $bc = match($budget->status){ 'approved','active'=>'success','closed'=>'secondary',default=>'brand' }; @endphp
                <div class="glass-panel rounded-2xl border border-border-default/60 p-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-text-primary text-sm">{{ $budget->name }}</div>
                        <div class="text-xs text-text-secondary">{{ $budget->year }} • {{ $budget->property?->address_line_1 ?? 'All Properties' }}</div>
                        <div class="text-xs text-text-tertiary mt-1">Income Target: R{{ number_format($budget->annualIncomeTarget) }} | Expense Target: R{{ number_format($budget->annualExpenseTarget) }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $bc }}-50 text-{{ $bc }}-700 border border-{{ $bc }}-200">{{ ucfirst($budget->status) }}</span>
                        <button wire:click="editBudget({{ $budget->id }})" class="text-xs px-2 py-1 border border-border-default rounded-lg hover:bg-surface-hover text-text-secondary">Edit</button>
                        @if($budget->status === 'draft')
                            <button wire:click="approveBudget({{ $budget->id }})" class="text-xs px-2 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Approve</button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($activeTab === 'variance')
    <!-- Variance Analysis -->
    <div class="mb-4 flex gap-3">
        <select wire:model.live="budgetPropertyId" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Properties</option>
            @foreach($properties as $prop)
                <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
            @endforeach
        </select>
    </div>
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Month</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Budget Income</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Actual Income</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Income Var</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Budget Expenses</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Actual Expenses</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Expense Var</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($varianceData as $row)
                <tr class="hover:bg-surface-hover/30">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $row['label'] }}</td>
                    <td class="px-4 py-3 text-right text-text-secondary text-xs">R{{ number_format($row['income_target']) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-text-primary">R{{ number_format($row['actual_income']) }}</td>
                    <td class="px-4 py-3 text-right text-xs font-medium {{ $row['income_variance'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $row['income_variance'] >= 0 ? '+' : '' }}R{{ number_format($row['income_variance']) }}
                    </td>
                    <td class="px-4 py-3 text-right text-text-secondary text-xs">R{{ number_format($row['expense_target']) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-text-primary">R{{ number_format($row['actual_expenses']) }}</td>
                    <td class="px-4 py-3 text-right text-xs font-medium {{ $row['expense_variance'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $row['expense_variance'] >= 0 ? '+' : '' }}R{{ number_format($row['expense_variance']) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-text-tertiary">No variance data available. Ensure an approved budget exists for this year.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($activeTab === 'forecast')
    <!-- 12-Month Forecast -->
    <div class="flex flex-wrap gap-3 mb-6">
        <div>
            <label class="block text-xs font-medium text-text-secondary mb-1">Vacancy Rate Override (%)</label>
            <input wire:model.live="forecastVacancy" type="range" min="0" max="30" step="0.5" class="w-48">
            <span class="text-xs text-brand-600 ml-2">{{ $forecastVacancy }}%</span>
        </div>
        <div>
            <label class="block text-xs font-medium text-text-secondary mb-1">Escalation Override (%)</label>
            <input wire:model.live="forecastEscalation" type="number" step="0.5" min="0" max="30" class="w-24 rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary">
        </div>
        <div>
            <label class="block text-xs font-medium text-text-secondary mb-1">Property</label>
            <select wire:model.live="forecastPropertyId" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Properties</option>
                @foreach($properties as $prop)
                    <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Month</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Projected Income</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Projected Expenses</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Projected Net</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($forecastData as $row)
                <tr class="hover:bg-surface-hover/30">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $row['label'] }}</td>
                    <td class="px-4 py-3 text-right text-success-600 font-medium">R{{ number_format($row['projected_income']) }}</td>
                    <td class="px-4 py-3 text-right text-danger-600 font-medium">R{{ number_format($row['projected_expenses']) }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $row['projected_net'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        R{{ number_format($row['projected_net']) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-12 text-center text-text-tertiary">Loading forecast… ensure active leases exist.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
