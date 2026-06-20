@props(['status' => null, 'label' => null])

@php
    $map = [
        'live'    => 'bg-green-50 text-green-700',
        'offline' => 'bg-amber-50 text-amber-700',
        'deleted' => 'bg-red-50 text-red-700',
    ];
    $dot = [
        'live'    => 'bg-green-500',
        'offline' => 'bg-amber-500',
        'deleted' => 'bg-red-500',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-700';
    $dotClass = $dot[$status] ?? 'bg-gray-400';
    $text = $label ?? (\App\Models\Lead::DEMO_STATUSES[$status] ?? $status);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
    {{ $text }}
</span>
