<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\PayrollRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PayrollService
{
    public function __construct(protected PayrollRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $cacheKey = 'payroll_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            set_time_limit((int) config('erpnext.payroll_max_execution', 300));

            $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $filters['to_date'] ?? now()->toDateString();
            $trendFrom = now()->subMonths(5)->startOfMonth()->toDateString();

            $trendFilters = array_merge($filters, [
                'from_date' => $trendFrom,
                'to_date' => $toDate,
            ]);

            $allSlips = collect($this->repository->getSalarySlips($trendFilters));
            $entries = collect($this->repository->getPayrollEntries($filters));
            $employees = collect($this->repository->getActiveEmployees($filters));
            $additional = collect($this->repository->getAdditionalSalaries($filters));
            $advances = collect($this->repository->getEmployeeAdvances($filters));
            $allPayments = collect($this->repository->getEmployeePayments($trendFilters));

            $submitted = $allSlips->where('docstatus', 1);
            $periodSlips = $submitted->filter(fn ($s) => $this->salarySlipInPeriod(
                $s,
                $fromDate,
                $toDate
            ));
            $pendingSlips = $allSlips->where('docstatus', 0);
            $pendingEntries = $entries->filter(fn ($e) => ($e['docstatus'] ?? 0) !== 1);
            $openAdvances = $advances->filter(fn ($a) => ($a['outstanding'] ?? 0) > 0);
            $periodPayments = $allPayments->filter(fn ($p) => ($p['posting_date'] ?? '') >= $fromDate
                && ($p['posting_date'] ?? '') <= $toDate);

            $totalGross = (float) $periodSlips->sum('gross_pay');
            $totalNet = (float) $periodSlips->sum('net_pay');
            $totalDeductions = (float) $periodSlips->sum('total_deduction');
            $employeesPaid = $periodSlips->pluck('employee_id')->filter()->unique()->count()
                ?: $periodSlips->pluck('employee')->unique()->count();

            return [
                'filters' => $filters,
                'kpis' => [
                    'total_gross_pay' => $totalGross,
                    'total_net_pay' => $totalNet,
                    'total_deductions' => $totalDeductions,
                    'payroll_cost_period' => $totalGross + (float) $additional->sum('amount'),
                    'employees_paid' => $employeesPaid,
                    'active_headcount' => $employees->count(),
                    'pending_salary_slips' => $pendingSlips->count(),
                    'pending_payroll_entries' => $pendingEntries->count(),
                    'additional_salary_total' => (float) $additional->sum('amount'),
                    'employee_advances_outstanding' => (float) $openAdvances->sum('outstanding'),
                    'average_net_salary' => $employeesPaid > 0 ? round($totalNet / $employeesPaid) : 0,
                    'bank_disbursement_period' => (float) $periodPayments->sum('paid_amount'),
                    'avg_payment_days' => round($periodSlips->avg('payment_days') ?: 0, 1),
                ],
                'kpi_types' => [
                    'employees_paid' => 'count',
                    'active_headcount' => 'count',
                    'pending_salary_slips' => 'count',
                    'pending_payroll_entries' => 'count',
                    'avg_payment_days' => 'decimal',
                ],
                'charts' => [
                    'monthly_payroll_trend' => $this->monthlyPayrollTrendFromSlips($submitted),
                    'department_wise' => [
                        'labels' => $periodSlips->groupBy('department')->keys()->take(10)->values()->all(),
                        'series' => $periodSlips->groupBy('department')->map->sum('net_pay')->values()->take(10)->all(),
                    ],
                    'cost_center' => [
                        'labels' => $periodSlips->groupBy('cost_center')->keys()->take(10)->values()->all(),
                        'series' => $periodSlips->groupBy('cost_center')->map->sum('net_pay')->values()->take(10)->all(),
                    ],
                    'gross_vs_net' => [
                        'labels' => ['Gross Pay', 'Deductions', 'Net Pay'],
                        'series' => [$totalGross, $totalDeductions, $totalNet],
                    ],
                    'component_breakdown' => [
                        'labels' => ['Net Pay', 'Statutory & Other Deductions', 'Additional Earnings'],
                        'series' => [
                            $totalNet,
                            $totalDeductions,
                            (float) $additional->sum('amount'),
                        ],
                    ],
                    'disbursement_trend' => $this->monthlyDisbursementTrendFromPayments($allPayments),
                ],
                'tables' => [
                    'pending_salary_slips' => $pendingSlips->take(15)->values()->all(),
                    'recent_payroll_runs' => $entries->sortByDesc('posting_date')->take(10)->values()->all(),
                    'top_earners' => $periodSlips->sortByDesc('net_pay')->take(10)->values()->all(),
                    'unpaid_advances' => $openAdvances->sortByDesc('outstanding')->values()->all(),
                    'additional_salary_items' => $additional->sortByDesc('amount')->values()->all(),
                    'payment_history' => $periodPayments->sortByDesc('posting_date')->take(15)->values()->all(),
                    'department_headcount' => $employees->groupBy('department')->map(fn ($g, $d) => [
                        'department' => $d,
                        'headcount' => $g->count(),
                        'avg_tenure_months' => round($g->avg(fn ($e) => Carbon::parse($e['date_of_joining'] ?? now())->diffInMonths(now())) ?: 0),
                    ])->sortByDesc('headcount')->values()->all(),
                    'employee_payroll_summary' => $this->repository->getEmployeePayrollSummary(
                        $filters,
                        $periodSlips->values()->all()
                    ),
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $submittedSlips
     * @return array{labels: array<int, string>, gross: array<int, float>, net: array<int, float>}
     */
    protected function monthlyPayrollTrendFromSlips(Collection $submittedSlips): array
    {
        $labels = [];
        $gross = [];
        $net = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $monthStart = $month->copy()->startOfMonth()->toDateString();
            $monthEnd = $month->copy()->endOfMonth()->toDateString();
            $monthSlips = $submittedSlips->filter(fn ($s) => $this->salarySlipInPeriod($s, $monthStart, $monthEnd));
            $gross[] = (float) $monthSlips->sum('gross_pay');
            $net[] = (float) $monthSlips->sum('net_pay');
        }

        return compact('labels', 'gross', 'net');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $payments
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    protected function monthlyDisbursementTrendFromPayments(Collection $payments): array
    {
        $labels = [];
        $series = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $monthStart = $month->copy()->startOfMonth()->toDateString();
            $monthEnd = $month->copy()->endOfMonth()->toDateString();
            $series[] = (float) $payments
                ->filter(fn ($p) => ($p['posting_date'] ?? '') >= $monthStart && ($p['posting_date'] ?? '') <= $monthEnd)
                ->sum('paid_amount');
        }

        return compact('labels', 'series');
    }

    /**
     * @param  array<string, mixed>  $slip
     */
    protected function salarySlipInPeriod(array $slip, string $fromDate, string $toDate): bool
    {
        $start = $slip['start_date'] ?? null;
        $end = $slip['end_date'] ?? null;

        if ($start && $end) {
            return $start <= $toDate && $end >= $fromDate;
        }

        $postingDate = $slip['posting_date'] ?? null;

        return $postingDate && $postingDate >= $fromDate && $postingDate <= $toDate;
    }
}
