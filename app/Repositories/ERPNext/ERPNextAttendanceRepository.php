<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\AttendanceRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Illuminate\Support\Facades\Log;

class ERPNextAttendanceRepository implements AttendanceRepositoryInterface
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
            Log::warning("Attendance dashboard: {$doctype} fetch failed", ['message' => $e->getMessage()]);

            return [];
        }
    }

    public function getEmployeeCheckins(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $from = ($filters['from_date'] ?? now()->startOfMonth()->toDateString()).' 00:00:00';
        $to = ($filters['to_date'] ?? now()->toDateString()).' 23:59:59';

        $erpFilters = [
            ['time', '>=', $from],
            ['time', '<=', $to],
        ];

        $rows = $this->safeList('Employee Checkin', $erpFilters, [
            'name', 'employee', 'employee_name', 'time', 'log_type', 'device_id', 'shift',
        ], 3000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee_id' => $row['employee'] ?? null,
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'time' => $row['time'] ?? null,
            'date' => isset($row['time']) ? substr((string) $row['time'], 0, 10) : null,
            'log_type' => $row['log_type'] ?? '',
            'device_id' => $row['device_id'] ?? '',
            'shift' => $row['shift'] ?? '',
            'department' => '',
        ], $rows);
    }

    public function getAttendanceRecords(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'attendance_date', [
            ['company', '=', $company],
            ['docstatus', '=', 1],
        ]);

        $rows = $this->safeList('Attendance', $erpFilters, [
            'name', 'employee', 'employee_name', 'attendance_date', 'status', 'department',
        ], 2000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee_id' => $row['employee'] ?? null,
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'attendance_date' => $row['attendance_date'] ?? null,
            'status' => $row['status'] ?? 'Absent',
            'department' => $row['department'] ?? 'General',
        ], $rows);
    }

    public function getLeaveApplications(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $erpFilters = [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
            ['from_date', '<=', $to],
            ['to_date', '>=', $from],
        ];

        $rows = $this->safeList('Leave Application', $erpFilters, [
            'name', 'employee', 'employee_name', 'leave_type', 'from_date', 'to_date',
            'status', 'total_leave_days', 'department', 'docstatus',
        ], 1000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee_id' => $row['employee'] ?? null,
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'leave_type' => $row['leave_type'] ?? 'Leave',
            'from_date' => $row['from_date'] ?? null,
            'to_date' => $row['to_date'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'total_leave_days' => (float) ($row['total_leave_days'] ?? 0),
            'department' => $row['department'] ?? 'General',
            'docstatus' => (int) ($row['docstatus'] ?? 0),
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
            'name', 'employee_name', 'department', 'designation', 'branch', 'default_shift',
        ], 2000);

        return array_map(fn ($row) => [
            'employee_id' => $row['name'],
            'employee' => $row['employee_name'] ?? $row['name'],
            'department' => $row['department'] ?? 'General',
            'designation' => $row['designation'] ?? '',
            'branch' => $row['branch'] ?? '',
            'default_shift' => $row['default_shift'] ?? '',
        ], $rows);
    }

    public function getShiftTypes(array $filters = []): array
    {
        $rows = $this->safeList('Shift Type', [], [
            'name', 'start_time', 'end_time',
        ], 100);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'start_time' => $this->normalizeTime($row['start_time'] ?? '09:00:00'),
            'end_time' => $this->normalizeTime($row['end_time'] ?? '17:00:00'),
        ], $rows);
    }

    public function getShiftAssignments(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $erpFilters = [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['start_date', '<=', $to],
        ];

        $rows = $this->safeList('Shift Assignment', $erpFilters, [
            'name', 'employee', 'employee_name', 'shift_type', 'start_date', 'end_date', 'status', 'docstatus',
        ], 2000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee_id' => $row['employee'] ?? null,
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'shift_type' => $row['shift_type'] ?? 'General',
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $row['end_date'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'docstatus' => (int) ($row['docstatus'] ?? 0),
        ], $rows);
    }

    protected function normalizeTime(string $time): string
    {
        $parts = explode(':', $time);

        return sprintf('%02d:%02d:00', (int) ($parts[0] ?? 9), (int) ($parts[1] ?? 0));
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
