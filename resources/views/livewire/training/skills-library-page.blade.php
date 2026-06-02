<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <svg class="h-8 w-8 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Skills Library
            </h1>
            <p class="mt-2 text-text-secondary">Level up your real estate expertise with courses, playbooks, and quizzes.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="bg-surface-card border border-border-default px-4 py-2 rounded-xl text-sm font-bold text-text-primary">
                My Progress: <span class="{{ $overallPct >= 80 ? 'text-success-600' : ($overallPct >= 40 ? 'text-warning-600' : 'text-brand-primary') }}">{{ $overallPct }}%</span>
            </div>
            <a href="{{ route('training.roleplay') }}" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                🎭 Role-Play Simulator
            </a>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
        @foreach(['all' => 'All Modules', 'onboarding' => 'Onboarding', 'skills' => 'Sales Skills', 'compliance' => 'Compliance', 'market' => 'Market Knowledge', 'tools' => 'PropOS Tools'] as $key => $label)
        <button wire:click="$set('activeCategory', '{{ $key }}')"
            class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all
            {{ $activeCategory === $key ? 'bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 shadow-md' : 'bg-surface-card border border-border-default text-text-secondary hover:bg-surface-raised' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <!-- Modules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($modules as $module)
        <div class="bg-surface-card border border-border-default rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all group flex flex-col">

            <!-- Thumbnail Header -->
            <div class="h-28 {{ $module->thumbnail_color ?? 'bg-brand-primary/10' }} relative flex items-center justify-center">
                @if($module->user_progress > 0)
                <div class="absolute top-0 left-0 right-0 h-1 bg-black/10">
                    <div class="h-1 bg-brand-primary transition-all" style="width: {{ $module->user_progress }}%"></div>
                </div>
                @endif

                <div class="h-12 w-12 rounded-2xl bg-white/80 backdrop-blur flex items-center justify-center shadow text-xl">
                    @switch($module->type)
                        @case('video') 🎬 @break
                        @case('quiz') 📝 @break
                        @case('guide') 📖 @break
                        @case('roleplay') 🎭 @break
                        @default 📚
                    @endswitch
                </div>

                @if($module->is_mandatory)
                <div class="absolute top-2 right-2">
                    <span class="px-2 py-0.5 bg-danger-500 text-white text-[10px] font-bold rounded-full uppercase">Required</span>
                </div>
                @endif
            </div>

            <!-- Content -->
            <div class="p-5 flex-1 flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-surface-raised text-text-tertiary">
                        {{ str_replace('_', ' ', $module->category) }}
                    </span>
                    <span class="text-xs text-text-tertiary flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $module->duration }}
                    </span>
                </div>

                <h3 class="text-base font-black text-text-primary mb-1.5 group-hover:text-brand-primary transition-colors leading-tight">{{ $module->title }}</h3>
                <p class="text-sm text-text-secondary leading-relaxed flex-1">{{ $module->description }}</p>

                <!-- Actions -->
                <div class="mt-4 pt-4 border-t border-border-default/40 flex items-center justify-between">
                    <span class="text-xs font-bold
                        {{ $module->user_status === 'completed' ? 'text-success-600' : ($module->user_status === 'in_progress' ? 'text-brand-primary' : 'text-text-tertiary') }}">
                        @if($module->user_status === 'completed')
                            ✓ Completed@if($module->user_score) · Score: {{ $module->user_score }}/100 @endif
                        @elseif($module->user_status === 'in_progress')
                            In Progress · {{ $module->user_progress }}%
                        @else
                            Not Started
                        @endif
                    </span>

                    <div class="flex items-center gap-2">
                        @if($module->content_body)
                        <button wire:click="openModule({{ $module->id }})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors
                            {{ $openModuleId === $module->id ? 'bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10' : 'border border-brand-primary/40 text-brand-primary hover:bg-brand-primary/5' }}">
                            {{ $openModuleId === $module->id ? 'Close' : 'Read' }}
                        </button>
                        @endif
                        @if($module->user_status !== 'completed')
                        <button wire:click="markComplete({{ $module->id }})" class="px-3 py-1.5 text-xs font-bold border border-success-300 text-success-600 rounded-lg hover:bg-success-50 transition-colors">
                            Mark Done
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Inline Content Reader -->
            @if($openModuleId === $module->id && $module->content_body)
            <div class="px-5 pb-5 border-t border-border-default/40 pt-4">
                <div class="prose prose-sm max-w-none text-text-primary text-sm leading-relaxed whitespace-pre-line">
                    {{ $module->content_body }}
                </div>
                <button wire:click="markComplete({{ $module->id }})" class="mt-4 w-full py-2 bg-success-500 text-white rounded-xl text-sm font-bold hover:bg-success-600 transition-colors">
                    <span wire:loading.remove wire:target="markComplete({{ $module->id }})">✓ Mark as Complete</span>
                    <span wire:loading wire:target="markComplete({{ $module->id }})">Saving...</span>
                </button>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-3 text-center py-14">
            <div class="h-14 w-14 bg-brand-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">📚</div>
            <p class="text-sm font-medium text-text-primary">No modules in this category yet.</p>
        </div>
        @endforelse
    </div>
</div>


