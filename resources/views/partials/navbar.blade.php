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
