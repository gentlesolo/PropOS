<div class="flex gap-0 h-full">

    {{-- -- Main column ---------------------------------------------------------- --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Budgeting & Forecasting</h1>
                <p class="text-sm text-text-secondary mt-0.5">Plan, track, and forecast financial performance</p>
            </div>
            @if($activeTab === 'setup')
            <button wire:click="openCreateForm"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="openCreateForm">
                <span wire:loading.remove wire:target="openCreateForm">+ New Budget</span>
                <span wire:loading wire:target="openCreateForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            @endif
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 mb-6 bg-surface-hover/40 rounded-xl p-1 w-fit">
            @foreach(['setup'=>'Budgets','variance'=>'Variance Analysis','forecast'=>'Forecast'] as $tab => $label)
            <button wire:click="$set('activeTab','{{ $tab }}')"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $activeTab === $tab ? 'bg-surface-card text-text-primary shadow-sm' : 'text-text-secondary hover:text-text-primary' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            @endforeach
        </div>

        {{-- -- Setup Tab ------------------------------------------------------- --}}
        @if($activeTab === 'setup')

        {{-- Create / Edit form --}}
        @if($showForm)
        <div class="bg-surface-card rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">{{ $editBudgetId ? 'Edit Budget' : 'New Budget' }}</h2>
                <button wire:click="cancelForm" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none" wire:loading.attr="disabled" wire:target="cancelForm">
                <span wire:loading.remove wire:target="cancelForm">&times;</span>
                <span wire:loading wire:target="cancelForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            <form wire:submit.prevent="saveBudget" class="space-y-5">

                {{-- Meta fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-text-secondary mb-1">Budget Name *</label>
                        <input wire:model="budgetName" type="text" placeholder="e.g. FY2026 Portfolio Budget"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @error('budgetName') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Year *</label>
                        <select wire:model="budgetYear"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                            @foreach([now()->year-1, now()->year, now()->year+1, now()->year+2] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Property</label>
                        <select wire:model="budgetPropertyId"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                            <option value="">All Properties (Portfolio)</option>
                            @foreach($properties as $prop)
                                <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Vacancy Rate Assumption (%)</label>
                        <input wire:model="vacancyRate" type="number" step="0.1" min="0" max="100"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @error('vacancyRate') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Rent Escalation Assumption (%)</label>
                        <input wire:model="escalation" type="number" step="0.1" min="0" max="100"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @error('escalation') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Monthly targets grid --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Monthly Targets ({{ $currencySymbol }})</span>
                            <span class="ml-3 text-xs text-text-tertiary">Income row first, Expenses row second</span>
                        </div>
                        <button type="button" wire:click="prefillFromActuals" wire:loading.attr="disabled"
                            class="text-xs px-3 py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">
                            <span wire:loading.remove wire:target="prefillFromActuals">Prefill from actuals</span>
                            <span wire:loading wire:target="prefillFromActuals">Loading&#8358;</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-2">
                        @foreach($months as $i => $m)
                        <div>
                            <div class="text-xs text-center text-text-tertiary mb-1 font-medium">{{ \Carbon\Carbon::create(null,$m,1)->format('M') }}</div>
                            <input wire:model.lazy="monthlyIncomeTargets.{{ $i }}" type="number" step="1000" min="0"
                                title="Income {{ \Carbon\Carbon::create(null,$m,1)->format('F') }}"
                                class="w-full rounded-lg border border-success-200 bg-success-50/30 px-1.5 py-1.5 text-xs text-text-primary text-right focus:border-success-400 focus:ring-1 focus:ring-success-400 mb-1">
                            <input wire:model.lazy="monthlyExpenseTargets.{{ $i }}" type="number" step="1000" min="0"
                                title="Expenses {{ \Carbon\Carbon::create(null,$m,1)->format('F') }}"
                                class="w-full rounded-lg border border-danger-200 bg-danger-50/30 px-1.5 py-1.5 text-xs text-text-primary text-right focus:border-danger-400 focus:ring-1 focus:ring-danger-400">
                        </div>
                        @endforeach
                    </div>
                    {{-- Annual totals --}}
                    <div class="flex gap-6 mt-3 pt-3 border-t border-border-default">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-success-400 rounded-full"></span>
                            <span class="text-xs text-text-secondary">Annual Income Target:</span>
                            <span class="text-sm font-bold text-success-600">{{ $currencySymbol }}{{ number_format($formIncomeTotal) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-danger-400 rounded-full"></span>
                            <span class="text-xs text-text-secondary">Annual Expense Target:</span>
                            <span class="text-sm font-bold text-danger-600">{{ $currencySymbol }}{{ number_format($formExpenseTotal) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-text-secondary">Net:</span>
                            @php $formNet = $formIncomeTotal - $formExpenseTotal; @endphp
                            <span class="text-sm font-bold {{ $formNet >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $currencySymbol }}{{ number_format($formNet) }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                    <textarea wire:model="budgetNotes" rows="2" placeholder="Assumptions, context, key decisions&#8358;"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                        <span wire:loading.remove wire:target="saveBudget">{{ $editBudgetId ? 'Update Budget' : 'Save as Draft' }}</span>
                        <span wire:loading wire:target="saveBudget">Saving&#8358;</span>
                    </button>
                    <button type="button" wire:click="cancelForm"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="cancelForm">
                <span wire:loading.remove wire:target="cancelForm">Cancel</span>
                <span wire:loading wire:target="cancelForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Budget list --}}
        @if($budgets->isEmpty() && !$showForm)
        <div class="bg-surface-card rounded-2xl border border-border-default p-14 text-center">
            <div class="text-text-tertiary text-sm mb-3">No budgets created yet.</div>
            <button wire:click="openCreateForm" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="openCreateForm">
                <span wire:loading.remove wire:target="openCreateForm">Create First Budget</span>
                <span wire:loading wire:target="openCreateForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>
        @else
        <div class="space-y-3">
            @foreach($budgets as $budget)
            @php
                $bc      = match($budget->status){ 'approved','active' => 'success', 'closed' => 'secondary', default => 'brand' };
                $isActive = $detailBudgetId === $budget->id && $showDetail;
            @endphp
            <div wire:click="openDetail({{ $budget->id }})"
                class="bg-surface-card rounded-2xl border {{ $isActive ? 'border-brand-400' : 'border-border-default' }} p-4 cursor-pointer hover:border-brand-300 transition-colors">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-text-primary text-sm">{{ $budget->name }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $bc }}-50 text-{{ $bc }}-700 border border-{{ $bc }}-200">
                                {{ ucfirst($budget->status) }}
                            </span>
                            @if($budget->property_id)
                            <span class="text-xs text-text-tertiary px-2 py-0.5 bg-surface-hover rounded-full">Property</span>
                            @endif
                        </div>
                        <div class="text-xs text-text-secondary">
                            {{ $budget->year }} &#8358; {{ $budget->property?->address_line_1 ?? 'All Properties' }}
                            @if($budget->approver) &#8358; Approved by {{ $budget->approver->first_name }} {{ $budget->approver->last_name }}@endif
                        </div>
                        <div class="flex gap-4 mt-2 text-xs">
                            <span class="text-success-600 font-medium">Income: {{ $currencySymbol }}{{ number_format($budget->annualIncomeTarget) }}</span>
                            <span class="text-danger-600 font-medium">Expenses: {{ $currencySymbol }}{{ number_format($budget->annualExpenseTarget) }}</span>
                            @php $net = $budget->annualIncomeTarget - $budget->annualExpenseTarget; @endphp
                            <span class="{{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }} font-bold">Net: {{ $currencySymbol }}{{ number_format($net) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0" wire:click.stop>
                        <button wire:click="editBudget({{ $budget->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs px-2.5 py-1.5 border border-border-default rounded-lg hover:bg-surface-hover text-text-secondary transition-colors" wire:loading.attr="disabled" wire:target="editBudget">
                <span wire:loading.remove wire:target="editBudget">Edit</span>
                <span wire:loading wire:target="editBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="duplicateBudget({{ $budget->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs px-2.5 py-1.5 border border-border-default rounded-lg hover:bg-surface-hover text-text-secondary transition-colors"
                            title="Duplicate to next year" wire:loading.attr="disabled" wire:target="duplicateBudget">
                <span wire:loading.remove wire:target="duplicateBudget">Copy</span>
                <span wire:loading wire:target="duplicateBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @if($budget->status === 'draft')
                            <button wire:click="approveBudget({{ $budget->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs px-2.5 py-1.5 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100 transition-colors" wire:loading.attr="disabled" wire:target="approveBudget">
                <span wire:loading.remove wire:target="approveBudget">Approve</span>
                <span wire:loading wire:target="approveBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            <button wire:click="deleteBudget({{ $budget->id }})" onclick="return confirm('Delete this draft budget?')"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs px-2.5 py-1.5 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="deleteBudget">
                <span wire:loading.remove wire:target="deleteBudget">Del</span>
                <span wire:loading wire:target="deleteBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @elseif($budget->status === 'approved')
                            <button wire:click="activateBudget({{ $budget->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs px-2.5 py-1.5 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-100 transition-colors" wire:loading.attr="disabled" wire:target="activateBudget">
                <span wire:loading.remove wire:target="activateBudget">Activate</span>
                <span wire:loading wire:target="activateBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @elseif($budget->status === 'active')
                            <button wire:click="closeBudget({{ $budget->id }})" onclick="return confirm('Close this budget?')"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs px-2.5 py-1.5 border border-border-default text-text-secondary rounded-lg hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="closeBudget">
                <span wire:loading.remove wire:target="closeBudget">Close</span>
                <span wire:loading wire:target="closeBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @endif {{-- end setup tab --}}

        {{-- -- Variance Tab ----------------------------------------------------- --}}
        @if($activeTab === 'variance')
        <div class="flex flex-wrap gap-3 mb-5">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Compare Against Budget</label>
                <select wire:model.live="varianceBudgetId"
                    class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">Auto (current year approved)</option>
                    @foreach($budgets->whereNotIn('status',['closed']) as $b)
                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->year }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Property Filter</label>
                <select wire:model.live="variancePropertyId"
                    class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">All Properties</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(!$varianceBudget)
        <div class="bg-surface-card rounded-2xl border border-warning-200 p-8 text-center text-warning-700 text-sm">
            No approved budget found for the current year. Create and approve a budget first.
        </div>
        @else
        @php
            $totActInc  = collect($varianceData)->sum('actual_income');
            $totTgtInc  = collect($varianceData)->sum('income_target');
            $totActExp  = collect($varianceData)->sum('actual_expenses');
            $totTgtExp  = collect($varianceData)->sum('expense_target');
        @endphp
        <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center justify-between">
                <span class="text-xs font-semibold text-text-secondary">Comparing against: <span class="text-text-primary">{{ $varianceBudget->name }}</span></span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-surface-hover/50 border-b border-border-default">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Month</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Budget Inc.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Actual Inc.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Inc. Var</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Budget Exp.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Actual Exp.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Exp. Var</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @foreach($varianceData as $row)
                    <tr class="hover:bg-surface-hover/30">
                        <td class="px-4 py-3 font-medium text-text-primary text-xs">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 text-right text-text-secondary text-xs">{{ $currencySymbol }}{{ number_format($row['income_target']) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-text-primary text-xs">{{ $currencySymbol }}{{ number_format($row['actual_income']) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-semibold {{ $row['income_variance'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $row['income_variance'] >= 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($row['income_variance']) }}
                        </td>
                        <td class="px-4 py-3 text-right text-text-secondary text-xs">{{ $currencySymbol }}{{ number_format($row['expense_target']) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-text-primary text-xs">{{ $currencySymbol }}{{ number_format($row['actual_expenses']) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-semibold {{ $row['expense_variance'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $row['expense_variance'] >= 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($row['expense_variance']) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-border-default bg-surface-hover/40">
                    <tr>
                        <td class="px-4 py-3 text-xs font-bold text-text-primary">YTD Total</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-text-secondary">{{ $currencySymbol }}{{ number_format($totTgtInc) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($totActInc) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold {{ ($totActInc-$totTgtInc) >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ ($totActInc-$totTgtInc) >= 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($totActInc - $totTgtInc) }}
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-text-secondary">{{ $currencySymbol }}{{ number_format($totTgtExp) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($totActExp) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold {{ ($totTgtExp-$totActExp) >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ ($totTgtExp-$totActExp) >= 0 ? '+' : '' }}{{ $currencySymbol }}{{ number_format($totTgtExp - $totActExp) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
        @endif

        {{-- -- Forecast Tab ----------------------------------------------------- --}}
        @if($activeTab === 'forecast')
        <div class="bg-surface-card rounded-2xl border border-border-default p-4 mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-2">Vacancy Rate Override: <span class="text-brand-600 font-bold">{{ $forecastVacancy }}%</span></label>
                    <input wire:model.live="forecastVacancy" type="range" min="0" max="30" step="0.5"
                        class="w-full accent-brand-primary">
                    <div class="flex justify-between text-xs text-text-tertiary mt-1"><span>0%</span><span>15%</span><span>30%</span></div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Escalation Override (%)</label>
                    <input wire:model.live="forecastEscalation" type="number" step="0.5" min="0" max="30"
                        placeholder="Use lease defaults"
                        class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <p class="text-xs text-text-tertiary mt-1">0 = use each lease's own escalation %</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Property Filter</label>
                    <select wire:model.live="forecastPropertyId"
                        class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        <option value="">All Properties</option>
                        @foreach($properties as $prop)
                            <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if($forecastData)
        @php
            $totFcInc = collect($forecastData)->sum('projected_income');
            $totFcExp = collect($forecastData)->sum('projected_expenses');
            $totFcNet = collect($forecastData)->sum('projected_net');
        @endphp
        <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-hover/50 border-b border-border-default">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Month</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Proj. Income</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Proj. Expenses</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Proj. Net</th>
                        <th class="px-4 py-3 w-24">
                            <div class="text-xs text-text-tertiary text-right">Net margin</div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @foreach($forecastData as $row)
                    @php $margin = $row['projected_income'] > 0 ? round(($row['projected_net'] / $row['projected_income']) * 100, 1) : 0; @endphp
                    <tr class="hover:bg-surface-hover/30">
                        <td class="px-4 py-3 font-medium text-text-primary text-xs">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 text-right text-success-600 font-medium text-xs">{{ $currencySymbol }}{{ number_format($row['projected_income']) }}</td>
                        <td class="px-4 py-3 text-right text-danger-600 font-medium text-xs">{{ $currencySymbol }}{{ number_format($row['projected_expenses']) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-xs {{ $row['projected_net'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $currencySymbol }}{{ number_format($row['projected_net']) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <div class="w-16 bg-surface-hover rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $margin >= 0 ? 'bg-success-400' : 'bg-danger-400' }}"
                                        style="width: {{ min(abs($margin), 100) }}%"></div>
                                </div>
                                <span class="text-xs {{ $margin >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $margin }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-border-default bg-surface-hover/40">
                    <tr>
                        <td class="px-4 py-3 text-xs font-bold text-text-primary">12-Month Total</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-success-600">{{ $currencySymbol }}{{ number_format($totFcInc) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-danger-600">{{ $currencySymbol }}{{ number_format($totFcExp) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold {{ $totFcNet >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $currencySymbol }}{{ number_format($totFcNet) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="bg-surface-card rounded-2xl border border-border-default p-12 text-center text-text-tertiary text-sm">
            No active leases found to generate a forecast. Add active leases first.
        </div>
        @endif
        @endif

    </div>{{-- end main column --}}

    {{-- -- Detail panel --------------------------------------------------------- --}}
    @if($showDetail && $detailBudget)
    <div class="w-80 border-l border-border-default bg-surface-card overflow-y-auto flex-shrink-0">
        <div class="p-5">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="font-semibold text-text-primary text-sm leading-tight">{{ $detailBudget->name }}</div>
                    <div class="text-xs text-text-tertiary mt-0.5">{{ $detailBudget->year }} &#8358; {{ $detailBudget->property?->address_line_1 ?? 'All Properties' }}</div>
                </div>
                <button wire:click="closeDetail" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none" wire:loading.attr="disabled" wire:target="closeDetail">
                <span wire:loading.remove wire:target="closeDetail">&times;</span>
                <span wire:loading wire:target="closeDetail" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>

            @php
                $dc   = match($detailBudget->status){ 'approved','active' => 'success', 'closed' => 'secondary', default => 'brand' };
                $dNet = $detailBudget->annualIncomeTarget - $detailBudget->annualExpenseTarget;
            @endphp

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 gap-2 mb-4">
                <div class="bg-surface-card rounded-xl border border-success-200 p-3 text-center">
                    <div class="text-sm font-bold text-success-600">{{ $currencySymbol }}{{ number_format($detailBudget->annualIncomeTarget) }}</div>
                    <div class="text-xs text-text-tertiary mt-0.5">Income Target</div>
                </div>
                <div class="bg-surface-card rounded-xl border border-danger-200 p-3 text-center">
                    <div class="text-sm font-bold text-danger-600">{{ $currencySymbol }}{{ number_format($detailBudget->annualExpenseTarget) }}</div>
                    <div class="text-xs text-text-tertiary mt-0.5">Expense Target</div>
                </div>
                <div class="col-span-2 bg-surface-card rounded-xl border border-{{ $dNet >= 0 ? 'success' : 'danger' }}-200 p-3 text-center">
                    <div class="text-base font-bold {{ $dNet >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $currencySymbol }}{{ number_format($dNet) }}</div>
                    <div class="text-xs text-text-tertiary mt-0.5">Projected Net</div>
                </div>
            </div>

            {{-- Status + approval --}}
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs text-text-secondary">Status</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $dc }}-50 text-{{ $dc }}-700 border border-{{ $dc }}-200">
                        {{ ucfirst($detailBudget->status) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-text-secondary">Assumptions</span>
                    <span class="text-xs text-text-primary">{{ $detailBudget->vacancy_rate_assumption }}% vacancy &#8358; {{ $detailBudget->escalation_assumption }}% escalation</span>
                </div>
                @if($detailBudget->approver)
                <div class="flex justify-between items-center mt-1.5">
                    <span class="text-xs text-text-secondary">Approved by</span>
                    <span class="text-xs text-text-primary">{{ $detailBudget->approver->first_name }} {{ $detailBudget->approver->last_name }}</span>
                </div>
                @endif
                @if($detailBudget->approved_at)
                <div class="flex justify-between items-center mt-1.5">
                    <span class="text-xs text-text-secondary">Approved on</span>
                    <span class="text-xs text-text-primary">{{ \Carbon\Carbon::parse($detailBudget->approved_at)->format('d M Y') }}</span>
                </div>
                @endif
            </div>

            {{-- Monthly breakdown --}}
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Monthly Breakdown</div>
                <div class="space-y-1.5">
                    @foreach(range(0,11) as $i)
                    @php
                        $incT = (float) ($detailBudget->monthly_income_targets[$i] ?? 0);
                        $expT = (float) ($detailBudget->monthly_expense_targets[$i] ?? 0);
                        $net  = $incT - $expT;
                    @endphp
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-7 text-text-tertiary font-medium">{{ \Carbon\Carbon::create(null,$i+1,1)->format('M') }}</span>
                        <div class="flex-1 flex items-center gap-1">
                            <div class="flex-1 bg-surface-hover rounded-full h-1.5 relative">
                                @if($incT > 0)
                                @php $maxMonth = collect(range(0,11))->map(fn($x) => max((float)($detailBudget->monthly_income_targets[$x]??0),(float)($detailBudget->monthly_expense_targets[$x]??0)))->max() ?: 1; @endphp
                                <div class="absolute top-0 left-0 h-1.5 rounded-full bg-success-400" style="width:{{ min(($incT/$maxMonth)*100,100) }}%"></div>
                                <div class="absolute top-0 left-0 h-1.5 rounded-full bg-danger-400 opacity-70" style="width:{{ min(($expT/$maxMonth)*100,100) }}%"></div>
                                @endif
                            </div>
                        </div>
                        <span class="{{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }} font-medium w-20 text-right">{{ $currencySymbol }}{{ number_format($net / 1000) }}k</span>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($detailBudget->notes)
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Notes</div>
                <p class="text-xs text-text-secondary leading-relaxed">{{ $detailBudget->notes }}</p>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-2">
                <button wire:click="editBudget({{ $detailBudget->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="editBudget">
                <span wire:loading.remove wire:target="editBudget">Edit Budget</span>
                <span wire:loading wire:target="editBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="duplicateBudget({{ $detailBudget->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-brand-300 text-brand-600 bg-brand-50 rounded-xl text-sm font-medium hover:bg-brand-100 transition-colors" wire:loading.attr="disabled" wire:target="duplicateBudget">
                <span wire:loading.remove wire:target="duplicateBudget">Duplicate to {{ $detailBudget->year + 1 }}</span>
                <span wire:loading wire:target="duplicateBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @if($detailBudget->status === 'draft')
                <button wire:click="approveBudget({{ $detailBudget->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors" wire:loading.attr="disabled" wire:target="approveBudget">
                <span wire:loading.remove wire:target="approveBudget">Approve Budget</span>
                <span wire:loading wire:target="approveBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="deleteBudget({{ $detailBudget->id }})" onclick="return confirm('Delete this draft budget?')"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="deleteBudget">
                <span wire:loading.remove wire:target="deleteBudget">Delete Draft</span>
                <span wire:loading wire:target="deleteBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @elseif($detailBudget->status === 'approved')
                <button wire:click="activateBudget({{ $detailBudget->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="activateBudget">
                <span wire:loading.remove wire:target="activateBudget">Set Active</span>
                <span wire:loading wire:target="activateBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @elseif($detailBudget->status === 'active')
                <button wire:click="closeBudget({{ $detailBudget->id }})" onclick="return confirm('Close this budget?')"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="closeBudget">
                <span wire:loading.remove wire:target="closeBudget">Close Budget</span>
                <span wire:loading wire:target="closeBudget" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>



