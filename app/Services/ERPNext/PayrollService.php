<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\PayrollRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PayrollService
{
    public function __construct(protected PayrollRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $cacheKey = 'payroll_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            $slips = collect($this->repository->getSalarySlips($filters));
            $entries = collect($this->repository->getPayrollEntries($filters));
            $employees = collect($this->repository->getActiveEmployees($filters));
            $additional = collect($this->repository->getAdditionalSalaries($filters));
            $advances = collect($this->repository->getEmployeeAdvances($filters));
            $payments = collect($this->repository->getEmployeePayments($filters));

            $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $filters['to_date'] ?? now()->toDateString();

            $submitted = $slips->where('docstatus', 1);
            $periodSlips = $submitted->filter(function ($s) use ($fromDate, $toDate) {
                $d = $s['posting_date'] ?? $s['end_date'] ?? null;

                return $d && $d >= $fromDate && $d <= $toDate;
            });
            $pendingSlips = $slips->where('docstatus', 0);
            $pendingEntries = $entries->filter(fn ($e) => ($e['docstatus'] ?? 0) !== 1);
            $openAdvances = $advances->filter(fn ($a) => ($a['outstanding'] ?? 0) > 0);

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
                    'bank_disbursement_period' => (float) $payments->sum('paid_amount'),
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
                    'monthly_payroll_trend' => $this->monthlyPayrollTrend($filters),
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
                    'disbursement_trend' => $this->monthlyDisbursementTrend($filters),
                ],
                'tables' => [
                    'pending_salary_slips' => $pendingSlips->take(15)->values()->all(),
                    'recent_payroll_runs' => $entries->sortByDesc('posting_date')->take(10)->values()->all(),
                    'top_earners' => $periodSlips->sortByDesc('net_pay')->take(10)->values()->all(),
                    'unpaid_advances' => $openAdvances->sortByDesc('outstanding')->values()->all(),
                    'additional_salary_items' => $additional->sortByDesc('amount')->values()->all(),
                    'payment_history' => $payments->sortByDesc('posting_date')->take(15)->values()->all(),
                    'department_headcount' => $employees->groupBy('department')->map(fn ($g, $d) => [
                        'department' => $d,
                        'headcount' => $g->count(),
                        'avg_tenure_months' => round($g->avg(fn ($e) => Carbon::parse($e['date_of_joining'] ?? now())->diffInMonths(now())) ?: 0),
                    ])->sortByDesc('headcount')->values()->all(),
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
            ];
        });
    }

    protected function monthlyPayrollTrend(array $filters = []): array
    {
        $labels = [];
        $gross = [];
        $net = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $monthFilters = array_merge($filters, [
                'from_date' => $month->copy()->startOfMonth()->toDateString(),
                'to_date' => $month->copy()->endOfMonth()->toDateString(),
            ]);
            $slips = collect($this->repository->getSalarySlips($monthFilters))->where('docstatus', 1);
            $gross[] = (float) $slips->sum('gross_pay');
            $net[] = (float) $slips->sum('net_pay');
        }

        return [
            'labels' => $labels,
            'gross' => $gross,
            'net' => $net,
        ];
    }

    protected function monthlyDisbursementTrend(array $filters = []): array
    {
        $labels = [];
        $series = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $monthFilters = array_merge($filters, [
                'from_date' => $month->copy()->startOfMonth()->toDateString(),
                'to_date' => $month->copy()->endOfMonth()->toDateString(),
            ]);
            $series[] = (float) collect($this->repository->getEmployeePayments($monthFilters))->sum('paid_amount');
        }

        return compact('labels', 'series');
    }
}
