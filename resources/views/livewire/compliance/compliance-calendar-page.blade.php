<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Compliance Calendar</h1>
            <p class="text-sm text-text-secondary mt-0.5">Track mandatory inspections, certifications, FICA deadlines, and audits</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Reminder
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-panel rounded-2xl border border-danger-200 p-4 text-center">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['overdue'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Overdue</div>
        </div>
        <div class="glass-panel rounded-2xl border border-warning-200 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['due_this_week'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Due This Week</div>
        </div>
        <div class="glass-panel rounded-2xl border border-brand-200 p-4 text-center">
            <div class="text-2xl font-bold text-brand-primary">{{ $stats['due_this_month'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Due This Month</div>
        </div>
        <div class="glass-panel rounded-2xl border border-success-200 p-4 text-center">
            <div class="text-2xl font-bold text-success-600">{{ $stats['completed'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Completed</div>
        </div>
    </div>

    {{-- Create Form --}}
    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-brand-primary/30 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">New Compliance Reminder</h2>
        <form wire:submit.prevent="createReminder" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                <input wire:model="title" type="text" placeholder="e.g. Annual gas safety certificate – 14 Oak St"
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Type *</label>
                <select wire:model="reminder_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['inspection'=>'Inspection','certification'=>'Certification','fica'=>'FICA Compliance','audit'=>'Audit','lease_renewal'=>'Lease Renewal','maintenance'=>'Maintenance','other'=>'Other'] as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Due Date *</label>
                <input wire:model="due_date" type="date"
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                @error('due_date') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                <textarea wire:model="notes" rows="2" placeholder="Optional notes..."
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"></textarea>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Save</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-5 py-2 bg-surface-hover text-text-secondary rounded-xl text-sm font-medium hover:text-text-primary transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-5">
        <div class="relative flex-1 min-w-[180px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search reminders..."
                class="w-full pl-9 pr-4 py-2 rounded-xl border border-border-default bg-surface-input text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        </div>
        <select wire:model.live="typeFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Types</option>
            @foreach(['inspection'=>'Inspection','certification'=>'Certification','fica'=>'FICA','audit'=>'Audit','lease_renewal'=>'Lease Renewal','maintenance'=>'Maintenance','other'=>'Other'] as $val=>$label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="acknowledged">Acknowledged</option>
            <option value="overdue">Overdue</option>
            <option value="completed">Completed</option>
        </select>
    </div>

    {{-- Reminder List --}}
    <div class="space-y-3">
        @forelse($reminders as $reminder)
        @php
            $urgency = $reminder->urgency;
            $borderColor = match($urgency) {
                'overdue'   => 'border-danger-300',
                'due_soon'  => 'border-warning-300',
                'upcoming'  => 'border-brand-200',
                'completed' => 'border-success-200',
                default     => 'border-border-default/60',
            };
            $badgeColor = match($urgency) {
                'overdue'   => 'bg-danger-50 text-danger-700',
                'due_soon'  => 'bg-warning-50 text-warning-700',
                'upcoming'  => 'bg-brand-50 text-brand-primary',
                'completed' => 'bg-success-50 text-success-700',
                default     => 'bg-surface-hover text-text-secondary',
            };
            $typeLabels = ['inspection'=>'Inspection','certification'=>'Certification','fica'=>'FICA','audit'=>'Audit','lease_renewal'=>'Lease Renewal','maintenance'=>'Maintenance','other'=>'Other'];
        @endphp
        <div class="glass-panel rounded-2xl border {{ $borderColor }} p-4 flex flex-col md:flex-row md:items-center gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-text-primary">{{ $reminder->title }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-surface-hover text-text-secondary">
                        {{ $typeLabels[$reminder->reminder_type] ?? $reminder->reminder_type }}
                    </span>
                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeColor }} capitalize">
                        {{ str_replace('_', ' ', $urgency === 'due_soon' ? 'due soon' : $urgency) }}
                    </span>
                </div>
                <div class="flex items-center gap-4 mt-1 text-xs text-text-secondary flex-wrap">
                    <span>Due: <span class="font-medium text-text-primary">{{ $reminder->due_date->format('d M Y') }}</span></span>
                    @if($reminder->due_date->isFuture())
                        <span>{{ $reminder->due_date->diffForHumans() }}</span>
                    @elseif($reminder->status !== 'completed')
                        <span class="text-danger-600 font-medium">{{ abs($reminder->due_date->diffInDays(now())) }} days overdue</span>
                    @endif
                    @if($reminder->createdBy)
                        <span>Created by {{ $reminder->createdBy->first_name }}</span>
                    @endif
                </div>
                @if($reminder->notes)
                <p class="text-xs text-text-tertiary mt-1 truncate">{{ $reminder->notes }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($reminder->status === 'pending')
                    <button wire:click="acknowledge({{ $reminder->id }})" class="px-3 py-1.5 text-xs font-medium bg-brand-primary/10 text-brand-primary rounded-lg hover:bg-brand-primary/20 transition-colors">
                        Acknowledge
                    </button>
                @endif
                @if(in_array($reminder->status, ['pending','acknowledged','overdue']))
                    <button wire:click="markComplete({{ $reminder->id }})" class="px-3 py-1.5 text-xs font-medium bg-success-50 text-success-700 rounded-lg hover:bg-success-100 transition-colors">
                        Mark Done
                    </button>
                @endif
                <button wire:click="delete({{ $reminder->id }})" wire:confirm="Delete this reminder?" class="px-3 py-1.5 text-xs font-medium text-danger-500 hover:text-danger-700 transition-colors">
                    Delete
                </button>
            </div>
        </div>
        @empty
        <div class="glass-panel rounded-2xl border border-border-default/60 p-12 text-center">
            <svg class="w-10 h-10 text-text-tertiary mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            <p class="text-text-secondary text-sm">No compliance reminders found.</p>
            <p class="text-text-tertiary text-xs mt-1">Add your first reminder using the button above.</p>
        </div>
        @endforelse
    </div>

    @if($reminders->hasPages())
    <div class="mt-4">{{ $reminders->links() }}</div>
    @endif
</div>
