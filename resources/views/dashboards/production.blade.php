@extends('layouts.executive')
@section('title', 'Production Dashboard')
@section('page-title', 'Production & Manufacturing Dashboard')

@section('content')
@php $exportQuery = http_build_query(request()->only(['company', 'from_date', 'to_date'])); @endphp
<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('dashboard.export', ['pdf', 'production']) }}?{{ $exportQuery }}" class="btn-primary text-xs">Export PDF</a>
    <a href="{{ route('dashboard.export', ['csv', 'production']) }}?{{ $exportQuery }}" class="btn-primary text-xs opacity-80">Export CSV</a>
</div>
<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" :types="$data['kpi_types'] ?? []" />
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="prod-machine" title="Active Orders per Machine" />
    <x-chart-card id="prod-status" title="Work Order Status" />
    <x-chart-card id="prod-pva" title="Planned vs Produced Quantity" />
    <x-chart-card id="prod-profit" title="Profitability Index by Item" />
    <x-chart-card id="prod-daily" title="Daily Production Output" />
    <x-chart-card id="prod-monthly" title="Monthly Production Volume (6 Months)" />
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Current Orders on Machines" :columns="['Machine','Work Order','Item','Operation','Qty','Progress %','Status','Profitability %']" :rows="collect($data['tables']['current_orders_on_machines']??[])->map(fn($r)=>[$r['machine']??'',$r['work_order']??'',$r['item']??'',$r['operation']??'',$r['order_qty']??0,($r['progress_pct']??0).'%',$r['status']??'',$r['profitability_index']??0])->all()" />
    <x-data-table title="Active Work Orders" :columns="['WO','Item','Planned','Produced','Completion %','Status','Profitability %']" :rows="collect($data['tables']['active_work_orders']??[])->map(fn($r)=>[$r['name']??'',$r['item_name']??'',$r['qty']??0,$r['produced_qty']??0,($r['completion_pct']??0).'%',$r['status']??'',$r['profitability_index']??0])->all()" />
    <x-data-table title="Delayed Work Orders" :columns="['WO','Item','Due Date','Produced','Pending','Status']" :rows="collect($data['tables']['delayed_work_orders']??[])->map(fn($r)=>[$r['name']??'',$r['item_name']??'',$r['planned_end']??'',$r['produced_qty']??0,$r['pending_qty']??0,$r['status']??''])->all()" />
    <x-data-table title="Job Cards In Progress" :columns="['Job Card','Machine','Work Order','Qty','Completed','Status']" :rows="collect($data['tables']['job_cards_in_progress']??[])->map(fn($r)=>[$r['name']??'',$r['workstation']??'',$r['work_order']??'',$r['for_quantity']??0,$r['completed_qty']??0,$r['status']??''])->all()" />
    <x-data-table title="Workstation Load & Utilization" :columns="['Machine','Type','Active Orders','Load Qty','Utilization %','Hour Rate']" :rows="collect($data['tables']['workstation_load']??[])->map(fn($r)=>[$r['workstation']??'',$r['type']??'',$r['active_orders']??0,$r['load_qty']??0,($r['utilization_pct']??0).'%',$r['hour_rate']??0])->all()" />
    <x-data-table title="Production Plans" :columns="['Plan','Date','Planned Qty','Produced Qty','Status','Source']" :rows="collect($data['tables']['production_plans']??[])->map(fn($r)=>[$r['name']??'',$r['posting_date']??'',$r['planned_qty']??0,$r['produced_qty']??0,$r['status']??'',$r['source']??''])->all()" />
    <x-data-table title="Low Profitability Orders" :columns="['WO','Item','Revenue','Total Cost','Profitability %']" :rows="collect($data['tables']['low_profitability_orders']??[])->map(fn($r)=>[$r['name']??'',$r['item_name']??'',$r['revenue_proxy']??0,$r['total_cost']??0,$r['profitability_index']??0])->all()" />
    <x-data-table title="Top Producing Orders" :columns="['WO','Item','Produced Qty','Completion %','Profitability %']" :rows="collect($data['tables']['top_producing_orders']??[])->map(fn($r)=>[$r['name']??'',$r['item_name']??'',$r['produced_qty']??0,($r['completion_pct']??0).'%',$r['profitability_index']??0])->all()" />
</div>
<div class="mt-8">
    <x-data-table title="Manufacture Stock Entries" :columns="['Entry','Date','Work Order','FG Qty','Value']" :rows="collect($data['tables']['manufacture_entries']??[])->map(fn($r)=>[$r['name']??'',$r['posting_date']??'',$r['work_order']??'',$r['fg_qty']??0,$r['total_amount']??0])->all()" />
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const machine = @json($data['charts']['machine_utilization'] ?? []);
    initApexChart('prod-machine', {
        chart: { type: 'bar', height: 280 },
        series: [{ name: 'Active Orders', data: machine.series || [] }],
        xaxis: { categories: machine.labels || [] },
        colors: ['#0ea5e9'],
    });

    const status = @json($data['charts']['work_order_status'] ?? []);
    initApexChart('prod-status', {
        chart: { type: 'donut', height: 280 },
        series: status.series || [],
        labels: status.labels || [],
        colors: ['#22c55e', '#f59e0b', '#ef4444', '#94a3b8', '#8b5cf6'],
    });

    const pva = @json($data['charts']['planned_vs_actual'] ?? []);
    initApexChart('prod-pva', {
        chart: { type: 'bar', height: 280 },
        series: [
            { name: 'Planned', data: pva.planned || [] },
            { name: 'Produced', data: pva.actual || [] },
        ],
        xaxis: { categories: pva.labels || [] },
        colors: ['#6366f1', '#22c55e'],
    });

    const profit = @json($data['charts']['profitability_by_item'] ?? []);
    initApexChart('prod-profit', {
        chart: { type: 'bar', height: 280 },
        series: [{ name: 'Profitability %', data: profit.series || [] }],
        xaxis: { categories: profit.labels || [] },
        colors: ['#10b981'],
    });

    const daily = @json($data['charts']['daily_output'] ?? []);
    initApexChart('prod-daily', {
        chart: { type: 'area', height: 280 },
        series: [{ name: 'FG Qty', data: daily.series || [] }],
        xaxis: { categories: daily.labels || [] },
        colors: ['#06b6d4'],
    });

    const monthly = @json($data['charts']['monthly_production'] ?? []);
    initApexChart('prod-monthly', {
        chart: { type: 'line', height: 280 },
        series: [{ name: 'Production Qty', data: monthly.series || [] }],
        xaxis: { categories: monthly.labels || [] },
        colors: ['#a855f7'],
    });

    window.notifyDashboardReady?.();
});
</script>
@endpush
