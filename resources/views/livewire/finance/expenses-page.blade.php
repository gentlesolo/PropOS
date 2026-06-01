<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Expenses</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track and approve property expenses</p>
        </div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
            + Add Expense
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="glass-panel rounded-2xl border border-danger-200 p-4 text-center">
            <div class="text-xl font-bold text-danger-600">R{{ number_format($stats['total_this_month']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Total This Month</div>
        </div>
        <div class="glass-panel rounded-2xl border border-warning-200 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['pending_approval'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Pending Approval</div>
        </div>
        <div class="glass-panel rounded-2xl border border-success-200 p-4 text-center">
            <div class="text-xl font-bold text-success-600">R{{ number_format($stats['deductible_total']) }}</div>
            <div class="text-xs text-text-secondary mt-1">Tax Deductible</div>
        </div>
    </div>

    <!-- Add Expense Slide-over -->
    @if($showForm)
    <div class="glass-panel rounded-2xl border border-brand-200 p-6 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">New Expense</h2>
        <form wire:submit.prevent="createExpense" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Vendor *</label>
                <input wire:model="vendor_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('vendor_name') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Category *</label>
                <select wire:model="category" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    @foreach(['maintenance'=>'Maintenance','utilities'=>'Utilities','insurance'=>'Insurance','municipal_rates'=>'Municipal Rates','management_fee'=>'Management Fee','advertising'=>'Advertising','legal'=>'Legal','cleaning'=>'Cleaning','other'=>'Other'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Description *</label>
                <textarea wire:model="description" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"></textarea>
                @error('description') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Amount (R) *</label>
                <input wire:model="amount" type="number" step="0.01" min="0.01" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('amount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Date *</label>
                <input wire:model="expense_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('expense_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Property (optional)</label>
                <select wire:model="property_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">— None —</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->address_line_1 }}, {{ $prop->city }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Receipt</label>
                <input wire:model="receipt" type="file" accept="image/*,.pdf" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            </div>
            <div class="flex items-center gap-2 md:col-span-2">
                <input wire:model="is_tax_deductible" type="checkbox" id="tax_ded" class="rounded border-border-default text-brand-primary">
                <label for="tax_ded" class="text-sm text-text-secondary">Tax deductible</label>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Submit Expense</button>
                <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-4">
        <div class="flex gap-2 items-center">
            <select wire:model="periodMonth" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                @foreach(range(1,12) as $m)
                    <option value="{{ str_pad($m,2,'0',STR_PAD_LEFT) }}">{{ \Carbon\Carbon::create(null,$m,1)->format('F') }}</option>
                @endforeach
            </select>
            <select wire:model="periodYear" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                @foreach([now()->year-1, now()->year, now()->year+1] as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <select wire:model.live="categoryFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Categories</option>
            @foreach(['maintenance'=>'Maintenance','utilities'=>'Utilities','insurance'=>'Insurance','municipal_rates'=>'Municipal Rates','management_fee'=>'Management Fee','advertising'=>'Advertising','legal'=>'Legal','cleaning'=>'Cleaning','other'=>'Other'] as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','paid'=>'Paid'] as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Table -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($expenses as $expense)
                @php $c = match($expense->status){ 'approved','paid'=>'success','rejected'=>'danger',default=>'warning' }; @endphp
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-mono text-xs text-text-primary font-medium">{{ $expense->reference }}</div>
                        @if($expense->is_tax_deductible)<span class="text-xs text-success-600">Deductible</span>@endif
                    </td>
                    <td class="px-4 py-3 text-text-primary text-sm font-medium">{{ $expense->vendor_name }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs capitalize">{{ str_replace('_', ' ', $expense->category) }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $expense->expense_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-bold text-text-primary">R{{ number_format($expense->amount) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">{{ ucfirst($expense->status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($expense->status === 'pending')
                            <div class="flex gap-1">
                                <button wire:click="approveExpense({{ $expense->id }})" class="text-xs px-2 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Approve</button>
                                <button wire:click="rejectExpense({{ $expense->id }})" class="text-xs px-2 py-1 bg-danger-50 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-100">Reject</button>
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-text-tertiary text-sm">No expenses for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">{{ $expenses->links() }}</div>
    </div>
</div>
