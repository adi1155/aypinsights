<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{{ $title }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}th{background:#0ea5e9;color:#fff}</style>
</head>
<body>
<h1>{{ $title }}</h1>
<p>Generated: {{ now()->format('Y-m-d H:i') }} | Company: {{ $filters['company'] ?? 'All' }}</p>
<h2>KPI Summary</h2>
<table><tr><th>Metric</th><th>Value</th></tr>
@foreach(($data['kpis'] ?? $data['summary'] ?? []) as $k => $v)
<tr><td>{{ ucwords(str_replace('_',' ',$k)) }}</td><td>{{ is_numeric($v) ? number_format($v,0) : $v }}</td></tr>
@endforeach
</table>
</body>
</html>
