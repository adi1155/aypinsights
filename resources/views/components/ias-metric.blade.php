@props(['label', 'value', 'currency' => 'PKR', 'suffix' => '', 'highlight' => false])

<div class="kpi-card {{ $highlight ? 'ring-1 ring-sky-500/40' : '' }}">
    <p class="kpi-label text-xs font-medium uppercase tracking-wider">{{ $label }}</p>
    <p class="kpi-value mt-2 text-xl font-bold lg:text-2xl">
        @if(is_numeric($value))
            {{ $currency }} {{ number_format((float) $value, 0) }}{{ $suffix }}
        @else
            {{ $value }}
        @endif
    </p>
</div>
