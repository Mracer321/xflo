{{--
    Shared user form. Expects:
      $action      — form action URL
      $method      — 'POST' (create) or 'PUT' (update)
      $user        — existing User model, or null when creating
      $roles       — role key => label map
      $submitLabel — submit button text
--}}
@php($user = $user ?? null)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            Please correct the highlighted fields below.
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Role --}}
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="role" name="role"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('role') border-red-500 @enderror">
                    @foreach ($roles as $key => $label)
                        <option value="{{ $key }}" @selected(old('role', $user->role ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <label class="inline-flex items-center gap-2 mt-2">
                    <input type="checkbox" name="is_active" value="1"
                        @checked(old('is_active', $user->is_active ?? true))
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password @if (! $user)<span class="text-red-500">*</span>@endif
                </label>
                <input id="password" name="password" type="password" autocomplete="new-password"
                    @if (! $user) required @endif
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('password') border-red-500 @enderror">
                @if ($user)
                    <p class="mt-1 text-xs text-gray-400">Leave blank to keep the current password.</p>
                @endif
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm Password
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                    @if (! $user) required @endif
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('users.index') }}"
            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ $submitLabel }}
        </button>
    </div>
</form>
