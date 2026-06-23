<nav class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">

    {{-- Mobile hamburger (toggles the sidebar) --}}
    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Page title (set per page via $title) --}}
    <div class="flex-1">
        <h1 class="text-lg font-semibold text-gray-800">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    {{-- Notification bell --}}
    @php($unreadCount = auth()->user()->unreadNotifications()->count())
    <a href="{{ route('notifications.index') }}"
        class="relative rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
        title="Notifications">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-none text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </a>

    {{-- User dropdown --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" @keydown.escape.window="open = false"
            class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-gray-100 focus:outline-none">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-medium text-gray-700 leading-tight">{{ auth()->user()->name }}</span>
                <span class="block text-xs text-gray-400 leading-tight">{{ auth()->user()->role_label }}</span>
            </span>
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Dropdown menu --}}
        <div x-show="open"
            x-transition
            @click.outside="open = false"
            class="absolute right-0 mt-2 w-48 origin-top-right rounded-md border border-gray-200 bg-white py-1 shadow-lg"
            style="display: none;">

            <div class="border-b border-gray-100 px-4 py-2 sm:hidden">
                <p class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-400">{{ auth()->user()->role_label }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</nav>
