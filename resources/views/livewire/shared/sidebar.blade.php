<div class="hidden md:flex md:flex-col md:w-64 bg-surface-card border-r border-border-default/60 h-full flex-shrink-0 transition-colors duration-300">
    <!-- Brand / Logo -->
    <div class="flex items-center h-16 px-6 border-b border-border-default/60">
        <div class="flex items-center space-x-3">
            @if($agency && $agency->logo_path)
                <img class="h-8 w-auto" src="{{ $agency->logo_path }}" alt="{{ $agency->name }}">
            @else
                <div class="h-8 w-8 rounded-lg bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center font-bold text-brand-primary shadow-sm">
                    P
                </div>
            @endif
            <span class="text-xl font-bold tracking-tight text-text-primary">{{ $agency->name ?? 'PropOS' }}</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        @foreach($menuItems as $item)
            @php
                $isActive = request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route']) }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group {{ $isActive ? 'bg-brand-primary/10 text-brand-primary' : 'text-text-secondary hover:bg-surface-raised hover:text-text-primary' }}">
                <!-- Icon -->
                @if($item['icon'] === 'home')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                @elseif($item['icon'] === 'users')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 20.5c-2.113 0-4.108-.577-5.837-1.587v-.109c0-1.113.285-2.16.786-3.07M14.25 11.75a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM3.75 18.25v-.003c0-1.113.285-2.16.786-3.07M3.75 18.25A9.38 9.38 0 0 1 1 18a9.337 9.337 0 0 1 4.121-5.493M10.5 4.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                @elseif($item['icon'] === 'home-modern')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1M15.75 3v18m-3-18v18M12 7.5h.008v.008H12V7.5Zm0 3h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm-3-6h.008v.008H9V7.5Zm0 3h.008v.008H9v-.008Zm0 3h.008v.008H9v-.008Z" />
                    </svg>
                @elseif($item['icon'] === 'sparkles')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904ZM19.006 8.246 18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006Z" />
                    </svg>
                @elseif($item['icon'] === 'chart-bar')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                @elseif($item['icon'] === 'megaphone')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                    </svg>
                @elseif($item['icon'] === 'map')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                    </svg>
                @elseif($item['icon'] === 'chart-pie')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                    </svg>
                @elseif($item['icon'] === 'heart')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                @elseif($item['icon'] === 'arrow-trending-up')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                @elseif($item['icon'] === 'shield-check')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                @elseif($item['icon'] === 'banknotes')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V5.998c0-.754-.726-1.294-1.453-1.096A60.064 60.064 0 0 1 2.25 5.25m0 13.5L2.25 5.25m0 13.5L15 18.75m-12.75-13.5L15 5.25m-12.75 13.5L15 18.75" />
                    </svg>
                @elseif($item['icon'] === 'academic-cap')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                @elseif($item['icon'] === 'cog')
                    <svg class="mr-3 h-5 w-5 {{ $isActive ? 'text-brand-primary' : 'text-text-tertiary group-hover:text-text-secondary' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                @endif
                <span class="{{ $isActive ? '' : 'group-hover:translate-x-1' }} transition-transform duration-200">{{ $item['title'] }}</span>
            </a>
        @endforeach
    </nav>

    <!-- User Profile Footer -->
    <div class="p-4 border-t border-border-default/60 flex items-center space-x-3 bg-surface-sunken/30">
        <div class="h-9 w-9 rounded-full bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center font-bold text-brand-primary">
            {{ strtoupper(substr(auth()->user()?->first_name ?? 'U', 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-text-primary truncate">{{ auth()->user()?->name }}</p>
            <p class="text-xs text-text-tertiary truncate">{{ auth()->user()?->email }}</p>
        </div>
    </div>
</div>
