<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Notifications</h1>
            <p class="mt-1 text-sm text-text-secondary">
                @if($unreadCount > 0)
                    You have <span class="font-semibold text-brand-primary">{{ $unreadCount }}</span> unread notification{{ $unreadCount === 1 ? '' : 's' }}.
                @else
                    All caught up — no unread notifications.
                @endif
            </p>
            @can('agency.manage')
            <a href="{{ route('settings.notifications') }}"
               class="inline-flex items-center gap-1 mt-2 text-xs text-text-tertiary hover:text-brand-primary transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                Notification Templates →
            </a>
            @endcan
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            @if($unreadCount > 0)
            <button wire:click="markAllRead"
                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-border-default text-text-secondary hover:text-brand-primary hover:border-brand-primary transition-colors">
                Mark all read
            </button>
            @endif
            <button wire:click="deleteAll"
                wire:confirm="Delete all notifications? This cannot be undone."
                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-border-default text-text-secondary hover:text-danger-600 hover:border-danger-300 transition-colors">
                Delete all
            </button>
        </div>
    </div>

    {{-- Filter tabs --}}
    <div class="flex gap-1 mb-4 border-b border-border-default">
        <button wire:click="setFilter('all')"
            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                   {{ $filter === 'all' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            All
        </button>
        <button wire:click="setFilter('unread')"
            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                   {{ $filter === 'unread' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            Unread
        </button>
        <button wire:click="setFilter('read')"
            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                   {{ $filter === 'read' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            Read
        </button>
    </div>

    {{-- Notification list --}}
    <div class="space-y-2">
        @forelse($notifications as $notif)
        <div wire:key="notif-{{ $notif->id }}"
             class="flex items-start gap-4 p-4 rounded-xl border transition-colors
                    {{ $notif->read_at ? 'border-border-default bg-surface-card' : 'border-brand-primary/20 bg-brand-primary/5' }}">

            {{-- Severity indicator --}}
            <div class="shrink-0 mt-1">
                <span class="block h-2.5 w-2.5 rounded-full
                    {{ match($notif->severity ?? 'info') {
                        'error'   => 'bg-danger-500',
                        'warning' => 'bg-warning-500',
                        'success' => 'bg-success-500',
                        default   => 'bg-brand-primary',
                    } }}">
                </span>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        @if($notif->action_url)
                        <a href="{{ $notif->action_url }}"
                           wire:click="markRead({{ $notif->id }})"
                           class="text-sm font-semibold text-text-primary hover:text-brand-primary transition-colors">
                            {{ $notif->title }}
                        </a>
                        @else
                        <p class="text-sm font-semibold text-text-primary">{{ $notif->title }}</p>
                        @endif

                        <p class="text-sm text-text-secondary mt-0.5 leading-relaxed">{{ $notif->body }}</p>
                    </div>

                    {{-- Timestamp --}}
                    <span class="shrink-0 text-xs text-text-tertiary font-mono whitespace-nowrap mt-0.5">
                        {{ $notif->created_at->diffForHumans() }}
                    </span>
                </div>

                {{-- Footer: type badge + actions --}}
                <div class="flex items-center gap-3 mt-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-surface-sunken text-text-tertiary border border-border-default">
                        {{ str_replace('_', ' ', $notif->type) }}
                    </span>

                    @if(! $notif->read_at)
                    <button wire:click="markRead({{ $notif->id }})"
                        class="text-xs text-text-tertiary hover:text-brand-primary transition-colors">
                        Mark read
                    </button>
                    @endif

                    <button wire:click="delete({{ $notif->id }})"
                        class="text-xs text-text-tertiary hover:text-danger-600 transition-colors ml-auto">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="py-16 text-center">
            <svg class="w-10 h-10 text-text-tertiary mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
            <p class="text-sm font-semibold text-text-secondary">No notifications</p>
            <p class="text-xs text-text-tertiary mt-1">
                @if($filter !== 'all')
                    Try switching to "All" to see everything.
                @else
                    You're all caught up.
                @endif
            </p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
