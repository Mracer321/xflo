<x-layouts.app title="New User">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to users</a>
            <h2 class="mt-1 text-xl font-bold text-gray-900">New User</h2>
        </div>

        @include('users._form', [
            'action'      => route('users.store'),
            'method'      => 'POST',
            'user'        => null,
            'roles'       => $roles,
            'submitLabel' => 'Create User',
        ])
    </div>
</x-layouts.app>
