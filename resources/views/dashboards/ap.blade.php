@extends('layouts.executive')
@section('title', 'AP Dashboard')
@section('page-title', 'Accounts Payable — Executive Dashboard')

@section('content')
<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" />
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="ap-aging" title="AP Aging Analysis" />
    <x-chart-card id="ap-supplier" title="Supplier-wise Outstanding" />
    <x-chart-card id="ap-monthly" title="Monthly Payable Trend" />
    <x-chart-card id="ap-due-paid" title="Due vs Paid" type="donut" />
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Overdue Suppliers" :columns="['Supplier','Outstanding','Due']" :rows="collect($data['tables']['overdue_suppliers']??[])->map(fn($r)=>[$r['supplier']??'',$r['outstanding']??0,$r['due_date']??''])->all()" />
    <x-data-table title="Unpaid Purchase Invoices" :columns="['Invoice','Supplier','Amount']" :rows="collect($data['tables']['unpaid_invoices']??[])->map(fn($r)=>[$r['name']??'',$r['supplier']??'',$r['outstanding_amount']??0])->all()" />
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const aging = @json($data['charts']['aging'] ?? []);
    initApexChart('ap-aging', { chart: { type: 'bar', height: 280 }, series: [{ data: aging.series||[] }], xaxis: { categories: aging.labels||[] }, colors: ['#f59e0b'] });
    const sup = @json($data['charts']['supplier_wise'] ?? []);
    initApexChart('ap-supplier', { chart: { type: 'bar', height: 280 }, series: [{ data: sup.series||[] }], xaxis: { categories: sup.labels||[] }, plotOptions: { bar: { horizontal: true } } });
    const mon = @json($data['charts']['monthly_payable'] ?? []);
    initApexChart('ap-monthly', { chart: { type: 'area', height: 280 }, series: [{ data: mon.series||[] }], xaxis: { categories: mon.labels||[] }, colors: ['#8b5cf6'] });
    const dp = @json($data['charts']['due_vs_paid'] ?? []);
    initApexChart('ap-due-paid', { chart: { type: 'donut', height: 280 }, series: dp.series||[], labels: dp.labels||[] });
    window.notifyDashboardReady?.();
});
</script>
@endpush
