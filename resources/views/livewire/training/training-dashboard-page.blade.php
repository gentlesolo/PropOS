<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Training Hub</h1>
            <p class="mt-2 text-text-secondary">Track your team's learning progress, compliance status, and skill development.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('training.objections') }}" class="px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">🛡️ Objection Handler</a>
            <a href="{{ route('training.roleplay') }}" class="px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">🎭 Role-Play</a>
            <a href="{{ route('training.skills') }}" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">📚 Skills Library</a>
        </div>
    </div>

    <!-- My Progress Overview -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60 text-center">
            <p class="text-3xl font-black text-brand-primary">{{ $completed }}/{{ $total }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">Modules Completed</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60 text-center">
            <p class="text-3xl font-black {{ $total > 0 ? ($completed / $total >= 0.8 ? 'text-success-600' : 'text-warning-600') : 'text-text-primary' }}">
                {{ $total > 0 ? round(($completed / $total) * 100) : 0 }}%
            </p>
            <p class="text-xs text-text-secondary mt-1 font-medium">Overall Progress</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-{{ $mandatoryCompleted >= $mandatory->count() ? 'success' : 'danger' }}-200 text-center">
            <p class="text-3xl font-black {{ $mandatoryCompleted >= $mandatory->count() ? 'text-success-600' : 'text-danger-600' }}">
                {{ $mandatoryCompleted }}/{{ $mandatory->count() }}
            </p>
            <p class="text-xs text-text-secondary mt-1 font-medium">Mandatory Modules</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60 text-center">
            @if($nextModule)
            <p class="text-sm font-bold text-text-primary leading-tight">{{ Str::limit($nextModule->title, 30) }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">Next Recommended</p>
            @else
            <p class="text-3xl">🏆</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">All modules done!</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Team Leaderboard -->
        <div class="xl:col-span-2">
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-border-default/60 bg-surface-sunken/30">
                    <h3 class="text-sm font-bold text-text-primary">Team Leaderboard</h3>
                </div>
                <div class="divide-y divide-border-default/40">
                    @forelse($leaderboard as $i => $entry)
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-black shrink-0
                            @if($i === 0) bg-warning-400 text-warning-900
                            @elseif($i === 1) bg-slate-300 text-slate-700
                            @elseif($i === 2) bg-orange-300 text-orange-800
                            @else bg-surface-raised text-text-tertiary @endif">
                            {{ $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : $i + 1)) }}
                        </div>
                        <div class="h-9 w-9 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center text-sm font-bold shrink-0">
                            {{ strtoupper(substr($entry['user']->first_name, 0, 1) . substr($entry['user']->last_name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-bold text-text-primary truncate">
                                    {{ $entry['user']->first_name }} {{ $entry['user']->last_name }}
                                    @if($entry['user']->id === auth()->id()) <span class="text-xs text-brand-primary ml-1">(You)</span> @endif
                                </p>
                                <span class="text-xs font-bold text-text-secondary ml-2 shrink-0">{{ $entry['pct'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-brand-primary transition-all" style="width: {{ $entry['pct'] }}%"></div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-black text-text-primary">{{ $entry['completed'] }}</p>
                            <p class="text-[10px] text-text-secondary">modules</p>
                        </div>
                        @if($entry['avg_score'] > 0)
                        <div class="text-right shrink-0">
                            <p class="text-sm font-black {{ $entry['avg_score'] >= 80 ? 'text-success-600' : ($entry['avg_score'] >= 60 ? 'text-warning-600' : 'text-danger-600') }}">{{ $entry['avg_score'] }}</p>
                            <p class="text-[10px] text-text-secondary">avg score</p>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-sm text-text-secondary">No team members with training records yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Recent Completions + Next Module -->
        <div class="xl:col-span-1 space-y-5">
            @if($nextModule)
            <div class="glass-panel rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-5">
                <p class="text-xs font-bold text-brand-primary uppercase tracking-wider mb-2">Continue Learning</p>
                <h3 class="text-base font-bold text-text-primary mb-1">{{ $nextModule->title }}</h3>
                <p class="text-xs text-text-secondary mb-3">{{ $nextModule->description }}</p>
                <a href="{{ route('training.skills') }}" class="block text-center py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                    Start Module →
                </a>
            </div>
            @endif

            @if($recentCompletions->isNotEmpty())
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-bold text-text-primary mb-3">Recently Completed</h3>
                <div class="space-y-3">
                    @foreach($recentCompletions as $prog)
                    <div class="flex items-start gap-3">
                        <div class="h-7 w-7 rounded-full bg-success-100 text-success-600 flex items-center justify-center text-sm shrink-0">✓</div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-text-primary truncate">{{ $prog->module?->title }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ $prog->completed_at?->diffForHumans() }}
                                @if($prog->score) · Score: {{ $prog->score }}/100 @endif
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Compliance Status -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-bold text-text-primary mb-3">Compliance Status</h3>
                @forelse($mandatory as $mod)
                @php
                    $done = $recentCompletions->where('module_id', $mod->id)->isNotEmpty()
                        || \App\Infrastructure\Persistence\Models\TrainingProgress::where('user_id', auth()->id())->where('module_id', $mod->id)->where('status', 'completed')->exists();
                @endphp
                <div class="flex items-center justify-between py-2 border-b border-border-default/40 last:border-0">
                    <p class="text-xs font-medium text-text-primary">{{ Str::limit($mod->title, 35) }}</p>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $done ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-700' }}">
                        {{ $done ? 'Done' : 'Required' }}
                    </span>
                </div>
                @empty
                <p class="text-xs text-text-secondary">No mandatory modules defined.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
