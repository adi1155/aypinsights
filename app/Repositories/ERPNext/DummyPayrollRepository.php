<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\PayrollRepositoryInterface;

class DummyPayrollRepository implements PayrollRepositoryInterface
{
    public function getSalarySlips(array $filters = []): array
    {
        $departments = ['Production', 'Sales', 'Admin', 'Finance', 'Logistics', 'HR'];
        $employees = [
            'Ahmed Khan', 'Sara Ali', 'Omar Farooq', 'Fatima Noor', 'Hassan Raza',
            'Ayesha Malik', 'Bilal Hussain', 'Zainab Shah', 'Usman Tariq', 'Maryam Iqbal',
        ];

        return array_map(function ($i) use ($departments, $employees) {
            $gross = rand(85000, 420000);
            $basic = round($gross * (rand(55, 70) / 100));
            $allowances = $gross - $basic;
            $deduction = round($gross * (rand(5, 18) / 100));
            $loan = round($deduction * (rand(20, 50) / 100));
            $advance = max(0, $deduction - $loan);
            $docstatus = $i <= 3 ? 0 : 1;

            return [
                'name' => 'Sal Slip/'.now()->format('y').'/'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'employee' => $employees[$i % count($employees)],
                'employee_id' => 'EMP-'.str_pad((string) ($i % 10 + 1), 4, '0', STR_PAD_LEFT),
                'department' => $departments[$i % count($departments)],
                'designation' => ['Manager', 'Executive', 'Officer', 'Supervisor'][$i % 4],
                'branch' => ['Head Office', 'North Plant', 'South Plant'][$i % 3],
                'cost_center' => ['Head Office', 'Manufacturing', 'Sales North'][$i % 3],
                'posting_date' => now()->subDays(rand(0, 25))->toDateString(),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => $docstatus ? 'Submitted' : 'Draft',
                'docstatus' => $docstatus,
                'base' => $basic,
                'gross_pay' => $gross,
                'net_pay' => $gross - $deduction,
                'total_deduction' => $deduction,
                'payment_days' => rand(22, 26),
                'total_working_days' => 26,
                'total_leaves' => rand(0, 3),
                'leave_without_pay' => rand(0, 1),
                'absent_days' => rand(0, 3),
                'payroll_entry' => 'HR-PRUN-'.now()->format('Y-m'),
                '_allowances' => $allowances,
                '_loan' => $loan,
                '_advance' => $advance,
                '_absent' => rand(0, 3),
            ];
        }, range(1, 48));
    }

    public function getPayrollEntries(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'HR-PRUN-'.now()->subMonths($i)->format('Y-m'),
            'posting_date' => now()->subMonths($i)->endOfMonth()->toDateString(),
            'start_date' => now()->subMonths($i)->startOfMonth()->toDateString(),
            'end_date' => now()->subMonths($i)->endOfMonth()->toDateString(),
            'status' => $i === 0 ? 'Draft' : 'Submitted',
            'docstatus' => $i === 0 ? 0 : 1,
            'employee_count' => rand(42, 58),
            'salary_slips_submitted' => $i === 0 ? rand(10, 20) : rand(42, 58),
        ], range(0, 5));
    }

    public function getActiveEmployees(array $filters = []): array
    {
        $departments = ['Production', 'Sales', 'Admin', 'Finance', 'Logistics', 'HR'];

        return array_map(fn ($i) => [
            'name' => 'EMP-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'employee_name' => ['Ahmed Khan', 'Sara Ali', 'Omar Farooq', 'Fatima Noor', 'Hassan Raza'][$i % 5]." {$i}",
            'department' => $departments[$i % count($departments)],
            'designation' => ['Manager', 'Executive', 'Officer', 'Supervisor'][$i % 4],
            'branch' => ['Head Office', 'North Plant', 'South Plant'][$i % 3],
            'date_of_joining' => now()->subMonths(rand(3, 84))->toDateString(),
            'status' => 'Active',
        ], range(1, 52));
    }

    public function getAdditionalSalaries(array $filters = []): array
    {
        return [
            ['name' => 'AS-001', 'employee' => 'Ahmed Khan', 'payroll_date' => now()->toDateString(), 'amount' => 45000, 'type' => 'Earning', 'salary_component' => 'Performance Bonus', 'department' => 'Sales'],
            ['name' => 'AS-002', 'employee' => 'Sara Ali', 'payroll_date' => now()->toDateString(), 'amount' => 28000, 'type' => 'Earning', 'salary_component' => 'Overtime', 'department' => 'Production'],
            ['name' => 'AS-003', 'employee' => 'Omar Farooq', 'payroll_date' => now()->subDays(3)->toDateString(), 'amount' => 15000, 'type' => 'Earning', 'salary_component' => 'Shift Allowance', 'department' => 'Logistics'],
            ['name' => 'AS-004', 'employee' => 'Fatima Noor', 'payroll_date' => now()->subDays(5)->toDateString(), 'amount' => 62000, 'type' => 'Earning', 'salary_component' => 'Annual Bonus', 'department' => 'Finance'],
        ];
    }

    public function getEmployeeAdvances(array $filters = []): array
    {
        return [
            ['name' => 'EA-001', 'employee' => 'Hassan Raza', 'posting_date' => now()->subDays(40)->toDateString(), 'advance_amount' => 100000, 'paid_amount' => 100000, 'claimed_amount' => 35000, 'outstanding' => 65000, 'status' => 'Unpaid', 'department' => 'Production'],
            ['name' => 'EA-002', 'employee' => 'Ayesha Malik', 'posting_date' => now()->subDays(20)->toDateString(), 'advance_amount' => 50000, 'paid_amount' => 50000, 'claimed_amount' => 10000, 'outstanding' => 40000, 'status' => 'Unpaid', 'department' => 'Admin'],
            ['name' => 'EA-003', 'employee' => 'Bilal Hussain', 'posting_date' => now()->subDays(90)->toDateString(), 'advance_amount' => 75000, 'paid_amount' => 75000, 'claimed_amount' => 75000, 'outstanding' => 0, 'status' => 'Paid', 'department' => 'Sales'],
        ];
    }

    public function getEmployeePayments(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'ACC-PAY-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'employee' => ['Ahmed Khan', 'Sara Ali', 'Omar Farooq', 'Fatima Noor'][$i % 4],
            'paid_amount' => rand(75000, 380000),
            'posting_date' => now()->subDays(rand(0, 28))->toDateString(),
            'mode_of_payment' => ['Bank Transfer', 'Cheque', 'Cash'][$i % 3],
            'reference_no' => 'REF-'.rand(10000, 99999),
        ], range(1, 25));
    }

    public function getEmployeePayrollSummary(array $filters = [], array $preloadedSlips = []): array
    {
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();

        return collect($preloadedSlips ?: $this->getSalarySlips($filters))
            ->where('docstatus', 1)
            ->filter(fn ($slip) => $this->salarySlipInPeriod($slip, $fromDate, $toDate))
            ->groupBy(fn ($slip) => $slip['employee_id'] ?? $slip['employee'])
            ->map(function ($group) {
                $slip = $group->sortByDesc('posting_date')->first();
                $basic = (float) ($slip['base'] ?? 0);
                $gross = (float) ($slip['gross_pay'] ?? 0);

                return [
                    'employee_code' => $slip['employee_id'] ?? '',
                    'employee_name' => $slip['employee'] ?? 'N/A',
                    'basic_salary' => $basic,
                    'allowances' => (float) ($slip['_allowances'] ?? max(0, $gross - $basic)),
                    'gross_salary' => $gross,
                    'deduction' => (float) ($slip['total_deduction'] ?? 0),
                    'net_payable' => (float) ($slip['net_pay'] ?? 0),
                    'monthly_leaves' => (float) ($slip['total_leaves'] ?? $slip['leave_without_pay'] ?? 0),
                    'absent' => (float) ($slip['_absent'] ?? $slip['absent_days'] ?? 0),
                    'loan' => (float) ($slip['_loan'] ?? 0),
                    'advance' => (float) ($slip['_advance'] ?? 0),
                ];
            })
            ->sortBy('employee_name')
            ->values()
            ->all();
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
