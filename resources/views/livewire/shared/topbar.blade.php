<header class="flex items-center justify-between h-16 px-6 bg-surface-card/80 backdrop-blur-xl border-b border-border-default/60 flex-shrink-0 transition-colors duration-300">
    <!-- Search Bar -->
    <div class="flex flex-1">
        <div class="w-full max-w-xs relative text-text-secondary focus-within:text-text-primary">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" />
                </svg>
            </span>
            <input class="block w-full pl-10 pr-3 py-2 border border-border-default/60 rounded-xl bg-surface-input placeholder-text-tertiary focus:outline-none focus:bg-surface-page focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm text-text-primary transition-all duration-200" placeholder="Search..." type="search">
        </div>
    </div>

    <!-- Actions Area -->
    <div class="flex items-center space-x-4">
        
        <!-- Theme Toggle Button -->
        <button type="button" x-data @click="$store.theme.toggle()" class="relative p-2 rounded-xl text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors focus:outline-none">
            <!-- Sun Icon (visible in dark mode) -->
            <svg x-show="$store.theme.isDark" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <!-- Moon Icon (visible in light mode) -->
            <svg x-show="!$store.theme.isDark" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        <!-- Notifications bell -->
        <button class="relative p-2 rounded-xl text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors focus:outline-none">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            @if($notificationsCount > 0)
                <span class="absolute top-1.5 right-1.5 flex h-2 w-2 rounded-full bg-rose-500 ring-2 ring-surface-card"></span>
            @endif
        </button>

        <!-- Divider -->
        <div class="h-6 w-px bg-border-default/60"></div>

        <!-- Log out button -->
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
