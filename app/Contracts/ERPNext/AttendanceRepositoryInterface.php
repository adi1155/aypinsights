<?php

namespace App\Contracts\ERPNext;

interface AttendanceRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEmployeeCheckins(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAttendanceRecords(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLeaveApplications(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveEmployees(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getShiftTypes(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getShiftAssignments(array $filters = []): array;
}
