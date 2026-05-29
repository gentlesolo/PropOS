<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PropOS') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">

    <!-- Theme Initialization script to prevent FOUC -->
    <script>
        function applyTheme() {
            if (localStorage.getItem('color-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        applyTheme();
        document.addEventListener('livewire:navigated', applyTheme);
    </script>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-text-primary bg-surface-page transition-colors duration-300">
    <div class="flex min-h-full relative">
        <!-- Theme Toggle Button -->
        <div class="absolute top-6 right-6 z-50">
            <button type="button" x-data @click="$store.theme.toggle()" class="p-2.5 rounded-xl border border-border-default bg-surface-card hover:bg-surface-raised text-text-secondary hover:text-text-primary shadow-sm hover:shadow hover-spring cursor-pointer focus:outline-none">
                <!-- Sun Icon (visible in dark mode) -->
                <svg x-show="$store.theme.isDark" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>
                <!-- Moon Icon (visible in light mode) -->
                <svg x-show="!$store.theme.isDark" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
            </button>
        </div>

        <!-- Left Side: Form Column -->
        <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:flex-none lg:px-20 xl:px-24 bg-surface-card z-10 w-full lg:max-w-xl shadow-xl transition-colors duration-300">
            <div class="mx-auto w-full max-w-md lg:w-96">
                {{ $slot }}
            </div>
        </div>

        <!-- Right Side: Graphic/Promo Column -->
        <div class="relative hidden w-0 flex-1 lg:block">
            <div class="absolute inset-0 bg-gradient-subtle dark:bg-gradient-hero transition-all duration-500">
                <!-- Decorative geometric lines and pattern overlays -->
                <svg class="absolute inset-0 h-full w-full stroke-text-secondary/10 dark:stroke-white/10 [mask-image:radial-gradient(100%_100%_at_top_right,white,transparent)]" aria-hidden="true">
                    <defs>
                        <pattern id="grid-pattern" width="200" height="200" x="100%" y="-1" patternUnits="userSpaceOnUse">
                            <path d="M.5 200V.5H200" fill="none" />
                        </pattern>
                    </defs>
                    <svg x="50%" y="-1" class="overflow-visible fill-text-secondary/3 dark:fill-white/5">
                        <path d="M-200 0h201v201h-201Z M600 0h201v201h-201Z M-400 600h201v201h-201Z M200 800h201v201h-201Z" stroke-width="0" />
                    </svg>
                    <rect width="100%" height="100%" stroke-width="0" fill="url(#grid-pattern)" />
                </svg>
                <div class="absolute inset-0 flex flex-col justify-between p-16 text-text-primary transition-colors duration-300">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-brand-primary/10 flex items-center justify-center border border-brand-primary/20 backdrop-blur-sm">
                            <span class="font-black tracking-wider text-xl text-brand-primary">P</span>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-text-primary">PropOS</span>
                    </div>

                    <!-- Floating Glass Panel (Mockup Dashboard / AI Feed) -->
                    <div class="glass-panel rounded-3xl p-8 max-w-lg shadow-2xl relative overflow-hidden transition-all duration-300">
                        <!-- Tiny header representing an active agency context -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full bg-success-500 dark:bg-success-400 animate-pulse"></div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-text-secondary">PropOS Copilot Active</span>
                            </div>
                            <span class="text-[10px] font-mono text-text-tertiary">v1.2.0-stable</span>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="p-3.5 bg-white/45 dark:bg-white/5 rounded-xl border border-border-default/60 dark:border-white/10 flex items-start space-x-3 transition-colors duration-300">
                                <div class="mt-0.5 h-6 w-6 rounded-lg bg-success-500/10 text-success-600 dark:text-success-200 flex items-center justify-center text-xs font-bold">✓</div>
                                <div>
                                    <div class="text-sm font-semibold text-text-primary">Lead Auto-Responded</div>
                                    <div class="text-xs text-text-secondary">WhatsApp response dispatched to Sarah K. for luxury listing.</div>
                                </div>
                            </div>
                            
                            <div class="p-3.5 bg-white/45 dark:bg-white/5 rounded-xl border border-border-default/60 dark:border-white/10 flex items-start space-x-3 transition-colors duration-300">
                                <div class="mt-0.5 h-6 w-6 rounded-lg bg-brand-primary/10 text-brand-primary dark:text-brand-primary-muted flex items-center justify-center text-xs font-bold">⚡</div>
                                <div>
                                    <div class="text-sm font-semibold text-text-primary">AI Negotiation Complete</div>
                                    <div class="text-xs text-text-secondary">Recommended counters generated for Nairobi Heights project.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-border-default/60 dark:border-white/10 flex items-center justify-between text-xs text-text-secondary">
                            <span>Daily Leads Handled: <strong class="text-text-primary">142</strong></span>
                            <span>Conversion Rate: <strong class="text-text-primary">+18.4%</strong></span>
                        </div>
                    </div>

                    <!-- Bottom branding statement -->
                    <div class="space-y-4">
                        <p class="text-3xl font-bold leading-normal tracking-tight text-text-primary">The next-generation, AI-native operating platform for real estate agencies.</p>
                        <div class="flex items-center space-x-4">
                            <div class="flex -space-x-2">
                                <span class="inline-block h-8 w-8 rounded-full ring-2 ring-border-subtle bg-surface-raised flex items-center justify-center text-xs font-bold font-mono text-text-secondary">A</span>
                                <span class="inline-block h-8 w-8 rounded-full ring-2 ring-border-subtle bg-surface-raised flex items-center justify-center text-xs font-bold font-mono text-text-secondary">K</span>
                                <span class="inline-block h-8 w-8 rounded-full ring-2 ring-border-subtle bg-surface-raised flex items-center justify-center text-xs font-bold font-mono text-text-secondary">M</span>
                            </div>
                            <span class="text-sm font-medium text-text-secondary">Empowering agencies in African & emerging markets.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Alpine Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                isDark: document.documentElement.classList.contains('dark'),
                toggle() {
                    this.isDark = !this.isDark;
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    }
                }
            });
        });
    </script>

    @livewireScripts
</body>
</html>
