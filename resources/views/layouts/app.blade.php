<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic Brand Styles (early — prevents FOUC on brand colors) -->
    @php
        $resolver = app(\App\Infrastructure\Tenancy\TenantResolver::class);
        $agency   = $resolver->getCurrentAgency();
        $fontMap  = [
            'Inter'   => 'Inter:wght@300..700',
            'Poppins' => 'Poppins:wght@300;400;500;600;700',
            'Lato'    => 'Lato:wght@300;400;700',
            'Roboto'  => 'Roboto:wght@300;400;500;700',
        ];
        $selectedFont = $agency->font_family ?? '';
        $radiusMap = [
            'sharp'   => ['--radius-button:2px;--radius-input:2px;--radius-card:4px;--radius-dialog:6px;'],
            'default' => [],
            'rounded' => ['--radius-button:10px;--radius-input:10px;--radius-card:18px;--radius-dialog:24px;'],
            'pill'    => ['--radius-button:9999px;--radius-input:9999px;--radius-card:24px;--radius-dialog:28px;'],
        ];
        $radiusVars = implode('', $radiusMap[$agency->border_radius ?? 'default'] ?? []);
    @endphp

    <title>{{ $agency->name ?? config('app.name', 'PropOS') }}</title>

    @if($agency->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/'.$agency->favicon_path) }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if($selectedFont && isset($fontMap[$selectedFont]))
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontMap[$selectedFont]) }}&display=swap" rel="stylesheet">
    @else
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=Geist+Mono:wght@100..900&display=swap" rel="stylesheet">
    @endif

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

    <style>
        :root {
            --brand-primary:   {{ $agency->primary_color   ?? '#1E40AF' }};
            --brand-secondary: {{ $agency->secondary_color ?? '#18181B' }};
            --brand-accent:    {{ $agency->accent_color    ?? '#F59E0B' }};
            @if($selectedFont)--font-sans: '{{ $selectedFont }}', sans-serif;@endif
            {{ $radiusVars }}
        }
        @if($agency->sidebar_style === 'light')
        #app-sidebar {
            background: rgba(255,255,255,0.85) !important;
            border-color: rgba(0,0,0,0.08) !important;
        }
        @elseif($agency->sidebar_style === 'brand')
        #app-sidebar {
            background: {{ $agency->primary_color ?? '#1E40AF' }} !important;
            border-color: rgba(255,255,255,0.12) !important;
        }
        #app-sidebar .text-text-secondary,
        #app-sidebar .text-text-tertiary,
        #app-sidebar .text-text-primary { color: rgba(255,255,255,0.85) !important; }
        #app-sidebar .text-brand-primary { color: #ffffff !important; }
        #app-sidebar a:hover { background: rgba(255,255,255,0.15) !important; }
        #app-sidebar a.border-brand-primary\/15 { background: rgba(255,255,255,0.2) !important; border-color: rgba(255,255,255,0.3) !important; }
        @endif
    </style>
    @if($agency->custom_css)
    <style id="agency-custom-css">{!! $agency->custom_css !!}</style>
    @endif
</head>
<body class="h-full font-sans antialiased text-text-primary bg-surface-page transition-colors duration-300 overflow-x-hidden selection:bg-brand-primary/30 selection:text-brand-primary">
    <div x-data="{ 
            sidebarOpen: false,
            lastKey: '',
            keyTimeout: null
         }"
         @keydown.window="
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) || $event.target.isContentEditable) return;
            
            const key = $event.key.toLowerCase();
            
            // Single key shortcut
            if (key === 'c' && !$event.ctrlKey && !$event.metaKey) {
                $dispatch('toggleChatPanel');
                return;
            }
            
            // Sequence shortcuts starting with 'g'
            if (lastKey === 'g') {
                if (key === 'c') {
                    window.location.href = '{{ route('crm.contacts') }}';
                } else if (key === 'l') {
                    window.location.href = '{{ route('listing.index') }}';
                } else if (key === 'p') {
                    window.location.href = '{{ route('crm.pipeline') }}';
                }
                lastKey = '';
                return;
            }
            
            if (key === 'g') {
                lastKey = 'g';
                clearTimeout(keyTimeout);
                keyTimeout = setTimeout(() => { lastKey = ''; }, 1000);
            }
         }"
         class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <livewire:shared.sidebar />

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Topbar Header -->
            <livewire:shared.topbar />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden p-6 md:p-8 relative">
                <!-- Glowing Ambient Lights -->
                <div class="absolute top-[-10%] right-[-10%] w-[600px] h-[600px] rounded-full bg-brand-primary/8 dark:bg-brand-primary/10 blur-[130px] pointer-events-none -z-10"></div>
                <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-brand-accent/5 dark:bg-brand-accent/5 blur-[120px] pointer-events-none -z-10"></div>

                <!-- Grid and Gradient Backplate -->
                <div class="absolute inset-0 bg-gradient-subtle dark:bg-gradient-hero transition-all duration-500 -z-20 opacity-40 pointer-events-none">
                    <svg class="absolute inset-0 h-full w-full stroke-text-secondary/5 dark:stroke-white/5 [mask-image:radial-gradient(100%_100%_at_top_right,white,transparent)]" aria-hidden="true">
                        <defs>
                            <pattern id="grid-pattern-app" width="120" height="120" x="100%" y="-1" patternUnits="userSpaceOnUse">
                                <path d="M.5 120V.5H120" fill="none" />
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

    <!-- Global Spotlight Search (Cmd+K) -->
    <livewire:shared.global-search />

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

    <!-- Sortable.js for Kanban drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    @livewireScripts
</body>
</html>
