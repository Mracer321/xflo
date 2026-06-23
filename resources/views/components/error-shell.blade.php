@props(['code', 'title', 'message'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} &middot; XFlow CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <p class="text-6xl font-extrabold text-indigo-600">{{ $code }}</p>
            <h1 class="mt-4 text-xl font-semibold text-gray-900">{{ $title }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>

            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Go home
                </a>
                <a href="javascript:history.back()"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Go back
                </a>
            </div>
        </div>
    </div>
</body>
</html>
