@extends('layouts.executive')
@section('title', 'Expense Dashboard')
@section('page-title', 'Expense Monitoring Dashboard')

@section('content')
<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" />
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="exp-trend" title="Expense Trend (30 Days)" />
    <x-chart-card id="exp-dept" title="Department-wise Expense" />
    <x-chart-card id="exp-cc" title="Cost Center Analysis" />
    <x-chart-card id="exp-budget" title="Budget vs Actual" />
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Pending Approvals" :columns="['Claim','Employee','Amount','Status']" :rows="collect($data['tables']['pending_approvals']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['total_claimed_amount']??0,$r['approval_status']??''])->all()" />
    <x-data-table title="High Value Expenses" :columns="['Claim','Employee','Amount']" :rows="collect($data['tables']['high_value']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['total_claimed_amount']??0])->all()" />
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($data['charts']['expense_trend'] ?? []);
    initApexChart('exp-trend', { chart: { type: 'area', height: 280 }, series: [{ data: trend.series||[] }], xaxis: { categories: trend.labels||[] }, colors: ['#ef4444'] });
    const dept = @json($data['charts']['department_wise'] ?? []);
    initApexChart('exp-dept', { chart: { type: 'pie', height: 280 }, series: dept.series||[], labels: dept.labels||[] });
    const cc = @json($data['charts']['cost_center'] ?? []);
    initApexChart('exp-cc', { chart: { type: 'bar', height: 280 }, series: [{ data: cc.series||[] }], xaxis: { categories: cc.labels||[] } });
    const bva = @json($data['charts']['budget_vs_actual'] ?? []);
    initApexChart('exp-budget', { chart: { type: 'bar', height: 280 }, series: [{ name: 'Budget', data: bva.budget||[] }, { name: 'Actual', data: bva.actual||[] }], xaxis: { categories: bva.labels||[] } });
    window.notifyDashboardReady?.();
});
</script>
@endpush
