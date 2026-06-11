<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-200 py-4 px-6">
            <p class="text-lg font-bold text-blue-700">{{ config('app.name', 'VillaCRM') }}</p>
        </header>
        <main class="flex-1 py-10 px-4">
            {{ $slot }}
        </main>
        <footer class="py-6 text-center text-xs text-gray-400">
            Powered by VillaCRM &mdash; AI-powered real estate platform
        </footer>
    </div>
    @livewireScripts
</body>
</html>
