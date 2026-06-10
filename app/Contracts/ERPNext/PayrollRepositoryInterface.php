<?php

namespace App\Contracts\ERPNext;

interface PayrollRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSalarySlips(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPayrollEntries(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveEmployees(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdditionalSalaries(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEmployeeAdvances(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEmployeePayments(array $filters = []): array;
}
