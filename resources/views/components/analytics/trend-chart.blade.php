@props([
    'title' => 'Trend',
    'series' => [],      // [['date'=>, 'label'=>, 'value'=>int], ...]
    'color' => 'indigo', // tailwind colour base
])

@php
    $bar = [
        'indigo' => 'bg-indigo-500',
        'green' => 'bg-green-500',
        'blue' => 'bg-blue-500',
        'amber' => 'bg-amber-500',
        'purple' => 'bg-purple-500',
    ][$color] ?? 'bg-indigo-500';

    $values = array_map(fn ($p) => $p['value'], $series);
    $max = max(1, ...($values ?: [0]));
    $total = array_sum($values);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5']) }}>
    <div class="mb-3 flex items-baseline justify-between">
        <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
        <span class="text-xs text-gray-400">{{ $total }} total · last {{ count($series) }} days</span>
    </div>

    @if ($total === 0)
        <p class="py-8 text-center text-sm text-gray-400">No activity in this window.</p>
    @else
        <div class="flex h-32 items-end gap-1" role="img" aria-label="{{ $title }}">
            @foreach ($series as $point)
                @php $h = (int) round($point['value'] / $max * 100); @endphp
                <div class="group relative flex flex-1 items-end" style="height:100%">
                    <div class="w-full rounded-t {{ $point['value'] > 0 ? $bar : 'bg-gray-100' }}"
                        style="height: {{ max($h, $point['value'] > 0 ? 4 : 1) }}%"
                        title="{{ $point['label'] }}: {{ $point['value'] }}"></div>
                    {{-- Hover tooltip --}}
                    <span class="pointer-events-none absolute -top-6 left-1/2 z-10 hidden -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-1.5 py-0.5 text-[10px] text-white group-hover:block">
                        {{ $point['label'] }}: {{ $point['value'] }}
                    </span>
                </div>
            @endforeach
        </div>
        {{-- Sparse x-axis labels (first / middle / last) --}}
        <div class="mt-2 flex justify-between text-[10px] text-gray-400">
            <span>{{ $series[0]['label'] ?? '' }}</span>
            <span>{{ $series[intdiv(count($series), 2)]['label'] ?? '' }}</span>
            <span>{{ $series[count($series) - 1]['label'] ?? '' }}</span>
        </div>
    @endif
</div>
