<?php

namespace App\Contracts\ERPNext;

interface ExpenseRepositoryInterface
{
    public function getExpenseClaims(array $filters = []): array;

    public function getGLExpenses(array $filters = []): array;

    public function getCostCenterBreakdown(array $filters = []): array;
}
