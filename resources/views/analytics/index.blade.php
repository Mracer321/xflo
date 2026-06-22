<x-layouts.app title="Analytics">
    @php
        $user = auth()->user();
        $isAdmin = $user->hasAnyRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_LEADS_ADMIN]);

        $tones = [
            'indigo' => 'text-indigo-600',
            'amber'  => 'text-amber-600',
            'green'  => 'text-green-600',
            'blue'   => 'text-blue-600',
            'red'    => 'text-red-600',
            'purple' => 'text-purple-600',
        ];
        $chartColors = ['indigo', 'green', 'blue', 'amber', 'purple'];
        $periodLabels = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'];
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Analytics &amp; Productivity</h2>
                <p class="text-sm text-gray-500">{{ $user->role_label }} view</p>
            </div>
            @if ($isAdmin)
                <a href="{{ route('analytics.team') }}"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Team Performance
                </a>
            @endif
        </div>

        {{-- Role widgets (req 7) --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($widgets as $card)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $tones[$card['tone']] ?? 'text-gray-900' }}">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Period dashboards: Today / This Week / This Month (reqs 1–3) --}}
        @foreach ($metrics as $periodKey => $cards)
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                    {{ $periodLabels[$periodKey] ?? ucfirst($periodKey) }}
                </h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($cards as $label => $value)
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                            <p class="mt-1.5 text-2xl font-bold text-gray-900">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Trend charts (req 8) --}}
        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">Trends (last 14 days)</h3>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                @foreach ($charts as $title => $series)
                    <x-analytics.trend-chart :title="$title" :series="$series"
                        :color="$chartColors[$loop->index % count($chartColors)]" />
                @endforeach
            </div>
        </div>

        {{-- Leaderboards (admins only — req 4) --}}
        @if ($isAdmin)
            {{-- Period filter for leaderboards (req 5) --}}
            <form method="GET" action="{{ route('analytics.index') }}"
                class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Leaderboard period</label>
                    <select name="period" onchange="this.form.submit()"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'custom' => 'Custom Range'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['period'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Apply</button>
                <span class="ml-auto self-center text-xs text-gray-400">Showing: {{ $rangeLabel }}</span>
            </form>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {{-- Developer leaderboard --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">Developer Leaderboard</h3>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="py-2">#</th>
                                <th class="py-2">Developer</th>
                                <th class="py-2 text-right">Leads Worked</th>
                                <th class="py-2 text-right">Demo Ready</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($developerLeaderboard as $i => $row)
                                <tr>
                                    <td class="py-2 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="py-2 font-medium text-gray-800">{{ $row['name'] }}</td>
                                    <td class="py-2 text-right text-gray-700">{{ $row['leads_worked'] }}</td>
                                    <td class="py-2 text-right text-gray-700">{{ $row['demo_ready'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-400">No developers.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Sales leaderboard --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">Sales Leaderboard</h3>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="py-2">#</th>
                                <th class="py-2">Sales User</th>
                                <th class="py-2 text-right">Follow Ups</th>
                                <th class="py-2 text-right">Conversions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salesLeaderboard as $i => $row)
                                <tr>
                                    <td class="py-2 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="py-2 font-medium text-gray-800">{{ $row['name'] }}</td>
                                    <td class="py-2 text-right text-gray-700">{{ $row['follow_ups'] }}</td>
                                    <td class="py-2 text-right text-gray-700">{{ $row['conversions'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-400">No sales users.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
