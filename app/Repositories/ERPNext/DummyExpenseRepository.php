<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\ExpenseRepositoryInterface;

class DummyExpenseRepository implements ExpenseRepositoryInterface
{
    public function getExpenseClaims(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'EXP-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'employee' => ['Ahmed Khan', 'Sara Ali', 'Omar Farooq'][$i % 3],
            'total_claimed_amount' => rand(15000, 185000),
            'approval_status' => ['Approved', 'Pending', 'Rejected'][rand(0, 2)],
            'posting_date' => now()->subDays(rand(0, 10))->toDateString(),
            'department' => ['Sales', 'Operations', 'Admin', 'IT'][$i % 4],
        ], range(1, 20));
    }

    public function getGLExpenses(array $filters = []): array
    {
        return [
            ['account' => 'Salaries', 'amount' => 1250000],
            ['account' => 'Utilities', 'amount' => 185000],
            ['account' => 'Travel', 'amount' => 92000],
            ['account' => 'Marketing', 'amount' => 340000],
            ['account' => 'Rent', 'amount' => 450000],
        ];
    }

    public function getCostCenterBreakdown(array $filters = []): array
    {
        return [
            ['cost_center' => 'Head Office', 'amount' => 890000],
            ['cost_center' => 'Manufacturing', 'amount' => 1450000],
            ['cost_center' => 'Sales North', 'amount' => 520000],
            ['cost_center' => 'Sales South', 'amount' => 480000],
        ];
    }
}
