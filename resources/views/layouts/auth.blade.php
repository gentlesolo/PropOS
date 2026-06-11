<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $resolver = app(\App\Infrastructure\Tenancy\TenantResolver::class);
        $agency   = $resolver->getCurrentAgency() ?? new \App\Infrastructure\Persistence\Models\Agency();
    @endphp

    <title>{{ $agency->name ?? config('app.name', 'VillaCRM') }}</title>

    @if($agency->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/'.$agency->favicon_path) }}">
    @endif

    <!-- Google Fonts: Geist & Geist Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --brand-primary:   #10B981;
            --brand-secondary: #030712;
            --brand-accent:    #F59E0B;
            --font-sans: 'Geist', sans-serif;
            --font-mono: 'Geist Mono', monospace;
        }

        body {
            font-family: 'Geist', sans-serif;
            background-color: #030712 !important;
            color: #FAFAFA !important;
        }

        /* Easing & Spring Animations */
        .ease-spring {
            transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Slow panning grid background */
        @keyframes pan-grid {
            0% { transform: translate(0, 0); }
            100% { transform: translate(40px, 40px); }
        }
        .animated-grid {
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(16, 185, 129, 0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(16, 185, 129, 0.04) 1px, transparent 1px);
            animation: pan-grid 60s linear infinite;
        }

        /* Pulsing emerald glow */
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.15; transform: scale(1); }
            50% { opacity: 0.28; transform: scale(1.15); }
        }
        .pulse-glow-1 {
            animation: pulse-glow 12s ease-in-out infinite;
        }
        .pulse-glow-2 {
            animation: pulse-glow 8s ease-in-out infinite alternate;
        }

        /* Infinite marquee ticker */
        @keyframes marquee {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        .ticker-scroll {
            display: flex;
            width: max-content;
            animation: marquee 45s linear infinite;
        }
        .ticker-scroll:hover {
            animation-play-state: paused;
        }

        /* Shimmer sweep animation for CTA button */
        @keyframes shimmer-sweep {
            0% { left: -100%; }
            100% { left: 200%; }
        }
        .cta-shimmer {
            position: relative;
            overflow: hidden;
        }
        .cta-shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
            transform: skewX(-20deg);
        }
        .cta-shimmer:hover::after {
            animation: shimmer-sweep 0.85s ease-in-out;
        }

        /* Override Chrome autofill background colors */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #FAFAFA !important;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #111827 !important;
        }
    </style>
</head>
<body class="h-full antialiased text-[#FAFAFA] bg-[#030712] selection:bg-[#10B981]/30 selection:text-white">
    <div class="flex flex-col lg:flex-row min-h-screen relative bg-[#030712] font-sans">
        
        <!-- Ambient Left Panel (collapses to a slim header strip on mobile) -->
        <div class="relative flex flex-col justify-between w-full lg:w-auto lg:flex-1 bg-[#030712] p-6 lg:p-16 border-b lg:border-b-0 lg:border-r border-white/5 overflow-hidden min-h-[120px] lg:min-h-screen">
            <!-- Background grids & ambient glows -->
            <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
                <!-- Slow panning grid background -->
                <div class="absolute -inset-10 animated-grid opacity-75"></div>
                <!-- Pulsing Glows -->
                <div class="absolute top-1/4 left-1/4 w-[400px] h-[400px] rounded-full bg-[#10B981] opacity-[0.06] blur-[120px] pulse-glow-1"></div>
                <div class="absolute bottom-1/4 right-1/4 w-[350px] h-[350px] rounded-full bg-[#F59E0B] opacity-[0.03] blur-[100px] pulse-glow-2"></div>
                <!-- Faint Map lines SVG overlay -->
                <svg class="absolute inset-0 w-full h-full stroke-white/[0.015] [mask-image:radial-gradient(100%_100%_at_top_left,white,transparent)]" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M-100 300 C 200 400, 400 200, 800 600" stroke-width="1.5" />
                    <path d="M0 600 C 300 500, 500 700, 1000 400" stroke-width="1" />
                    <circle cx="200" cy="350" r="3" fill="#10B981" class="animate-pulse" />
                    <circle cx="800" cy="600" r="3" fill="#10B981" class="animate-pulse" />
                    <circle cx="500" cy="550" r="2" fill="#F59E0B" class="animate-pulse" />
                </svg>
            </div>

            <!-- Top Header Logo (Desktop) or Mobile Row -->
            <div class="relative z-10 flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
                    <div class="h-9 w-9 rounded-md bg-[#10B981]/10 flex items-center justify-center border border-[#10B981]/25 backdrop-blur-sm">
                        <!-- Custom premium logo box -->
                        <span class="font-bold tracking-wider text-lg text-[#10B981]">P</span>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-[#FAFAFA]">
                        Prop<span class="relative inline-block text-[#FAFAFA]">O<span class="absolute top-[4px] left-[3px] w-[5px] h-[5px] bg-[#10B981] rounded-full shadow-[0_0_8px_#10B981]"></span></span>S
                    </span>
                </div>
                <!-- Active stats indicator badge -->
                <div class="flex items-center space-x-2 px-2.5 py-1 rounded-full bg-[#111827] border border-white/5 text-[11px] font-mono text-[#A1A1AA]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
                    <span>System Active · Emerging Markets</span>
                </div>
            </div>

            <!-- Desktop Middle branding copy (hidden on mobile) -->
            <div class="hidden lg:block relative z-10 my-auto max-w-xl space-y-8">
                <div class="space-y-3">
                    <div class="inline-flex px-2.5 py-1 rounded-md bg-[#10B981]/10 border border-[#10B981]/20 text-xs font-mono text-[#10B981] font-semibold tracking-wider uppercase">
                        AI-Native Property OS
                    </div>
                    <h1 class="text-4xl xl:text-5xl font-bold leading-[1.1] text-[#FAFAFA] tracking-[-0.02em]">
                        Your agency.<br>One system.
                    </h1>
                </div>

                <!-- 3 Sleek Micro Stats -->
                <div class="grid grid-cols-3 gap-6 pt-6 border-t border-white/5">
                    <div class="space-y-1">
                        <div class="text-xl font-semibold font-mono text-[#10B981]">₦4.2B+</div>
                        <div class="text-xs text-[#A1A1AA]">Transactions tracked</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xl font-semibold font-mono text-[#FAFAFA]">12k+</div>
                        <div class="text-xs text-[#A1A1AA]">Listings managed</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xl font-semibold font-mono text-[#FAFAFA]">94%</div>
                        <div class="text-xs text-[#A1A1AA]">Agent retention</div>
                    </div>
                </div>
            </div>

            <!-- Desktop Bottom Scrolling Deals Ticker (hidden on mobile) -->
            <div class="hidden lg:block relative z-10 w-full overflow-hidden border-t border-white/5 pt-6 pointer-events-none">
                <div class="ticker-scroll text-[11px] font-mono text-[#52525B] space-x-12 uppercase tracking-wider">
                    <div class="flex items-center space-x-12">
                        <span>LAGOS · 3BR Luxury Penthouse · ₦320M · Closed</span>
                        <span class="text-[#10B981]">●</span>
                        <span>CAPE TOWN · Sea Point Studio · R4.2M · Closed</span>
                        <span class="text-[#10B981]">●</span>
                        <span>NAIROBI · Kilimani Heights · KSh 18.5M · Closed</span>
                        <span class="text-[#10B981]">●</span>
                        <span>ACCRA · Airport Residential Duplex · $450K · Closed</span>
                        <span class="text-[#10B981]">●</span>
                    </div>
                    <!-- Duplicate for seamless looping -->
                    <div class="flex items-center space-x-12">
                        <span>LAGOS · 3BR Luxury Penthouse · ₦320M · Closed</span>
                        <span class="text-[#10B981]">●</span>
                        <span>CAPE TOWN · Sea Point Studio · R4.2M · Closed</span>
                        <span class="text-[#10B981]">●</span>
                        <span>NAIROBI · Kilimani Heights · KSh 18.5M · Closed</span>
                        <span class="text-[#10B981]">●</span>
                        <span>ACCRA · Airport Residential Duplex · $450K · Closed</span>
                        <span class="text-[#10B981]">●</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Form Right Panel -->
        <div class="relative flex flex-col justify-center items-center w-full lg:max-w-2xl xl:max-w-3xl lg:w-[600px] xl:w-[680px] bg-[#090d16] p-6 sm:p-12 lg:p-20 z-10 border-t lg:border-t-0 border-white/5 transition-colors duration-300 min-h-screen">
            <!-- Thin Emerald Progress Bar (Dynamic line at top of form panel) -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-[#111827] z-20">
                <div id="auth-progress-bar" class="h-full bg-[#10B981] transition-all duration-300 ease-spring" style="width: 0%;"></div>
            </div>

            <!-- Vertical Centered Form Slot -->
            <div class="w-full max-w-[420px] py-8">
                {{ $slot }}
            </div>
        </div>

    </div>

    @livewireScripts
</body>
</html>
