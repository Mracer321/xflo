<x-layouts.app title="Edit Lead">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <a href="{{ route('leads.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to leads</a>
            <h2 class="mt-1 text-xl font-bold text-gray-900">Edit Lead</h2>
            <p class="text-sm text-gray-500">{{ $lead->business_name }}</p>
        </div>

        @include('leads._form', [
            'action'      => route('leads.update', $lead),
            'method'      => 'PUT',
            'lead'        => $lead,
            'statuses'    => $statuses,
            'submitLabel' => 'Update Lead',
        ])
    </div>
</x-layouts.app>
