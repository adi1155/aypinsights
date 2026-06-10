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
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
        ]);

        $rows = $this->safeList('Salary Slip', $erpFilters, [
            'name', 'employee', 'employee_name', 'department', 'designation', 'branch',
            'posting_date', 'start_date', 'end_date', 'status', 'docstatus',
            'gross_pay', 'net_pay', 'total_deduction', 'payment_days', 'total_working_days',
            'payroll_entry',
        ], 1000);

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
