<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Inspections</h1>
            <p class="text-sm text-text-secondary mt-0.5">Schedule and track property inspections and appraisals</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Schedule Inspection
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([['label'=>'Scheduled','val'=>$stats['scheduled'],'color'=>'brand'],['label'=>'Completed','val'=>$stats['completed'],'color'=>'success'],['label'=>'Passed','val'=>$stats['passed'],'color'=>'success'],['label'=>'Failed','val'=>$stats['failed'],'color'=>'danger']] as $s)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $s['val'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Schedule Inspection</h2>
        <form wire:submit.prevent="createInspection" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Type *</label>
                <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['pre_purchase'=>'Pre-Purchase','pre_rental'=>'Pre-Rental','routine'=>'Routine','exit'=>'Exit','appraisal'=>'Appraisal','building'=>'Building','pest'=>'Pest','electrical'=>'Electrical','plumbing'=>'Plumbing'] as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Scheduled Date & Time *</label>
                <input wire:model="scheduled_at" type="datetime-local" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('scheduled_at') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
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
                <label class="block text-xs font-medium text-text-secondary mb-1">Linked Deal</label>
                <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None</option>
                    @foreach($deals as $d)
                    <option value="{{ $d->id }}">{{ $d->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Inspector Name</label>
                <input wire:model="inspector_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Inspector Company</label>
                <input wire:model="inspector_company" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Estimated Cost (₦)</label>
                <input wire:model="cost" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Schedule</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by inspector or address…"
            class="flex-1 min-w-[200px] rounded-xl border border-border-default bg-surface-input px-4 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            @foreach(['scheduled','in_progress','completed','cancelled'] as $s)
            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select wire:model.live="typeFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Types</option>
            @foreach(['pre_purchase','pre_rental','routine','exit','appraisal','building','pest','electrical','plumbing'] as $t)
            <option value="{{ $t }}">{{ ucwords(str_replace('_', ' ', $t)) }}</option>
            @endforeach
        </select>
    </div>

    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-hover/50 border-b border-border-default">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Property / Deal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Inspector</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Scheduled</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Result</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default">
                @forelse($inspections as $inspection)
                @php $sc = $inspection->statusColor; $rc = $inspection->resultColor; @endphp
                <tr class="hover:bg-surface-hover/30 transition-colors">
                    <td class="px-4 py-3 text-xs font-medium text-text-primary">{{ ucwords(str_replace('_', ' ', $inspection->type)) }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $inspection->listing?->property?->address ?? $inspection->deal?->title ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $inspection->inspector_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary text-xs">{{ $inspection->scheduled_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst($inspection->status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($inspection->result !== 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $rc }}-50 text-{{ $rc }}-700 border border-{{ $rc }}-200">{{ ucwords(str_replace('_', ' ', $inspection->result)) }}</span>
                        @else
                        <span class="text-xs text-text-tertiary">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($inspection->status === 'scheduled')
                        <div class="flex gap-1.5">
                            <button wire:click="markComplete({{ $inspection->id }}, 'pass')" class="text-xs px-2 py-0.5 bg-success-50 text-success-700 border border-success-200 rounded-md hover:bg-success-100 transition-colors">Pass</button>
                            <button wire:click="markComplete({{ $inspection->id }}, 'fail')" class="text-xs px-2 py-0.5 bg-danger-50 text-danger-700 border border-danger-200 rounded-md hover:bg-danger-100 transition-colors">Fail</button>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-text-tertiary text-sm">No inspections found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-border-default">{{ $inspections->links() }}</div>
    </div>
</div>
