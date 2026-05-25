<x-mail::message>
# AYP Executive Report: {{ ucwords(str_replace('_', ' ', $reportType)) }}

Your scheduled financial summary for **{{ now()->format('l, F j, Y') }}** is ready.

@foreach(($data['summary'] ?? $data['kpis'] ?? []) as $key => $value)
- **{{ ucwords(str_replace('_', ' ', $key)) }}:** {{ is_numeric($value) ? number_format($value, 0) : $value }}
@endforeach

<x-mail::button :url="config('app.url')">
Open Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
