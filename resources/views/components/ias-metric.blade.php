@props(['label', 'value', 'currency' => 'PKR', 'suffix' => '', 'highlight' => false])

<div class="kpi-card {{ $highlight ? 'ring-1 ring-sky-500/40' : '' }}">
    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">{{ $label }}</p>
    <p class="mt-2 text-xl font-bold text-white lg:text-2xl">
        @if(is_numeric($value))
            {{ $currency }} {{ number_format((float) $value, 0) }}{{ $suffix }}
        @else
            {{ $value }}
        @endif
    </p>
</div>
