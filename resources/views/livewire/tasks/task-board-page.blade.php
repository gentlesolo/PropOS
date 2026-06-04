<div class="flex flex-col lg:flex-row h-full min-h-[calc(100vh-4rem)] bg-[#030712] font-sans relative overflow-hidden" 
     x-data="{ 
         openShortcuts: false,
         showDetail: @entangle('showDetail')
     }"
     @keydown.window="if ($event.key === '?' && !['INPUT', 'TEXTAREA'].includes($event.target.tagName)) { openShortcuts = !openShortcuts; }">

    <span class="sr-only">My Open Tasks</span>

    <style>
        .pop-checkbox {
            animation: checkPop 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes checkPop {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .overdue-pulse {
            border-left: 3px solid #F59E0B;
            animation: borderGlow 2s infinite alternate ease-in-out;
        }
        @keyframes borderGlow {
            0% { box-shadow: inset 3px 0 6px rgba(245, 158, 11, 0.1), 0 0 0 rgba(245, 158, 11, 0); }
            100% { box-shadow: inset 3px 0 12px rgba(245, 158, 11, 0.25), 0 0 8px rgba(245, 158, 11, 0.15); }
        }
        .task-row:hover .hover-actions {
            opacity: 1;
            transform: translateX(0);
        }
    </style>

    {{-- ── LEFT COLUMN: SMART LISTS & GROUPS NAV (220px) ── --}}
    <div class="w-full lg:w-[220px] lg:border-r lg:border-white/5 bg-[#090d16]/50 backdrop-blur-md flex flex-col flex-shrink-0 p-4 space-y-6">
        <div>
            <h2 class="text-[10px] font-bold uppercase tracking-wider text-[#52525B] mb-2.5">Workspace</h2>
            <ul class="space-y-1">
                @php
                    $navItems = [
                        ['id' => 'my_day', 'label' => 'My Day', 'icon' => 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m11.314 11.314l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z'],
                        ['id' => 'upcoming', 'label' => 'Upcoming', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['id' => 'all', 'label' => 'All Tasks', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['id' => 'pipeline', 'label' => 'By Pipeline Stage', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['id' => 'ai_generated', 'label' => 'AI-Generated', 'icon' => 'M9.813 15.904L9 21l-1.813-5.096L2.091 15 7.187 13.187 9 8l1.813 5.187L15.909 15l-6.096.904zM21 10l-1.25 2.75L17 14l2.75 1.25L21 18l1.25-2.75L25 14l-2.75-1.25L21 10z'],
                        ['id' => 'completed', 'label' => 'Completed', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                @endphp
                @foreach($navItems as $ni)
                    @php $active = $activeNav === $ni['id'] && !$quickFilter; @endphp
                    <li>
                        <button wire:click="setNav('{{ $ni['id'] }}')" 
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs transition-all duration-150
                                       {{ $active ? 'bg-[#10B981]/10 text-[#10B981] font-semibold' : 'text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/[0.02]' }}">
                            <div class="flex items-center gap-2">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ni['icon'] }}"/>
                                </svg>
                                <span>{{ $ni['label'] }}</span>
                            </div>
                            <span class="font-mono text-[9px] px-1.5 py-0.5 rounded bg-white/5 text-[#52525B]">{{ $navCounts[$ni['id']] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-[10px] font-bold uppercase tracking-wider text-[#52525B] mb-2.5">Quick Filters</h2>
            <ul class="space-y-1">
                @php
                    $filters = [
                        ['id' => 'due_today', 'label' => 'Due Today', 'dot' => 'bg-[#0EA5E9]'],
                        ['id' => 'overdue', 'label' => 'Overdue', 'dot' => 'bg-[#F43F5E]'],
                        ['id' => 'unassigned', 'label' => 'Unassigned', 'dot' => 'bg-[#52525B]'],
                        ['id' => 'high_priority', 'label' => 'High Priority', 'dot' => 'bg-[#F59E0B]'],
                    ];
                @endphp
                @foreach($filters as $f)
                    @php $active = $quickFilter === $f['id']; @endphp
                    <li>
                        <button wire:click="setQuickFilter('{{ $f['id'] }}')"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs transition-all duration-150
                                       {{ $active ? 'bg-white/5 text-[#FAFAFA] font-semibold border-l-2 border-[#10B981]' : 'text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/[0.02]' }}">
                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full {{ $f['dot'] }}"></span>
                                <span>{{ $f['label'] }}</span>
                            </div>
                            <span class="font-mono text-[9px] px-1.5 py-0.5 rounded bg-white/5 text-[#52525B]">{{ $filterCounts[$f['id']] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="pt-6 border-t border-white/5">
            <button @click="openShortcuts = true" class="w-full text-left text-[10px] text-[#52525B] hover:text-[#A1A1AA] flex items-center justify-between">
                <span>Keyboard Shortcuts</span>
                <kbd class="px-1.5 py-0.5 bg-[#111827] border border-white/10 rounded font-mono">?</kbd>
            </button>
        </div>
    </div>

    {{-- ── MAIN COLUMN: TASK LIST ── --}}
    <div class="flex-1 overflow-y-auto bg-[#030712] p-6 flex flex-col">
        
        {{-- Top bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[#FAFAFA]">Operations Panel</h1>
                <p class="text-xs text-[#A1A1AA] mt-0.5">Linear-speed execution console & pipeline tasks</p>
            </div>
            
            <div class="flex items-center gap-3">
                {{-- Search bar --}}
                <div class="relative">
                    <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search tasks..."
                           class="w-48 h-8 bg-[#111827] border border-white/10 text-xs text-[#FAFAFA] placeholder-[#52525B] pl-8 pr-2.5 rounded-md focus:outline-none focus:border-[#10B981] transition-all">
                    <svg class="absolute left-2.5 top-2.5 h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                {{-- AI sweep generation button --}}
                <button wire:click="triggerAiSweep" 
                        class="h-8 px-3 bg-[#10B981]/15 hover:bg-[#10B981]/25 border border-[#10B981]/30 text-[#10B981] rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all shadow-[0_2px_10px_rgba(16,185,129,0.05)]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-1.813-5.096L2.091 15 7.187 13.187 9 8l1.813 5.187L15.909 15l-6.096.904z"/>
                    </svg>
                    ✦ AI Sweep Pipeline
                </button>
            </div>
        </div>

        {{-- ── STICKY QUICK ADD ROW ── --}}
        <div x-data="{ openInput: false }" class="mb-4 bg-[#090d16]/30 border border-white/5 rounded-lg overflow-hidden transition-all duration-150">
            <button x-show="!openInput" @click="openInput = true; $nextTick(() => $refs.quickAdd.focus())"
                    class="w-full h-10 px-4 flex items-center text-xs text-[#52525B] hover:text-[#A1A1AA] hover:bg-white/[0.01] transition-all justify-start gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>Add task... Use <code class="text-[#F59E0B] font-mono">/tomorrow</code> <code class="text-[#F59E0B] font-mono">/high</code> inline to set parameters</span>
            </button>
            
            <form x-show="openInput" @submit.prevent="openInput = false" wire:submit.prevent="submitQuickAdd" class="flex items-center gap-2 p-2">
                <input x-ref="quickAdd" wire:model="quickAddText" type="text" placeholder="e.g. /tomorrow /high Call Adaeze on mandate terms..."
                       @keydown.escape="openInput = false"
                       class="flex-1 h-8 bg-[#111827] border border-white/10 rounded text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                <button type="submit" class="h-8 px-3 bg-[#10B981] hover:bg-[#10B981]/90 text-black text-xs font-semibold rounded">Save</button>
                <button type="button" @click="openInput = false" class="h-8 px-2.5 border border-white/10 text-[#A1A1AA] text-xs rounded hover:bg-white/5">Cancel</button>
            </form>
        </div>

        {{-- ── TASK ROW LIST ── --}}
        <div class="space-y-6">
            @php
                $grouped = [
                    'Today' => [],
                    'Tomorrow' => [],
                    'This Week' => [],
                    'Later' => []
                ];
                $todayVal = today();
                $tomorrowVal = today()->addDay();
                $endOfWeekVal = today()->endOfWeek();

                foreach($tasks as $t) {
                    if (!$t->due_at) {
                        $grouped['Later'][] = $t;
                    } else {
                        if ($t->due_at->isToday() || $t->due_at->isPast()) {
                            $grouped['Today'][] = $t;
                        } elseif ($t->due_at->isTomorrow()) {
                            $grouped['Tomorrow'][] = $t;
                        } elseif ($t->due_at->gt($tomorrowVal) && $t->due_at->lte($endOfWeekVal)) {
                            $grouped['This Week'][] = $t;
                        } else {
                            $grouped['Later'][] = $t;
                        }
                    }
                }
            @endphp

            @php $hasTasks = false; @endphp
            @foreach($grouped as $groupName => $items)
                @if(count($items) > 0)
                    @php $hasTasks = true; @endphp
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-[#52525B] px-1 py-1 border-b border-white/5">
                            <span>{{ $groupName }}</span>
                            <span class="font-mono">{{ count($items) }} tasks</span>
                        </div>

                        <div class="divide-y divide-white/5 border border-white/5 rounded-lg bg-[#090d16]/20 overflow-hidden">
                            @foreach($items as $task)
                                @php
                                    $isOverdue = $task->is_overdue;
                                    $isCompleted = $task->status === 'completed';
                                    $pColor = match ($task->priority) {
                                        'urgent' => 'bg-[#F43F5E]',
                                        'high' => 'bg-[#F59E0B]',
                                        'medium' => 'bg-[#0EA5E9]',
                                        default => 'bg-[#52525B]',
                                    };
                                    $isAiGenerated = str_starts_with($task->title, '✦');
                                @endphp
                                <div class="task-row flex items-center justify-between h-12 px-3 hover:bg-white/[0.01] transition-all duration-150 relative gap-3
                                            {{ $isOverdue ? 'overdue-pulse' : '' }}
                                            {{ $isCompleted ? 'opacity-40' : '' }}"
                                     @click="showDetail = true; @this.openDetail({{ $task->id }})">
                                     
                                    <div class="flex items-center gap-3 min-w-0">
                                        {{-- Emerald Checkbox --}}
                                        <button wire:click.stop="toggleTaskStatus({{ $task->id }})"
                                                class="h-5 w-5 rounded-full border border-white/20 hover:border-[#10B981] flex items-center justify-center transition-all duration-200 shrink-0
                                                       {{ $isCompleted ? 'bg-[#10B981] border-[#10B981]' : 'bg-transparent' }}">
                                            @if($isCompleted)
                                                <svg class="h-3 w-3 text-black font-extrabold pop-checkbox" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                            @endif
                                        </button>

                                        {{-- Title --}}
                                        <span class="text-xs font-medium truncate font-sans
                                                     {{ $isCompleted ? 'line-through text-[#52525B]' : ($isOverdue ? 'text-[#F43F5E]' : 'text-[#FAFAFA]') }}">
                                            {{ $task->title }}
                                        </span>
                                    </div>

                                    {{-- Right side metadata --}}
                                    <div class="flex items-center gap-2.5 shrink-0">
                                        
                                        {{-- Action icons (revealed on hover) --}}
                                        <div class="hover-actions opacity-0 translate-x-2 transition-all duration-150 flex items-center gap-2 mr-2" @click.stop>
                                            <button wire:click="openEdit({{ $task->id }})" class="p-1 hover:bg-white/5 rounded text-[#A1A1AA] hover:text-[#FAFAFA]" title="Edit">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            
                                            {{-- Snooze Trigger dropdown --}}
                                            <div x-data="{ openSnooze: false }" class="relative">
                                                <button @click="openSnooze = !openSnooze" class="p-1 hover:bg-white/5 rounded text-[#A1A1AA] hover:text-[#F59E0B]" title="Snooze">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </button>
                                                <div x-show="openSnooze" @click.away="openSnooze = false"
                                                     class="absolute right-0 bottom-full mb-1 w-32 bg-[#090d16] border border-white/10 rounded-md shadow-lg z-50 text-[10px] font-sans flex flex-col p-1">
                                                    <button wire:click="snoozeTask({{ $task->id }}, '1_hour')" @click="openSnooze = false" class="px-2 py-1.5 text-left text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">In 1 hour</button>
                                                    <button wire:click="snoozeTask({{ $task->id }}, 'tomorrow')" @click="openSnooze = false" class="px-2 py-1.5 text-left text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">Tomorrow morning</button>
                                                    <button wire:click="snoozeTask({{ $task->id }}, 'next_week')" @click="openSnooze = false" class="px-2 py-1.5 text-left text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">Next Monday</button>
                                                </div>
                                            </div>

                                            <button wire:click="deleteTask({{ $task->id }})" onclick="return confirm('Delete task?')" class="p-1 hover:bg-[#F43F5E]/10 rounded text-[#A1A1AA] hover:text-[#F43F5E]" title="Delete">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>

                                        {{-- Related Listing Property mini-chip --}}
                                        @if($task->deal && $task->deal->listing && $task->deal->listing->property)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-white/5 text-[#A1A1AA] max-w-[80px] truncate" title="{{ $task->deal->listing->property->address_line_1 }}">
                                                {{ $task->deal->listing->property->city ?: 'Property' }}
                                            </span>
                                        @endif

                                        {{-- Related Contact Avatar --}}
                                        @if($task->contact)
                                            @php
                                                $initials = strtoupper(substr($task->contact->first_name, 0, 1) . substr($task->contact->last_name, 0, 1));
                                            @endphp
                                            <div class="h-5 w-5 rounded-full bg-[#0EA5E9]/10 border border-[#0EA5E9]/25 flex items-center justify-center text-[9px] font-bold text-[#0EA5E9]" title="Contact: {{ $task->contact->full_name }}">
                                                {{ $initials }}
                                            </div>
                                        @endif

                                        {{-- Assignee Avatar --}}
                                        @if($task->assignedTo)
                                            @php
                                                $aInitials = strtoupper(substr($task->assignedTo->first_name, 0, 1) . substr($task->assignedTo->last_name, 0, 1));
                                            @endphp
                                            <div class="h-5 w-5 rounded-full bg-[#10B981]/10 border border-[#10B981]/25 flex items-center justify-center text-[9px] font-bold text-[#10B981]" title="Assignee: {{ $task->assignedTo->first_name }}">
                                                {{ $aInitials }}
                                            </div>
                                        @else
                                            <div class="h-5 w-5 rounded-full border border-dashed border-white/10 flex items-center justify-center text-[9px] text-[#52525B]" title="Unassigned">
                                                —
                                            </div>
                                        @endif

                                        {{-- Due Date Chip --}}
                                        @if($task->due_at)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded font-mono
                                                         {{ $isOverdue ? 'bg-[#F43F5E]/10 text-[#F43F5E] border border-[#F43F5E]/20' : 'bg-white/5 text-[#A1A1AA]' }}">
                                                {{ $task->due_at->format('d M') }}
                                            </span>
                                        @endif

                                        {{-- Priority Dot --}}
                                        <span class="h-1.5 w-1.5 rounded-full {{ $pColor }}"></span>
                                        <span class="sr-only">{{ ucfirst($task->priority) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if(!$hasTasks)
                {{-- Empty State --}}
                <div class="flex-1 flex flex-col items-center justify-center py-16 text-center space-y-4">
                    <div class="h-12 w-12 bg-[#111827] border border-white/5 rounded-md flex items-center justify-center text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-[#FAFAFA]">You're all caught up</h3>
                        <p class="text-xs text-[#A1A1AA] mt-1">Want me to check your pipeline for follow-ups you might have missed?</p>
                    </div>
                    <button wire:click="triggerAiSweep" 
                            class="px-4 py-2 bg-[#10B981] hover:bg-[#10B981]/90 text-black text-xs font-bold rounded shadow-md transition-all flex items-center gap-1.5">
                        ✦ Sweep Active Pipeline
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ── RIGHT COLUMN: TASK DETAIL PANEL (360px) ── --}}
    <div x-show="showDetail" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="w-full lg:w-[360px] border-l border-white/5 bg-[#090d16] flex flex-col flex-shrink-0 z-20 h-full p-5 space-y-5 overflow-y-auto">
        
        @if($detailTask)
            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                <span class="text-[9px] font-mono tracking-wider text-[#52525B] uppercase">Task Console Detail</span>
                <button @click="showDetail = false" class="text-[#A1A1AA] hover:text-[#FAFAFA] text-lg leading-none">&times;</button>
            </div>

            {{-- Title input (large editable) --}}
            <input type="text" value="{{ $detailTask->title }}" 
                   wire:blur="updateTitle($event.target.value)"
                   class="w-full bg-transparent border-b border-transparent hover:border-white/10 focus:border-[#10B981] text-base font-semibold text-[#FAFAFA] py-1 px-1 focus:outline-none transition-all">

            {{-- Metadata Row Picker Chips --}}
            <div class="space-y-2.5 text-xs">
                
                {{-- Assignee --}}
                <div class="flex items-center justify-between py-1 border-b border-white/[0.02]">
                    <span class="text-[#A1A1AA] text-[11px]">Assign To</span>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="px-2.5 py-1 rounded bg-white/5 border border-white/10 text-xs text-[#FAFAFA] flex items-center gap-1.5 hover:bg-white/10">
                            {{ $detailTask->assignedTo ? $detailTask->assignedTo->first_name : 'Unassigned' }}
                            <svg class="h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-44 bg-[#111827] border border-white/10 rounded-md shadow-lg z-50 p-1 space-y-0.5">
                            <button wire:click="updateAssignee(null)" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">Unassigned</button>
                            @foreach($agents as $agent)
                                <button wire:click="updateAssignee({{ $agent->id }})" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">{{ $agent->first_name }} {{ $agent->last_name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Due Date --}}
                <div class="flex items-center justify-between py-1 border-b border-white/[0.02]">
                    <span class="text-[#A1A1AA] text-[11px]">Due Date</span>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="px-2.5 py-1 rounded bg-white/5 border border-white/10 text-xs text-[#FAFAFA] flex items-center gap-1.5 hover:bg-white/10">
                            {{ $detailTask->due_at ? $detailTask->due_at->format('d M Y H:i') : 'No date' }}
                            <svg class="h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 p-2 bg-[#111827] border border-white/10 rounded-md shadow-lg z-50">
                            <input type="datetime-local" 
                                   value="{{ $detailTask->due_at ? $detailTask->due_at->format('Y-m-d\TH:i') : '' }}"
                                   wire:change="updateDueAt($event.target.value)"
                                   class="bg-[#030712] border border-white/10 rounded p-1.5 text-xs text-[#FAFAFA] focus:outline-none focus:border-[#10B981]">
                        </div>
                    </div>
                </div>

                {{-- Priority --}}
                <div class="flex items-center justify-between py-1 border-b border-white/[0.02]">
                    <span class="text-[#A1A1AA] text-[11px]">Priority</span>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="px-2.5 py-1 rounded bg-white/5 border border-white/10 text-xs text-[#FAFAFA] flex items-center gap-1.5 hover:bg-white/10">
                            <span class="h-1.5 w-1.5 rounded-full {{ match($detailTask->priority) {'urgent'=>'bg-[#F43F5E]','high'=>'bg-[#F59E0B]','medium'=>'bg-[#0EA5E9]',default=>'bg-[#52525B]'} }}"></span>
                            {{ ucfirst($detailTask->priority) }}
                            <svg class="h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-32 bg-[#111827] border border-white/10 rounded-md shadow-lg z-50 p-1 space-y-0.5">
                            @foreach(['low','medium','high','urgent'] as $p)
                                <button wire:click="updatePriority('{{ $p }}')" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">{{ ucfirst($p) }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Contact --}}
                <div class="flex items-center justify-between py-1 border-b border-white/[0.02]">
                    <span class="text-[#A1A1AA] text-[11px]">Related Contact</span>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="px-2.5 py-1 rounded bg-white/5 border border-white/10 text-xs text-[#FAFAFA] flex items-center gap-1.5 hover:bg-white/10 max-w-[150px] truncate">
                            {{ $detailTask->contact ? $detailTask->contact->first_name . ' ' . $detailTask->contact->last_name : 'None' }}
                            <svg class="h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-48 bg-[#111827] border border-white/10 rounded-md shadow-lg z-50 p-1 max-h-48 overflow-y-auto space-y-0.5">
                            <button wire:click="updateContact(null)" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">None</button>
                            @foreach($contacts as $contact)
                                <button wire:click="updateContact({{ $contact->id }})" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">{{ $contact->first_name }} {{ $contact->last_name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Listing --}}
                <div class="flex items-center justify-between py-1 border-b border-white/[0.02]">
                    <span class="text-[#A1A1AA] text-[11px]">Related Listing</span>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="px-2.5 py-1 rounded bg-white/5 border border-white/10 text-xs text-[#FAFAFA] flex items-center gap-1.5 hover:bg-white/10 max-w-[150px] truncate">
                            {{ $detailTask->deal && $detailTask->deal->listing && $detailTask->deal->listing->property ? $detailTask->deal->listing->property->city : 'None' }}
                            <svg class="h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-52 bg-[#111827] border border-white/10 rounded-md shadow-lg z-50 p-1 max-h-48 overflow-y-auto space-y-0.5">
                            <button wire:click="updateListing(null)" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">None</button>
                            @foreach($deals as $deal)
                                @if($deal->listing_id)
                                    <button wire:click="updateListing({{ $deal->listing_id }})" @click="open = false" class="w-full text-left px-2 py-1.5 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 rounded">{{ $deal->title }}</button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>

            {{-- Rich Description Area --}}
            <div x-data="{ hasFocus: false }" class="space-y-1.5">
                <span class="text-[9px] font-bold uppercase tracking-wider text-[#52525B]">Description</span>
                <div class="border border-white/5 focus-within:border-[#10B981] bg-[#111827]/40 rounded-lg overflow-hidden transition-all">
                    <textarea wire:blur="updateDescription($event.target.value)"
                              @focus="hasFocus = true"
                              @blur="setTimeout(() => hasFocus = false, 200)"
                              rows="3" placeholder="Add detailed descriptions or task scope..."
                              class="w-full bg-transparent p-3 text-xs text-[#FAFAFA] placeholder-[#52525B] focus:outline-none resize-none">{{ $detailTask->description }}</textarea>
                    
                    {{-- Subtle formatting toolbar --}}
                    <div x-show="hasFocus" class="h-7 px-2 border-t border-white/5 bg-[#0f172a] flex items-center justify-between text-[10px] text-[#52525B]">
                        <div class="flex items-center gap-2">
                            <button class="hover:text-[#FAFAFA]" title="Bold">B</button>
                            <button class="hover:text-[#FAFAFA] italic" title="Italic">I</button>
                            <button class="hover:text-[#FAFAFA] underline" title="Underline">U</button>
                        </div>
                        <span class="text-[9px] text-[#A1A1AA]">Press outside to save</span>
                    </div>
                </div>
            </div>

            {{-- AI Context Section --}}
            @php
                $contactName = $detailTask->contact ? $detailTask->contact->first_name : 'the buyer';
                $formattedTime = $detailTask->created_at->format('M dS');
            @endphp
            <div class="p-3 bg-[#111827] border-l-2 border-[#10B981] rounded-r-md text-[11px] leading-relaxed text-[#A1A1AA] italic">
                <div class="text-[9px] font-black uppercase tracking-wider text-[#10B981] not-italic mb-0.5">✦ AI Context Agent</div>
                This task was auto-generated from your transaction logs and pipeline updates regarding {{ $contactName }} around {{ $formattedTime }}. High conversion probability flagged based on interest indices.
            </div>

            {{-- Subtasks checklist --}}
            <div class="space-y-2.5">
                <span class="text-[9px] font-bold uppercase tracking-wider text-[#52525B]">Subtasks Checklist</span>
                
                {{-- Subtask rows --}}
                <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                    @foreach($detailTask->subtasks ?? [] as $idx => $st)
                        <div class="flex items-center justify-between text-xs py-1 px-2 bg-[#111827]/40 border border-white/[0.02] rounded">
                            <label class="flex items-center gap-2 text-[#FAFAFA] cursor-pointer">
                                <input type="checkbox" 
                                       {{ $st['completed'] ? 'checked' : '' }}
                                       wire:click="toggleSubtask({{ $idx }})"
                                       class="rounded border-white/10 text-[#10B981] focus:ring-[#10B981] bg-[#111827]">
                                <span class="{{ $st['completed'] ? 'line-through text-[#52525B]' : '' }}">{{ $st['title'] }}</span>
                            </label>
                            <button wire:click="deleteSubtask({{ $idx }})" class="text-[#52525B] hover:text-[#F43F5E]">&times;</button>
                        </div>
                    @endforeach
                </div>

                {{-- Add Subtask Form --}}
                <form wire:submit.prevent="addSubtask" class="flex gap-1.5">
                    <input wire:model="newSubtaskTitle" type="text" placeholder="Add subtask..."
                           class="flex-1 h-7 bg-[#111827] border border-white/10 rounded text-[11px] text-[#FAFAFA] px-2.5 focus:outline-none focus:border-[#10B981]">
                    <button type="submit" class="h-7 px-2.5 bg-white/5 border border-white/10 rounded text-[11px] text-[#FAFAFA] hover:bg-white/10">Add</button>
                </form>
            </div>

            {{-- Activity logs / Comments --}}
            <div class="space-y-2.5 flex-1 flex flex-col min-h-0 pt-3 border-t border-white/5">
                <span class="text-[9px] font-bold uppercase tracking-wider text-[#52525B]">Activity Log & Comments</span>
                
                {{-- History thread --}}
                <div class="flex-1 overflow-y-auto space-y-2.5 max-h-56 pr-1 text-[11px]">
                    @foreach($detailTask->activity_log ?? [] as $log)
                        <div class="p-2 rounded bg-white/[0.01] border border-white/5 space-y-1">
                            <div class="flex items-center justify-between text-[9px] text-[#52525B]">
                                <span class="font-bold text-[#A1A1AA]">{{ $log['user'] }}</span>
                                <span>{{ \Carbon\Carbon::parse($log['time'])->diffForHumans() }}</span>
                            </div>
                            <p class="{{ $log['type'] === 'comment' ? 'text-[#FAFAFA]' : 'text-[#A1A1AA] italic' }}">
                                {{ $log['message'] }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Add Comment input --}}
                <form wire:submit.prevent="addComment" class="flex gap-1.5 pt-2">
                    <input wire:model="newCommentText" type="text" placeholder="Ask AI or comment..."
                           class="flex-1 h-8 bg-[#111827] border border-white/10 rounded text-[11px] text-[#FAFAFA] px-2.5 focus:outline-none focus:border-[#10B981]">
                    <button type="submit" class="h-8 px-3 bg-[#10B981] hover:bg-[#10B981]/90 text-black text-xs font-semibold rounded">Send</button>
                </form>
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center">
                <p class="text-xs text-[#52525B]">Select a task to view full operations logs and subtask checklist.</p>
            </div>
        @endif

    </div>

    {{-- ── AI SUGGESTIONS CHECKLIST MODAL ── --}}
    @if($showAiSweepModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showAiSweepModal', false)"></div>
            
            <div class="relative w-full max-w-lg bg-[#090d16] border border-[#10B981]/20 rounded-xl shadow-2xl p-6 overflow-hidden flex flex-col max-h-[85vh] z-50">
                <div class="flex items-center justify-between pb-4 border-b border-white/5">
                    <div>
                        <h2 class="text-base font-semibold text-[#FAFAFA] flex items-center gap-1.5">
                            <svg class="h-4.5 w-4.5 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-1.813-5.096L2.091 15 7.187 13.187 9 8l1.813 5.187L15.909 15l-6.096.904z"/></svg>
                            ✦ Neural Pipeline AI Sweep
                        </h2>
                        <p class="text-[11px] text-[#A1A1AA] mt-0.5">Surfacing 5 recommended high-impact follow-ups from active deals</p>
                    </div>
                    <button wire:click="$set('showAiSweepModal', false)" class="text-[#52525B] hover:text-[#FAFAFA] text-lg leading-none">&times;</button>
                </div>

                <div class="flex-1 overflow-y-auto my-4 space-y-3 pr-1">
                    @foreach($suggestedTasks as $st)
                        <div class="p-3 bg-[#111827]/40 border border-white/5 rounded-lg flex items-start gap-3 hover:border-[#10B981]/30 transition-all">
                            <input type="checkbox" value="{{ $st['temp_id'] }}" wire:model="selectedSuggestions"
                                   class="mt-0.5 rounded border-white/10 text-[#10B981] focus:ring-[#10B981] bg-[#111827] h-4 w-4">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-[#FAFAFA] leading-tight block">{{ $st['title'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-[9px] font-bold text-[#A1A1AA] uppercase tracking-wider">{{ $st['priority'] }}</span>
                                    <span class="px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-[9px] font-bold text-[#A1A1AA] uppercase tracking-wider">{{ $st['type'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-3 pt-4 border-t border-white/5">
                    <button wire:click="addSuggestedTasks" class="flex-1 h-9 bg-[#10B981] hover:bg-[#10B981]/90 text-black text-xs font-bold rounded shadow-md transition-all">
                        Create Checked Suggestions ({{ count($selectedSuggestions) }})
                    </button>
                    <button wire:click="$set('showAiSweepModal', false)" class="h-9 px-4 border border-white/10 text-[#A1A1AA] text-xs rounded hover:bg-white/5">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── KEYBOARD SHORTCUTS CHEAT SHEET OVERLAY ── --}}
    <div x-show="openShortcuts" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openShortcuts = false"></div>
        
        <div class="relative w-full max-w-sm bg-[#090d16] border border-white/10 rounded-xl shadow-2xl p-6 z-50">
            <div class="flex items-center justify-between pb-3 border-b border-white/5">
                <h3 class="text-sm font-semibold text-[#FAFAFA] flex items-center gap-1.5">
                    <svg class="h-4.5 w-4.5 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    Shortcut Command Center
                </h3>
                <button @click="openShortcuts = false" class="text-[#52525B] hover:text-[#FAFAFA] text-lg leading-none">&times;</button>
            </div>

            <div class="my-4 space-y-3.5 text-xs text-[#A1A1AA]">
                <div class="flex justify-between items-center">
                    <span>Show / hide shortcuts cheat sheet</span>
                    <kbd class="px-1.5 py-0.5 bg-[#111827] border border-white/10 rounded font-mono text-[#FAFAFA]">?</kbd>
                </div>
                <div class="flex justify-between items-center">
                    <span>Close any open detail panel / modal</span>
                    <kbd class="px-1.5 py-0.5 bg-[#111827] border border-white/10 rounded font-mono text-[#FAFAFA]">ESC</kbd>
                </div>
                <div class="flex justify-between items-center">
                    <span>Trigger AI Pipeline Sweep</span>
                    <kbd class="px-1.5 py-0.5 bg-[#111827] border border-white/10 rounded font-mono text-[#FAFAFA]">✦</kbd>
                </div>
                <div class="flex justify-between items-center">
                    <span>Set priority in inline Quick Add</span>
                    <span class="font-mono text-[#F59E0B]">/urgent /high /low</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Set due date in inline Quick Add</span>
                    <span class="font-mono text-[#F59E0B]">/today /tomorrow /monday</span>
                </div>
            </div>

            <button @click="openShortcuts = false" class="w-full h-8 bg-white/5 border border-white/10 text-xs font-semibold text-[#FAFAFA] rounded hover:bg-white/10">
                Dismiss
            </button>
        </div>
    </div>

</div>
