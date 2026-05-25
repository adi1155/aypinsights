<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\ExpenseRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ExpenseService
{
    public function __construct(protected ExpenseRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $cacheKey = 'expense_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            $claims = $this->repository->getExpenseClaims($filters);
            $glExpenses = $this->repository->getGLExpenses($filters);
            $costCenters = $this->repository->getCostCenterBreakdown($filters);
            $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $filters['to_date'] ?? now()->toDateString();
            $periodTotal = (float) collect($claims)
                ->filter(function ($c) use ($fromDate, $toDate) {
                    $d = $c['posting_date'] ?? null;
                    return $d && $d >= $fromDate && $d <= $toDate;
                })
                ->sum('total_claimed_amount');
            $monthlyTotal = (float) collect($glExpenses)->sum('amount');
            $pending = collect($claims)->whereIn('approval_status', ['Pending', 'Draft']);

            return [
                'filters' => $filters,
                'kpis' => [
                    'period_expenses' => $periodTotal,
                    'todays_expenses' => $periodTotal,
                    'monthly_expenses' => $monthlyTotal,
                    'department_wise' => (float) (collect($claims)->groupBy('department')->map->sum('total_claimed_amount')->sortDesc()->first() ?? 0),
                    'budget_variance' => 0,
                    'top_category' => collect($glExpenses)->sortByDesc('amount')->first()['account'] ?? 'Salaries',
                    'average_daily_expense' => round($monthlyTotal / max(now()->day, 1)),
                    'unapproved_expenses' => $pending->sum('total_claimed_amount'),
                    'pending_claims' => $pending->count(),
                ],
                'charts' => [
                    'expense_trend' => $this->dailyTrend($claims),
                    'department_wise' => [
                        'labels' => collect($claims)->pluck('department')->unique()->values()->all(),
                        'series' => collect($claims)->groupBy('department')->map->sum('total_claimed_amount')->values()->all(),
                    ],
                    'cost_center' => [
                        'labels' => collect($costCenters)->pluck('cost_center')->all(),
                        'series' => collect($costCenters)->pluck('amount')->all(),
                    ],
                    'budget_vs_actual' => [
                        'labels' => collect($glExpenses)->pluck('account')->all(),
                        'budget' => collect($glExpenses)->pluck('amount')->map(fn ($a) => $a * 1.1)->all(),
                        'actual' => collect($glExpenses)->pluck('amount')->all(),
                    ],
                ],
                'tables' => [
                    'latest_expenses' => collect($claims)->take(15)->values()->all(),
                    'high_value' => collect($claims)->sortByDesc('total_claimed_amount')->take(10)->values()->all(),
                    'pending_approvals' => $pending->values()->all(),
                    'department_spending' => collect($claims)->groupBy('department')->map(fn ($g, $d) => [
                        'department' => $d,
                        'amount' => $g->sum('total_claimed_amount'),
                    ])->values()->all(),
                    'breakdown' => $glExpenses,
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
            ];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $claims
     */
    protected function dailyTrend(array $claims): array
    {
        $byDate = collect($claims)
            ->filter(fn ($c) => ! empty($c['posting_date']))
            ->groupBy('posting_date')
            ->map->sum('total_claimed_amount')
            ->sortKeys();

        return [
            'labels' => $byDate->keys()->take(30)->values()->all(),
            'series' => $byDate->values()->take(30)->map(fn ($v) => (float) $v)->all(),
        ];
    }
}
