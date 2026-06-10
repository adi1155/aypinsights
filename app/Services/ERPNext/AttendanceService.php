<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AttendanceService
{
    protected const LATE_GRACE_MINUTES = 15;

    public function __construct(protected AttendanceRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $cacheKey = 'attendance_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            $employees = collect($this->repository->getActiveEmployees($filters));
            $checkins = collect($this->repository->getEmployeeCheckins($filters));
            $leaves = collect($this->repository->getLeaveApplications($filters));
            $attendanceRecords = collect($this->repository->getAttendanceRecords($filters));
            $shiftTypes = collect($this->repository->getShiftTypes($filters))->keyBy('name');
            $shiftAssignments = collect($this->repository->getShiftAssignments($filters));

            $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $filters['to_date'] ?? now()->toDateString();
            $today = now()->toDateString();

            $deptMap = $employees->keyBy('employee_id');
            $checkins = $checkins->map(function ($c) use ($deptMap, $shiftTypes, $shiftAssignments) {
                $dept = $deptMap->get($c['employee_id'])['department'] ?? $c['department'] ?? 'General';
                $c['department'] = $dept;
                if (empty($c['shift'])) {
                    $shift = $this->resolveEmployeeShift($c['employee_id'], $c['date'] ?? '', $shiftAssignments, $shiftTypes, $deptMap->get($c['employee_id']));
                    $c['shift'] = $shift['name'] ?? 'Unassigned';
                }

                return $c;
            });

            $presenceByDay = $this->buildPresenceIndex($checkins, $shiftAssignments, $shiftTypes, $employees);
            $approvedLeaves = $leaves->filter(fn ($l) => ($l['status'] ?? '') === 'Approved' && ($l['docstatus'] ?? 0) === 1);
            $pendingLeaves = $leaves->filter(fn ($l) => in_array($l['status'] ?? '', ['Open', 'Draft'], true) && ($l['docstatus'] ?? 0) === 0);

            $todayStatus = $this->buildDayStatus($employees, $presenceByDay, $approvedLeaves, $today, $shiftAssignments, $shiftTypes);
            $scheduledToday = $todayStatus->filter(fn ($r) => $r['has_shift']);
            $presentToday = $todayStatus->where('status', 'Present')->count();
            $onLeaveToday = $todayStatus->where('status', 'On Leave')->count();
            $absentToday = $todayStatus->where('status', 'Absent')->count();
            $onTimeToday = $todayStatus->where('status', 'Present')->where('late', false)->count();
            $lateToday = $todayStatus->where('late', true)->count();
            $headcount = $employees->count();
            $shiftsActiveToday = $scheduledToday->pluck('shift')->unique()->filter()->count();

            $workingDays = $this->countWeekdays($fromDate, $toDate);
            $presentEmployeeDays = $this->countPresentEmployeeDays($presenceByDay, $fromDate, $toDate);
            $scheduledEmployeeDays = $this->countScheduledEmployeeDays($employees, $shiftAssignments, $fromDate, $toDate);
            $expectedEmployeeDays = max(1, $scheduledEmployeeDays ?: ($headcount * max(1, $workingDays)));
            $attendanceRate = round(($presentEmployeeDays / $expectedEmployeeDays) * 100, 1);

            return [
                'filters' => $filters,
                'kpis' => [
                    'active_headcount' => $headcount,
                    'employees_scheduled_today' => $scheduledToday->count(),
                    'shifts_active_today' => $shiftsActiveToday,
                    'present_today' => $presentToday,
                    'on_time_today' => $onTimeToday,
                    'late_arrivals_today' => $lateToday,
                    'on_leave_today' => $onLeaveToday,
                    'absent_today' => $absentToday,
                    'attendance_rate_pct' => $attendanceRate,
                    'total_checkins' => $checkins->count(),
                    'unique_present_days' => $presentEmployeeDays,
                    'pending_leave_requests' => $pendingLeaves->count(),
                    'approved_leaves_period' => $approvedLeaves->count(),
                    'leave_days_period' => (float) $approvedLeaves->sum('total_leave_days'),
                    'avg_daily_present' => $workingDays > 0 ? round($presentEmployeeDays / $workingDays, 1) : 0,
                ],
                'kpi_types' => [
                    'active_headcount' => 'count',
                    'employees_scheduled_today' => 'count',
                    'shifts_active_today' => 'count',
                    'present_today' => 'count',
                    'on_time_today' => 'count',
                    'late_arrivals_today' => 'count',
                    'on_leave_today' => 'count',
                    'absent_today' => 'count',
                    'attendance_rate_pct' => 'percent',
                    'total_checkins' => 'count',
                    'unique_present_days' => 'count',
                    'pending_leave_requests' => 'count',
                    'approved_leaves_period' => 'count',
                    'avg_daily_present' => 'decimal',
                ],
                'charts' => [
                    'daily_attendance' => $this->dailyAttendanceTrend($presenceByDay, $fromDate, $toDate),
                    'shift_wise_present' => $this->shiftWisePresentChart($todayStatus),
                    'shift_punctuality' => $this->shiftPunctualityChart($todayStatus),
                    'department_present' => $this->departmentPresentChart($employees, $presenceByDay, $today),
                    'leave_type_breakdown' => [
                        'labels' => $approvedLeaves->groupBy('leave_type')->keys()->take(8)->values()->all(),
                        'series' => $approvedLeaves->groupBy('leave_type')->map->sum('total_leave_days')->values()->take(8)->all(),
                    ],
                    'attendance_vs_leave' => [
                        'labels' => ['Present', 'On Leave', 'Absent'],
                        'series' => [$presentToday, $onLeaveToday, max(0, $absentToday)],
                    ],
                    'monthly_leave_trend' => $this->monthlyLeaveTrend($leaves, $filters),
                    'checkin_log_types' => [
                        'labels' => $checkins->groupBy('log_type')->keys()->filter()->values()->all() ?: ['IN', 'OUT'],
                        'series' => $checkins->groupBy('log_type')->map->count()->values()->all() ?: [0, 0],
                    ],
                ],
                'tables' => [
                    'todays_attendance' => $todayStatus->values()->all(),
                    'shift_wise_summary' => $this->shiftWiseSummary($todayStatus)->values()->all(),
                    'shift_assignments' => $shiftAssignments->sortBy('shift_type')->take(30)->values()->all(),
                    'recent_checkins' => $checkins->sortByDesc('time')->take(20)->values()->all(),
                    'pending_leave_approvals' => $pendingLeaves->values()->all(),
                    'approved_leaves' => $approvedLeaves->sortByDesc('from_date')->take(15)->values()->all(),
                    'absent_today' => $todayStatus->where('status', 'Absent')->values()->all(),
                    'late_today' => $todayStatus->where('late', true)->values()->all(),
                    'department_summary' => $this->departmentSummary($employees, $presenceByDay, $approvedLeaves, $today, $shiftAssignments, $shiftTypes),
                    'erp_attendance_records' => $attendanceRecords->sortByDesc('attendance_date')->take(15)->values()->all(),
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
                'attendance_rule' => 'Present = at least one check-in that day. Late = first check-in after assigned shift start + '.self::LATE_GRACE_MINUTES.' min grace.',
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $checkins
     * @param  Collection<int, array<string, mixed>>  $shiftAssignments
     * @param  Collection<string, array<string, mixed>>  $shiftTypes
     * @param  Collection<int, array<string, mixed>>  $employees
     * @return array<string, array<string, mixed>>
     */
    protected function buildPresenceIndex(
        Collection $checkins,
        Collection $shiftAssignments,
        Collection $shiftTypes,
        Collection $employees,
    ): array {
        $index = [];
        $empMap = $employees->keyBy('employee_id');

        foreach ($checkins as $checkin) {
            $employeeId = $checkin['employee_id'] ?? $checkin['employee'];
            $date = $checkin['date'] ?? (isset($checkin['time']) ? substr((string) $checkin['time'], 0, 10) : null);
            if (! $employeeId || ! $date) {
                continue;
            }

            $shift = $this->resolveEmployeeShift(
                $employeeId,
                $date,
                $shiftAssignments,
                $shiftTypes,
                $empMap->get($employeeId),
                $checkin['shift'] ?? null,
            );

            $key = $employeeId.'|'.$date;
            if (! isset($index[$key])) {
                $index[$key] = [
                    'employee_id' => $employeeId,
                    'employee' => $checkin['employee'],
                    'department' => $checkin['department'] ?? 'General',
                    'date' => $date,
                    'shift' => $shift['name'],
                    'shift_start' => $shift['start_time'],
                    'shift_end' => $shift['end_time'],
                    'first_checkin' => $checkin['time'],
                    'checkin_count' => 1,
                    'late' => $this->isLateArrival($checkin['time'], $shift),
                ];
            } else {
                $index[$key]['checkin_count']++;
                if ($checkin['time'] < $index[$key]['first_checkin']) {
                    $index[$key]['first_checkin'] = $checkin['time'];
                    $index[$key]['late'] = $this->isLateArrival($checkin['time'], $shift);
                }
            }
        }

        return $index;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $employees
     * @param  Collection<int, array<string, mixed>>  $shiftAssignments
     * @param  Collection<string, array<string, mixed>>  $shiftTypes
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildDayStatus(
        Collection $employees,
        array $presenceByDay,
        Collection $leaves,
        string $date,
        Collection $shiftAssignments,
        Collection $shiftTypes,
    ): Collection {
        return $employees->map(function ($emp) use ($presenceByDay, $leaves, $date, $shiftAssignments, $shiftTypes) {
            $id = $emp['employee_id'];
            $key = $id.'|'.$date;
            $presence = $presenceByDay[$key] ?? null;
            $shift = $this->resolveEmployeeShift($id, $date, $shiftAssignments, $shiftTypes, $emp, $presence['shift'] ?? null);
            $hasShift = $shift['assigned'] ?? false;

            if ($presence) {
                return [
                    'employee_id' => $id,
                    'employee' => $emp['employee'],
                    'department' => $emp['department'] ?? 'General',
                    'shift' => $shift['name'],
                    'shift_start' => $shift['start_time'],
                    'shift_end' => $shift['end_time'],
                    'status' => 'Present',
                    'first_checkin' => $presence['first_checkin'],
                    'checkin_count' => $presence['checkin_count'],
                    'late' => $presence['late'],
                    'has_shift' => $hasShift,
                ];
            }

            if ($this->isOnLeave($id, $date, $leaves)) {
                $leave = $leaves->first(fn ($l) => $this->isOnLeave($id, $date, collect([$l])));

                return [
                    'employee_id' => $id,
                    'employee' => $emp['employee'],
                    'department' => $emp['department'] ?? 'General',
                    'shift' => $shift['name'],
                    'shift_start' => $shift['start_time'],
                    'shift_end' => $shift['end_time'],
                    'status' => 'On Leave',
                    'leave_type' => $leave['leave_type'] ?? 'Leave',
                    'first_checkin' => null,
                    'checkin_count' => 0,
                    'late' => false,
                    'has_shift' => $hasShift,
                ];
            }

            if (! $hasShift) {
                return [
                    'employee_id' => $id,
                    'employee' => $emp['employee'],
                    'department' => $emp['department'] ?? 'General',
                    'shift' => 'No Shift',
                    'shift_start' => null,
                    'shift_end' => null,
                    'status' => 'Off Shift',
                    'first_checkin' => null,
                    'checkin_count' => 0,
                    'late' => false,
                    'has_shift' => false,
                ];
            }

            return [
                'employee_id' => $id,
                'employee' => $emp['employee'],
                'department' => $emp['department'] ?? 'General',
                'shift' => $shift['name'],
                'shift_start' => $shift['start_time'],
                'shift_end' => $shift['end_time'],
                'status' => 'Absent',
                'first_checkin' => null,
                'checkin_count' => 0,
                'late' => false,
                'has_shift' => true,
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $shiftAssignments
     * @param  Collection<string, array<string, mixed>>  $shiftTypes
     * @param  array<string, mixed>|null  $employee
     * @return array{name: string, start_time: ?string, end_time: ?string, assigned: bool}
     */
    protected function resolveEmployeeShift(
        string $employeeId,
        string $date,
        Collection $shiftAssignments,
        Collection $shiftTypes,
        ?array $employee = null,
        ?string $checkinShift = null,
    ): array {
        $assignment = $shiftAssignments
            ->filter(fn ($a) => ($a['employee_id'] ?? '') === $employeeId && $this->assignmentCoversDate($a, $date))
            ->sortByDesc('start_date')
            ->first();

        $shiftName = $checkinShift
            ?: ($assignment['shift_type'] ?? null)
            ?: ($employee['default_shift'] ?? null)
            ?: 'Unassigned';

        $type = $shiftTypes->get($shiftName);

        return [
            'name' => $shiftName,
            'start_time' => $type['start_time'] ?? null,
            'end_time' => $type['end_time'] ?? null,
            'assigned' => $assignment !== null || ! empty($employee['default_shift']) || ! empty($checkinShift),
        ];
    }

    /**
     * @param  array<string, mixed>  $assignment
     */
    protected function assignmentCoversDate(array $assignment, string $date): bool
    {
        if (($assignment['docstatus'] ?? 0) !== 1) {
            return false;
        }
        if (! empty($assignment['start_date']) && $date < $assignment['start_date']) {
            return false;
        }
        if (! empty($assignment['end_date']) && $date > $assignment['end_date']) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{name: string, start_time: ?string, end_time: ?string, assigned: bool}  $shift
     */
    protected function isLateArrival(?string $time, array $shift): bool
    {
        if (! $time || empty($shift['start_time'])) {
            return false;
        }

        try {
            $checkin = Carbon::parse($time);
            $shiftStart = Carbon::parse($checkin->toDateString().' '.$shift['start_time']);

            return $checkin->gt($shiftStart->copy()->addMinutes(self::LATE_GRACE_MINUTES));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $leaves
     */
    protected function isOnLeave(string $employeeId, string $date, Collection $leaves): bool
    {
        return $leaves->contains(function ($leave) use ($employeeId, $date) {
            if (($leave['employee_id'] ?? '') !== $employeeId) {
                return false;
            }
            if (($leave['status'] ?? '') !== 'Approved' || ($leave['docstatus'] ?? 0) !== 1) {
                return false;
            }

            return $date >= ($leave['from_date'] ?? '') && $date <= ($leave['to_date'] ?? '');
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $employees
     * @param  Collection<int, array<string, mixed>>  $shiftAssignments
     */
    protected function countScheduledEmployeeDays(
        Collection $employees,
        Collection $shiftAssignments,
        string $from,
        string $to,
    ): int {
        $count = 0;
        $period = CarbonPeriod::create($from, $to);

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                continue;
            }
            $d = $day->toDateString();
            foreach ($employees as $emp) {
                $shift = $this->resolveEmployeeShift($emp['employee_id'], $d, $shiftAssignments, collect(), $emp);
                if ($shift['assigned']) {
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function countWeekdays(string $from, string $to): int
    {
        $period = CarbonPeriod::create($from, $to);
        $count = 0;
        foreach ($period as $day) {
            if (! $day->isWeekend()) {
                $count++;
            }
        }

        return max(1, $count);
    }

    protected function countPresentEmployeeDays(array $presenceByDay, string $from, string $to): int
    {
        return collect($presenceByDay)->filter(fn ($p) => $p['date'] >= $from && $p['date'] <= $to)->count();
    }

    protected function dailyAttendanceTrend(array $presenceByDay, string $from, string $to): array
    {
        $labels = [];
        $present = [];
        $period = CarbonPeriod::create($from, $to);

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                continue;
            }
            $d = $day->toDateString();
            $labels[] = $day->format('d M');
            $present[] = collect($presenceByDay)->filter(fn ($p) => $p['date'] === $d)->count();
        }

        return compact('labels', 'present');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $todayStatus
     * @return array{labels: array<int, string>, series: array<int, int>}
     */
    protected function shiftWisePresentChart(Collection $todayStatus): array
    {
        $grouped = $todayStatus
            ->where('status', 'Present')
            ->groupBy('shift')
            ->map->count()
            ->sortDesc();

        return [
            'labels' => $grouped->keys()->values()->all(),
            'series' => $grouped->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $todayStatus
     * @return array{labels: array<int, string>, on_time: array<int, int>, late: array<int, int>}
     */
    protected function shiftPunctualityChart(Collection $todayStatus): array
    {
        $present = $todayStatus->where('status', 'Present')->groupBy('shift');
        $labels = $present->keys()->values()->all();
        $onTime = [];
        $late = [];

        foreach ($labels as $shift) {
            $rows = $present->get($shift, collect());
            $onTime[] = $rows->where('late', false)->count();
            $late[] = $rows->where('late', true)->count();
        }

        return compact('labels', 'onTime', 'late');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $employees
     */
    protected function departmentPresentChart(Collection $employees, array $presenceByDay, string $date): array
    {
        $byDept = $employees->groupBy('department')->map(function ($group) use ($presenceByDay, $date) {
            return $group->filter(fn ($emp) => isset($presenceByDay[$emp['employee_id'].'|'.$date]))->count();
        });

        return [
            'labels' => $byDept->keys()->values()->all(),
            'series' => $byDept->values()->all(),
        ];
    }

    protected function monthlyLeaveTrend(Collection $leaves, array $filters): array
    {
        $labels = [];
        $series = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();
            $series[] = (float) $leaves
                ->filter(fn ($l) => ($l['docstatus'] ?? 0) === 1 && ($l['from_date'] ?? '') <= $end && ($l['to_date'] ?? '') >= $start)
                ->sum('total_leave_days');
        }

        return compact('labels', 'series');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $todayStatus
     * @return Collection<int, array<string, mixed>>
     */
    protected function shiftWiseSummary(Collection $todayStatus): Collection
    {
        return $todayStatus
            ->filter(fn ($r) => ($r['has_shift'] ?? false) && ($r['shift'] ?? '') !== 'No Shift')
            ->groupBy('shift')
            ->map(function ($rows, $shift) {
                return [
                    'shift' => $shift,
                    'scheduled' => $rows->count(),
                    'present' => $rows->where('status', 'Present')->count(),
                    'on_leave' => $rows->where('status', 'On Leave')->count(),
                    'absent' => $rows->where('status', 'Absent')->count(),
                    'on_time' => $rows->where('status', 'Present')->where('late', false)->count(),
                    'late' => $rows->where('late', true)->count(),
                    'shift_start' => $rows->first()['shift_start'] ?? null,
                    'shift_end' => $rows->first()['shift_end'] ?? null,
                ];
            })
            ->sortByDesc('scheduled')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $employees
     * @param  Collection<int, array<string, mixed>>  $shiftAssignments
     * @param  Collection<string, array<string, mixed>>  $shiftTypes
     * @return array<int, array<string, mixed>>
     */
    protected function departmentSummary(
        Collection $employees,
        array $presenceByDay,
        Collection $leaves,
        string $date,
        Collection $shiftAssignments,
        Collection $shiftTypes,
    ): array {
        return $employees->groupBy('department')->map(function ($group, $dept) use ($presenceByDay, $leaves, $date, $shiftAssignments, $shiftTypes) {
            $present = 0;
            $onLeave = 0;
            $absent = 0;
            $late = 0;

            foreach ($group as $emp) {
                $key = $emp['employee_id'].'|'.$date;
                $shift = $this->resolveEmployeeShift($emp['employee_id'], $date, $shiftAssignments, $shiftTypes, $emp);

                if (isset($presenceByDay[$key])) {
                    $present++;
                    if ($presenceByDay[$key]['late'] ?? false) {
                        $late++;
                    }
                } elseif ($this->isOnLeave($emp['employee_id'], $date, $leaves)) {
                    $onLeave++;
                } elseif ($shift['assigned']) {
                    $absent++;
                }
            }

            return [
                'department' => $dept,
                'headcount' => $group->count(),
                'present' => $present,
                'on_leave' => $onLeave,
                'absent' => $absent,
                'late' => $late,
            ];
        })->sortByDesc('headcount')->values()->all();
    }
}
