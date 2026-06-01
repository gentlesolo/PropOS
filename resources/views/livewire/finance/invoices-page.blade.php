<div x-data class="flex gap-0 h-full">

    {{-- ══ Main column ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Invoices</h1>
                <p class="text-sm text-text-secondary mt-0.5">Generate, send, and manage all invoices</p>
            </div>
            <div class="flex gap-2">
                <div class="flex gap-1 items-center">
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
                    <button wire:click="generateInvoices" wire:loading.attr="disabled"
                        class="px-3 py-2 bg-surface-hover border border-border-default text-text-secondary rounded-xl text-sm hover:bg-surface-card transition-colors">
                        <span wire:loading.remove wire:target="generateInvoices">Generate Rent</span>
                        <span wire:loading wire:target="generateInvoices">Generating…</span>
                    </button>
                </div>
                <button wire:click="openCreateForm"
                    class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                    + New Invoice
                </button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="glass-panel rounded-2xl border border-brand-200 p-4 text-center">
                <div class="text-xl font-bold text-brand-600">₦{{ number_format($stats['totalInvoiced']) }}</div>
                <div class="text-xs text-text-secondary mt-1">Invoiced This Month</div>
            </div>
            <div class="glass-panel rounded-2xl border border-success-200 p-4 text-center">
                <div class="text-xl font-bold text-success-600">₦{{ number_format($stats['totalCollected']) }}</div>
                <div class="text-xs text-text-secondary mt-1">Collected</div>
            </div>
            <div class="glass-panel rounded-2xl border border-warning-200 p-4 text-center">
                <div class="text-xl font-bold text-warning-600">₦{{ number_format($stats['outstandingAr']) }}</div>
                <div class="text-xs text-text-secondary mt-1">Outstanding AR</div>
            </div>
            <div class="glass-panel rounded-2xl border border-danger-200 p-4 text-center">
                <div class="text-2xl font-bold text-danger-600">{{ $stats['overdueCount'] }}</div>
                <div class="text-xs text-text-secondary mt-1">Overdue</div>
            </div>
        </div>

        {{-- Payment link banner --}}
        @if($paymentLinkUrl)
        <div class="glass-panel rounded-2xl border border-brand-200 p-3 mb-4 flex items-center gap-3">
            <span class="text-xs font-medium text-text-secondary whitespace-nowrap">Payment Link:</span>
            <input type="text" value="{{ $paymentLinkUrl }}" readonly
                class="flex-1 rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-xs text-text-primary font-mono">
            <button onclick="navigator.clipboard.writeText('{{ addslashes($paymentLinkUrl) }}')"
                class="text-xs px-3 py-1.5 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-100 whitespace-nowrap">
                Copy
            </button>
            <button wire:click="$set('paymentLinkUrl', null)" class="text-text-tertiary hover:text-text-secondary text-lg leading-none">&times;</button>
        </div>
        @endif

        {{-- Create Form --}}
        @if($showCreateForm)
        <div class="glass-panel rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">New Invoice</h2>
                <button wire:click="$set('showCreateForm', false)" class="text-text-tertiary hover:text-text-secondary text-xl">&times;</button>
            </div>
            <form wire:submit.prevent="createInvoice" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Lease / Tenant *</label>
                        <select wire:model="form_lease_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                            <option value="">— Select Lease —</option>
                            @foreach($leases as $lease)
                            <option value="{{ $lease->id }}">
                                {{ $lease->tenant?->contact?->full_name ?? 'Unknown' }} — {{ $lease->listing?->property?->address_line_1 ?? $lease->reference }}
                            </option>
                            @endforeach
                        </select>
                        @error('form_lease_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Invoice Type *</label>
                        <select wire:model="form_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                            @foreach(['maintenance'=>'Maintenance','utility'=>'Utility','commission'=>'Commission','other'=>'Other'] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Due Date *</label>
                        <input wire:model="form_due_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('form_due_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Line Items --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-text-secondary">Line Items *</label>
                        <button type="button" wire:click="addLineItem" class="text-xs text-brand-600 hover:text-brand-primary font-medium">+ Add Line</button>
                    </div>
                    <div class="space-y-2">
                        @foreach($form_line_items as $i => $item)
                        <div class="grid grid-cols-12 gap-2 items-start">
                            <div class="col-span-5">
                                <input wire:model="form_line_items.{{ $i }}.description" type="text" placeholder="Description"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                @error("form_line_items.{$i}.description") <p class="text-xs text-danger-600 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <select wire:model="form_line_items.{{ $i }}.category" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-2 text-xs text-text-primary">
                                    @foreach(['rent'=>'Rent','maintenance'=>'Maint.','utility'=>'Utility','parking'=>'Parking','late_fee'=>'Late Fee','other'=>'Other'] as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input wire:model="form_line_items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" placeholder="Qty"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            </div>
                            <div class="col-span-2">
                                <input wire:model="form_line_items.{{ $i }}.unit_price" type="number" step="0.01" min="0.01" placeholder="Unit price"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                @error("form_line_items.{$i}.unit_price") <p class="text-xs text-danger-600 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-1 flex items-center justify-center pt-1.5">
                                @if(count($form_line_items) > 1)
                                <button type="button" wire:click="removeLineItem({{ $i }})" class="text-danger-500 hover:text-danger-700 text-base leading-none">&times;</button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Notes (internal)</label>
                    <textarea wire:model="form_notes" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="Internal note…"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Create Invoice</button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Edit Form --}}
        @if($showEditForm)
        <div class="glass-panel rounded-2xl border border-warning-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Edit Draft Invoice</h2>
                <button wire:click="$set('showEditForm', false)" class="text-text-tertiary hover:text-text-secondary text-xl">&times;</button>
            </div>
            <form wire:submit.prevent="saveEdit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Due Date *</label>
                        <input wire:model="edit_due_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('edit_due_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                        <input wire:model="edit_notes" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-text-secondary">Line Items</label>
                        <button type="button" wire:click="addEditLineItem" class="text-xs text-brand-600 hover:text-brand-primary font-medium">+ Add Line</button>
                    </div>
                    <div class="space-y-2">
                        @foreach($edit_line_items as $i => $item)
                        <div class="grid grid-cols-12 gap-2 items-start">
                            <div class="col-span-5">
                                <input wire:model="edit_line_items.{{ $i }}.description" type="text" placeholder="Description"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                @error("edit_line_items.{$i}.description") <p class="text-xs text-danger-600 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <select wire:model="edit_line_items.{{ $i }}.category" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-2 text-xs text-text-primary">
                                    @foreach(['rent'=>'Rent','maintenance'=>'Maint.','utility'=>'Utility','parking'=>'Parking','late_fee'=>'Late Fee','other'=>'Other'] as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input wire:model="edit_line_items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" placeholder="Qty"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            </div>
                            <div class="col-span-2">
                                <input wire:model="edit_line_items.{{ $i }}.unit_price" type="number" step="0.01" min="0.01" placeholder="Price"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            </div>
                            <div class="col-span-1 flex items-center justify-center pt-1.5">
                                @if(count($edit_line_items) > 1)
                                <button type="button" wire:click="removeEditLineItem({{ $i }})" class="text-danger-500 hover:text-danger-700 text-base leading-none">&times;</button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Save Changes</button>
                    <button type="button" wire:click="$set('showEditForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Filters --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search reference, tenant…"
                class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Statuses</option>
                @foreach(['draft'=>'Draft','sent'=>'Sent','paid'=>'Paid','partially_paid'=>'Partial','overdue'=>'Overdue','void'=>'Void'] as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="typeFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Types</option>
                @foreach(['rent'=>'Rent','maintenance'=>'Maintenance','utility'=>'Utility','commission'=>'Commission','other'=>'Other'] as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-hover/50 border-b border-border-default">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tenant / Property</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Due</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary uppercase tracking-wider">Paid</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @forelse($invoices as $invoice)
                    @php
                        $c  = match($invoice->status){
                            'paid'         => 'success',
                            'overdue'      => 'danger',
                            'partially_paid'=> 'warning',
                            'sent'         => 'brand',
                            'void'         => 'secondary',
                            default        => 'secondary',
                        };
                        $isActive = $detailInvoiceId === $invoice->id && $showDetail;
                    @endphp
                    <tr wire:click="openDetail({{ $invoice->id }})"
                        class="cursor-pointer transition-colors {{ $isActive ? 'bg-brand-50/30' : 'hover:bg-surface-hover/30' }}">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs font-medium text-text-primary">{{ $invoice->reference }}</div>
                            <div class="text-xs text-text-tertiary capitalize mt-0.5">{{ str_replace('_',' ',$invoice->type) }} · {{ $invoice->lineItems->count() }} line{{ $invoice->lineItems->count() === 1 ? '' : 's' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-text-primary">{{ $invoice->lease?->tenant?->contact?->full_name ?? '—' }}</div>
                            <div class="text-xs text-text-tertiary">{{ $invoice->lease?->listing?->property?->address_line_1 ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-text-secondary">{{ $invoice->due_date->format('d M Y') }}</div>
                            <div class="text-xs text-text-tertiary">{{ str_pad($invoice->period_month,2,'0',STR_PAD_LEFT) }}/{{ $invoice->period_year }}</div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-text-primary">₦{{ number_format($invoice->total) }}</td>
                        <td class="px-4 py-3 text-right text-xs {{ $invoice->amount_paid > 0 ? 'text-success-600 font-medium' : 'text-text-tertiary' }}">
                            {{ $invoice->amount_paid > 0 ? '₦'.number_format($invoice->amount_paid) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">
                                {{ ucfirst(str_replace('_',' ',$invoice->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3" wire:click.stop>
                            <div class="flex gap-1 justify-end">
                                @if($invoice->status === 'draft')
                                    <button wire:click="openEditForm({{ $invoice->id }})" class="text-xs px-2 py-1 border border-border-default rounded-lg hover:bg-surface-hover text-text-secondary" title="Edit">Edit</button>
                                    <button wire:click="deleteInvoice({{ $invoice->id }})" onclick="return confirm('Delete this draft?')" class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50" title="Delete">Del</button>
                                @elseif(!in_array($invoice->status, ['paid','void']))
                                    <button wire:click="openPaymentModal({{ $invoice->id }})" class="text-xs px-2 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Pay</button>
                                    <button wire:click="sendInvoice({{ $invoice->id }})" wire:loading.attr="disabled" class="text-xs px-2 py-1 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-100">Send</button>
                                    <button wire:click="voidInvoice({{ $invoice->id }})" onclick="return confirm('Void this invoice?')" class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">Void</button>
                                @else
                                    <button wire:click="openNoteModal({{ $invoice->id }})" class="text-xs px-2 py-1 border border-border-default rounded-lg hover:bg-surface-hover text-text-secondary">Note</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-14 text-center text-text-tertiary text-sm">
                            No invoices found.
                            @if($statusFilter || $typeFilter || $search)
                                <button wire:click="$set('statusFilter',''); $set('typeFilter',''); $set('search','')" class="ml-2 text-brand-600 underline text-xs">Clear filters</button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-border-default">{{ $invoices->links() }}</div>
        </div>
    </div>

    {{-- ══ Detail side panel ════════════════════════════════════════════════════ --}}
    @if($showDetail && $detailInvoice)
    <div class="w-96 border-l border-border-default bg-surface-card overflow-y-auto flex-shrink-0">
        <div class="p-5">
            {{-- Panel header --}}
            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="font-mono text-sm font-bold text-text-primary">{{ $detailInvoice->reference }}</div>
                    <div class="text-xs text-text-tertiary capitalize mt-0.5">{{ str_replace('_',' ',$detailInvoice->type) }}</div>
                </div>
                <button wire:click="closeDetail" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>

            {{-- Status + total --}}
            @php
                $dc = match($detailInvoice->status){
                    'paid'          => 'success',
                    'overdue'       => 'danger',
                    'partially_paid'=> 'warning',
                    'sent'          => 'brand',
                    default         => 'secondary',
                };
            @endphp
            <div class="glass-panel rounded-2xl border border-{{ $dc }}-200 p-4 mb-4 text-center">
                <div class="text-3xl font-bold text-text-primary mb-1">₦{{ number_format($detailInvoice->total) }}</div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $dc }}-50 text-{{ $dc }}-700 border border-{{ $dc }}-200">
                    {{ ucfirst(str_replace('_',' ',$detailInvoice->status)) }}
                </span>
                @if($detailInvoice->balance > 0 && $detailInvoice->status !== 'paid')
                <div class="text-xs text-{{ $dc }}-600 mt-2 font-medium">Balance: ₦{{ number_format($detailInvoice->balance) }}</div>
                @endif
            </div>

            {{-- Tenant & property --}}
            <div class="glass-panel rounded-xl border border-border-default/60 p-4 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Tenant</div>
                <div class="font-medium text-text-primary text-sm">{{ $detailInvoice->lease?->tenant?->contact?->full_name ?? '—' }}</div>
                <div class="text-xs text-text-secondary mt-1">{{ $detailInvoice->lease?->listing?->property?->address_line_1 ?? '—' }}, {{ $detailInvoice->lease?->listing?->property?->city ?? '' }}</div>
                @if($detailInvoice->lease)
                <div class="text-xs text-text-tertiary mt-1">Lease {{ $detailInvoice->lease->reference }} · ₦{{ number_format($detailInvoice->lease->monthly_rent) }}/mo</div>
                @endif
            </div>

            {{-- Dates --}}
            <div class="glass-panel rounded-xl border border-border-default/60 p-4 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Dates</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between"><span class="text-text-secondary">Period</span><span class="text-text-primary font-medium">{{ str_pad($detailInvoice->period_month,2,'0',STR_PAD_LEFT) }}/{{ $detailInvoice->period_year }}</span></div>
                    <div class="flex justify-between"><span class="text-text-secondary">Due Date</span><span class="text-text-primary font-medium">{{ $detailInvoice->due_date->format('d M Y') }}</span></div>
                    @if($detailInvoice->issued_at)
                    <div class="flex justify-between"><span class="text-text-secondary">Issued</span><span class="text-text-primary">{{ $detailInvoice->issued_at->format('d M Y') }}</span></div>
                    @endif
                    @if($detailInvoice->paid_at)
                    <div class="flex justify-between"><span class="text-text-secondary">Paid At</span><span class="text-success-600 font-medium">{{ $detailInvoice->paid_at->format('d M Y') }}</span></div>
                    @endif
                </div>
            </div>

            {{-- Line items --}}
            <div class="glass-panel rounded-xl border border-border-default/60 p-4 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Line Items</div>
                <div class="space-y-2">
                    @foreach($detailInvoice->lineItems as $item)
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-text-primary truncate">{{ $item->description }}</div>
                            <div class="text-xs text-text-tertiary capitalize">{{ str_replace('_',' ',$item->category) }}
                                @if($item->quantity != 1) · ×{{ rtrim(rtrim(number_format($item->quantity,2),'0'),'.') }}@endif
                            </div>
                        </div>
                        <div class="text-xs font-bold text-text-primary whitespace-nowrap">₦{{ number_format($item->amount) }}</div>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-border-default mt-3 pt-3 space-y-1">
                    @if($detailInvoice->tax_amount > 0)
                    <div class="flex justify-between text-xs text-text-secondary"><span>Tax</span><span>₦{{ number_format($detailInvoice->tax_amount) }}</span></div>
                    @endif
                    <div class="flex justify-between text-sm font-bold text-text-primary"><span>Total</span><span>₦{{ number_format($detailInvoice->total) }}</span></div>
                    @if($detailInvoice->amount_paid > 0)
                    <div class="flex justify-between text-xs text-success-600 font-medium"><span>Paid</span><span>₦{{ number_format($detailInvoice->amount_paid) }}</span></div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            @if($detailInvoice->notes)
            <div class="glass-panel rounded-xl border border-border-default/60 p-4 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Notes</div>
                <p class="text-xs text-text-secondary">{{ $detailInvoice->notes }}</p>
            </div>
            @endif

            {{-- Payment gateway --}}
            @if($detailInvoice->gateway_payment_url)
            <div class="glass-panel rounded-xl border border-brand-200 p-4 mb-4">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Payment Link</div>
                <div class="flex gap-2">
                    <input type="text" value="{{ $detailInvoice->gateway_payment_url }}" readonly
                        class="flex-1 rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs font-mono text-text-primary">
                    <button onclick="navigator.clipboard.writeText('{{ addslashes($detailInvoice->gateway_payment_url) }}')"
                        class="text-xs px-2 py-1.5 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg">Copy</button>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-2">
                @if($detailInvoice->status === 'draft')
                    <button wire:click="openEditForm({{ $detailInvoice->id }})" class="w-full py-2 border border-warning-300 text-warning-700 bg-warning-50 rounded-xl text-sm font-medium hover:bg-warning-100 transition-colors">Edit Draft</button>
                    <button wire:click="sendInvoice({{ $detailInvoice->id }})" wire:loading.attr="disabled" class="w-full py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Send to Tenant</button>
                    <button wire:click="deleteInvoice({{ $detailInvoice->id }})" onclick="return confirm('Delete this draft?')" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Delete Draft</button>
                @elseif(!in_array($detailInvoice->status, ['paid','void']))
                    <button wire:click="openPaymentModal({{ $detailInvoice->id }})" class="w-full py-2 bg-success-600 text-white rounded-xl text-sm font-medium hover:bg-success-700 transition-colors">Record Payment</button>
                    <button wire:click="generatePaymentLink({{ $detailInvoice->id }})" wire:loading.attr="disabled" class="w-full py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Generate Payment Link</button>
                    <button wire:click="sendInvoice({{ $detailInvoice->id }})" wire:loading.attr="disabled" class="w-full py-2 border border-brand-300 text-brand-600 bg-brand-50 rounded-xl text-sm font-medium hover:bg-brand-100 transition-colors">Resend Invoice</button>
                    <button wire:click="openNoteModal({{ $detailInvoice->id }})" class="w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Add Note</button>
                    <button wire:click="voidInvoice({{ $detailInvoice->id }})" onclick="return confirm('Void this invoice?')" class="w-full py-2 border border-danger-200 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">Void Invoice</button>
                @else
                    <button wire:click="openNoteModal({{ $detailInvoice->id }})" class="w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Add / Edit Note</button>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ══ Record Payment Modal ══════════════════════════════════════════════════ --}}
    @if($showPaymentModal)
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
         wire:click.self="$set('showPaymentModal', false)">
        <div class="bg-surface-card rounded-2xl border border-border-default p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-base font-semibold text-text-primary mb-5">Record Payment</h3>
            <form wire:submit.prevent="recordPayment" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Amount *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-tertiary text-sm">₦</span>
                        <input wire:model="paymentAmount" type="number" step="0.01" min="0.01"
                            class="w-full rounded-lg border border-border-default bg-surface-input pl-7 pr-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    @error('paymentAmount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Payment Date *</label>
                    <input wire:model="paymentDate" type="date"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @error('paymentDate') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Method *</label>
                    <select wire:model="paymentMethod"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="eft">EFT</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="payfast">PayFast</option>
                        <option value="e_wallet">E-Wallet</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Reference (optional)</label>
                    <input wire:model="paymentReference" type="text" placeholder="e.g. bank trnx ID"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 py-2.5 bg-success-600 text-white rounded-xl text-sm font-semibold hover:bg-success-700 transition-colors">
                        <span wire:loading.remove wire:target="recordPayment">Record Payment</span>
                        <span wire:loading wire:target="recordPayment">Saving…</span>
                    </button>
                    <button type="button" wire:click="$set('showPaymentModal', false)"
                        class="px-4 py-2.5 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ══ Note Modal ═══════════════════════════════════════════════════════════ --}}
    @if($showNoteModal)
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
         wire:click.self="$set('showNoteModal', false)">
        <div class="bg-surface-card rounded-2xl border border-border-default p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-base font-semibold text-text-primary mb-4">Invoice Note</h3>
            <form wire:submit.prevent="saveNote" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Note</label>
                    <textarea wire:model="noteText" rows="4" placeholder="e.g. Tenant confirmed payment by phone on 01 Jun…"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    @error('noteText') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 py-2.5 bg-brand-primary text-white rounded-xl text-sm font-semibold hover:bg-brand-hover transition-colors">Save Note</button>
                    <button type="button" wire:click="$set('showNoteModal', false)"
                        class="px-4 py-2.5 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
