<?php

use App\Jobs\GenerateDailyClosingSnapshot;
use App\Jobs\RebuildDashboardCache;
use App\Jobs\SendScheduledReportEmail;
use App\Models\ScheduledReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RebuildDashboardCache)->dailyAt('05:30');
Schedule::call(function () {
    $company = config('erpnext.default_company', 'GMP Foods (Pvt.) Ltd');
    dispatch(new GenerateDailyClosingSnapshot($company));
})->dailyAt('23:55');

Schedule::call(function () {
    ScheduledReport::where('is_active', true)
        ->where('frequency', 'daily')
        ->each(fn ($report) => dispatch(new SendScheduledReportEmail($report->id)));
})->dailyAt('08:00');
