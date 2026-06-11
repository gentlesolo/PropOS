<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tenant Portal &mdash; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface-page antialiased min-h-screen font-sans text-text-primary">

    <!-- Header -->
    <header class="bg-surface-card border-b border-border-default sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-brand-primary to-brand-primary/70 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                </div>
                <span class="text-sm font-bold text-text-primary">{{ config('app.name', 'VillaCRM') }}</span>
            </div>
            <span class="text-xs font-medium text-text-tertiary px-2.5 py-1 bg-surface-hover rounded-full">Tenant Portal</span>
        </div>
    </header>

    <!-- Page Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-border-default mt-12 py-6 text-center text-xs text-text-tertiary">
        Secure Tenant Portal &mdash; {{ config('app.name', 'VillaCRM') }}
    </footer>

    <!-- Toast Notifications -->
    <div x-data="{
            toasts: [],
            addToast(detail) {
                let id = Date.now();
                this.toasts.push({ id, message: detail.message, type: detail.type || 'info' });
                setTimeout(() => this.removeToast(id), 4000);
            },
            removeToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        x-on:notify.window="addToast($event.detail)"
        class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
        role="status" aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-y-1"
                 :class="{
                     'bg-success-50 border-success-200 text-success-800': toast.type === 'success',
                     'bg-danger-50 border-danger-200 text-danger-800': toast.type === 'error',
                     'bg-warning-50 border-warning-200 text-warning-800': toast.type === 'warning',
                     'bg-brand-50 border-brand-200 text-brand-700': toast.type === 'info' || !toast.type,
                 }"
                 class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border shadow-lg text-sm font-medium max-w-sm w-full">
                <span x-text="toast.message" class="flex-1"></span>
                <button @click="removeToast(toast.id)" class="shrink-0 opacity-40 hover:opacity-100 transition-opacity text-lg leading-none">&times;</button>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
