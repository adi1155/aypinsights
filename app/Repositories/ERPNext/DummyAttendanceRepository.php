<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\AttendanceRepositoryInterface;
use Carbon\Carbon;

class DummyAttendanceRepository implements AttendanceRepositoryInterface
{
    public function getShiftTypes(array $filters = []): array
    {
        return [
            ['name' => 'Morning Shift', 'start_time' => '08:00:00', 'end_time' => '16:00:00'],
            ['name' => 'General Shift', 'start_time' => '09:00:00', 'end_time' => '17:00:00'],
            ['name' => 'Evening Shift', 'start_time' => '14:00:00', 'end_time' => '22:00:00'],
            ['name' => 'Night Shift', 'start_time' => '22:00:00', 'end_time' => '06:00:00'],
        ];
    }

    public function getShiftAssignments(array $filters = []): array
    {
        $assignments = [
            ['employee_id' => 'EMP-0001', 'employee' => 'Ahmed Khan', 'shift_type' => 'Morning Shift'],
            ['employee_id' => 'EMP-0002', 'employee' => 'Sara Ali', 'shift_type' => 'General Shift'],
            ['employee_id' => 'EMP-0003', 'employee' => 'Omar Farooq', 'shift_type' => 'General Shift'],
            ['employee_id' => 'EMP-0004', 'employee' => 'Fatima Noor', 'shift_type' => 'General Shift'],
            ['employee_id' => 'EMP-0005', 'employee' => 'Hassan Raza', 'shift_type' => 'Evening Shift'],
            ['employee_id' => 'EMP-0006', 'employee' => 'Ayesha Malik', 'shift_type' => 'General Shift'],
            ['employee_id' => 'EMP-0007', 'employee' => 'Bilal Hussain', 'shift_type' => 'Morning Shift'],
            ['employee_id' => 'EMP-0008', 'employee' => 'Zainab Shah', 'shift_type' => 'Evening Shift'],
        ];

        $start = ($filters['from_date'] ?? now()->startOfMonth()->toDateString());
        $end = ($filters['to_date'] ?? now()->toDateString());

        return array_map(fn ($a, $i) => array_merge($a, [
            'name' => 'HR-SHA-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'Active',
            'docstatus' => 1,
        ]), $assignments, array_keys($assignments));
    }

    public function getEmployeeCheckins(array $filters = []): array
    {
        $shiftMap = collect($this->getShiftAssignments($filters))->keyBy('employee_id');
        $shiftTimes = collect($this->getShiftTypes($filters))->keyBy('name');

        $employees = [
            ['id' => 'EMP-0001', 'name' => 'Ahmed Khan', 'dept' => 'Production'],
            ['id' => 'EMP-0002', 'name' => 'Sara Ali', 'dept' => 'Sales'],
            ['id' => 'EMP-0003', 'name' => 'Omar Farooq', 'dept' => 'Admin'],
            ['id' => 'EMP-0004', 'name' => 'Fatima Noor', 'dept' => 'Finance'],
            ['id' => 'EMP-0005', 'name' => 'Hassan Raza', 'dept' => 'Logistics'],
            ['id' => 'EMP-0006', 'name' => 'Ayesha Malik', 'dept' => 'HR'],
            ['id' => 'EMP-0007', 'name' => 'Bilal Hussain', 'dept' => 'Production'],
            ['id' => 'EMP-0008', 'name' => 'Zainab Shah', 'dept' => 'Sales'],
        ];

        $rows = [];
        $from = Carbon::parse($filters['from_date'] ?? now()->startOfMonth());
        $to = Carbon::parse($filters['to_date'] ?? now());

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }
            foreach ($employees as $emp) {
                if (rand(0, 10) <= 2) {
                    continue;
                }

                $shiftName = $shiftMap->get($emp['id'])['shift_type'] ?? 'General Shift';
                $startTime = $shiftTimes->get($shiftName)['start_time'] ?? '09:00:00';
                [$h, $m] = array_map('intval', explode(':', $startTime));
                $lateMins = rand(0, 10) > 7 ? rand(20, 45) : rand(-10, 10);
                $in = $day->copy()->setTime($h, max(0, $m + $lateMins));

                $rows[] = [
                    'name' => 'CHK-'.$day->format('ymd').'-'.$emp['id'],
                    'employee_id' => $emp['id'],
                    'employee' => $emp['name'],
                    'time' => $in->format('Y-m-d H:i:s'),
                    'date' => $day->toDateString(),
                    'log_type' => 'IN',
                    'device_id' => 'Gate-1',
                    'shift' => $shiftName,
                    'department' => $emp['dept'],
                ];
            }
        }

        return $rows;
    }

    public function getAttendanceRecords(array $filters = []): array
    {
        return [];
    }

    public function getLeaveApplications(array $filters = []): array
    {
        return [
            ['name' => 'HR-LAP-001', 'employee_id' => 'EMP-0003', 'employee' => 'Omar Farooq', 'leave_type' => 'Casual Leave', 'from_date' => now()->toDateString(), 'to_date' => now()->toDateString(), 'status' => 'Approved', 'total_leave_days' => 1, 'department' => 'Admin', 'docstatus' => 1],
            ['name' => 'HR-LAP-002', 'employee_id' => 'EMP-0006', 'employee' => 'Ayesha Malik', 'leave_type' => 'Sick Leave', 'from_date' => now()->subDay()->toDateString(), 'to_date' => now()->addDay()->toDateString(), 'status' => 'Approved', 'total_leave_days' => 3, 'department' => 'HR', 'docstatus' => 1],
            ['name' => 'HR-LAP-003', 'employee_id' => 'EMP-0008', 'employee' => 'Zainab Shah', 'leave_type' => 'Annual Leave', 'from_date' => now()->addDays(3)->toDateString(), 'to_date' => now()->addDays(7)->toDateString(), 'status' => 'Open', 'total_leave_days' => 5, 'department' => 'Sales', 'docstatus' => 0],
            ['name' => 'HR-LAP-004', 'employee_id' => 'EMP-0002', 'employee' => 'Sara Ali', 'leave_type' => 'Casual Leave', 'from_date' => now()->subDays(5)->toDateString(), 'to_date' => now()->subDays(4)->toDateString(), 'status' => 'Approved', 'total_leave_days' => 2, 'department' => 'Sales', 'docstatus' => 1],
        ];
    }

    public function getActiveEmployees(array $filters = []): array
    {
        return [
            ['employee_id' => 'EMP-0001', 'employee' => 'Ahmed Khan', 'department' => 'Production', 'designation' => 'Supervisor', 'branch' => 'Head Office', 'default_shift' => 'Morning Shift'],
            ['employee_id' => 'EMP-0002', 'employee' => 'Sara Ali', 'department' => 'Sales', 'designation' => 'Executive', 'branch' => 'North', 'default_shift' => 'General Shift'],
            ['employee_id' => 'EMP-0003', 'employee' => 'Omar Farooq', 'department' => 'Admin', 'designation' => 'Officer', 'branch' => 'Head Office', 'default_shift' => 'General Shift'],
            ['employee_id' => 'EMP-0004', 'employee' => 'Fatima Noor', 'department' => 'Finance', 'designation' => 'Manager', 'branch' => 'Head Office', 'default_shift' => 'General Shift'],
            ['employee_id' => 'EMP-0005', 'employee' => 'Hassan Raza', 'department' => 'Logistics', 'designation' => 'Officer', 'branch' => 'South', 'default_shift' => 'Evening Shift'],
            ['employee_id' => 'EMP-0006', 'employee' => 'Ayesha Malik', 'department' => 'HR', 'designation' => 'Executive', 'branch' => 'Head Office', 'default_shift' => 'General Shift'],
            ['employee_id' => 'EMP-0007', 'employee' => 'Bilal Hussain', 'department' => 'Production', 'designation' => 'Operator', 'branch' => 'North Plant', 'default_shift' => 'Morning Shift'],
            ['employee_id' => 'EMP-0008', 'employee' => 'Zainab Shah', 'department' => 'Sales', 'designation' => 'Executive', 'branch' => 'South', 'default_shift' => 'Evening Shift'],
        ];
    }
}
