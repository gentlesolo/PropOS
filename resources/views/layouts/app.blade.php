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
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Dynamic Brand Styles -->
    @php
        $resolver = app(\App\Infrastructure\Tenancy\TenantResolver::class);
        $agency = $resolver->getCurrentAgency();
    @endphp
    <style>
        :root {
            --brand-primary: {{ $agency->primary_color ?? '#1E40AF' }};
            --brand-secondary: {{ $agency->secondary_color ?? '#3B82F6' }};
            --brand-accent: {{ $agency->accent_color ?? '#F59E0B' }};
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-text-primary bg-surface-page transition-colors duration-300">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <livewire:shared.sidebar />

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Topbar Header -->
            <livewire:shared.topbar />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 md:p-8 relative">
                <!-- Optional subtle gradient background effect similar to auth -->
                <div class="absolute inset-0 bg-gradient-subtle dark:bg-gradient-hero transition-all duration-500 -z-10 opacity-30 pointer-events-none">
                    <svg class="absolute inset-0 h-full w-full stroke-text-secondary/10 dark:stroke-white/10 [mask-image:radial-gradient(100%_100%_at_top_right,white,transparent)]" aria-hidden="true">
                        <defs>
                            <pattern id="grid-pattern-app" width="200" height="200" x="100%" y="-1" patternUnits="userSpaceOnUse">
                                <path d="M.5 200V.5H200" fill="none" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" stroke-width="0" fill="url(#grid-pattern-app)" />
                    </svg>
                </div>
                
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Global AI Chat Panel (Floating) -->
    <livewire:ai.chat-panel />

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
