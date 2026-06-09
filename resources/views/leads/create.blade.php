<x-layouts.app title="New Lead">
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <a href="{{ route('leads.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to leads</a>
            <h2 class="mt-1 text-xl font-bold text-gray-900">New Lead</h2>
        </div>

        @include('leads._form', [
            'action'      => route('leads.store'),
            'method'      => 'POST',
            'lead'        => null,
            'statuses'    => $statuses,
            'submitLabel' => 'Create Lead',
        ])
    </div>
</x-layouts.app>
