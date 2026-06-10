<x-layouts.app title="Edit User">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to users</a>
            <h2 class="mt-1 text-xl font-bold text-gray-900">Edit User</h2>
            <p class="text-sm text-gray-500">{{ $user->name }}</p>
        </div>

        @include('users._form', [
            'action'      => route('users.update', $user),
            'method'      => 'PUT',
            'user'        => $user,
            'roles'       => $roles,
            'submitLabel' => 'Update User',
        ])
    </div>
</x-layouts.app>
