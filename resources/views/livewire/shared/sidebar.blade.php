<div>
    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         class="fixed inset-0 bg-[#030712]/80 backdrop-blur-sm z-40 md:hidden"
         @click="sidebarOpen = false"
         style="display:none">
    </div>

    {{-- Sidebar container --}}
    <div id="app-sidebar"
         :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            sidebarCollapsed ? 'md:w-16' : 'md:w-[260px]'
         ]"
         class="fixed inset-y-0 left-0 z-50 bg-[#090d16] border-r border-white/5 h-full flex flex-col flex-shrink-0 transition-all duration-300 ease-spring md:relative md:translate-x-0 md:flex">

        {{-- Brand / Logo --}}
        <div class="flex items-center justify-between h-16 px-4 border-b border-white/5 shrink-0 bg-[#090d16]/10">
            <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                <div class="h-8 w-8 rounded-md bg-gradient-to-br from-[#10B981] to-[#0ea5e9] flex items-center justify-center font-black text-white text-sm shadow-md shadow-[#10B981]/20 shrink-0 border border-white/10">
                    P
                </div>
                <div class="flex flex-col min-w-0" x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95">
                    <span class="text-sm font-black tracking-tight text-[#FAFAFA] truncate">{{ $agency->name ?? 'PropOS' }}</span>
                    <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[#10B981] leading-none mt-0.5">Control Room</span>
                </div>
            </div>

            {{-- Mobile close --}}
            <button @click="sidebarOpen = false"
                    class="md:hidden p-1.5 rounded-lg text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-[#111827] focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6 sidebar-scrollbar">
            @foreach($groups as $group)
            <div class="space-y-1">
                {{-- Group label --}}
                @if($group['label'])
                <p x-show="!sidebarCollapsed" class="px-3 text-[10px] font-black uppercase tracking-[0.20em] text-[#52525B] select-none opacity-80 flex items-center gap-2">
                    {{ $group['label'] }}
                </p>
                <div x-show="sidebarCollapsed" class="h-px bg-white/5 my-2"></div>
                @endif

                {{-- Group items --}}
                <ul class="space-y-0.5">
                    @foreach($group['items'] as $item)
                    @php $active = $item['active'] ?? request()->routeIs($item['route']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           :title="sidebarCollapsed ? '{{ $item['title'] }}' : ''"
                           class="relative flex items-center gap-3 px-3 py-2.5 rounded-md text-xs font-bold transition-all duration-200 group
                                  {{ $active
                                      ? 'bg-[#10B981]/8 text-[#10B981] border border-[#10B981]/15 shadow-sm'
                                      : 'text-[#A1A1AA] hover:text-[#10B981] hover:bg-[#10B981]/5 border border-transparent' }}"
                           :class="sidebarCollapsed ? 'justify-center px-0' : ''">

                            {{-- Active border indicator --}}
                            @if($active)
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-[3px] rounded-r bg-[#10B981] shadow-[0_0_8px_#10B981]"></span>
                            @endif

                            {{-- Icon --}}
                            <svg class="h-[18px] w-[18px] shrink-0 transition-all duration-200 group-hover:scale-110
                                        {{ $active ? 'text-[#10B981]' : 'text-[#52525B] group-hover:text-[#10B981]' }}"
                                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['svg'] }}"/>
                            </svg>

                            {{-- Label --}}
                            <span x-show="!sidebarCollapsed" class="truncate transition-colors duration-200">{{ $item['title'] }}</span>

                            @if($item['title'] === 'Offers' && \App\Infrastructure\Persistence\Models\Offer::where('agency_id', auth()->user()?->agency_id)->where('status', 'pending')->exists())
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 flex h-1.5 w-1.5" :class="sidebarCollapsed ? 'right-2' : 'right-3.5'">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#F59E0B] opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#F59E0B]"></span>
                                </span>
                            @endif
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </nav>

        {{-- AI Assistant trigger button (distinctive) --}}
        <div class="px-3 py-2.5 border-t border-white/5 bg-[#030712]/40 shrink-0">
            <button @click="$dispatch('toggleChatPanel')"
                    :title="sidebarCollapsed ? 'AI Assistant' : ''"
                    class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-md text-xs font-bold transition-all duration-300 relative overflow-hidden group
                           bg-gradient-to-r from-[#10B981] to-[#0ea5e9] text-white shadow-lg shadow-[#10B981]/10 hover:shadow-[#10B981]/20 hover:scale-[1.02] active:scale-95">
                <span class="absolute inset-0 border border-white/20 rounded-md pointer-events-none"></span>
                <svg class="h-4 w-4 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904zm9.193-7.658L18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006z"/>
                </svg>
                <span x-show="!sidebarCollapsed">AI Command Center</span>
            </button>
        </div>

        {{-- User profile footer --}}
        <div class="shrink-0 px-3 py-3 border-t border-white/5 bg-[#090d16]/30">
            <a href="{{ route('settings.profile') }}"
               class="flex items-center gap-3 rounded-md p-2 bg-[#111827]/40 hover:bg-[#111827] border border-white/5 hover:border-[#10B981]/30 transition-all duration-200 group shadow-sm"
               :class="sidebarCollapsed ? 'justify-center p-1.5' : ''">
                <div class="relative shrink-0">
                    <div class="h-8 w-8 rounded-md bg-gradient-to-br from-[#10B981] to-[#10B981]/80 text-[#FAFAFA] flex items-center justify-center text-xs font-black shadow-md shadow-[#10B981]/15 group-hover:scale-105 transition-transform duration-200">
                        {{ strtoupper(substr(auth()->user()?->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()?->last_name ?? '', 0, 1)) }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-[#22C55E] border border-[#090d16] animate-pulse"></span>
                </div>
                <div class="flex-1 min-w-0" x-show="!sidebarCollapsed">
                    <p class="text-xs font-bold text-[#FAFAFA] truncate group-hover:text-[#10B981] transition-colors">
                        {{ auth()->user()?->name }}
                    </p>
                    <p class="text-[9px] font-black text-[#52525B] tracking-wider uppercase mt-0.5 truncate">
                        {{ auth()->user()?->job_title ?? 'Agent' }}
                    </p>
                </div>
                <svg x-show="!sidebarCollapsed" class="h-3 w-3 text-[#52525B] group-hover:text-[#10B981] group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
            background: rgba(16, 185, 129, 0.4);
        }
    </style>
</div>
