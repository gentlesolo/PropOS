<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Tenant Management</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track all tenants, FICA status and linked leases</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Tenant
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([['label'=>'Active','val'=>$stats['active'],'color'=>'success'],['label'=>'Prospects','val'=>$stats['prospect'],'color'=>'brand'],['label'=>'Vacating','val'=>$stats['vacating'],'color'=>'warning'],['label'=>'Total','val'=>$stats['total'],'color'=>'secondary']] as $s)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $s['val'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Add Tenant Profile</h2>
        <form wire:submit.prevent="createTenant" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Contact *</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Select contact…</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
                @error('contact_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Linked Property</label>
                <select wire:model="listing_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None</option>
                    @foreach($listings as $l)
                    <option value="{{ $l->id }}">{{ $l->property?->address ?? 'Listing #'.$l->id }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                <select wire:model="status" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['prospect','active','vacating','vacated','blacklisted'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">ID Number</label>
                <input wire:model="id_number" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Employer</label>
                <input wire:model="employer" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Monthly Income</label>
                <input wire:model="monthly_income" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Save Tenant</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name…"
            class="flex-1 min-w-[200px] rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['prospect','active','vacating','vacated','blacklisted'] as $s)
            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Tenant List -->
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
                        @php $sc = $tenant->statusColor; @endphp
                        <tr class="hover:bg-surface-hover/30 cursor-pointer transition-colors" wire:click="selectTenant({{ $tenant->id }})">
                            <td class="px-4 py-3">
                                <div class="font-medium text-text-primary">{{ $tenant->contact?->full_name ?? '—' }}</div>
                                <div class="text-xs text-text-tertiary">{{ $tenant->contact?->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-text-secondary text-xs">{{ $tenant->listing?->property?->address ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst($tenant->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-text-secondary text-xs">
                                @if($tenant->activeLease)
                                R{{ number_format($tenant->activeLease->monthly_rent) }}/mo
                                @else
                                <span class="text-text-tertiary">No lease</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($tenant->fica_documents && count($tenant->fica_documents) > 0)
                                <span class="inline-flex items-center gap-1 text-xs text-success-700"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>{{ count($tenant->fica_documents) }} doc(s)</span>
                                @else
                                <span class="text-xs text-text-tertiary">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <svg class="w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">No tenants found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-border-default">{{ $tenants->links() }}</div>
            </div>
        </div>

        <!-- Tenant Detail Panel -->
        <div>
            @if($selectedTenant)
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
                <!-- Detail Header -->
                <div class="p-5 border-b border-border-default">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-text-primary">{{ $selectedTenant->contact?->full_name }}</h3>
                            <p class="text-xs text-text-secondary mt-0.5">{{ $selectedTenant->contact?->email }}</p>
                        </div>
                        <button wire:click="sendPortalLink" wire:loading.attr="disabled" class="text-xs px-3 py-1.5 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100 transition-colors">
                            Send Portal Link
                        </button>
                    </div>
                    <!-- Tabs -->
                    <div class="flex gap-1 mt-4">
                        @foreach(['overview' => 'Overview', 'fica' => 'FICA', 'maintenance' => 'Maintenance'] as $tab => $label)
                        <button wire:click="$set('detailTab','{{ $tab }}')" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $detailTab === $tab ? 'bg-brand-primary text-white' : 'text-text-secondary hover:bg-surface-hover' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="p-5">
                    @if($detailTab === 'overview')
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-text-secondary">Status</dt><dd class="font-medium text-text-primary capitalize">{{ $selectedTenant->status }}</dd></div>
                        <div class="flex justify-between"><dt class="text-text-secondary">ID Number</dt><dd class="font-medium text-text-primary">{{ $selectedTenant->id_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-text-secondary">Employer</dt><dd class="font-medium text-text-primary">{{ $selectedTenant->employer ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-text-secondary">Monthly Income</dt><dd class="font-medium text-text-primary">{{ $selectedTenant->monthly_income ? 'R'.number_format($selectedTenant->monthly_income) : '—' }}</dd></div>
                    </dl>
                    @if($selectedTenant->activeLease)
                    <div class="mt-4 pt-4 border-t border-border-default">
                        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Active Lease</p>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-text-secondary">Reference</dt><dd class="font-mono text-xs text-text-primary">{{ $selectedTenant->activeLease->reference }}</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Rent</dt><dd class="font-bold text-text-primary">R{{ number_format($selectedTenant->activeLease->monthly_rent) }}/mo</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Expires</dt><dd class="font-medium text-text-primary">{{ $selectedTenant->activeLease->end_date->format('d M Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-text-secondary">Days Left</dt>
                                <dd class="font-medium @if($selectedTenant->activeLease->daysUntilExpiry < 60) text-warning-600 @else text-text-primary @endif">
                                    {{ $selectedTenant->activeLease->daysUntilExpiry }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    @endif

                    @elseif($detailTab === 'fica')
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">FICA Documents</p>
                    @if($selectedTenant->fica_documents && count($selectedTenant->fica_documents) > 0)
                    <ul class="space-y-2 mb-4">
                        @foreach($selectedTenant->fica_documents as $doc)
                        <li class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-brand-primary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="text-text-primary truncate">{{ $doc['name'] }}</span>
                            <span class="text-text-tertiary text-xs ml-auto flex-shrink-0">{{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y') }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-sm text-text-tertiary mb-4">No FICA documents uploaded.</p>
                    @endif
                    <form wire:submit.prevent="uploadFica" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Upload Document (PDF / Image, max 10MB)</label>
                            <input wire:model="ficaFile" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:text-xs hover:file:bg-brand-100">
                            @error('ficaFile') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" wire:loading.attr="disabled" class="w-full py-2 bg-brand-primary text-white rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors">Upload</button>
                    </form>

                    @elseif($detailTab === 'maintenance')
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Maintenance Requests</p>
                        <button wire:click="$toggle('showMaintenanceForm')" class="text-xs px-2.5 py-1 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100">New Request</button>
                    </div>

                    @if($showMaintenanceForm)
                    <form wire:submit.prevent="submitMaintenance" class="space-y-3 mb-4 p-3 bg-surface-hover/30 rounded-xl">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                            <input wire:model="maintenance_title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="e.g. Leaking tap in kitchen">
                            @error('maintenance_title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Description *</label>
                            <textarea wire:model="maintenance_description" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"></textarea>
                            @error('maintenance_description') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Priority</label>
                            <select wire:model="maintenance_priority" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                                @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-medium hover:bg-brand-secondary">Submit</button>
                            <button type="button" wire:click="$set('showMaintenanceForm',false)" class="px-3 py-1.5 border border-border-default rounded-lg text-xs text-text-secondary hover:bg-surface-hover">Cancel</button>
                        </div>
                    </form>
                    @endif

                    @if($maintenanceRequests->isEmpty())
                    <p class="text-sm text-text-tertiary">No maintenance requests.</p>
                    @else
                    <ul class="space-y-2">
                        @foreach($maintenanceRequests as $req)
                        @php $pc = match($req->priority){ 'urgent'=>'danger','high'=>'warning','medium'=>'brand',default=>'secondary' }; @endphp
                        <li class="p-3 bg-surface-hover/30 rounded-xl">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-sm font-medium text-text-primary">{{ $req->title }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200 flex-shrink-0">{{ ucfirst($req->priority) }}</span>
                            </div>
                            <p class="text-xs text-text-secondary mt-1">{{ $req->description }}</p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-text-tertiary">{{ $req->created_at->format('d M Y') }}</span>
                                <span class="text-xs capitalize text-text-secondary">{{ str_replace('_',' ',$req->status) }}</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    @endif
                </div>
            </div>
            @else
            <div class="glass-panel rounded-2xl border border-border-default/60 p-8 text-center">
                <p class="text-sm text-text-tertiary">Select a tenant to view details</p>
            </div>
            @endif
        </div>
    </div>
</div>
