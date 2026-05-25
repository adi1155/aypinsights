@extends('layouts.executive')
@section('title', 'Daily Closing')
@section('page-title', 'Daily Closing Dashboard')

@section('content')
@php $exportQuery = http_build_query(request()->only(['company', 'from_date', 'to_date'])); @endphp
<div class="mb-4 flex justify-end gap-2">
    <a href="{{ route('dashboard.export', ['pdf', 'daily-closing']) }}?{{ $exportQuery }}" class="btn-primary text-xs">PDF</a>
    <a href="{{ route('dashboard.export', ['csv', 'daily-closing']) }}?{{ $exportQuery }}" class="btn-primary text-xs opacity-80">CSV</a>
</div>

<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" />

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="cash-flow-trend" title="Cash Flow Trend" />
    <x-chart-card id="receipts-payments" title="Receipts vs Payments" type="donut" />
    <x-chart-card id="bank-wise" title="Bank-wise Balance" type="bar" />
    <x-chart-card id="payment-mode" title="Collections by Payment Mode" type="bar" />
</div>

<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Top Receipts (Period)" :columns="['Reference','Party','Amount']" :rows="collect($data['tables']['top_receipts'] ?? [])->map(fn($r)=>[$r['name']??'',$r['party']??'',$r['paid_amount']??0])->all()" />
    <x-data-table title="Top Payments (Period)" :columns="['Reference','Party','Amount']" :rows="collect($data['tables']['top_payments'] ?? [])->map(fn($r)=>[$r['name']??'',$r['party']??'',$r['paid_amount']??0])->all()" />
    <x-data-table title="Bank Ledger" :columns="['Bank','Balance']" :rows="collect($data['tables']['bank_ledger'] ?? [])->map(fn($r)=>[$r['bank']??'',$r['balance']??0])->all()" />
    <x-data-table title="Pending Journal Entries" :columns="['JV','Date','Status']" :rows="collect($data['tables']['pending_journals'] ?? [])->map(fn($r)=>[$r['name']??'',$r['posting_date']??'',$r['status']??''])->all()" />
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($data['charts']['cash_flow_trend'] ?? []);
    initApexChart('cash-flow-trend', {
        chart: { type: 'line', height: 280 },
        series: [{ name: 'Receipts', data: trend.receipts||[] }, { name: 'Payments', data: trend.payments||[] }],
        xaxis: { categories: trend.labels||[] },
        colors: ['#22d3ee','#a78bfa'],
    });
    const rvp = @json($data['charts']['receipts_vs_payments'] ?? []);
    initApexChart('receipts-payments', {
        chart: { type: 'donut', height: 280 },
        series: rvp.series||[],
        labels: rvp.labels||[],
    });
    const bank = @json($data['charts']['bank_wise'] ?? []);
    initApexChart('bank-wise', {
        chart: { type: 'bar', height: 280 },
        series: [{ data: bank.series||[] }],
        xaxis: { categories: bank.labels||[] },
        colors: ['#0ea5e9'],
    });
    const modes = @json($data['charts']['payment_mode_wise'] ?? $data['charts']['hourly_collections'] ?? []);
    initApexChart('payment-mode', {
        chart: { type: 'bar', height: 280 },
        series: [{ name: 'Collections', data: modes.series||[] }],
        xaxis: { categories: modes.labels||[] },
        colors: ['#10b981'],
    });
    window.notifyDashboardReady?.();
});
</script>
@endpush
