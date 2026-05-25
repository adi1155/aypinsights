@props(['kpis' => [], 'currency' => 'PKR'])

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach($kpis as $label => $value)
        <div class="kpi-card animate-fade-in">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">{{ str_replace('_', ' ', $label) }}</p>
            <p class="mt-2 text-xl font-bold text-white lg:text-2xl">
                @if(is_numeric($value))
                    {{ $currency }} {{ number_format((float) $value, 0) }}
                @else
                    {{ $value }}
                @endif
            </p>
        </div>
    @endforeach
</div>
