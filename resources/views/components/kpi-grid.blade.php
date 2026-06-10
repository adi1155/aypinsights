@props(['kpis' => [], 'currency' => 'PKR', 'types' => []])

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach($kpis as $label => $value)
        @php $type = $types[$label] ?? 'currency'; @endphp
        <div class="kpi-card animate-fade-in">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">{{ str_replace('_', ' ', $label) }}</p>
            <p class="mt-2 text-xl font-bold text-white lg:text-2xl">
                @if($type === 'count')
                    {{ number_format((float) $value, 0) }}
                @elseif($type === 'decimal')
                    {{ number_format((float) $value, 1) }}
                @elseif($type === 'percent')
                    {{ number_format((float) $value, 1) }}%
                @elseif(is_numeric($value))
                    {{ $currency }} {{ number_format((float) $value, 0) }}
                @else
                    {{ $value }}
                @endif
            </p>
        </div>
    @endforeach
</div>
