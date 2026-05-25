@props(['awaitCharts' => null])

@php
    $awaitCharts = $awaitCharts ?? request()->routeIs('dashboard.*');
@endphp
<script>
    window.__PAGE_PROGRESS_START = Date.now();
    window.__DASHBOARD_AWAITING_CHARTS = @json($awaitCharts);
</script>
