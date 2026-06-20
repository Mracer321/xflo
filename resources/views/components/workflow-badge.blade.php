@props(['status' => null, 'label' => null])

@php
    $map = [
        'new_lead'         => 'bg-gray-100 text-gray-700',
        'assigned'         => 'bg-blue-50 text-blue-700',
        'demo_in_progress' => 'bg-amber-50 text-amber-700',
        'demo_ready'       => 'bg-purple-50 text-purple-700',
        'demo_sent'        => 'bg-indigo-50 text-indigo-700',
        'follow_up'        => 'bg-orange-50 text-orange-700',
        'converted'        => 'bg-green-50 text-green-700',
        'rejected'         => 'bg-red-50 text-red-700',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-700';
    $text = $label ?? (\App\Models\Lead::WORKFLOW_STATUSES[$status] ?? $status);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $text }}
</span>
