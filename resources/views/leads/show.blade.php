<x-layouts.app title="Lead Details">
    @php
        $user = auth()->user();
        $canManage = $user->hasAnyRole(['super_admin', 'leads_admin', 'sales']);
        $canAssign = $user->hasAnyRole(['super_admin', 'leads_admin']);
        $task = $lead->developerTask;
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">

        {{-- Back + flash --}}
        <div>
            <a href="{{ route('leads.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to leads</a>
        </div>
        @if (session('status'))
            <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $lead->business_name }}</h2>
                <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                    @if ($lead->owner_name)<span>{{ $lead->owner_name }}</span>@endif
                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                        {{ $lead->status_label }}
                    </span>
                </div>
            </div>
            @if ($canManage)
                <a href="{{ route('leads.edit', $lead) }}"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Edit Lead
                </a>
            @endif
        </div>

        {{-- Business information --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">Business Information</h3>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ([
                    'Owner Name' => $lead->owner_name,
                    'Category' => $lead->category,
                    'Email' => $lead->email,
                    'Mobile Number' => $lead->mobile_number,
                    'WhatsApp Number' => $lead->whatsapp_number,
                    'Website Exists' => $lead->website_exists ? 'Yes' : 'No',
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm text-gray-800">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Address</dt>
                    <dd class="mt-0.5 text-sm text-gray-800">{{ $lead->address ?: '—' }}</dd>
                </div>
                @if ($lead->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Notes</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-gray-800">{{ $lead->notes }}</dd>
                    </div>
                @endif
            </dl>

            {{-- Social / web links --}}
            <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                @foreach ([
                    'Google Business' => $lead->google_business_url,
                    'Facebook' => $lead->facebook_url,
                    'Instagram' => $lead->instagram_url,
                ] as $label => $url)
                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">
                            {{ $label }}
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Assets (grouped by type) --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">Files &amp; Assets</h3>

            <div class="space-y-6">
                @foreach ($assetTypes as $typeKey => $typeLabel)
                    @php $items = $assetsByType->get($typeKey, collect()); @endphp
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ $typeLabel }} <span class="text-gray-400">({{ $items->count() }})</span>
                            </h4>
                        </div>

                        @if ($items->isEmpty())
                            <p class="text-sm text-gray-400">No files.</p>
                        @else
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($items as $asset)
                                    <div class="group relative rounded-lg border border-gray-200 p-2">
                                        {{-- Preview --}}
                                        @if ($asset->is_image)
                                            <a href="{{ $asset->url }}" target="_blank" rel="noopener">
                                                <img src="{{ $asset->url }}" alt="{{ $asset->file_name }}"
                                                    class="h-28 w-full rounded object-cover">
                                            </a>
                                        @else
                                            <a href="{{ $asset->url }}" target="_blank" rel="noopener"
                                                class="flex h-28 w-full items-center justify-center rounded bg-gray-50">
                                                <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </a>
                                        @endif

                                        <p class="mt-1.5 truncate text-xs text-gray-700" title="{{ $asset->file_name }}">
                                            {{ $asset->file_name }}
                                        </p>

                                        {{-- Actions --}}
                                        <div class="mt-1 flex items-center gap-2">
                                            <a href="{{ route('assets.download', $asset) }}"
                                                class="text-xs font-medium text-indigo-600 hover:underline">Download</a>
                                            <form method="POST" action="{{ route('assets.destroy', $asset) }}"
                                                onsubmit="return confirm('Delete this file?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Upload form for this type --}}
                        <form method="POST" action="{{ route('leads.assets.store', $lead) }}"
                            enctype="multipart/form-data" class="mt-3 flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="file_type" value="{{ $typeKey }}">
                            <input type="file" name="files[]" multiple required
                                class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                            <button type="submit"
                                class="shrink-0 rounded-md bg-gray-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-900">
                                Upload
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            @error('files') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('files.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Developer assignment + workflow --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">Developer Workflow</h3>

            {{-- Assignment --}}
            <div class="mb-5">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Assigned Developer</dt>
                <dd class="mt-0.5 text-sm text-gray-800">{{ $task?->developer?->name ?? 'Not assigned' }}</dd>

                @if ($canAssign)
                    <form method="POST" action="{{ route('leads.developer-task.store', $lead) }}"
                        class="mt-2 flex items-center gap-2">
                        @csrf
                        <select name="developer_id" required
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                            <option value="">Select developer…</option>
                            @foreach ($developers as $developer)
                                <option value="{{ $developer->id }}" @selected($task?->developer_id === $developer->id)>
                                    {{ $developer->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-900">
                            {{ $task?->developer_id ? 'Reassign' : 'Assign' }}
                        </button>
                    </form>
                    @error('developer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>

            {{-- Workflow form (only once a developer is assigned) --}}
            @if ($task && $task->developer_id)
                <form method="POST" action="{{ route('developer-tasks.update', $task) }}"
                    x-data="{ status: '{{ old('status', $task->status) }}', reasonStatuses: @js(array_values($reasonStatuses)) }"
                    class="space-y-4 border-t border-gray-100 pt-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" name="status" x-model="status"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                @foreach ($developerStatuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Demo URL --}}
                        <div>
                            <label for="demo_url" class="block text-sm font-medium text-gray-700 mb-1">Demo URL</label>
                            <input id="demo_url" name="demo_url" type="url" placeholder="https://…"
                                value="{{ old('demo_url', $task->demo_url) }}"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                            @error('demo_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Deployment platform --}}
                        <div>
                            <label for="deployment_platform" class="block text-sm font-medium text-gray-700 mb-1">Deployment Platform</label>
                            <select id="deployment_platform" name="deployment_platform"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                <option value="">—</option>
                                @foreach ($platforms as $key => $label)
                                    <option value="{{ $key }}" @selected(old('deployment_platform', $task->deployment_platform) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('deployment_platform') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Deployment date --}}
                        <div>
                            <label for="deployment_date" class="block text-sm font-medium text-gray-700 mb-1">Deployment Date</label>
                            <input id="deployment_date" name="deployment_date" type="date"
                                value="{{ old('deployment_date', $task->deployment_date?->format('Y-m-d')) }}"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                            @error('deployment_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="dev_notes" class="block text-sm font-medium text-gray-700 mb-1">Developer Notes</label>
                        <textarea id="dev_notes" name="notes" rows="3"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">{{ old('notes', $task->notes) }}</textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Reason (required for offline/deleted) --}}
                    <div x-show="reasonStatuses.includes(status)" x-cloak>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea id="reason" name="reason" rows="2"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">{{ old('reason', $task->reason) }}</textarea>
                        <p class="mt-1 text-xs text-gray-400">Required when marking Offline or Deleted.</p>
                        @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Update Workflow
                        </button>
                    </div>
                </form>
            @else
                <p class="border-t border-gray-100 pt-5 text-sm text-gray-400">
                    Assign a developer to begin the workflow.
                </p>
            @endif
        </div>
    </div>
</x-layouts.app>
