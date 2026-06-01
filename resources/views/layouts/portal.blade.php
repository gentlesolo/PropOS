<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tenant Portal &mdash; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface-base antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-surface-primary border-b border-border-default py-4 px-6">
            <div class="max-w-3xl mx-auto flex items-center justify-between">
                <p class="text-base font-bold text-brand-primary">{{ config('app.name', 'PropOS') }}</p>
                <span class="text-xs text-text-tertiary">Tenant Portal</span>
            </div>
        </header>
        <main class="flex-1 py-8 px-4">
            <div class="max-w-3xl mx-auto">
                {{ $slot }}
            </div>
        </main>
        <footer class="py-6 text-center text-xs text-text-tertiary border-t border-border-default">
            Powered by {{ config('app.name', 'PropOS') }} &mdash; Secure Tenant Portal
        </footer>
    </div>
    @livewireScripts
</body>
</html>
