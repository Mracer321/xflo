<x-layouts.app title="Leads">
    @php
        $canManage = auth()->user()->hasAnyRole(['super_admin', 'leads_admin', 'sales']);
    @endphp
    <div class="space-y-5"
        x-data="{
            selected: [],
            get allOnPage() {
                return @js($leads->pluck('id')->map(fn ($id) => (string) $id)->all());
            },
            get allSelected() {
                return this.allOnPage.length > 0 && this.selected.length === this.allOnPage.length;
            },
            toggleAll(checked) {
                this.selected = checked ? [...this.allOnPage] : [];
            },
        }">

        {{-- Flash message --}}
        @if (session('status'))
            <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Leads</h2>
                <p class="text-sm text-gray-500">{{ $leads->total() }} total</p>
            </div>
            @if ($canManage)
                <a href="{{ route('leads.create') }}"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Lead
                </a>
            @endif
        </div>

        {{-- Filter / search bar — fields shown are scoped to the user's role
             (see LeadController::allowedFilters); $visibleFilters is the
             authoritative list and is also enforced in the query. --}}
        <form method="GET" action="{{ route('leads.index') }}"
            class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-12">

            {{-- Search (all roles) --}}
            <div class="lg:col-span-4">
                <input type="text" name="search" value="{{ $filters['search'] }}"
                    placeholder="Search name, owner, email, phone, category…"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
            </div>

            {{-- Legacy pipeline status (Super Admin) --}}
            @if (in_array('status', $visibleFilters))
                <div class="lg:col-span-3">
                    <select name="status"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Website (Super Admin) --}}
            @if (in_array('website', $visibleFilters))
                <div class="lg:col-span-2">
                    <select name="website"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Website: any</option>
                        <option value="1" @selected($filters['website'] === '1')>Has website</option>
                        <option value="0" @selected($filters['website'] === '0')>No website</option>
                    </select>
                </div>
            @endif

            {{-- Workflow status (all roles) --}}
            <div class="lg:col-span-3">
                <select name="workflow_status"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All workflow stages</option>
                    @foreach ($workflowStatuses as $key => $label)
                        <option value="{{ $key }}" @selected($filters['workflow_status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Demo status (all roles) --}}
            <div class="lg:col-span-3">
                <select name="demo_status"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All demo statuses</option>
                    @foreach ($demoStatuses as $key => $label)
                        <option value="{{ $key }}" @selected($filters['demo_status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Assigned developer (Super Admin, Leads Admin) --}}
            @if (in_array('developer_id', $visibleFilters))
                <div class="lg:col-span-3">
                    <select name="developer_id"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">All developers</option>
                        @foreach ($developers as $developer)
                            <option value="{{ $developer->id }}" @selected((string) $filters['developer_id'] === (string) $developer->id)>{{ $developer->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Created by (Super Admin) --}}
            @if (in_array('created_by', $visibleFilters))
                <div class="lg:col-span-3">
                    <select name="created_by"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">All creators</option>
                        @foreach ($creators as $creator)
                            <option value="{{ $creator->id }}" @selected((string) $filters['created_by'] === (string) $creator->id)>{{ $creator->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Date range (all roles) --}}
            <div class="lg:col-span-3">
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" title="Created from"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div class="lg:col-span-3">
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" title="Created to"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
            </div>

            {{-- Actions (all roles) --}}
            <div class="flex gap-2 lg:col-span-2">
                <button type="submit"
                    class="flex-1 rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                    Filter
                </button>
                <a href="{{ route('leads.index') }}"
                    class="flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Clear
                </a>
            </div>
        </form>

        {{-- Bulk action bar (shown when rows are selected) --}}
        @if ($canManage)
            <form method="POST" action="{{ route('leads.bulk-destroy') }}"
                x-show="selected.length > 0"
                @submit="if (! confirm('Delete ' + selected.length + ' selected lead(s)?')) $event.preventDefault()"
                class="flex items-center justify-between rounded-md bg-red-50 px-4 py-2"
                style="display: none;">
                @csrf
                @method('DELETE')
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <span class="text-sm text-red-700">
                    <span x-text="selected.length"></span> selected
                </span>
                <button type="submit"
                    class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700">
                    Delete selected
                </button>
            </form>
        @endif

        {{-- Table --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        @if ($canManage)
                            <th class="px-4 py-3">
                                <input type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleAll($event.target.checked)"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                        @endif
                        <th class="px-4 py-3">Business</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Workflow</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-gray-50">
                            {{-- Bulk select --}}
                            @if ($canManage)
                                <td class="px-4 py-3">
                                    <input type="checkbox" x-model="selected" value="{{ $lead->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                            @endif

                            {{-- Business --}}
                            <td class="px-4 py-3">
                                <a href="{{ route('leads.show', $lead) }}"
                                    class="font-medium text-gray-900 hover:text-indigo-600 hover:underline">
                                    {{ $lead->business_name }}
                                </a>
                                @if ($lead->owner_name)
                                    <div class="text-xs text-gray-500">{{ $lead->owner_name }}</div>
                                @endif
                            </td>

                            {{-- Contact (with copy + WhatsApp) --}}
                            <td class="px-4 py-3">
                                @if ($lead->mobile_number)
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-gray-700">{{ $lead->mobile_number }}</span>

                                        {{-- Copy number --}}
                                        <span x-data="{ copied: false }" class="relative">
                                            <button type="button"
                                                @click="navigator.clipboard.writeText('{{ $lead->mobile_number }}'); copied = true; setTimeout(() => copied = false, 1200)"
                                                title="Copy number"
                                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                            <span x-show="copied" x-transition style="display:none;"
                                                class="absolute -top-7 left-1/2 -translate-x-1/2 rounded bg-gray-800 px-2 py-0.5 text-xs text-white">
                                                Copied
                                            </span>
                                        </span>
                                    </div>
                                @endif

                                @php
                                    $wa = preg_replace('/\D/', '', (string) ($lead->whatsapp_number ?: $lead->mobile_number));
                                @endphp
                                @if ($wa)
                                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
                                        class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-green-600 hover:text-green-700">
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.945C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.515 5.26l-.999 3.648 3.683-.957z" />
                                        </svg>
                                        WhatsApp
                                    </a>
                                @endif

                                @if (! $lead->mobile_number && ! $wa)
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Category --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $lead->category ?: '—' }}
                            </td>

                            {{-- Workflow --}}
                            <td class="px-4 py-3">
                                <x-workflow-badge :status="$lead->workflow_status" />
                                @if ($lead->developer)
                                    <div class="mt-1 text-xs text-gray-400">{{ $lead->developer->name }}</div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                    {{ $lead->status_label }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('leads.show', $lead) }}"
                                        class="rounded-md px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                        View
                                    </a>
                                    @if ($canManage)
                                        <a href="{{ route('leads.edit', $lead) }}"
                                            class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('leads.destroy', $lead) }}"
                                            onsubmit="return confirm('Delete this lead?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-10 text-center text-sm text-gray-500">
                                No leads found.
                                @if ($canManage)
                                    <a href="{{ route('leads.create') }}" class="font-medium text-indigo-600 hover:underline">Create one</a>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $leads->links() }}
        </div>
    </div>
</x-layouts.app>
