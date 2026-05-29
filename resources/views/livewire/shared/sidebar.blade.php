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
         class="fixed inset-y-0 left-0 z-50 w-64 bg-surface-page border-r border-border-default/40 h-full flex flex-col flex-shrink-0 transition-transform duration-300 ease-in-out md:relative md:translate-x-0 md:flex">

        {{-- Brand / Logo --}}
        <div class="flex items-center justify-between h-16 px-5 border-b border-border-default/40 shrink-0">
            <div class="flex items-center gap-3">
                @if($agency && $agency->logo_path)
                    <img class="h-7 w-auto" src="{{ asset('storage/'.$agency->logo_path) }}" alt="{{ $agency->name }}">
                @else
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-brand-primary to-info-500 flex items-center justify-center font-black text-white text-sm shadow-brand-sm shrink-0">
                        {{ strtoupper(substr($agency->name ?? 'P', 0, 1)) }}
                    </div>
                @endif
                <span class="text-base font-black tracking-tight text-text-primary truncate">{{ $agency->name ?? 'PropOS' }}</span>
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
        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-5">
            @foreach($groups as $group)
            <div>
                {{-- Group label --}}
                @if($group['label'])
                <p class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-widest text-text-tertiary select-none">
                    {{ $group['label'] }}
                </p>
                @endif

                {{-- Group items --}}
                <ul class="space-y-0.5">
                    @foreach($group['items'] as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="relative flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition-all duration-200 group
                                  {{ $active
                                      ? 'bg-brand-primary/10 text-brand-primary'
                                      : 'text-text-secondary hover:text-text-primary hover:bg-surface-raised' }}">

                            {{-- Active pill --}}
                            @if($active)
                            <span class="absolute left-0 inset-y-2 w-0.5 rounded-r-full bg-brand-primary"></span>
                            @endif

                            {{-- Icon --}}
                            <svg class="h-[18px] w-[18px] shrink-0 transition-colors duration-200
                                        {{ $active ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-primary' }}"
                                 fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['svg'] }}"/>
                            </svg>

                            {{-- Label --}}
                            <span class="truncate">{{ $item['title'] }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </nav>

        {{-- User profile footer --}}
        <div class="shrink-0 px-4 py-4 border-t border-border-default/40">
            <a href="{{ route('settings.profile') }}"
               class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-surface-raised transition-colors group">
                <div class="h-8 w-8 rounded-full bg-brand-primary text-white flex items-center justify-center text-xs font-bold shrink-0 group-hover:scale-105 transition-transform">
                    {{ strtoupper(substr(auth()->user()?->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()?->last_name ?? '', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-text-primary truncate group-hover:text-brand-primary transition-colors">
                        {{ auth()->user()?->name }}
                    </p>
                    <p class="text-xs text-text-tertiary truncate">
                        {{ ucfirst(str_replace('_', ' ', auth()->user()?->roles->first()?->name ?? 'Member')) }}
                    </p>
                </div>
                <svg class="h-4 w-4 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>
    </div>
</div>
