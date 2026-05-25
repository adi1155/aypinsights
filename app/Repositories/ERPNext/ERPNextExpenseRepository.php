<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\ExpenseRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Carbon\Carbon;

class ERPNextExpenseRepository implements ExpenseRepositoryInterface
{
    use BuildsErpNextFilters;

    public function __construct(protected ERPNextClient $client) {}

    public function getExpenseClaims(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
        ]);

        $rows = $this->client->getList('Expense Claim', $erpFilters, [
            'name', 'employee_name', 'employee', 'total_claimed_amount', 'approval_status',
            'posting_date', 'department', 'expense_approver',
        ], 500);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'employee' => $row['employee_name'] ?? $row['employee'] ?? 'N/A',
            'total_claimed_amount' => (float) ($row['total_claimed_amount'] ?? 0),
            'approval_status' => $row['approval_status'] ?? 'Draft',
            'posting_date' => $row['posting_date'] ?? null,
            'department' => $row['department'] ?? 'General',
        ], $rows);
    }

    public function getGLExpenses(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $from = $filters['from_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? Carbon::now()->endOfMonth()->toDateString();

        $erpFilters = [
            ['company', '=', $company],
            ['is_cancelled', '=', 0],
            ['posting_date', '>=', $from],
            ['posting_date', '<=', $to],
        ];

        $entries = $this->client->getList('GL Entry', $erpFilters, [
            'account', 'debit', 'credit',
        ], 2000);

        return collect($entries)
            ->filter(fn ($e) => ($e['debit'] ?? 0) > 0 && $this->isExpenseAccount((string) ($e['account'] ?? '')))
            ->groupBy('account')
            ->map(fn ($group, $account) => [
                'account' => $this->shortAccountName($account),
                'amount' => (float) $group->sum('debit'),
            ])
            ->sortByDesc('amount')
            ->take(15)
            ->values()
            ->all();
    }

    public function getCostCenterBreakdown(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $from = $filters['from_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? Carbon::now()->endOfMonth()->toDateString();

        $erpFilters = [
            ['company', '=', $company],
            ['is_cancelled', '=', 0],
            ['posting_date', '>=', $from],
            ['posting_date', '<=', $to],
            ['cost_center', '!=', ''],
        ];

        $entries = $this->client->getList('GL Entry', $erpFilters, [
            'cost_center', 'debit', 'credit',
        ], 2000);

        return collect($entries)
            ->filter(fn ($e) => ($e['debit'] ?? 0) > 0)
            ->groupBy('cost_center')
            ->map(fn ($group, $cc) => [
                'cost_center' => $cc,
                'amount' => (float) $group->sum('debit'),
            ])
            ->sortByDesc('amount')
            ->take(12)
            ->values()
            ->all();
    }

    protected function isExpenseAccount(string $account): bool
    {
        static $cache = null;
        if ($cache === null) {
            $accounts = $this->client->getList('Account', [
                ['root_type', '=', 'Expense'],
                ['is_group', '=', 0],
            ], ['name'], 500);
            $cache = collect($accounts)->pluck('name')->flip()->all();
        }

        return isset($cache[$account]) || str_contains(strtolower($account), 'expense');
    }

    protected function shortAccountName(string $account): string
    {
        $parts = explode(' - ', $account);

        return trim($parts[1] ?? $parts[0]);
    }
}
