<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\PayrollRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Illuminate\Support\Facades\Log;

class ERPNextPayrollRepository implements PayrollRepositoryInterface
{
    use BuildsErpNextFilters;

    public function __construct(protected ERPNextClient $client) {}

    /**
     * @param  array<int, array<int, mixed>>  $filters
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function safeList(string $doctype, array $filters, array $fields, int $limit): array
    {
        try {
            return $this->client->getList($doctype, $filters, $fields, $limit);
        } catch (\Throwable $e) {
            Log::warning("Payroll dashboard: {$doctype} fetch failed", ['message' => $e->getMessage()]);

            return [];
        }
    }

    public function getSalarySlips(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildPayrollPeriodFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
        ]);

        $rows = $this->safeList('Salary Slip', $erpFilters, [
            'name', 'employee', 'employee_name', 'department', 'designation', 'branch',
            'posting_date', 'start_date', 'end_date', 'status', 'docstatus',
            'gross_pay', 'net_pay', 'total_deduction', 'payment_days', 'total_working_days',
            'leave_without_pay', 'absent_days', 'payroll_entry',
        ], 2000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'employee_id' => $row['employee'] ?? null,
            'department' => $row['department'] ?? 'General',
            'designation' => $row['designation'] ?? '',
            'branch' => $row['branch'] ?? '',
            'cost_center' => $row['department'] ?? $row['branch'] ?? 'General',
            'posting_date' => $row['posting_date'] ?? $row['end_date'] ?? null,
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $row['end_date'] ?? null,
            'status' => $row['status'] ?? ($row['docstatus'] == 1 ? 'Submitted' : 'Draft'),
            'docstatus' => (int) ($row['docstatus'] ?? 0),
            'gross_pay' => (float) ($row['gross_pay'] ?? 0),
            'net_pay' => (float) ($row['net_pay'] ?? 0),
            'total_deduction' => (float) ($row['total_deduction'] ?? 0),
            'payment_days' => (float) ($row['payment_days'] ?? 0),
            'total_working_days' => (float) ($row['total_working_days'] ?? 0),
            'leave_without_pay' => (float) ($row['leave_without_pay'] ?? 0),
            'absent_days' => (float) ($row['absent_days'] ?? 0),
            'payroll_entry' => $row['payroll_entry'] ?? null,
        ], $rows);
    }

    public function getPayrollEntries(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'start_date', [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
        ]);

        $rows = $this->safeList('Payroll Entry', $erpFilters, [
            'name', 'posting_date', 'start_date', 'end_date', 'status', 'docstatus',
            'salary_slips_created', 'salary_slips_submitted',
        ], 200);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'posting_date' => $row['posting_date'] ?? null,
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $row['end_date'] ?? null,
            'status' => $row['status'] ?? ($row['docstatus'] == 1 ? 'Submitted' : 'Draft'),
            'docstatus' => (int) ($row['docstatus'] ?? 0),
            'employee_count' => (int) ($row['salary_slips_submitted'] ?? $row['salary_slips_created'] ?? 0),
            'salary_slips_submitted' => (int) ($row['salary_slips_submitted'] ?? 0),
        ], $rows);
    }

    public function getActiveEmployees(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = [
            ['company', '=', $company],
            ['status', '=', 'Active'],
        ];

        $rows = $this->safeList('Employee', $erpFilters, [
            'name', 'employee_name', 'department', 'designation', 'branch', 'date_of_joining', 'status',
        ], 2000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee_name' => $row['employee_name'] ?? $row['name'],
            'department' => $row['department'] ?? 'General',
            'designation' => $row['designation'] ?? '',
            'branch' => $row['branch'] ?? '',
            'date_of_joining' => $row['date_of_joining'] ?? null,
            'status' => $row['status'] ?? 'Active',
        ], $rows);
    }

    public function getAdditionalSalaries(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'payroll_date', [
            ['company', '=', $company],
            ['docstatus', '=', 1],
        ]);

        $rows = $this->safeList('Additional Salary', $erpFilters, [
            'name', 'employee', 'employee_name', 'payroll_date', 'amount', 'type',
            'salary_component', 'department',
        ], 500);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'payroll_date' => $row['payroll_date'] ?? null,
            'amount' => (float) ($row['amount'] ?? 0),
            'type' => $row['type'] ?? 'Earning',
            'salary_component' => $row['salary_component'] ?? 'Additional',
            'department' => $row['department'] ?? 'General',
        ], $rows);
    }

    public function getEmployeeAdvances(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = [
            ['company', '=', $company],
            ['docstatus', '=', 1],
        ];

        $rows = $this->safeList('Employee Advance', $erpFilters, [
            'name', 'employee', 'employee_name', 'posting_date', 'advance_amount',
            'paid_amount', 'claimed_amount', 'status', 'department',
        ], 500);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'posting_date' => $row['posting_date'] ?? null,
            'advance_amount' => (float) ($row['advance_amount'] ?? 0),
            'paid_amount' => (float) ($row['paid_amount'] ?? 0),
            'claimed_amount' => (float) ($row['claimed_amount'] ?? 0),
            'outstanding' => (float) max(0, ($row['advance_amount'] ?? 0) - ($row['claimed_amount'] ?? 0)),
            'status' => $row['status'] ?? 'Unpaid',
            'department' => $row['department'] ?? 'General',
        ], $rows);
    }

    public function getEmployeePayments(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['payment_type', '=', 'Pay'],
            ['party_type', '=', 'Employee'],
        ]);

        $rows = $this->safeList('Payment Entry', $erpFilters, [
            'name', 'party', 'paid_amount', 'posting_date', 'mode_of_payment', 'reference_no',
        ], 500);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee' => $row['party'] ?? 'N/A',
            'paid_amount' => (float) ($row['paid_amount'] ?? 0),
            'posting_date' => $row['posting_date'] ?? null,
            'mode_of_payment' => $row['mode_of_payment'] ?? 'Bank',
            'reference_no' => $row['reference_no'] ?? '',
        ], $rows);
    }

    public function getEmployeePayrollSummary(array $filters = [], array $preloadedSlips = []): array
    {
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();

        $slips = collect($preloadedSlips ?: $this->getSalarySlips($filters))
            ->where('docstatus', 1)
            ->filter(fn ($slip) => $this->salarySlipInPeriod($slip, $fromDate, $toDate))
            ->groupBy(fn ($slip) => $slip['employee_id'] ?? $slip['employee'])
            ->map(fn ($group) => $group->sortByDesc('end_date')->sortByDesc('posting_date')->first());

        $leavesByEmployee = $this->leaveDaysByEmployee($filters);
        $loansByEmployee = $this->deductionAmountsByEmployee($filters, ['loan']);
        $advancesByEmployee = $this->deductionAmountsByEmployee($filters, ['adv']);

        return $slips->map(function ($slip) use ($leavesByEmployee, $loansByEmployee, $advancesByEmployee) {
            $employeeId = $slip['employee_id'] ?? $slip['employee'] ?? '';
            $gross = (float) ($slip['gross_pay'] ?? 0);
            $basic = $gross;
            $allowances = 0.0;

            $monthlyLeaves = (float) ($leavesByEmployee[$employeeId] ?? $slip['leave_without_pay'] ?? 0);
            $absent = (float) ($slip['absent_days'] ?? 0);

            return [
                'employee_code' => $employeeId,
                'employee_name' => $slip['employee'] ?? 'N/A',
                'basic_salary' => $basic,
                'allowances' => $allowances,
                'gross_salary' => $gross,
                'deduction' => (float) ($slip['total_deduction'] ?? 0),
                'net_payable' => (float) ($slip['net_pay'] ?? 0),
                'monthly_leaves' => $monthlyLeaves,
                'absent' => $absent,
                'loan' => (float) ($loansByEmployee[$employeeId] ?? 0),
                'advance' => (float) ($advancesByEmployee[$employeeId] ?? 0),
            ];
        })->sortBy('employee_name')->values()->all();
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

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<int, mixed>>  $defaults
     * @return array<int, array<int, mixed>>
     */
    protected function buildPayrollPeriodFilters(array $filters, array $defaults = []): array
    {
        $built = $defaults;

        if (! empty($filters['from_date'])) {
            $built[] = ['end_date', '>=', $filters['from_date']];
        }
        if (! empty($filters['to_date'])) {
            $built[] = ['start_date', '<=', $filters['to_date']];
        }

        return $built;
    }

    /**
     * @return array<string, float>
     */
    protected function leaveDaysByEmployee(array $filters): array
    {
        $company = $this->defaultCompany($filters);
        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $rows = $this->safeList('Leave Application', [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['status', '=', 'Approved'],
            ['from_date', '<=', $to],
            ['to_date', '>=', $from],
        ], ['employee', 'total_leave_days'], 2000);

        $totals = [];
        foreach ($rows as $row) {
            $employee = $row['employee'] ?? '';
            if ($employee === '') {
                continue;
            }
            $totals[$employee] = ($totals[$employee] ?? 0) + (float) ($row['total_leave_days'] ?? 0);
        }

        return $totals;
    }

    /**
     * @return array<string, float>
     */
    protected function absentDaysByEmployee(array $filters): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'attendance_date', [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['status', '=', 'Absent'],
        ]);

        $rows = $this->safeList('Attendance', $erpFilters, ['employee'], 5000);
        $totals = [];

        foreach ($rows as $row) {
            $employee = $row['employee'] ?? '';
            if ($employee === '') {
                continue;
            }
            $totals[$employee] = ($totals[$employee] ?? 0) + 1;
        }

        return $totals;
    }

    /**
     * @param  array<int, string>  $keywords
     * @return array<string, float>
     */
    protected function deductionAmountsByEmployee(array $filters, array $keywords): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'payroll_date', [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['type', '=', 'Deduction'],
        ]);

        $rows = $this->safeList('Additional Salary', $erpFilters, [
            'employee', 'amount', 'salary_component',
        ], 2000);

        $totals = [];
        foreach ($rows as $row) {
            $component = strtolower((string) ($row['salary_component'] ?? ''));
            $matches = collect($keywords)->contains(fn ($keyword) => str_contains($component, strtolower($keyword)));
            if (! $matches) {
                continue;
            }

            $employee = $row['employee'] ?? '';
            if ($employee === '') {
                continue;
            }
            $totals[$employee] = ($totals[$employee] ?? 0) + (float) ($row['amount'] ?? 0);
        }

        if (in_array('loan', $keywords, true)) {
            $loanFilters = $this->buildDateRangeOnField($filters, 'posting_date', [
                ['docstatus', '=', 1],
            ]);
            $loanRows = $this->safeList('Loan Repayment', $loanFilters, ['applicant', 'amount_paid'], 2000);
            foreach ($loanRows as $row) {
                $employee = $row['applicant'] ?? '';
                if ($employee === '') {
                    continue;
                }
                $totals[$employee] = ($totals[$employee] ?? 0) + (float) ($row['amount_paid'] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<int, mixed>>  $defaults
     * @return array<int, array<int, mixed>>
     */
    protected function buildDateRangeOnField(array $filters, string $dateField, array $defaults = []): array
    {
        $built = $defaults;

        if (! empty($filters['from_date'])) {
            $built[] = [$dateField, '>=', $filters['from_date']];
        }
        if (! empty($filters['to_date'])) {
            $built[] = [$dateField, '<=', $filters['to_date']];
        }

        return $built;
    }
}
