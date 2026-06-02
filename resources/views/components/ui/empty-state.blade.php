@props([
    'title' => 'No Data Found',
    'description' => 'There are no records to display.',
    'icon' => 'inbox', // inbox, search, chart, users
    'actionText' => null,
    'actionClick' => null
])

<div class="flex flex-col items-center justify-center p-10 text-center relative overflow-hidden bg-surface-sunken/10 rounded-2xl border border-border-default border-dashed">
    <!-- Subtle glow behind icon -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-brand-primary/5 rounded-full blur-2xl pointer-events-none"></div>
    
    <div class="relative z-10 mb-4 h-16 w-16 bg-surface-card rounded-2xl border border-border-default shadow-sm flex items-center justify-center text-text-tertiary">
        @if($icon === 'inbox')
        <svg class="w-8 h-8 text-brand-primary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        @elseif($icon === 'search')
        <svg class="w-8 h-8 text-brand-primary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
        @elseif($icon === 'users')
        <svg class="w-8 h-8 text-brand-primary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
        </svg>
        @else
        {{ $slot }}
        @endif
    </div>

    <h3 class="text-base font-bold text-text-primary tracking-tight">{{ $title }}</h3>
    <p class="mt-1 text-sm text-text-tertiary max-w-sm">{{ $description }}</p>

    @if($actionText && $actionClick)
    <div class="mt-6">
        <button wire:click="{{ $actionClick }}" class="inline-flex items-center gap-2 px-4 py-2 bg-surface-card border border-border-default text-text-primary font-semibold text-sm rounded-xl hover:border-brand-primary/50 hover:bg-surface-raised transition-all shadow-sm hover-spring active:scale-95">
            {{ $actionText }}
        </button>
    </div>
    @endif
    
    @if(isset($action))
    <div class="mt-6">
        {{ $action }}
    </div>
    @endif
</div>
