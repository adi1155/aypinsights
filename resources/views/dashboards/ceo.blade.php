@extends('layouts.executive')
@section('title', 'CEO Dashboard — IAS')
@section('page-title', 'CEO Dashboard — IAS Financial Overview')

@php
    $currency = $data['currency'] ?? 'PKR';
    $bs = $data['statement_of_financial_position'] ?? [];
    $pl = $data['statement_of_profit_or_loss'] ?? [];
    $ratios = $data['ratios'] ?? [];
    $ops = $data['operational'] ?? [];
@endphp

@section('content')
@if(!empty($error))
    <div class="mb-4 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-amber-300">{{ $error }}</div>
@endif

{{-- Header: Health + Period --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="flex flex-wrap items-center gap-4">
        <div class="glass-card px-6 py-4 min-w-[140px]">
            <p class="text-xs text-slate-400">IAS Health Score</p>
            <p class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-sky-400">{{ $data['health_score'] ?? 0 }}%</p>
        </div>
        <div class="glass-card px-5 py-3 text-sm">
            <p class="text-slate-500">Reporting period</p>
            <p class="font-semibold text-white">{{ $filters['from_date'] ?? '' }} → {{ $filters['to_date'] ?? '' }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $filters['company'] ?? '' }}</p>
        </div>
        @if($bs['accounting_equation_balanced'] ?? false)
            <span class="rounded-full px-3 py-1 text-xs font-semibold traffic-green">A = L + E ✓</span>
        @else
            <span class="rounded-full px-3 py-1 text-xs font-semibold traffic-amber">Review equation</span>
        @endif
    </div>
    @php $exportQuery = http_build_query(request()->only(['company', 'from_date', 'to_date'])); @endphp
    <div class="flex gap-2">
        <a href="{{ route('dashboard.export', ['pdf', 'ceo']) }}?{{ $exportQuery }}" class="btn-primary text-xs">Export PDF</a>
        <a href="{{ route('dashboard.export', ['csv', 'ceo']) }}?{{ $exportQuery }}" class="btn-primary text-xs opacity-80">Export CSV</a>
    </div>
</div>

{{-- IAS 1: Statement of Financial Position --}}
<section class="mb-8">
    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-sky-400">
        <span class="h-px flex-1 bg-sky-500/30"></span>
        Statement of Financial Position (IAS 1)
        <span class="h-px flex-1 bg-sky-500/30"></span>
    </h2>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <x-ias-metric label="Total Assets" :value="$bs['total_assets'] ?? 0" :currency="$currency" highlight />
        <x-ias-metric label="Current Assets" :value="$bs['current_assets'] ?? 0" :currency="$currency" />
        <x-ias-metric label="Non-Current Assets" :value="$bs['non_current_assets'] ?? 0" :currency="$currency" />
        <x-ias-metric label="Total Liabilities" :value="$bs['total_liabilities'] ?? 0" :currency="$currency" />
        <x-ias-metric label="Total Equity" :value="$bs['total_equity'] ?? 0" :currency="$currency" highlight />
        <x-ias-metric label="L + E" :value="$bs['total_liabilities_and_equity'] ?? 0" :currency="$currency" />
    </div>
</section>

{{-- IAS 1: Statement of Profit or Loss --}}
<section class="mb-8">
    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-violet-400">
        <span class="h-px flex-1 bg-violet-500/30"></span>
        Statement of Profit or Loss (IAS 1)
        <span class="h-px flex-1 bg-violet-500/30"></span>
    </h2>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <x-ias-metric label="Total Income" :value="$pl['total_income'] ?? 0" :currency="$currency" highlight />
        <x-ias-metric label="Total Expenses" :value="$pl['total_expenses'] ?? 0" :currency="$currency" />
        <x-ias-metric label="Gross Profit" :value="$pl['gross_profit'] ?? 0" :currency="$currency" />
        <x-ias-metric label="Net Profit / Loss" :value="$pl['net_profit_loss'] ?? 0" :currency="$currency" :highlight="($pl['net_profit_loss'] ?? 0) >= 0" />
        <x-ias-metric label="Net Margin" :value="$pl['net_profit_margin_pct'] ?? 0" suffix="%" />
        <x-ias-metric label="Working Capital" :value="$ratios['working_capital'] ?? 0" :currency="$currency" />
    </div>
</section>

{{-- Key ratios & operational --}}
<section class="mb-8 grid gap-6 lg:grid-cols-2">
    <div class="glass-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-300">Key Financial Ratios</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            @foreach([
                'current_ratio' => 'Current Ratio',
                'quick_ratio' => 'Quick Ratio',
                'debt_to_equity' => 'Debt to Equity',
                'debt_ratio' => 'Debt Ratio',
                'equity_ratio' => 'Equity Ratio',
                'net_profit_margin' => 'Net Profit Margin %',
                'expense_ratio' => 'Expense Ratio %',
                'return_on_equity' => 'Return on Equity %',
                'return_on_assets' => 'Return on Assets %',
                'asset_turnover' => 'Asset Turnover',
            ] as $key => $label)
                <div class="rounded-lg bg-white/5 px-3 py-2">
                    <dt class="text-slate-500">{{ $label }}</dt>
                    <dd class="font-semibold text-white">
                        @if(isset($ratios[$key]) && $ratios[$key] !== null)
                            {{ is_float($ratios[$key]) ? number_format($ratios[$key], 2) : $ratios[$key] }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
    <div class="glass-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-300">Operational Position</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Trade Receivables</dt><dd class="font-medium text-white">{{ $currency }} {{ number_format($ops['total_receivables'] ?? 0, 0) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Overdue Receivables</dt><dd class="font-medium text-amber-400">{{ $currency }} {{ number_format($ops['overdue_receivables'] ?? 0, 0) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Trade Payables</dt><dd class="font-medium text-white">{{ $currency }} {{ number_format($ops['total_payables'] ?? 0, 0) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Overdue Payables</dt><dd class="font-medium text-red-400">{{ $currency }} {{ number_format($ops['overdue_payables'] ?? 0, 0) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Recovery %</dt><dd class="font-medium text-emerald-400">{{ $ops['recovery_percentage'] ?? 0 }}%</dd></div>
        </dl>
    </div>
</section>

{{-- Insights --}}
@if(!empty($data['insights']))
<section class="mb-8">
    <h3 class="mb-3 text-sm font-semibold text-slate-400">Executive Insights</h3>
    <div class="grid gap-3 md:grid-cols-2">
        @foreach($data['insights'] as $insight)
            @php
                $border = match($insight['type'] ?? '') {
                    'positive' => 'border-emerald-500',
                    'negative' => 'border-red-500',
                    default => 'border-amber-500',
                };
            @endphp
            <div class="glass-card border-l-4 p-4 {{ $border }}">
                <p class="font-semibold text-white">{{ $insight['title'] }}</p>
                <p class="text-sm text-slate-400 mt-1">{{ $insight['message'] }}</p>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- Charts --}}
<div class="mb-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="chart-position" title="Financial Position (Assets / Liabilities / Equity)" />
    <x-chart-card id="chart-income-expense" title="Income vs Expenses vs Net Profit" />
    <x-chart-card id="chart-assets" title="Asset Composition" type="donut" />
    <x-chart-card id="chart-expenses" title="Expense Composition" type="donut" />
</div>

{{-- Account tables --}}
<div class="grid gap-6 xl:grid-cols-2">
    <x-data-table title="Top Asset Accounts" :columns="['Account','Amount']" :rows="collect($data['tables']['top_assets'] ?? [])->map(fn($r)=>[$r['account'],$r['amount']])->all()" />
    <x-data-table title="Top Liability Accounts" :columns="['Account','Amount']" :rows="collect($data['tables']['top_liabilities'] ?? [])->map(fn($r)=>[$r['account'],$r['amount']])->all()" />
    <x-data-table title="Top Equity Accounts" :columns="['Account','Amount']" :rows="collect($data['tables']['top_equity'] ?? [])->map(fn($r)=>[$r['account'],$r['amount']])->all()" />
    <x-data-table title="Top Income Accounts" :columns="['Account','Amount']" :rows="collect($data['tables']['top_income'] ?? [])->map(fn($r)=>[$r['account'],$r['amount']])->all()" />
    <x-data-table title="Top Expense Accounts" :columns="['Account','Amount']" :rows="collect($data['tables']['top_expenses'] ?? [])->map(fn($r)=>[$r['account'],$r['amount']])->all()" />
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let chartsDone = 0;
    const chartTotal = 4;
    const onChartReady = () => {
        chartsDone += 1;
        if (window.AypPageProgress) {
            window.AypPageProgress.setProgress(70 + (chartsDone / chartTotal) * 25, 'Rendering charts (' + chartsDone + '/' + chartTotal + ')');
        }
        if (chartsDone >= chartTotal) {
            window.notifyDashboardReady?.();
        }
    };

    const currency = @json($data['currency'] ?? 'PKR');
    const round2 = (v) => Math.round(Number(v) * 100) / 100;
    const roundSeries = (arr) => (arr || []).map(round2);
    const fmtBarAmount = (val) => `${currency} ${round2(val).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const barAmountFormat = {
        yaxis: { labels: { formatter: fmtBarAmount } },
        tooltip: { y: { formatter: fmtBarAmount } },
        dataLabels: {
            enabled: true,
            formatter: (val) => fmtBarAmount(val),
            offsetY: -6,
            style: { fontSize: '11px' },
        },
    };

    const pos = @json($data['charts']['financial_position'] ?? []);
    initApexChart('chart-position', {
        chart: { type: 'bar', height: 300 },
        series: [{ name: 'Amount', data: roundSeries(pos.series) }],
        xaxis: { categories: pos.labels || [] },
        colors: ['#0ea5e9', '#f59e0b', '#8b5cf6'],
        plotOptions: { bar: { distributed: true } },
        ...barAmountFormat,
    });
    onChartReady();

    const ie = @json($data['charts']['income_vs_expense'] ?? []);
    initApexChart('chart-income-expense', {
        chart: { type: 'bar', height: 300 },
        series: [{ data: roundSeries(ie.series) }],
        xaxis: { categories: ie.labels || [] },
        colors: ['#10b981', '#ef4444', '#22d3ee'],
        plotOptions: { bar: { distributed: true } },
        ...barAmountFormat,
    });
    onChartReady();

    const assets = @json($data['charts']['asset_composition'] ?? []);
    initApexChart('chart-assets', {
        chart: { type: 'donut', height: 300 },
        series: assets.series || [],
        labels: assets.labels || [],
    });
    onChartReady();

    const exp = @json($data['charts']['expense_composition'] ?? []);
    initApexChart('chart-expenses', {
        chart: { type: 'donut', height: 300 },
        series: exp.series || [],
        labels: exp.labels || [],
    });
    onChartReady();

    setTimeout(() => window.notifyDashboardReady?.(), 2500);
});
</script>
@endpush
