<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} &middot; XFlow CRM</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">
    {{-- `sidebarOpen` controls the mobile slide-over; desktop sidebar is always visible. --}}
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">

        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
            style="display: none;"></div>

        {{-- Sidebar --}}
        @include('partials.sidebar')

        {{-- Main column --}}
        <div class="lg:pl-64">
            {{-- Navbar --}}
            @include('partials.navbar')

            {{-- Page heading (optional, set per page) --}}
            @isset($header)
                <header class="bg-white border-b border-gray-200">
                    <div class="px-4 sm:px-6 lg:px-8 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Page content --}}
            <main class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
