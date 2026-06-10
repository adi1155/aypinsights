<?php

namespace App\Jobs;

use App\Services\ERPNext\APService;
use App\Services\ERPNext\ARService;
use App\Services\ERPNext\DashboardAggregator;
use App\Services\ERPNext\ExpenseService;
use App\Services\ERPNext\FinancialService;
use App\Services\ERPNext\AttendanceService;
use App\Services\ERPNext\PayrollService;
use App\Services\ERPNext\ProductionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RebuildDashboardCache implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $filters = []) {}

    public function handle(
        FinancialService $financial,
        ARService $ar,
        APService $ap,
        ExpenseService $expense,
        PayrollService $payroll,
        AttendanceService $attendance,
        ProductionService $production,
        DashboardAggregator $ceo,
    ): void {
        Cache::flush();
        $financial->getDailyClosingDashboard($this->filters);
        $ar->getDashboard($this->filters);
        $ap->getDashboard($this->filters);
        $expense->getDashboard($this->filters);
        $payroll->getDashboard($this->filters);
        $attendance->getDashboard($this->filters);
        $production->getDashboard($this->filters);
        $ceo->getCeoDashboard($this->filters);
    }
}
