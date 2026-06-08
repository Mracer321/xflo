<x-layouts.app title="Dashboard">
    <div class="space-y-6">

        {{-- Welcome --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                Welcome back, {{ auth()->user()->name }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Signed in as <span class="font-medium text-gray-700">{{ auth()->user()->role_label }}</span>
            </p>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Total Leads', 'value' => '—', 'hint' => 'No data yet'],
                ['label' => 'Open Deals', 'value' => '—', 'hint' => 'No data yet'],
                ['label' => 'Won This Month', 'value' => '—', 'hint' => 'No data yet'],
                ['label' => 'Active Users', 'value' => '—', 'hint' => 'No data yet'],
            ] as $card)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Placeholder panel --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Getting started</h3>
            <p class="mt-2 text-sm text-gray-600">
                Your XFlow workspace is ready. Modules like Leads and Users will appear here as they are built.
            </p>
        </div>
    </div>
</x-layouts.app>
