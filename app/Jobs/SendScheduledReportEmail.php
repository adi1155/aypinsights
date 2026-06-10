<?php

namespace App\Jobs;

use App\Mail\ExecutiveDashboardReport;
use App\Models\ScheduledReport;
use App\Services\ERPNext\DashboardAggregator;
use App\Services\ERPNext\FinancialService;
use App\Services\ERPNext\PayrollService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendScheduledReportEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $scheduledReportId) {}

    public function handle(FinancialService $financial, DashboardAggregator $ceo, PayrollService $payroll): void
    {
        $report = ScheduledReport::with('user')->findOrFail($this->scheduledReportId);
        if (! $report->is_active) {
            return;
        }

        $filters = $report->filters ?? [];
        $data = match ($report->report_type) {
            'ceo' => $ceo->getCeoDashboard($filters),
            'daily_closing' => $financial->getDailyClosingDashboard($filters),
            'payroll' => $payroll->getDashboard($filters),
            default => $financial->getDailyClosingDashboard($filters),
        };

        foreach ($report->recipients as $email) {
            Mail::to($email)->send(new ExecutiveDashboardReport($report->report_type, $data));
        }

        $report->update(['last_sent_at' => now()]);
    }
}
