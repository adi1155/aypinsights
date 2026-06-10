@extends('layouts.executive')
@section('title', 'Payroll Dashboard')
@section('page-title', 'Payroll & Compensation Dashboard')

@section('content')
@php $exportQuery = http_build_query(request()->only(['company', 'from_date', 'to_date'])); @endphp
<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('dashboard.export', ['pdf', 'payroll']) }}?{{ $exportQuery }}" class="btn-primary text-xs">Export PDF</a>
    <a href="{{ route('dashboard.export', ['csv', 'payroll']) }}?{{ $exportQuery }}" class="btn-primary text-xs opacity-80">Export CSV</a>
</div>
<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" :types="$data['kpi_types'] ?? []" />
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="payroll-trend" title="Monthly Payroll Trend (6 Months)" />
    <x-chart-card id="payroll-dept" title="Department-wise Net Payroll" />
    <x-chart-card id="payroll-cc" title="Cost Center Payroll" />
    <x-chart-card id="payroll-gvn" title="Gross vs Deductions vs Net" />
    <x-chart-card id="payroll-comp" title="Payroll Composition" />
    <x-chart-card id="payroll-disb" title="Bank Disbursement Trend" />
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Pending Salary Slips" :columns="['Slip','Employee','Department','Gross','Status']" :rows="collect($data['tables']['pending_salary_slips']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['department']??'',$r['gross_pay']??0,$r['status']??''])->all()" />
    <x-data-table title="Recent Payroll Runs" :columns="['Entry','Period','Employees','Status']" :rows="collect($data['tables']['recent_payroll_runs']??[])->map(fn($r)=>[$r['name']??'',($r['start_date']??'').' — '.($r['end_date']??''),$r['employee_count']??0,$r['status']??''])->all()" />
    <x-data-table title="Top Earners (Net Pay)" :columns="['Employee','Department','Net Pay','Deductions']" :rows="collect($data['tables']['top_earners']??[])->map(fn($r)=>[$r['employee']??'',$r['department']??'',$r['net_pay']??0,$r['total_deduction']??0])->all()" />
    <x-data-table title="Outstanding Employee Advances" :columns="['Advance','Employee','Outstanding','Status']" :rows="collect($data['tables']['unpaid_advances']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['outstanding']??0,$r['status']??''])->all()" />
    <x-data-table title="Additional Salary & Bonuses" :columns="['Ref','Employee','Component','Amount']" :rows="collect($data['tables']['additional_salary_items']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['salary_component']??'',$r['amount']??0])->all()" />
    <x-data-table title="Salary Disbursements" :columns="['Payment','Employee','Amount','Date','Mode']" :rows="collect($data['tables']['payment_history']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['paid_amount']??0,$r['posting_date']??'',$r['mode_of_payment']??''])->all()" />
</div>
<div class="mt-8">
    <x-data-table title="Department Headcount & Tenure" :columns="['Department','Headcount','Avg Tenure (months)']" :rows="collect($data['tables']['department_headcount']??[])->map(fn($r)=>[$r['department']??'',$r['headcount']??0,$r['avg_tenure_months']??0])->all()" />
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($data['charts']['monthly_payroll_trend'] ?? []);
    initApexChart('payroll-trend', {
        chart: { type: 'area', height: 280 },
        series: [
            { name: 'Gross', data: trend.gross || [] },
            { name: 'Net', data: trend.net || [] },
        ],
        xaxis: { categories: trend.labels || [] },
        colors: ['#38bdf8', '#22c55e'],
    });

    const dept = @json($data['charts']['department_wise'] ?? []);
    initApexChart('payroll-dept', {
        chart: { type: 'bar', height: 280 },
        series: [{ name: 'Net Pay', data: dept.series || [] }],
        xaxis: { categories: dept.labels || [] },
        colors: ['#8b5cf6'],
    });

    const cc = @json($data['charts']['cost_center'] ?? []);
    initApexChart('payroll-cc', {
        chart: { type: 'bar', height: 280 },
        series: [{ data: cc.series || [] }],
        xaxis: { categories: cc.labels || [] },
        colors: ['#f59e0b'],
    });

    const gvn = @json($data['charts']['gross_vs_net'] ?? []);
    initApexChart('payroll-gvn', {
        chart: { type: 'bar', height: 280 },
        series: [{ data: gvn.series || [] }],
        xaxis: { categories: gvn.labels || [] },
        colors: ['#0ea5e9', '#ef4444', '#22c55e'],
        plotOptions: { bar: { distributed: true } },
    });

    const comp = @json($data['charts']['component_breakdown'] ?? []);
    initApexChart('payroll-comp', {
        chart: { type: 'donut', height: 280 },
        series: comp.series || [],
        labels: comp.labels || [],
        colors: ['#22c55e', '#ef4444', '#a855f7'],
    });

    const disb = @json($data['charts']['disbursement_trend'] ?? []);
    initApexChart('payroll-disb', {
        chart: { type: 'line', height: 280 },
        series: [{ name: 'Disbursed', data: disb.series || [] }],
        xaxis: { categories: disb.labels || [] },
        colors: ['#06b6d4'],
    });

    window.notifyDashboardReady?.();
});
</script>
@endpush
