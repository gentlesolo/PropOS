<div class="flex gap-0 h-full">

    {{-- -- Main column ---------------------------------------------------------- --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Expenses</h1>
                <p class="text-sm text-text-secondary mt-0.5">Track, approve, and manage property expenses</p>
            </div>
            <button wire:click="openCreateForm"
                class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                + Add Expense
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-surface-card rounded-2xl border border-danger-200 p-4 text-center">
                <div class="text-xl font-bold text-danger-600">{{ $currencySymbol }}{{ number_format($stats['totalThisMonth']) }}</div>
                <div class="text-xs text-text-secondary mt-1">Approved This Month</div>
            </div>
            <div class="bg-surface-card rounded-2xl border border-warning-200 p-4 text-center">
                <div class="text-2xl font-bold text-warning-600">{{ $stats['pendingApproval'] }}</div>
                <div class="text-xs text-text-secondary mt-1">Pending Approval</div>
            </div>
            <div class="bg-surface-card rounded-2xl border border-success-200 p-4 text-center">
                <div class="text-xl font-bold text-success-600">{{ $currencySymbol }}{{ number_format($stats['deductibleTotal']) }}</div>
                <div class="text-xs text-text-secondary mt-1">Tax Deductible</div>
            </div>
        </div>

        {{-- Create form --}}
        @if($showCreateForm)
        <div class="bg-surface-card rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">New Expense</h2>
                <button wire:click="$set('showCreateForm', false)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="createExpense" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Vendor *</label>
                    <input wire:model="vendor_name" type="text"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('vendor_name') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Category *</label>
                    <select wire:model="category" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        @foreach(['maintenance'=>'Maintenance','utilities'=>'Utilities','insurance'=>'Insurance','municipal_rates'=>'Municipal Rates','management_fee'=>'Management Fee','advertising'=>'Advertising','legal'=>'Legal','cleaning'=>'Cleaning','other'=>'Other'] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Description *</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"></textarea>
                    @error('description') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Amount ({{ $currencySymbol }}) *</label>
                    <input wire:model="amount" type="number" step="0.01" min="0.01"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('amount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Date *</label>
                    <input wire:model="expense_date" type="date"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('expense_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Property (optional)</label>
                    <select wire:model="property_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        <option value="">&#8358; Portfolio-wide &#8358;</option>
                        @foreach($properties as $p)
                            <option value="{{ $p->id }}">{{ $p->address_line_1 }}, {{ $p->city }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Receipt</label>
                    <input wire:model="receipt" type="file" accept="image/*,.pdf"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <input wire:model="is_tax_deductible" type="checkbox" id="tax_ded" class="rounded border-border-default text-brand-primary">
                    <label for="tax_ded" class="text-sm text-text-secondary">Tax deductible</label>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Submit for Approval</button>
                    <button type="button" wire:click="$set('showCreateForm', false)"
                        class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Edit form --}}
        @if($showEditForm)
        <div class="bg-surface-card rounded-2xl border border-warning-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Edit Expense</h2>
                <button wire:click="cancelEdit" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="saveEdit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Vendor *</label>
                    <input wire:model="edit_vendor_name" type="text"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('edit_vendor_name') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Category *</label>
                    <select wire:model="edit_category" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        @foreach(['maintenance'=>'Maintenance','utilities'=>'Utilities','insurance'=>'Insurance','municipal_rates'=>'Municipal Rates','management_fee'=>'Management Fee','advertising'=>'Advertising','legal'=>'Legal','cleaning'=>'Cleaning','other'=>'Other'] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Description *</label>
                    <textarea wire:model="edit_description" rows="2"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"></textarea>
                    @error('edit_description') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Amount ({{ $currencySymbol }}) *</label>
                    <input wire:model="edit_amount" type="number" step="0.01" min="0.01"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('edit_amount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Date *</label>
                    <input wire:model="edit_expense_date" type="date"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('edit_expense_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Property</label>
                    <select wire:model="edit_property_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        <option value="">&#8358; Portfolio-wide &#8358;</option>
                        @foreach($properties as $p)
                            <option value="{{ $p->id }}">{{ $p->address_line_1 }}, {{ $p->city }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                    <input wire:model="edit_notes" type="text"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="edit_tax_deductible" type="checkbox" id="edit_tax_ded" class="rounded border-border-default text-brand-primary">
                    <label for="edit_tax_ded" class="text-sm text-text-secondary">Tax deductible</label>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Save & Resubmit</button>
                    <button type="button" wire:click="cancelEdit"
                        class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Filters --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="flex gap-1">
                <select wire:model="periodMonth" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    @foreach(range(1,12) as $m)
                        <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}">{{ \Carbon\Carbon::create(null,$m,1)->format('M') }}</option>
                    @endforeach
                </select>
                <select wire:model="periodYear" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    @foreach([now()->year-1, now()->year, now()->year+1] as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search vendor, description, ref&#8358;"
                class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <select wire:model.live="categoryFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Categories</option>
                @foreach(['maintenance'=>'Maintenance','utilities'=>'Utilities','insurance'=>'Insurance','municipal_rates'=>'Municipal Rates','management_fee'=>'Management Fee','advertising'=>'Advertising','legal'=>'Legal','cleaning'=>'Cleaning','other'=>'Other'] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Statuses</option>
                @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','paid'=>'Paid'] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-hover/50 border-b border-border-default">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Expense</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @forelse($expenses as $expense)
                    @php
                        $c       = match($expense->status){ 'approved','paid' => 'success', 'rejected' => 'danger', default => 'warning' };
                        $isActive = $detailExpenseId === $expense->id && $showDetail;
                    @endphp
                    <tr wire:click="openDetail({{ $expense->id }})"
                        class="cursor-pointer transition-colors {{ $isActive ? 'bg-brand-50/30' : 'hover:bg-surface-hover/30' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-text-primary text-sm">{{ $expense->vendor_name }}</div>
                            <div class="text-xs text-text-tertiary font-mono mt-0.5">{{ $expense->reference }}</div>
                        </td>
                        <td class="px-4 py-3 text-text-secondary text-xs capitalize">{{ str_replace('_',' ',$expense->category) }}</td>
                        <td class="px-4 py-3 text-text-secondary text-xs">{{ $expense->property?->address_line_1 ?? 'Portfolio' }}</td>
                        <td class="px-4 py-3 text-text-secondary text-xs">{{ $expense->expense_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($expense->amount) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">
                                {{ ucfirst($expense->status) }}
                            </span>
                            @if($expense->is_tax_deductible)
                            <span class="ml-1 text-xs text-success-600">? Ded.</span>
                            @endif
                        </td>
                        <td class="px-4 py-3" wire:click.stop>
                            <div class="flex gap-1 justify-end">
                                @if($expense->status === 'pending')
                                    <button wire:click="approveExpense({{ $expense->id }})" class="text-xs px-2 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Approve</button>
                                    <button wire:click="rejectExpense({{ $expense->id }})" class="text-xs px-2 py-1 bg-danger-50 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-100">Reject</button>
                                    <button wire:click="openEditForm({{ $expense->id }})" class="text-xs px-2 py-1 border border-border-default text-text-secondary rounded-lg hover:bg-surface-hover">Edit</button>
                                @elseif($expense->status === 'approved')
                                    <button wire:click="markPaid({{ $expense->id }})" class="text-xs px-2 py-1 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-100">Mark Paid</button>
                                @elseif($expense->status === 'rejected')
                                    <button wire:click="openEditForm({{ $expense->id }})" class="text-xs px-2 py-1 border border-border-default text-text-secondary rounded-lg hover:bg-surface-hover">Edit</button>
                                    <button wire:click="deleteExpense({{ $expense->id }})" onclick="return confirm('Delete this expense?')" class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">Del</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-14 text-center text-text-tertiary text-sm">
                            No expenses found.
                            @if($search || $statusFilter || $categoryFilter)
                                <button wire:click="$set('search',''); $set('statusFilter',''); $set('categoryFilter','')" class="ml-2 text-brand-600 underline text-xs">Clear filters</button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-border-default">{{ $expenses->links() }}</div>
        </div>
    </div>

    {{-- -- Detail panel --------------------------------------------------------- --}}
    @if($showDetail && $detailExpense)
    <div class="w-80 border-l border-border-default bg-surface-card overflow-y-auto flex-shrink-0">
        <div class="p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="font-semibold text-text-primary text-sm leading-tight">{{ $detailExpense->vendor_name }}</div>
                    <div class="font-mono text-xs text-text-tertiary mt-0.5">{{ $detailExpense->reference }}</div>
                </div>
                <button wire:click="closeDetail" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>

            @php $dc = match($detailExpense->status){ 'approved','paid' => 'success', 'rejected' => 'danger', default => 'warning' }; @endphp

            {{-- Amount --}}
            <div class="bg-surface-card rounded-2xl border border-{{ $dc }}-200 p-4 mb-4 text-center">
                <div class="text-3xl font-bold text-text-primary mb-1">{{ $currencySymbol }}{{ number_format($detailExpense->amount) }}</div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $dc }}-50 text-{{ $dc }}-700 border border-{{ $dc }}-200">
                    {{ ucfirst($detailExpense->status) }}
                </span>
                @if($detailExpense->is_tax_deductible)
                <div class="text-xs text-success-600 mt-2 font-medium">? Tax Deductible</div>
                @endif
            </div>

            {{-- Details --}}
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Details</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between"><span class="text-text-secondary">Category</span><span class="text-text-primary capitalize font-medium">{{ str_replace('_',' ',$detailExpense->category) }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Date</span><span class="text-text-primary font-medium">{{ $detailExpense->expense_date->format('d M Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Period</span><span class="text-text-primary">{{ str_pad($detailExpense->period_month,2,'0',STR_PAD_LEFT) }}/{{ $detailExpense->period_year }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Property</span><span class="text-text-primary text-right max-w-36 truncate">{{ $detailExpense->property?->address_line_1 ?? 'Portfolio' }}</span></div>
                </div>
            </div>

            {{-- Description --}}
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Description</div>
                <p class="text-xs text-text-secondary leading-relaxed">{{ $detailExpense->description }}</p>
            </div>

            {{-- Approval info --}}
            @if($detailExpense->approver)
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Approval</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between"><span class="text-text-secondary">By</span><span class="text-text-primary font-medium">{{ $detailExpense->approver->first_name }} {{ $detailExpense->approver->last_name }}</span></div>
                    @if($detailExpense->approved_at)
                    <div class="flex justify-between"><span class="text-text-secondary">On</span><span class="text-text-primary">{{ \Carbon\Carbon::parse($detailExpense->approved_at)->format('d M Y') }}</span></div>
                    @endif
                </div>
            </div>
            @endif

            @if($detailExpense->notes)
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Notes</div>
                <p class="text-xs text-text-secondary">{{ $detailExpense->notes }}</p>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-2 mt-4">
                @if($detailExpense->status === 'pending')
                    <button wire:click="approveExpense({{ $detailExpense->id }})" class="w-full py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors">Approve</button>
                    <button wire:click="rejectExpense({{ $detailExpense->id }})" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Reject</button>
                    <button wire:click="openEditForm({{ $detailExpense->id }})" class="w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Edit</button>
                    <button wire:click="deleteExpense({{ $detailExpense->id }})" onclick="return confirm('Delete this expense?')" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Delete</button>
                @elseif($detailExpense->status === 'approved')
                    <button wire:click="markPaid({{ $detailExpense->id }})" class="w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Mark as Paid</button>
                @elseif($detailExpense->status === 'rejected')
                    <button wire:click="openEditForm({{ $detailExpense->id }})" class="w-full py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Edit & Resubmit</button>
                    <button wire:click="deleteExpense({{ $detailExpense->id }})" onclick="return confirm('Delete this expense?')" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Delete</button>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>



