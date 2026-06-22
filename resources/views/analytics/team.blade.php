<x-layouts.app title="Team Performance">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Team Performance</h2>
                <p class="text-sm text-gray-500">Showing: {{ $rangeLabel }}</p>
            </div>
            <a href="{{ route('analytics.index') }}"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                &larr; Back to Analytics
            </a>
        </div>

        {{-- Period filter (req 5) --}}
        <form method="GET" action="{{ route('analytics.team') }}"
            class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Period</label>
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
        </form>

        {{-- Developer table (req 6) --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-3">
                <h3 class="text-sm font-semibold text-gray-900">Developers</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3 text-right">Assigned Leads</th>
                        <th class="px-5 py-3 text-right">Leads Worked</th>
                        <th class="px-5 py-3 text-right">Demo Ready</th>
                        <th class="px-5 py-3 text-right">Converted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($developers as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['assigned'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['leads_worked'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['demo_ready'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['converted'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-6 text-center text-gray-400">No developers.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Sales table (req 6) --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-3">
                <h3 class="text-sm font-semibold text-gray-900">Sales</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3 text-right">Follow Ups</th>
                        <th class="px-5 py-3 text-right">Demo Sent</th>
                        <th class="px-5 py-3 text-right">Conversions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sales as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['follow_ups'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['demo_sent'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $row['conversions'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-gray-400">No sales users.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
