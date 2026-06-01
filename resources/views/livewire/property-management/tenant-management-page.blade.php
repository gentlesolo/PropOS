<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Tenant Management</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track all tenants, FICA documents, leases and maintenance</p>
        </div>
        <button wire:click="openCreateForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Tenant
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([['label'=>'Active','val'=>$stats['active'],'c'=>'success'],['label'=>'Prospects','val'=>$stats['prospect'],'c'=>'brand'],['label'=>'Vacating','val'=>$stats['vacating'],'c'=>'warning'],['label'=>'Total','val'=>$stats['total'],'c'=>'secondary']] as $s)
        <div class="glass-panel rounded-2xl border border-{{ $s['c'] }}-200 p-4 text-center">
            <div class="text-2xl font-bold text-{{ $s['c'] }}-600">{{ $s['val'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Create form --}}
    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-brand-200 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-text-primary">Add Tenant Profile</h2>
            <button wire:click="$set('showCreateForm', false)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
        </div>
        <form wire:submit.prevent="createTenant" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Contact *</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select contact…</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
                @error('contact_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Linked Rental Property</label>
                <select wire:model="listing_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None yet</option>
                    @foreach($listings as $l)
                    <option value="{{ $l->id }}">{{ $l->property?->address_line_1 ?? 'Listing #'.$l->id }}, {{ $l->property?->city ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                <select wire:model="status" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['prospect'=>'Prospect','active'=>'Active','vacating'=>'Vacating'] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Employer</label>
                <input wire:model="employer" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Monthly Income ({{ $currencySymbol }})</label>
                <input wire:model="monthly_income" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Internal Notes</label>
                <input wire:model="create_notes" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">Save Tenant</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Edit form --}}
    @if($showEditForm)
    <div class="glass-panel rounded-2xl border border-warning-200 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-text-primary">Edit Tenant Profile</h2>
            <button wire:click="cancelEdit" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
        </div>
        <form wire:submit.prevent="saveEdit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Status *</label>
                <select wire:model="edit_status" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['prospect'=>'Prospect','active'=>'Active','vacating'=>'Vacating','vacated'=>'Vacated','blacklisted'=>'Blacklisted'] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
                @error('edit_status') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Assigned Agent</label>
                <select wire:model="edit_assigned_agent" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">— Keep current —</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->first_name }} {{ $agent->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Employer</label>
                <input wire:model="edit_employer" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Monthly Income ({{ $currencySymbol }})</label>
                <input wire:model="edit_monthly_income" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('edit_monthly_income') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                <textarea wire:model="edit_notes" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"></textarea>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-warning-600 text-white rounded-xl text-sm font-medium hover:bg-warning-700 transition-colors">Save Changes</button>
                <button type="button" wire:click="cancelEdit" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name or phone…"
            class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['prospect'=>'Prospect','active'=>'Active','vacating'=>'Vacating','vacated'=>'Vacated','blacklisted'=>'Blacklisted'] as $v => $l)
            <option value="{{ $v }}">{{ $l }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Tenant list --}}
        <div class="xl:col-span-2">
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-surface-hover/50 border-b border-border-default">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Tenant</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Lease</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">FICA</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        @forelse($tenants as $tenant)
                        @php
                            $sc       = $tenant->statusColor;
                            $selected = $selectedTenantId === $tenant->id;
                        @endphp
                        <tr wire:click="selectTenant({{ $tenant->id }})"
                            class="cursor-pointer transition-colors {{ $selected ? 'bg-brand-50/30' : 'hover:bg-surface-hover/30' }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-text-primary">{{ $tenant->contact?->full_name ?? '—' }}</div>
                                <div class="text-xs text-text-tertiary">{{ $tenant->contact?->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-text-secondary text-xs">{{ $tenant->listing?->property?->address_line_1 ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($tenant->activeLease)
                                <span class="text-text-primary font-medium">{{ $currencySymbol }}{{ number_format($tenant->activeLease->monthly_rent) }}/mo</span>
                                @else
                                <span class="text-text-tertiary">No lease</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($tenant->fica_documents && count($tenant->fica_documents) > 0)
                                <span class="text-xs text-success-700 font-medium">✓ {{ count($tenant->fica_documents) }} doc(s)</span>
                                @else
                                <span class="text-xs text-warning-600">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right" wire:click.stop>
                                <div class="flex gap-1 justify-end">
                                    <button wire:click="openEditForm({{ $tenant->id }})"
                                        class="text-xs px-2 py-1 border border-border-default text-text-secondary rounded-lg hover:bg-surface-hover">Edit</button>
                                    @if($tenant->status !== 'blacklisted')
                                    <button wire:click="blacklistTenant({{ $tenant->id }})"
                                        onclick="return confirm('Blacklist {{ addslashes($tenant->contact?->full_name ?? 'tenant') }}?')"
                                        class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">Block</button>
                                    @endif
                                    @if(!$tenant->activeLease)
                                    <button wire:click="deleteTenant({{ $tenant->id }})"
                                        onclick="return confirm('Delete this tenant record?')"
                                        class="text-xs px-2 py-1 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">Del</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">
                                No tenants found.
                                @if($search || $statusFilter)
                                <button wire:click="$set('search',''); $set('statusFilter','')" class="ml-2 text-brand-600 underline text-xs">Clear filters</button>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-border-default">{{ $tenants->links() }}</div>
            </div>
        </div>

        {{-- Detail panel --}}
        <div>
            @if($selectedTenant)
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden sticky top-6">

                {{-- Header --}}
                <div class="p-4 border-b border-border-default">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="font-semibold text-text-primary">{{ $selectedTenant->contact?->full_name }}</h3>
                            <p class="text-xs text-text-secondary mt-0.5">{{ $selectedTenant->contact?->email }}</p>
                            <p class="text-xs text-text-tertiary">{{ $selectedTenant->contact?->phone }}</p>
                        </div>
                        <button wire:click="closeTenant" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
                    </div>
                    <div class="flex gap-1 flex-wrap">
                        <button wire:click="sendPortalLink" wire:loading.attr="disabled"
                            class="text-xs px-2.5 py-1.5 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-100">
                            Send Portal Link
                        </button>
                        <button wire:click="openEditForm({{ $selectedTenant->id }})"
                            class="text-xs px-2.5 py-1.5 border border-border-default text-text-secondary rounded-lg hover:bg-surface-hover">
                            Edit Profile
                        </button>
                        @if(!$selectedTenant->activeLease)
                        <button wire:click="deleteTenant({{ $selectedTenant->id }})"
                            onclick="return confirm('Delete this tenant?')"
                            class="text-xs px-2.5 py-1.5 text-danger-600 border border-danger-200 rounded-lg hover:bg-danger-50">
                            Delete
                        </button>
                        @endif
                    </div>
                    {{-- Tab bar --}}
                    <div class="flex gap-1 mt-3 border-t border-border-default pt-3">
                        @foreach(['overview'=>'Overview','payments'=>'Payments','fica'=>'FICA','maintenance'=>'Maintenance'] as $tab => $label)
                        <button wire:click="$set('detailTab','{{ $tab }}')"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $detailTab === $tab ? 'bg-brand-primary text-white' : 'text-text-secondary hover:bg-surface-hover' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <div class="p-4">

                    {{-- Overview tab --}}
                    @if($detailTab === 'overview')
                    @php $sc = $selectedTenant->statusColor; @endphp
                    <div class="mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">
                            {{ ucfirst($selectedTenant->status) }}
                        </span>
                        @if($selectedTenant->agent)
                        <span class="ml-2 text-xs text-text-tertiary">Agent: {{ $selectedTenant->agent->first_name }} {{ $selectedTenant->agent->last_name }}</span>
                        @endif
                    </div>

                    <dl class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between"><dt class="text-text-secondary">Employer</dt><dd class="font-medium text-text-primary">{{ $selectedTenant->employer ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-text-secondary">Monthly Income</dt><dd class="font-bold text-success-600">{{ $selectedTenant->monthly_income ? $currencySymbol.number_format($selectedTenant->monthly_income) : '—' }}</dd></div>
                        @if($selectedTenant->notes)
                        <div><dt class="text-text-secondary text-xs mb-1">Notes</dt><dd class="text-text-primary text-xs bg-surface-hover/40 rounded-lg p-2">{{ $selectedTenant->notes }}</dd></div>
                        @endif
                    </dl>

                    @if($selectedTenant->activeLease)
                    <div class="pt-3 border-t border-border-default">
                        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Active Lease</p>
                        <dl class="space-y-1.5 text-xs">
                            <div class="flex justify-between"><dt class="text-text-secondary">Ref</dt><dd class="font-mono text-text-primary">{{ $selectedTenant->activeLease->reference }}</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Rent</dt><dd class="font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($selectedTenant->activeLease->monthly_rent) }}/mo</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Deposit</dt><dd class="text-text-primary">{{ $currencySymbol }}{{ number_format($selectedTenant->activeLease->deposit_amount ?? 0) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Start</dt><dd class="text-text-primary">{{ $selectedTenant->activeLease->start_date->format('d M Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">End</dt><dd class="text-text-primary {{ $selectedTenant->activeLease->daysUntilExpiry < 60 ? 'text-warning-600 font-semibold' : '' }}">{{ $selectedTenant->activeLease->end_date->format('d M Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Days left</dt>
                                <dd class="font-semibold {{ $selectedTenant->activeLease->daysUntilExpiry < 60 ? 'text-warning-600' : 'text-text-primary' }}">
                                    {{ $selectedTenant->activeLease->daysUntilExpiry }}
                                </dd>
                            </div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Outstanding</dt>
                                <dd class="font-bold {{ $selectedTenant->activeLease->outstandingBalance > 0 ? 'text-danger-600' : 'text-success-600' }}">
                                    {{ $currencySymbol }}{{ number_format($selectedTenant->activeLease->outstandingBalance) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    @else
                    <div class="pt-3 border-t border-border-default text-xs text-text-tertiary">No active lease.</div>
                    @endif

                    {{-- Payments tab --}}
                    @elseif($detailTab === 'payments')
                    @php
                        $lease = $selectedTenant->activeLease;
                        $payments = $lease ? $lease->rentPayments->sortByDesc('due_date')->take(12) : collect();
                    @endphp
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Recent Payments</p>
                    @if($payments->isEmpty())
                    <p class="text-sm text-text-tertiary">No payment history.</p>
                    @else
                    <div class="space-y-2">
                        @foreach($payments as $pmt)
                        @php $pc = match($pmt->status){ 'paid'=>'success','overdue'=>'danger','partial'=>'warning','waived'=>'secondary',default=>'brand' }; @endphp
                        <div class="flex items-center justify-between p-2.5 bg-surface-hover/30 rounded-xl text-xs">
                            <div>
                                <div class="font-medium text-text-primary">{{ $pmt->due_date->format('M Y') }}</div>
                                <div class="text-text-tertiary font-mono">{{ $pmt->reference }}</div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($pmt->status) }}</span>
                                <div class="text-text-primary font-bold mt-0.5">{{ $currencySymbol }}{{ number_format($pmt->amount_paid ?? 0) }}<span class="text-text-tertiary font-normal">/{{ $currencySymbol }}{{ number_format($pmt->amount_due) }}</span></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- FICA tab --}}
                    @elseif($detailTab === 'fica')
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">FICA Documents</p>
                    @if($selectedTenant->fica_documents && count($selectedTenant->fica_documents) > 0)
                    <ul class="space-y-2 mb-4">
                        @foreach($selectedTenant->fica_documents as $doc)
                        <li class="flex items-center gap-2 text-sm p-2 bg-surface-hover/30 rounded-xl">
                            <svg class="w-4 h-4 text-brand-primary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="text-text-primary truncate text-xs flex-1">{{ $doc['name'] }}</span>
                            <span class="text-text-tertiary text-xs">{{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y') }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-xs text-text-tertiary mb-4">No documents uploaded yet.</p>
                    @endif
                    <form wire:submit.prevent="uploadFica" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Upload Document (PDF/Image, max 10MB)</label>
                            <input wire:model="ficaFile" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full text-xs text-text-secondary file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                            @error('ficaFile') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full py-2 bg-brand-primary text-white rounded-xl text-xs font-medium hover:bg-brand-hover transition-colors">Upload Document</button>
                    </form>

                    {{-- Maintenance tab --}}
                    @elseif($detailTab === 'maintenance')
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Requests</p>
                        <button wire:click="$toggle('showMaintenanceForm')"
                            class="text-xs px-2.5 py-1 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100">+ New</button>
                    </div>

                    @if($showMaintenanceForm)
                    <form wire:submit.prevent="submitMaintenance" class="space-y-3 mb-4 p-3 bg-surface-hover/30 rounded-xl">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                            <input wire:model="maintenance_title" type="text" placeholder="e.g. Leaking tap in kitchen"
                                class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            @error('maintenance_title') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Description *</label>
                            <textarea wire:model="maintenance_description" rows="2"
                                class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"></textarea>
                            @error('maintenance_description') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Priority</label>
                            <select wire:model="maintenance_priority" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                                @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" wire:loading.attr="disabled"
                                class="flex-1 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-medium hover:bg-brand-hover">Submit</button>
                            <button type="button" wire:click="$set('showMaintenanceForm',false)"
                                class="px-3 py-1.5 border border-border-default rounded-lg text-xs text-text-secondary hover:bg-surface-hover">Cancel</button>
                        </div>
                    </form>
                    @endif

                    @if($maintenanceRequests->isEmpty())
                    <p class="text-xs text-text-tertiary">No maintenance requests.</p>
                    @else
                    <div class="space-y-2">
                        @foreach($maintenanceRequests as $req)
                        @php $pc = match($req->priority){ 'urgent'=>'danger','high'=>'warning','medium'=>'brand',default=>'secondary' }; @endphp
                        <div class="p-3 bg-surface-hover/30 rounded-xl">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <span class="text-xs font-semibold text-text-primary leading-tight">{{ $req->title }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200 flex-shrink-0">
                                    {{ ucfirst($req->priority) }}
                                </span>
                            </div>
                            <p class="text-xs text-text-secondary leading-relaxed mb-2">{{ \Illuminate\Support\Str::limit($req->description, 80) }}</p>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs text-text-tertiary">{{ $req->created_at->format('d M Y') }}</span>
                                <div class="flex gap-1">
                                    @if($req->status === 'open')
                                    <button wire:click="updateMaintenanceStatus({{ $req->id }}, 'in_progress')"
                                        class="text-xs px-2 py-0.5 bg-brand-50 text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-100">Start</button>
                                    @elseif($req->status === 'in_progress')
                                    <button wire:click="updateMaintenanceStatus({{ $req->id }}, 'resolved')"
                                        class="text-xs px-2 py-0.5 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100">Resolve</button>
                                    @elseif($req->status === 'resolved')
                                    <button wire:click="updateMaintenanceStatus({{ $req->id }}, 'closed')"
                                        class="text-xs px-2 py-0.5 border border-border-default text-text-secondary rounded-lg hover:bg-surface-hover">Close</button>
                                    @endif
                                    <span class="text-xs px-2 py-0.5 capitalize text-text-tertiary">{{ str_replace('_',' ',$req->status) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @endif {{-- end detailTab --}}
                </div>
            </div>
            @else
            <div class="glass-panel rounded-2xl border border-border-default/60 p-10 text-center">
                <p class="text-sm text-text-tertiary">Select a tenant from the list to view details</p>
            </div>
            @endif
        </div>
    </div>
</div>
