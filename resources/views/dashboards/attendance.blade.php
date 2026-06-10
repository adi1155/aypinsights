@extends('layouts.executive')
@section('title', 'Attendance & Leave Dashboard')
@section('page-title', 'Attendance & Leave Dashboard')

@section('content')
@php $exportQuery = http_build_query(request()->only(['company', 'from_date', 'to_date'])); @endphp
<div class="mb-4 flex flex-wrap items-center gap-3">
    <a href="{{ route('dashboard.export', ['pdf', 'attendance']) }}?{{ $exportQuery }}" class="btn-primary text-xs">Export PDF</a>
    <a href="{{ route('dashboard.export', ['csv', 'attendance']) }}?{{ $exportQuery }}" class="btn-primary text-xs opacity-80">Export CSV</a>
    <span class="rounded-lg border border-sky-800/50 bg-sky-950/30 px-3 py-1.5 text-xs text-sky-300">{{ $data['attendance_rule'] ?? '' }}</span>
</div>
<x-kpi-grid :kpis="$data['kpis']" :currency="$data['currency']" :types="$data['kpi_types'] ?? []" />
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-chart-card id="att-shift" title="Today's Present by Shift" />
    <x-chart-card id="att-punctuality" title="Shift-wise On Time vs Late" />
    <x-chart-card id="att-daily" title="Daily Present Count (Check-in Based)" />
    <x-chart-card id="att-dept" title="Today's Present by Department" />
    <x-chart-card id="att-leave-type" title="Leave Days by Type" />
    <x-chart-card id="att-vs-leave" title="Today: Present vs Leave vs Absent" />
    <x-chart-card id="att-leave-trend" title="Monthly Leave Days (6 Months)" />
    <x-chart-card id="att-log-types" title="Check-in Log Types" />
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <x-data-table title="Today's Attendance (Shift-wise)" :columns="['Employee','Department','Shift','Shift Start','Status','First Check-in','Late?']" :rows="collect($data['tables']['todays_attendance']??[])->map(fn($r)=>[$r['employee']??'',$r['department']??'',$r['shift']??'',$r['shift_start']??'—',$r['status']??'',$r['first_checkin']??'—',($r['late']??false)?'Yes':'No'])->all()" />
    <x-data-table title="Shift-wise Summary (Today)" :columns="['Shift','Scheduled','Present','On Time','Late','On Leave','Absent','Start','End']" :rows="collect($data['tables']['shift_wise_summary']??[])->map(fn($r)=>[$r['shift']??'',$r['scheduled']??0,$r['present']??0,$r['on_time']??0,$r['late']??0,$r['on_leave']??0,$r['absent']??0,$r['shift_start']??'',$r['shift_end']??''])->all()" />
    <x-data-table title="Shift Assignments" :columns="['Ref','Employee','Shift','From','To','Status']" :rows="collect($data['tables']['shift_assignments']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['shift_type']??'',$r['start_date']??'',$r['end_date']??'—',$r['status']??''])->all()" />
    <x-data-table title="Late Arrivals Today" :columns="['Employee','Shift','Shift Start','First Check-in','Department']" :rows="collect($data['tables']['late_today']??[])->map(fn($r)=>[$r['employee']??'',$r['shift']??'',$r['shift_start']??'',$r['first_checkin']??'',$r['department']??''])->all()" />
    <x-data-table title="Absent Today (Scheduled Shifts)" :columns="['Employee','Department','Shift','Shift Start']" :rows="collect($data['tables']['absent_today']??[])->map(fn($r)=>[$r['employee']??'',$r['department']??'',$r['shift']??'',$r['shift_start']??''])->all()" />
    <x-data-table title="Recent Check-ins" :columns="['Employee','Shift','Time','Type','Device']" :rows="collect($data['tables']['recent_checkins']??[])->map(fn($r)=>[$r['employee']??'',$r['shift']??'',$r['time']??'',$r['log_type']??'',$r['device_id']??''])->all()" />
    <x-data-table title="Pending Leave Approvals" :columns="['Ref','Employee','Type','From','To','Days']" :rows="collect($data['tables']['pending_leave_approvals']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['leave_type']??'',$r['from_date']??'',$r['to_date']??'',$r['total_leave_days']??0])->all()" />
    <x-data-table title="Approved Leaves (Period)" :columns="['Ref','Employee','Type','From','To','Days']" :rows="collect($data['tables']['approved_leaves']??[])->map(fn($r)=>[$r['name']??'',$r['employee']??'',$r['leave_type']??'',$r['from_date']??'',$r['to_date']??'',$r['total_leave_days']??0])->all()" />
    <x-data-table title="Department Summary (Today)" :columns="['Department','Headcount','Present','Late','On Leave','Absent']" :rows="collect($data['tables']['department_summary']??[])->map(fn($r)=>[$r['department']??'',$r['headcount']??0,$r['present']??0,$r['late']??0,$r['on_leave']??0,$r['absent']??0])->all()" />
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const shift = @json($data['charts']['shift_wise_present'] ?? []);
    initApexChart('att-shift', {
        chart: { type: 'bar', height: 280 },
        series: [{ name: 'Present', data: shift.series || [] }],
        xaxis: { categories: shift.labels || [] },
        colors: ['#22c55e'],
    });

    const punct = @json($data['charts']['shift_punctuality'] ?? []);
    initApexChart('att-punctuality', {
        chart: { type: 'bar', height: 280, stacked: true },
        series: [
            { name: 'On Time', data: punct.on_time || [] },
            { name: 'Late', data: punct.late || [] },
        ],
        xaxis: { categories: punct.labels || [] },
        colors: ['#22c55e', '#ef4444'],
    });

    const daily = @json($data['charts']['daily_attendance'] ?? []);
    initApexChart('att-daily', {
        chart: { type: 'area', height: 280 },
        series: [{ name: 'Present', data: daily.present || [] }],
        xaxis: { categories: daily.labels || [] },
        colors: ['#0ea5e9'],
    });

    const dept = @json($data['charts']['department_present'] ?? []);
    initApexChart('att-dept', {
        chart: { type: 'bar', height: 280 },
        series: [{ name: 'Present', data: dept.series || [] }],
        xaxis: { categories: dept.labels || [] },
        colors: ['#8b5cf6'],
    });

    const leaveType = @json($data['charts']['leave_type_breakdown'] ?? []);
    initApexChart('att-leave-type', {
        chart: { type: 'donut', height: 280 },
        series: leaveType.series || [],
        labels: leaveType.labels || [],
        colors: ['#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#22c55e'],
    });

    const avl = @json($data['charts']['attendance_vs_leave'] ?? []);
    initApexChart('att-vs-leave', {
        chart: { type: 'bar', height: 280 },
        series: [{ data: avl.series || [] }],
        xaxis: { categories: avl.labels || [] },
        colors: ['#22c55e', '#f59e0b', '#ef4444'],
        plotOptions: { bar: { distributed: true } },
    });

    const leaveTrend = @json($data['charts']['monthly_leave_trend'] ?? []);
    initApexChart('att-leave-trend', {
        chart: { type: 'line', height: 280 },
        series: [{ name: 'Leave Days', data: leaveTrend.series || [] }],
        xaxis: { categories: leaveTrend.labels || [] },
        colors: ['#a855f7'],
    });

    const logTypes = @json($data['charts']['checkin_log_types'] ?? []);
    initApexChart('att-log-types', {
        chart: { type: 'pie', height: 280 },
        series: logTypes.series || [],
        labels: logTypes.labels || [],
        colors: ['#22c55e', '#ef4444', '#94a3b8'],
    });

    window.notifyDashboardReady?.();
});
</script>
@endpush
