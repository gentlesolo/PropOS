<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Task Board</h1>
            <p class="text-sm text-text-secondary mt-0.5">Manage your daily tasks and follow-ups</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Task
        </button>
    </div>

    {{-- Stats --}}
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

    {{-- Filters --}}
    <div class="glass-panel rounded-2xl border border-border-default/60 p-4 mb-6 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tasks…" class="flex-1 min-w-[180px] rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <select wire:model.live="priorityFilter" class="rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Priorities</option>
            <option value="urgent">Urgent</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
        <select wire:model.live="typeFilter" class="rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Types</option>
            @foreach(['call'=>'Call','email'=>'Email','meeting'=>'Meeting','document'=>'Document','follow_up'=>'Follow Up','viewing'=>'Viewing','other'=>'Other'] as $v=>$l)
            <option value="{{ $v }}">{{ $l }}</option>
            @endforeach
        </select>
        <select wire:model.live="assigneeFilter" class="rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Assignees</option>
            @foreach($agents as $a)
            <option value="{{ $a->id }}">{{ $a->first_name }} {{ $a->last_name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
            <input wire:model.live="showMyTasksOnly" type="checkbox" class="rounded border-border-default text-brand-primary focus:ring-brand-primary">
            My tasks only
        </label>
    </div>

    {{-- Overdue Alert --}}
    @if($overdue->isNotEmpty())
    <div class="bg-danger-50 border border-danger-200 rounded-2xl p-4 mb-6">
        <div class="text-sm font-semibold text-danger-700 mb-2">⚠ {{ $overdue->count() }} Overdue Task(s)</div>
        @foreach($overdue->take(4) as $t)
        <div class="flex items-center justify-between py-1.5 border-b border-danger-100 last:border-0">
            <div>
                <span class="text-sm text-danger-800">{{ $t->title }}</span>
                @if($t->assignedTo)<span class="text-xs text-danger-500 ml-2">— {{ $t->assignedTo->first_name }}</span>@endif
            </div>
            <div class="flex gap-2 items-center">
                <span class="text-xs text-danger-500">{{ $t->due_at?->diffForHumans() }}</span>
                <button wire:click="openEdit({{ $t->id }})" class="text-xs px-2 py-0.5 bg-warning-100 text-warning-700 rounded-md hover:bg-warning-200 transition-colors">Edit</button>
                <button wire:click="completeTask({{ $t->id }})" class="text-xs px-2 py-0.5 bg-success-100 text-success-700 rounded-md hover:bg-success-200 transition-colors">Done</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Create / Edit Form --}}
    @if($showForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editingId ? 'Edit Task' : 'Create Task' }}</h2>
        <form wire:submit.prevent="saveTask" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                <input wire:model="title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="What needs to be done?">
                @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Description</label>
                <textarea wire:model="description" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="Optional notes…"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Type</label>
                <select wire:model="type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    @foreach(['call'=>'Call','email'=>'Email','meeting'=>'Meeting','document'=>'Document','follow_up'=>'Follow Up','viewing'=>'Viewing','other'=>'Other'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Priority</label>
                <select wire:model="priority" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            @if($editingId)
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                <select wire:model="status" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Assign To</label>
                <select wire:model="assigned_to" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">Myself</option>
                    @foreach($agents as $a)
                    <option value="{{ $a->id }}">{{ $a->first_name }} {{ $a->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Due Date</label>
                <input wire:model="due_at" type="datetime-local" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Link Contact</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">None</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Link Deal</label>
                <select wire:model="deal_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                    <option value="">None</option>
                    @foreach($deals as $d)
                    <option value="{{ $d->id }}">{{ $d->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                    {{ $editingId ? 'Update Task' : 'Create Task' }}
                </button>
                <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                @if($editingId)
                <button type="button" wire:click="deleteTask({{ $editingId }})" wire:confirm="Delete this task?" class="ml-auto px-4 py-2 border border-danger-300 text-danger-600 rounded-xl text-sm hover:bg-danger-50 transition-colors">Delete</button>
                @endif
            </div>
        </form>
    </div>
    @endif

    {{-- Detail Slide-Over --}}
    @if($showDetail && $detailTask)
    <div class="fixed inset-0 z-40 flex justify-end" x-data>
        <div class="absolute inset-0 bg-black/40" wire:click="closeDetail"></div>
        <div class="relative z-50 w-full max-w-md bg-surface-base border-l border-border-default h-full overflow-y-auto p-6 flex flex-col gap-4">
            <div class="flex items-start justify-between">
                <h2 class="text-lg font-bold text-text-primary pr-4">{{ $detailTask->title }}</h2>
                <button wire:click="closeDetail" class="text-text-tertiary hover:text-text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @php
                $pc = ['urgent'=>'danger','high'=>'warning','medium'=>'brand','low'=>'secondary'][$detailTask->priority] ?? 'secondary';
                $typeLabels = ['call'=>'Call','email'=>'Email','meeting'=>'Meeting','document'=>'Document','follow_up'=>'Follow Up','viewing'=>'Viewing','other'=>'Other'];
            @endphp
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($detailTask->priority) }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-muted text-text-secondary border border-border-default">{{ $typeLabels[$detailTask->type] ?? $detailTask->type }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-muted text-text-secondary border border-border-default">{{ ucfirst(str_replace('_',' ',$detailTask->status)) }}</span>
            </div>
            @if($detailTask->description)
            <p class="text-sm text-text-secondary">{{ $detailTask->description }}</p>
            @endif
            <div class="space-y-2 text-sm">
                @if($detailTask->due_at)
                <div class="flex justify-between"><span class="text-text-tertiary">Due</span><span class="font-medium {{ $detailTask->is_overdue ? 'text-danger-600' : 'text-text-primary' }}">{{ $detailTask->due_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($detailTask->assignedTo)
                <div class="flex justify-between"><span class="text-text-tertiary">Assigned To</span><span class="font-medium text-text-primary">{{ $detailTask->assignedTo->first_name }} {{ $detailTask->assignedTo->last_name }}</span></div>
                @endif
                @if($detailTask->contact)
                <div class="flex justify-between"><span class="text-text-tertiary">Contact</span><span class="font-medium text-text-primary">{{ $detailTask->contact->full_name }}</span></div>
                @endif
                @if($detailTask->deal)
                <div class="flex justify-between"><span class="text-text-tertiary">Deal</span><span class="font-medium text-text-primary">{{ $detailTask->deal->title }}</span></div>
                @endif
                @if($detailTask->completed_at)
                <div class="flex justify-between"><span class="text-text-tertiary">Completed</span><span class="font-medium text-success-600">{{ $detailTask->completed_at->format('d M Y H:i') }}</span></div>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 pt-2 border-t border-border-default mt-auto">
                <button wire:click="openEdit({{ $detailTask->id }})" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">Edit</button>
                @if($detailTask->status === 'pending')
                <button wire:click="startTask({{ $detailTask->id }})" class="px-4 py-2 bg-warning-100 text-warning-700 border border-warning-300 rounded-xl text-sm font-medium hover:bg-warning-200 transition-colors">Start</button>
                <button wire:click="completeTask({{ $detailTask->id }})" class="px-4 py-2 bg-success-100 text-success-700 border border-success-300 rounded-xl text-sm font-medium hover:bg-success-200 transition-colors">Complete</button>
                @elseif($detailTask->status === 'in_progress')
                <button wire:click="completeTask({{ $detailTask->id }})" class="px-4 py-2 bg-success-100 text-success-700 border border-success-300 rounded-xl text-sm font-medium hover:bg-success-200 transition-colors">Complete</button>
                @elseif($detailTask->status === 'completed')
                <button wire:click="reopenTask({{ $detailTask->id }})" class="px-4 py-2 bg-surface-muted text-text-secondary border border-border-default rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Reopen</button>
                @endif
                <button wire:click="deleteTask({{ $detailTask->id }})" wire:confirm="Delete this task permanently?" class="ml-auto px-4 py-2 border border-danger-300 text-danger-600 rounded-xl text-sm hover:bg-danger-50 transition-colors">Delete</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Board Columns --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        {{-- Pending --}}
        <div>
            <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-warning-400 inline-block"></span> Pending ({{ $pending->count() }})
            </h3>
            <div class="space-y-3">
                @forelse($pending as $task)
                @php $pc = ['urgent'=>'danger','high'=>'warning','medium'=>'brand','low'=>'secondary'][$task->priority] ?? 'secondary'; @endphp
                <div class="glass-panel rounded-xl border border-border-default/60 p-4 cursor-pointer hover:border-brand-primary/40 transition-colors" wire:click="openDetail({{ $task->id }})">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="text-sm font-medium text-text-primary leading-snug">{{ $task->title }}</p>
                        <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($task->priority) }}</span>
                    </div>
                    @if($task->contact)<p class="text-xs text-text-tertiary mb-2">{{ $task->contact->first_name }} {{ $task->contact->last_name }}</p>@endif
                    <div class="flex items-center justify-between gap-1 flex-wrap">
                        <span class="text-xs text-text-tertiary {{ $task->is_overdue ? 'text-danger-500 font-medium' : '' }}">{{ $task->due_at ? $task->due_at->format('d M') : '—' }}</span>
                        <div class="flex gap-1" wire:click.stop>
                            <button wire:click="startTask({{ $task->id }})" class="text-xs px-2 py-0.5 bg-warning-50 text-warning-700 border border-warning-200 rounded hover:bg-warning-100 transition-colors">Start</button>
                            <button wire:click="completeTask({{ $task->id }})" class="text-xs px-2 py-0.5 bg-success-50 text-success-700 border border-success-200 rounded hover:bg-success-100 transition-colors">Done</button>
                            <button wire:click="openEdit({{ $task->id }})" class="text-xs px-2 py-0.5 bg-surface-muted text-text-secondary border border-border-default rounded hover:bg-surface-hover transition-colors">Edit</button>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">No pending tasks.</p>
                @endforelse
            </div>
        </div>

        {{-- In Progress --}}
        <div>
            <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-primary inline-block"></span> In Progress ({{ $inProgress->count() }})
            </h3>
            <div class="space-y-3">
                @forelse($inProgress as $task)
                @php $pc = ['urgent'=>'danger','high'=>'warning','medium'=>'brand','low'=>'secondary'][$task->priority] ?? 'secondary'; @endphp
                <div class="glass-panel rounded-xl border border-brand-primary/30 p-4 cursor-pointer hover:border-brand-primary/60 transition-colors" wire:click="openDetail({{ $task->id }})">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <p class="text-sm font-medium text-text-primary leading-snug">{{ $task->title }}</p>
                        <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 border border-{{ $pc }}-200">{{ ucfirst($task->priority) }}</span>
                    </div>
                    <p class="text-xs text-text-tertiary mb-2">{{ $task->assignedTo?->first_name ?? 'Unassigned' }} · {{ $task->due_at ? $task->due_at->format('d M') : 'No date' }}</p>
                    <div class="flex gap-1" wire:click.stop>
                        <button wire:click="completeTask({{ $task->id }})" class="text-xs px-2 py-0.5 bg-success-50 text-success-700 border border-success-200 rounded hover:bg-success-100 transition-colors">Complete</button>
                        <button wire:click="openEdit({{ $task->id }})" class="text-xs px-2 py-0.5 bg-surface-muted text-text-secondary border border-border-default rounded hover:bg-surface-hover transition-colors">Edit</button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">None in progress.</p>
                @endforelse
            </div>
        </div>

        {{-- Completed --}}
        <div>
            <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-success-500 inline-block"></span> Completed ({{ $completed->count() }})
            </h3>
            <div class="space-y-3">
                @forelse($completed as $task)
                <div class="glass-panel rounded-xl border border-border-default/60 p-4 opacity-70 cursor-pointer hover:opacity-90 transition-opacity" wire:click="openDetail({{ $task->id }})">
                    <p class="text-sm font-medium text-text-primary line-through leading-snug">{{ $task->title }}</p>
                    <div class="flex items-center justify-between mt-2" wire:click.stop>
                        <span class="text-xs text-text-tertiary">{{ $task->completed_at?->diffForHumans() }}</span>
                        <button wire:click="reopenTask({{ $task->id }})" class="text-xs px-2 py-0.5 bg-surface-muted text-text-secondary border border-border-default rounded hover:bg-surface-hover transition-colors">Reopen</button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">No completed tasks yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Cancelled --}}
        <div>
            <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-text-tertiary inline-block"></span> Cancelled ({{ $cancelled->count() }})
            </h3>
            <div class="space-y-3">
                @forelse($cancelled as $task)
                <div class="glass-panel rounded-xl border border-border-default/60 p-4 opacity-50 cursor-pointer hover:opacity-70 transition-opacity" wire:click="openDetail({{ $task->id }})">
                    <p class="text-sm font-medium text-text-primary line-through leading-snug">{{ $task->title }}</p>
                    <div class="flex items-center justify-between mt-2" wire:click.stop>
                        <span class="text-xs text-text-tertiary">{{ $task->updated_at->diffForHumans() }}</span>
                        <button wire:click="reopenTask({{ $task->id }})" class="text-xs px-2 py-0.5 bg-surface-muted text-text-secondary border border-border-default rounded hover:bg-surface-hover transition-colors">Restore</button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-tertiary text-center py-8">No cancelled tasks.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
