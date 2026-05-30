<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Task Board</h1>
            <p class="text-sm text-text-secondary mt-0.5">Manage your daily tasks and follow-ups</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Task
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4">
            <div class="text-2xl font-bold text-text-primary">{{ $stats['pending'] }}</div>
            <div class="text-xs text-text-secondary mt-1">My Open Tasks</div>
        </div>
        <div class="glass-panel rounded-2xl border border-danger-200 p-4">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['overdue'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Overdue</div>
        </div>
        <div class="glass-panel rounded-2xl border border-warning-200 p-4">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['due_today'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Due Today</div>
        </div>
        <div class="glass-panel rounded-2xl border border-success-200 p-4">
            <div class="text-2xl font-bold text-success-600">{{ $stats['completed_week'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Completed This Week</div>
        </div>
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Create Task</h2>
        <form wire:submit.prevent="createTask" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                <input wire:model="title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="What needs to be done?">
                @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Type</label>
                <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @foreach(['call'=>'Call','email'=>'Email','meeting'=>'Meeting','document'=>'Document','follow_up'=>'Follow Up','viewing'=>'Viewing','other'=>'Other'] as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Priority</label>
                <select wire:model="priority" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Assign To</label>
                <select wire:model="assigned_to" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">Myself</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->first_name }} {{ $agent->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Due Date</label>
                <input wire:model="due_at" type="datetime-local" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Link Contact</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Link Deal</label>
                <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <option value="">None</option>
                    @foreach($deals as $d)
                    <option value="{{ $d->id }}">{{ $d->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Create Task</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Overdue Alert -->
    @if($overdue->isNotEmpty())
    <div class="bg-danger-50 border border-danger-200 rounded-2xl p-4 mb-6">
        <div class="text-sm font-semibold text-danger-700 mb-2">{{ $overdue->count() }} Overdue Task(s)</div>
        @foreach($overdue->take(3) as $t)
        <div class="flex items-center justify-between py-1.5 border-b border-danger-100 last:border-0">
            <span class="text-sm text-danger-800">{{ $t->title }}</span>
            <div class="flex gap-2">
                <span class="text-xs text-danger-500">{{ $t->due_at?->diffForHumans() }}</span>
                <button wire:click="completeTask({{ $t->id }})" class="text-xs px-2 py-0.5 bg-success-100 text-success-700 rounded-md hover:bg-success-200 transition-colors">Done</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Board Columns -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Pending -->
        <div>
            <h3 class="text-sm font-semibold text-text-secondary uppercase tracking-wider mb-3 px-1">Pending ({{ $pending->count() }})</h3>
            <div class="space-y-3">
                @forelse($pending as $task)
                @php $pc = ['urgent'=>'danger','high'=>'warning','medium'=>'brand','low'=>'secondary'][$task->priority] ?? 'secondary'; @endphp
                <div class="glass-panel rounded-xl border border-border-default/60 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text-primary truncate">{{ $task->title }}</p>
                            @if($task->contact)
                            <p class="text-xs text-text-tertiary mt-0.5">{{ $task->contact->full_name }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($task->priority) }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-text-tertiary">{{ $task->due_at ? $task->due_at->format('d M H:i') : 'No due date' }}</span>
                        <button wire:click="completeTask({{ $task->id }})" class="text-xs px-2.5 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100 transition-colors">Complete</button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">No pending tasks.</p>
                @endforelse
            </div>
        </div>

        <!-- In Progress -->
        <div>
            <h3 class="text-sm font-semibold text-text-secondary uppercase tracking-wider mb-3 px-1">In Progress ({{ $inProgress->count() }})</h3>
            <div class="space-y-3">
                @forelse($inProgress as $task)
                <div class="glass-panel rounded-xl border border-border-default/60 p-4">
                    <p class="text-sm font-medium text-text-primary">{{ $task->title }}</p>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-text-tertiary">{{ $task->assignedTo?->first_name ?? 'Unassigned' }}</span>
                        <button wire:click="completeTask({{ $task->id }})" class="text-xs px-2.5 py-1 bg-success-50 text-success-700 border border-success-200 rounded-lg hover:bg-success-100 transition-colors">Complete</button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">None in progress.</p>
                @endforelse
            </div>
        </div>

        <!-- Completed -->
        <div>
            <h3 class="text-sm font-semibold text-text-secondary uppercase tracking-wider mb-3 px-1">Recently Completed</h3>
            <div class="space-y-3">
                @forelse($completed as $task)
                <div class="glass-panel rounded-xl border border-border-default/60 p-4 opacity-70">
                    <p class="text-sm font-medium text-text-primary line-through">{{ $task->title }}</p>
                    <p class="text-xs text-text-tertiary mt-1">{{ $task->completed_at?->diffForHumans() }}</p>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">No completed tasks yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
