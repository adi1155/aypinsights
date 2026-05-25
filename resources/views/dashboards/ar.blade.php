@extends('layouts.executive')
@section('title', 'AR Dashboard')
@section('page-title', 'Accounts Receivable — Executive Dashboard')

@section('content')
<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" />
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="ar-aging" title="AR Aging" />
    <x-chart-card id="ar-collection" title="Collection Trend" />
    <x-chart-card id="ar-customer" title="Customer-wise Outstanding" />
    <x-chart-card id="ar-branch" title="Branch Recovery %" />
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Overdue Customers" :columns="['Customer','Outstanding','Due']" :rows="collect($data['tables']['overdue_customers']??[])->map(fn($r)=>[$r['customer']??'',$r['outstanding']??0,$r['due_date']??''])->all()" />
    <x-data-table title="Today's Collections" :columns="['Ref','Party','Amount']" :rows="collect($data['tables']['todays_collections']??[])->map(fn($r)=>[$r['name']??'',$r['party']??'',$r['paid_amount']??0])->all()" />
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const aging = @json($data['charts']['aging'] ?? []);
    initApexChart('ar-aging', { chart: { type: 'bar', height: 280 }, series: [{ data: aging.series||[] }], xaxis: { categories: aging.labels||[] }, colors: ['#0ea5e9'] });
    const col = @json($data['charts']['collection_trend'] ?? []);
    initApexChart('ar-collection', { chart: { type: 'line', height: 280 }, series: [{ data: col.series||[] }], xaxis: { categories: col.labels||[] }, colors: ['#10b981'] });
    const cust = @json($data['charts']['customer_wise'] ?? []);
    initApexChart('ar-customer', { chart: { type: 'bar', height: 280 }, series: [{ data: cust.series||[] }], xaxis: { categories: cust.labels||[] }, plotOptions: { bar: { horizontal: true } } });
    const br = @json($data['charts']['branch_recovery'] ?? []);
    initApexChart('ar-branch', { chart: { type: 'radialBar', height: 280 }, series: br.series||[], labels: br.labels||[] });
    window.notifyDashboardReady?.();
});
</script>
@endpush
