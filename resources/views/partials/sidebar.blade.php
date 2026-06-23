@php
    use App\Models\User;

    $user = auth()->user();

    // Navigation items. `roles` = which roles may see the item (empty = everyone authenticated).
    // `route` items use named routes; `href` items are placeholders until those modules are built.
    $navItems = [
        [
            'label'   => 'Dashboard',
            'icon'    => 'home',
            'route'   => 'dashboard',
            'active'  => request()->routeIs('dashboard'),
            'roles'   => [],
        ],
        [
            'label'   => 'Leads',
            'icon'    => 'users',
            'route'   => 'leads.index',
            'active'  => request()->routeIs('leads.*'),
            'roles'   => [User::ROLE_SUPER_ADMIN, User::ROLE_LEADS_ADMIN, User::ROLE_SALES, User::ROLE_DEVELOPER],
        ],
        [
            'label'   => 'Analytics',
            'icon'    => 'chart',
            'route'   => 'analytics.index',
            'active'  => request()->routeIs('analytics.*'),
            'roles'   => [User::ROLE_SUPER_ADMIN, User::ROLE_LEADS_ADMIN, User::ROLE_SALES, User::ROLE_DEVELOPER],
        ],
        [
            'label'   => 'Notifications',
            'icon'    => 'bell',
            'route'   => 'notifications.index',
            'active'  => request()->routeIs('notifications.*'),
            'roles'   => [],
        ],
        [
            'label'   => 'Users',
            'icon'    => 'cog',
            'route'   => 'users.index',
            'active'  => request()->routeIs('users.*'),
            'roles'   => [User::ROLE_SUPER_ADMIN],
        ],
        [
            'label'   => 'System Status',
            'icon'    => 'pulse',
            'route'   => 'system.status',
            'active'  => request()->routeIs('system.*'),
            'roles'   => [User::ROLE_SUPER_ADMIN],
        ],
    ];

    $icons = [
        'home'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'users' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z',
        'cog'   => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'code'  => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
        'bell'  => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'pulse' => 'M3 12h4l3 8 4-16 3 8h4',
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 transform bg-gray-900 text-gray-300 transition-transform duration-200 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    {{-- Brand --}}
    <div class="flex h-16 items-center justify-between px-6 border-b border-gray-800">
        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-white">XFlow</a>
        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="px-3 py-4 space-y-1">
        @foreach ($navItems as $item)
            @if (empty($item['roles']) || $user->hasAnyRole($item['roles']))
                <a href="{{ isset($item['route']) ? route($item['route']) : $item['href'] }}"
                    class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition
                        {{ $item['active']
                            ? 'bg-gray-800 text-white'
                            : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$item['icon']] }}" />
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>
