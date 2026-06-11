@extends('layouts.executive')
@section('page-title', 'Scheduled Reports')
@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <div class="glass-card p-6">
        <h3 class="mb-4 font-semibold ayp-heading">Create Schedule</h3>
        <form method="POST" action="{{ route('settings.scheduled.store') }}" class="space-y-3">
            @csrf
            <select name="report_type" class="ayp-input w-full rounded-xl px-3 py-2"><option value="ceo">CEO</option><option value="daily_closing">Daily Closing</option><option value="ap">AP</option><option value="ar">AR</option><option value="expense">Expense</option><option value="payroll">Payroll</option><option value="attendance">Attendance</option><option value="production">Production</option></select>
            <select name="format" class="ayp-input w-full rounded-xl px-3 py-2"><option value="pdf">PDF</option><option value="csv">CSV</option></select>
            <select name="frequency" class="ayp-input w-full rounded-xl px-3 py-2"><option value="daily">Daily</option><option value="weekly">Weekly</option></select>
            <input type="time" name="delivery_time" value="08:00" class="ayp-input w-full rounded-xl px-3 py-2">
            <input name="recipients" placeholder="email1@corp.com, email2@corp.com" class="ayp-input w-full rounded-xl px-3 py-2">
            <button class="btn-primary">Schedule</button>
        </form>
    </div>
    <x-data-table title="Active Schedules" :columns="['Type','Format','Frequency','Last Sent']" :rows="$reports->map(fn($r)=>[$r->report_type,$r->format,$r->frequency,$r->last_sent_at?->format('Y-m-d H:i')??'Never'])->all()" />
</div>
@endsection
