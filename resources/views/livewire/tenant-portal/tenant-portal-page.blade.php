<div>
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text-primary">
            Welcome, {{ $tenant->contact?->first_name ?? 'Tenant' }}
        </h1>
        @if($tenant->listing?->property)
        <p class="text-sm text-text-secondary mt-1">
            {{ $tenant->listing->property->address_line_1 }}@if($tenant->listing->property->city), {{ $tenant->listing->property->city }}@endif
        </p>
        @endif
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 p-1 bg-surface-hover/40 rounded-xl w-fit">
        @foreach(['lease' => 'My Lease', 'payments' => 'Payments', 'maintenance' => 'Maintenance', 'documents' => 'Documents'] as $tab => $label)
        <button wire:click="$set('activeTab','{{ $tab }}')"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $activeTab === $tab ? 'bg-surface-primary text-brand-primary shadow-sm' : 'text-text-secondary hover:text-text-primary' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <!-- Lease Tab -->
    @if($activeTab === 'lease')
    @if($tenant->activeLease)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-text-primary">Lease Summary</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Active</span>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="p-3 bg-surface-hover/30 rounded-xl">
                <dt class="text-xs text-text-secondary mb-1">Reference</dt>
                <dd class="font-mono font-medium text-text-primary">{{ $tenant->activeLease->reference }}</dd>
            </div>
            <div class="p-3 bg-surface-hover/30 rounded-xl">
                <dt class="text-xs text-text-secondary mb-1">Monthly Rent</dt>
                <dd class="text-xl font-bold text-text-primary">R{{ number_format($tenant->activeLease->monthly_rent) }}</dd>
            </div>
            <div class="p-3 bg-surface-hover/30 rounded-xl">
                <dt class="text-xs text-text-secondary mb-1">Payment Due</dt>
                <dd class="font-medium text-text-primary">{{ $tenant->activeLease->payment_day }}{{ match((int)$tenant->activeLease->payment_day) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of each month</dd>
            </div>
            <div class="p-3 bg-surface-hover/30 rounded-xl">
                <dt class="text-xs text-text-secondary mb-1">Lease Starts</dt>
                <dd class="font-medium text-text-primary">{{ $tenant->activeLease->start_date->format('d M Y') }}</dd>
            </div>
            <div class="p-3 bg-surface-hover/30 rounded-xl">
                <dt class="text-xs text-text-secondary mb-1">Lease Expires</dt>
                <dd class="font-medium {{ $tenant->activeLease->daysUntilExpiry <= 60 ? 'text-warning-600' : 'text-text-primary' }}">
                    {{ $tenant->activeLease->end_date->format('d M Y') }}
                    @if($tenant->activeLease->daysUntilExpiry > 0 && $tenant->activeLease->daysUntilExpiry <= 60)
                    <span class="text-xs ml-1">({{ $tenant->activeLease->daysUntilExpiry }} days)</span>
                    @endif
                </dd>
            </div>
            <div class="p-3 bg-surface-hover/30 rounded-xl">
                <dt class="text-xs text-text-secondary mb-1">Security Deposit</dt>
                <dd class="font-medium text-text-primary">{{ $tenant->activeLease->deposit_amount ? 'R'.number_format($tenant->activeLease->deposit_amount) : '—' }}</dd>
            </div>
        </dl>
        @if($tenant->activeLease->special_conditions)
        <div class="mt-4 p-3 bg-surface-hover/30 rounded-xl">
            <dt class="text-xs text-text-secondary mb-1">Special Conditions</dt>
            <dd class="text-sm text-text-primary">{{ $tenant->activeLease->special_conditions }}</dd>
        </div>
        @endif
    </div>
    @else
    <div class="glass-panel rounded-2xl border border-border-default/60 p-8 text-center">
        <p class="text-text-secondary">No active lease found. Please contact your property manager.</p>
    </div>
    @endif

    <!-- Payments Tab -->
    @elseif($activeTab === 'payments')
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <div class="px-5 py-4 border-b border-border-default">
            <h2 class="text-base font-semibold text-text-primary">Payment History</h2>
        </div>
        @if($tenant->activeLease && $tenant->activeLease->rentPayments->isNotEmpty())
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Reference</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Due Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Amount Due</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Paid</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @foreach($tenant->activeLease->rentPayments->sortByDesc('due_date') as $payment)
                @php $pc = match($payment->status){ 'paid'=>'success','overdue'=>'danger','partial'=>'warning',default=>'secondary' }; @endphp
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs text-text-tertiary">{{ $payment->reference }}</td>
                    <td class="px-5 py-3 text-text-secondary">{{ $payment->due_date->format('d M Y') }}</td>
                    <td class="px-5 py-3 font-bold text-text-primary">R{{ number_format($payment->amount_due) }}</td>
                    <td class="px-5 py-3 text-text-secondary">{{ $payment->amount_paid ? 'R'.number_format($payment->amount_paid) : '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($payment->status) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-8 text-center text-text-secondary text-sm">No payment records available.</div>
        @endif
    </div>

    <!-- Maintenance Tab -->
    @elseif($activeTab === 'maintenance')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-text-primary">Maintenance Requests</h2>
            <button wire:click="$toggle('showMaintenanceForm')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Request
            </button>
        </div>

        @if($showMaintenanceForm)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <h3 class="text-sm font-semibold text-text-primary mb-4">Submit a Maintenance Request</h3>
            <form wire:submit.prevent="submitMaintenance" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                    <input wire:model="maintenance_title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="e.g. Leaking tap in bathroom">
                    @error('maintenance_title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Description *</label>
                    <textarea wire:model="maintenance_description" rows="3" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="Describe the issue in detail…"></textarea>
                    @error('maintenance_description') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Priority</label>
                    <select wire:model="maintenance_priority" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="low">Low — can wait</option>
                        <option value="medium">Medium — within a week</option>
                        <option value="high">High — within 48 hours</option>
                        <option value="urgent">Urgent — immediate attention</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Submit Request</button>
                    <button type="button" wire:click="$set('showMaintenanceForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        @if($maintenanceRequests->isEmpty())
        <div class="glass-panel rounded-2xl border border-border-default/60 p-8 text-center">
            <p class="text-text-secondary text-sm">No maintenance requests submitted yet.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($maintenanceRequests as $req)
            @php $pc = match($req->priority){ 'urgent'=>'danger','high'=>'warning','medium'=>'brand',default=>'secondary' }; $sc = match($req->status){ 'resolved'=>'success','in_progress'=>'brand','closed'=>'secondary',default=>'warning' }; @endphp
            <div class="glass-panel rounded-2xl border border-border-default/60 p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <h3 class="font-medium text-text-primary">{{ $req->title }}</h3>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($req->priority) }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst(str_replace('_',' ',$req->status)) }}</span>
                    </div>
                </div>
                <p class="text-sm text-text-secondary">{{ $req->description }}</p>
                <p class="text-xs text-text-tertiary mt-2">Submitted {{ $req->created_at->format('d M Y') }}</p>
                @if($req->resolution_notes)
                <div class="mt-3 p-3 bg-success-50 rounded-lg border border-success-200">
                    <p class="text-xs font-medium text-success-700 mb-1">Resolution Notes</p>
                    <p class="text-sm text-success-800">{{ $req->resolution_notes }}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Documents Tab -->
    @elseif($activeTab === 'documents')
    <div class="glass-panel rounded-2xl border border-border-default/60 p-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Your Documents</h2>
        @if($tenant->activeLease?->contract)
        <div class="flex items-center justify-between p-4 bg-surface-hover/30 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-text-primary">Lease Agreement</p>
                    <p class="text-xs text-text-secondary">{{ $tenant->activeLease->reference }}</p>
                </div>
            </div>
            @if($tenant->activeLease->contract->file_path)
            <a href="{{ Storage::url($tenant->activeLease->contract->file_path) }}" target="_blank"
                class="text-xs px-3 py-1.5 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg hover:bg-brand-100 transition-colors">
                Download
            </a>
            @else
            <span class="text-xs text-text-tertiary">Not available</span>
            @endif
        </div>
        @else
        <p class="text-sm text-text-secondary">No documents available. Please contact your property manager.</p>
        @endif
    </div>
    @endif
</div>
