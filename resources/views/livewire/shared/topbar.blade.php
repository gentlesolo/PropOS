<header class="flex items-center justify-between h-16 px-6 bg-[#090d16]/80 backdrop-blur-xl border-b border-white/5 flex-shrink-0 transition-colors duration-300 relative z-30" x-data="{ notifOpen: @entangle('showNotifications') }">

    <!-- Left: Mobile Menu & Desktop Collapse Toggle -->
    <div class="flex items-center space-x-3">
        <!-- Mobile Sidebar Toggle -->
        <button type="button" @click="sidebarOpen = true" class="md:hidden p-2 rounded-md text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-[#111827] transition-colors focus:outline-none">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <!-- Desktop Sidebar Collapse Toggle -->
        <button type="button" @click="toggleSidebar()" class="hidden md:flex p-2 rounded-md text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-[#111827] transition-colors focus:outline-none">
            <svg x-show="!sidebarCollapsed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            <svg x-show="sidebarCollapsed" style="display:none" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        <!-- PropOS Mark -->
        <span class="text-xs font-black uppercase tracking-wider text-[#A1A1AA] hidden md:block">PropOS Terminal</span>
    </div>

    <!-- Center: Universal Search -->
    <div class="flex-1 max-w-sm mx-auto relative hidden md:block">
        <button @click="$dispatch('open-global-search')" class="w-full flex items-center justify-between pl-3.5 pr-2 py-1.5 bg-[#030712] border border-white/5 hover:border-[#10B981]/40 rounded-md text-[#52525B] hover:text-[#A1A1AA] text-xs font-semibold transition-all duration-200 focus:outline-none cursor-pointer">
            <div class="flex items-center space-x-2">
                <svg class="h-3.5 w-3.5 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" />
                </svg>
                <span>Search command...</span>
            </div>
            <kbd class="px-1.5 py-0.5 bg-[#111827] border border-white/10 rounded text-[9px] font-mono text-[#A1A1AA]">⌘K</kbd>
        </button>
    </div>

    <!-- Right: Actions & User Info -->
    <div class="flex items-center space-x-4">
        <!-- Theme Toggle -->
        <button type="button" x-data @click="$store.theme.toggle()" class="p-2 rounded-md text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-[#111827] transition-colors focus:outline-none">
            <svg x-show="$store.theme.isDark" style="display:none" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <svg x-show="!$store.theme.isDark" style="display:none" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        <!-- Notification Bell with Amber Dot -->
        <div class="relative" x-cloak>
            <button wire:click="toggleNotifications"
                    class="relative p-2 rounded-md text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-[#111827] transition-colors focus:outline-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                @if($unreadCount > 0)
                <span class="absolute top-2 right-2 flex h-2 w-2 rounded-full bg-[#F59E0B] ring-2 ring-[#090d16]"></span>
                @endif
            </button>

            <!-- Dropdown panel -->
            <div x-show="notifOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click.outside="notifOpen = false"
                 class="absolute right-0 top-full mt-2 w-80 bg-[#090d16] border border-white/5 rounded-md shadow-2xl z-50 overflow-hidden"
                 style="display:none">

                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-white/5 bg-[#030712]/40">
                    <span class="text-xs font-bold text-[#FAFAFA]">Notifications</span>
                    @if($unreadCount > 0)
                    <button wire:click="toggleNotifications" class="text-[10px] font-black text-[#10B981] hover:underline uppercase tracking-wider">
                        Mark all read
                    </button>
                    @endif
                </div>

                <!-- Notification list -->
                <div class="max-h-80 overflow-y-auto divide-y divide-white/5">
                    @forelse($notifications as $notif)
                    <div class="flex gap-3 px-4 py-3 hover:bg-[#111827]/40 transition-colors
                                {{ $notif->read_at ? 'opacity-50' : '' }}">

                        <!-- Severity dot -->
                        <div class="shrink-0 mt-1.5">
                            <span class="block h-2 w-2 rounded-full
                                {{ match($notif->severity ?? 'info') {
                                    'warning' => 'bg-[#F59E0B]',
                                    'error'   => 'bg-[#F43F5E]',
                                    'success' => 'bg-[#22C55E]',
                                    default   => 'bg-[#10B981]',
                                } }}">
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="block text-xs font-bold text-[#FAFAFA] hover:text-[#10B981] truncate">
                                {{ $notif->title }}
                            </a>
                            @else
                            <p class="text-xs font-bold text-[#FAFAFA] truncate">{{ $notif->title }}</p>
                            @endif
                            <p class="text-[11px] text-[#A1A1AA] mt-0.5 line-clamp-2 leading-relaxed">{{ $notif->body }}</p>
                            <p class="text-[9px] text-[#52525B] mt-1 font-mono">{{ $notif->created_at->diffForHumans() }}</p>
                        </div>

                        <!-- Delete -->
                        <button wire:click="deleteNotification({{ $notif->id }})"
                                class="shrink-0 self-start text-[#52525B] hover:text-[#F43F5E] transition-colors mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @empty
                    <div class="px-4 py-8 text-center bg-[#030712]/20">
                        <svg class="w-8 h-8 text-[#52525B] mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        <p class="text-xs font-semibold text-[#52525B]">No notifications</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="h-6 w-px bg-white/5"></div>

        <!-- Avatar + Good morning greeting -->
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex flex-col text-right">
                <span class="text-xs font-bold text-[#FAFAFA]">Good morning, {{ auth()->user()->first_name }}</span>
                <span class="text-[9px] font-black uppercase tracking-wider text-[#52525B]">{{ auth()->user()->job_title ?? 'Agent' }}</span>
            </div>
            <div class="h-8 w-8 rounded-md bg-gradient-to-br from-[#10B981] to-[#10B981]/80 text-[#FAFAFA] flex items-center justify-center text-xs font-black shadow-md shadow-[#10B981]/15 border border-white/10 select-none">
                {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
            </div>
        </div>

        <!-- Logout button -->
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" title="Log Out" class="p-2 rounded-md text-[#A1A1AA] hover:text-[#F43F5E] hover:bg-[#111827] transition-colors focus:outline-none flex items-center">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
            </button>
        </form>
    </div>
</header>
