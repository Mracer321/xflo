<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md">
            {{-- Brand --}}
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900">XFlow</h1>
                <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
            </div>

            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-8">
                {{-- Session status (e.g. after logout) --}}
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input id="email" name="email" type="email"
                            value="{{ old('email') }}"
                            required autofocus autocomplete="username"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <input id="password" name="password" type="password"
                            required autocomplete="current-password"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                        Sign in
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} XFlow CRM
            </p>
        </div>
    </div>
</x-guest-layout>
