<x-layouts.app title="Notifications">
    <div class="space-y-5">

        {{-- Flash messages --}}
        @if (session('status'))
            <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Notifications</h2>
                <p class="text-sm text-gray-500">
                    {{ auth()->user()->unreadNotifications()->count() }} unread &middot; {{ $notifications->total() }} total
                </p>
            </div>
            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        {{-- List --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                    class="flex items-start gap-3 border-b border-gray-100 px-4 py-4 last:border-b-0 hover:bg-gray-50
                        {{ $notification->read_at ? 'opacity-60' : 'bg-indigo-50/40' }}">
                    @csrf
                    @method('PATCH')

                    {{-- Unread dot --}}
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-indigo-500' }}"></span>

                    <button type="submit" class="flex-1 text-left">
                        <p class="text-sm font-medium text-gray-900">{{ $data['message'] ?? 'Notification' }}</p>
                        @if (! empty($data['notes']))
                            <p class="mt-0.5 text-sm text-gray-600">{{ $data['notes'] }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </button>
                </form>
            @empty
                <p class="px-4 py-12 text-center text-sm text-gray-400">You have no notifications.</p>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div>{{ $notifications->links() }}</div>
    </div>
</x-layouts.app>
