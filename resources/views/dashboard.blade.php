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

        {{-- Role-aware workflow widgets --}}
        @php
            $tones = [
                'indigo' => 'text-indigo-600',
                'amber'  => 'text-amber-600',
                'green'  => 'text-green-600',
                'blue'   => 'text-blue-600',
                'red'    => 'text-red-600',
            ];
        @endphp
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($widgets as $card)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $tones[$card['tone']] ?? 'text-gray-900' }}">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Quick link --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Demo Workflow</h3>
            <p class="mt-2 text-sm text-gray-600">
                Track each lead from assignment through demo creation, sending, follow-up and final result.
            </p>
            <a href="{{ route('leads.index') }}"
                class="mt-3 inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Go to Leads
            </a>
        </div>
    </div>
</x-layouts.app>
