<header class="flex items-center justify-between h-16 px-6 bg-surface-card/80 backdrop-blur-xl border-b border-border-default flex-shrink-0 transition-colors duration-300" x-data="{ notifOpen: @entangle('showNotifications') }">

    <!-- Left: Mobile Menu & Breadcrumbs -->
    <div class="flex flex-1 items-center space-x-4">
        <button type="button" @click="sidebarOpen = true" class="md:hidden p-2 -ml-2 rounded-xl text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors focus:outline-none">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
        
        <!-- Breadcrumbs -->
        <nav class="hidden md:flex items-center space-x-2 text-sm font-medium text-text-secondary">
            @php
                $segments = request()->segments();
                $url = '';
            @endphp
            <a href="{{ route('dashboard') }}" class="hover:text-text-primary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </a>
            @foreach($segments as $segment)
                @php $url .= '/'.$segment; @endphp
                <svg class="w-4 h-4 text-text-tertiary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ $url }}" class="{{ $loop->last ? 'text-text-primary font-bold' : 'hover:text-text-primary transition-colors' }} capitalize">
                    {{ str_replace('-', ' ', $segment) }}
                </a>
            @endforeach
        </nav>
        
        <!-- Global Search -->
        <div class="w-full max-w-xs relative text-text-secondary focus-within:text-text-primary hidden lg:block ml-4">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" />
                </svg>
            </span>
            <input class="block w-full pl-10 pr-3 py-2 border border-border-default rounded-xl bg-surface-input placeholder-text-tertiary focus:outline-none focus:bg-surface-page focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-card text-sm text-text-primary transition-all duration-200" placeholder="Search..." type="search">
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center space-x-3">

        <!-- Theme Toggle -->
        <button type="button" x-data @click="$store.theme.toggle()" class="relative p-2 rounded-xl text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors focus:outline-none">
            <svg x-show="$store.theme.isDark" style="display:none" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <svg x-show="!$store.theme.isDark" style="display:none" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        <!-- Notification Bell -->
        <div class="relative" x-cloak>
            <button wire:click="toggleNotifications"
                    class="relative p-2 rounded-xl text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                @if($unreadCount > 0)
                <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-danger-500 text-[9px] font-bold text-white ring-2 ring-surface-card">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
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
                 class="absolute right-0 top-full mt-2 w-80 bg-surface-card border border-border-default rounded-2xl shadow-2xl z-50 overflow-hidden"
                 style="display:none">

                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-border-default">
                    <span class="text-sm font-semibold text-text-primary">Notifications</span>
                    @if($unreadCount > 0)
                    <button wire:click="toggleNotifications" class="text-xs text-brand-primary hover:underline">
                        Mark all read
                    </button>
                    @endif
                </div>

                <!-- Notification list -->
                <div class="max-h-80 overflow-y-auto divide-y divide-border-default/40">
                    @forelse($notifications as $notif)
                    <div class="flex gap-3 px-4 py-3 hover:bg-surface-raised/50 transition-colors
                                {{ $notif->read_at ? 'opacity-60' : '' }}">

                        <!-- Severity dot -->
                        <div class="shrink-0 mt-1">
                            <span class="block h-2 w-2 rounded-full
                                {{ match($notif->severity ?? 'info') {
                                    'warning' => 'bg-warning-400',
                                    'error'   => 'bg-danger-500',
                                    'success' => 'bg-success-500',
                                    default   => 'bg-brand-primary',
                                } }}">
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="block text-sm font-medium text-text-primary hover:text-brand-primary truncate">
                                {{ $notif->title }}
                            </a>
                            @else
                            <p class="text-sm font-medium text-text-primary truncate">{{ $notif->title }}</p>
                            @endif
                            <p class="text-xs text-text-secondary mt-0.5 line-clamp-2">{{ $notif->body }}</p>
                            <p class="text-[10px] text-text-tertiary mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                        </div>

                        <!-- Delete -->
                        <button wire:click="deleteNotification({{ $notif->id }})"
                                class="shrink-0 self-start text-text-tertiary hover:text-danger-500 transition-colors mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @empty
                    <div class="px-4 py-8 text-center">
                        <svg class="w-8 h-8 text-text-tertiary mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        <p class="text-xs text-text-tertiary">No notifications</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="h-6 w-px bg-border-default/60"></div>

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-text-secondary hover:text-brand-primary transition-colors flex items-center space-x-1 focus:outline-none">
                <span>Log out</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3-3H18m-3-3 3 3-3 3" />
                </svg>
            </button>
        </form>
    </div>
</header>

