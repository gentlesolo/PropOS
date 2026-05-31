<div>
    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         class="fixed inset-0 bg-surface-overlay/80 backdrop-blur-sm z-40 md:hidden"
         @click="sidebarOpen = false"
         style="display:none">
    </div>

    {{-- Sidebar container --}}
    <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
         class="fixed inset-y-0 left-0 z-50 w-64 bg-surface-sunken/70 backdrop-blur-md border-r border-border-default/45 h-full flex flex-col flex-shrink-0 transition-transform duration-300 ease-in-out md:relative md:translate-x-0 md:flex">

        {{-- Brand / Logo --}}
        <div class="flex items-center justify-between h-16 px-6 border-b border-border-default/30 shrink-0 bg-surface-sunken/10">
            <div class="flex items-center gap-3 min-w-0">
                @if($agency && $agency->logo_path)
                    <img class="h-7 w-auto object-contain" src="{{ asset('storage/'.$agency->logo_path) }}" alt="{{ $agency->name }}">
                @else
                    <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-brand-primary to-info-500 flex items-center justify-center font-black text-white text-sm shadow-md shadow-brand-primary/20 shrink-0 border border-white/10">
                        {{ strtoupper(substr($agency->name ?? 'P', 0, 1)) }}
                    </div>
                @endif
                <div class="flex flex-col min-w-0">
                    <span class="text-sm font-black tracking-tight text-text-primary truncate">{{ $agency->name ?? 'PropOS' }}</span>
                    <span class="text-[9px] font-black uppercase tracking-[0.2em] text-brand-primary leading-none mt-0.5">Enterprise</span>
                </div>
            </div>

            {{-- Mobile close --}}
            <button @click="sidebarOpen = false"
                    class="md:hidden p-1.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-raised focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 px-4 space-y-6 sidebar-scrollbar">
            @foreach($groups as $group)
            <div class="space-y-2">
                {{-- Group label --}}
                @if($group['label'])
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.15em] text-text-tertiary select-none opacity-80 flex items-center gap-2">
                    <span class="h-1 w-1 rounded-full bg-brand-primary/40"></span>
                    {{ $group['label'] }}
                </p>
                @endif

                {{-- Group items --}}
                <ul class="space-y-1">
                    @foreach($group['items'] as $item)
                    @php $active = $item['active'] ?? request()->routeIs($item['route']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="relative flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-bold transition-all duration-300 group
                                  {{ $active
                                      ? 'bg-gradient-to-r from-brand-primary/12 to-brand-primary/4 text-brand-primary border border-brand-primary/15 shadow-sm'
                                      : 'text-text-secondary hover:text-brand-primary hover:bg-brand-primary/5 hover:translate-x-1 border border-transparent' }}">

                            {{-- Active pill --}}
                            @if($active)
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-brand-primary shadow-[0_0_8px_var(--color-brand-primary,#3B82F6)]"></span>
                            @endif

                            {{-- Icon --}}
                            <svg class="h-[18px] w-[18px] shrink-0 transition-all duration-300 group-hover:scale-110
                                        {{ $active ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-brand-primary' }}"
                                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['svg'] }}"/>
                            </svg>

                            {{-- Label --}}
                            <span class="truncate transition-colors duration-200">{{ $item['title'] }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </nav>

        {{-- User profile footer --}}
        <div class="shrink-0 px-4 py-4 border-t border-border-default/30 bg-surface-sunken/30">
            <a href="{{ route('settings.profile') }}"
               class="flex items-center gap-3 rounded-2xl p-2.5 bg-surface-card/40 hover:bg-surface-raised border border-border-default/40 hover:border-brand-primary/20 transition-all duration-300 group shadow-sm">
                <div class="relative shrink-0">
                    <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white flex items-center justify-center text-xs font-black shadow-md shadow-brand-primary/10 group-hover:scale-105 transition-transform duration-300">
                        {{ strtoupper(substr(auth()->user()?->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()?->last_name ?? '', 0, 1)) }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-success-500 border-2 border-surface-card animate-pulse"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-text-primary truncate group-hover:text-brand-primary transition-colors">
                        {{ auth()->user()?->name }}
                    </p>
                    <p class="text-[10px] font-bold text-text-tertiary tracking-wider uppercase mt-0.5">
                        {{ ucfirst(str_replace('_', ' ', auth()->user()?->roles->first()?->name ?? 'Member')) }}
                    </p>
                </div>
                <svg class="h-4 w-4 text-text-tertiary group-hover:text-brand-primary group-hover:translate-x-0.5 transition-all duration-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>
    </div>

    <style>
        .sidebar-scrollbar::-webkit-scrollbar {
            width: 3px;
        }
        .sidebar-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.15);
            border-radius: 9999px;
        }
        .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.4);
        }
    </style>
</div>
