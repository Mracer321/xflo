<x-layouts.app title="System Status">
    <div class="space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-900">System Status</h2>
            <p class="mt-1 text-sm text-gray-500">Live health of the application's infrastructure.</p>
        </div>

        {{-- Health checks --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($checks as $name => $check)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium capitalize text-gray-500">{{ $name }}</p>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $check['ok'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $check['ok'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            {{ $check['ok'] ? 'OK' : 'Down' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">{{ $check['detail'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Operational metrics --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Environment & Queues</h3>
            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3 lg:grid-cols-4">
                @php
                    $labels = [
                        'app_env' => 'Environment',
                        'debug' => 'Debug mode',
                        'cache_store' => 'Cache store',
                        'queue_connection' => 'Queue',
                        'mail_mailer' => 'Mail',
                        'pending_jobs' => 'Pending jobs',
                        'failed_jobs' => 'Failed jobs',
                        'storage_free' => 'Storage free',
                    ];
                @endphp
                @foreach ($labels as $key => $label)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $metrics[$key] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if (($metrics['failed_jobs'] ?? 0) > 0)
                <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    There are failed queue jobs. Inspect with <code>php artisan queue:failed</code> and retry with
                    <code>php artisan queue:retry all</code>.
                </p>
            @endif
        </div>

        <p class="text-xs text-gray-400">
            JSON probe available at <a href="{{ url('/health') }}" class="underline">/health</a>.
        </p>
    </div>
</x-layouts.app>
