<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\APRepositoryInterface;
use App\Contracts\ERPNext\ARRepositoryInterface;
use App\Repositories\ERPNext\ERPNextIASRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Builds IAS 1-aligned executive financial presentation from ERPNext GL data.
 */
class IASFinancialService
{
    /** @var array<int, string> */
    protected array $currentAssetTypes = ['Bank', 'Cash', 'Receivable', 'Stock', 'Current Asset', 'Temporary'];

    /** @var array<int, string> */
    protected array $currentLiabilityTypes = ['Payable', 'Tax', 'Current Liability', 'Liability'];

    public function __construct(
        protected ERPNextIASRepository $repository,
        protected ARRepositoryInterface $arRepository,
        protected APRepositoryInterface $apRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getCeoIasDashboard(array $filters): array
    {
        $cacheKey = 'ceo.ias.'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            $raw = $this->repository->getFinancialStatements($filters);
            $bs = $raw['balance_sheet'];
            $pl = $raw['profit_loss'];

            $assets = abs((float) ($bs['totals']['Asset'] ?? 0));
            $liabilities = abs((float) ($bs['totals']['Liability'] ?? 0));
            $equity = abs((float) ($bs['totals']['Equity'] ?? 0));
            $income = abs((float) ($pl['totals']['Income'] ?? 0));
            $expenses = abs((float) ($pl['totals']['Expense'] ?? 0));
            $netProfit = $income - $expenses;

            $currentAssets = $this->sumAccountTypes($bs['by_account_type']['Asset'] ?? [], $this->currentAssetTypes);
            $nonCurrentAssets = max(0, $assets - $currentAssets);
            $currentLiabilities = $this->sumAccountTypes($bs['by_account_type']['Liability'] ?? [], $this->currentLiabilityTypes);
            $nonCurrentLiabilities = max(0, $liabilities - $currentLiabilities);

            $arData = $this->safeAr($filters);
            $apData = $this->safeAp($filters);

            $ratios = $this->computeRatios(
                $assets, $liabilities, $equity, $income, $expenses, $netProfit,
                $currentAssets, $currentLiabilities,
                (float) ($arData['total_receivables'] ?? 0),
                (float) ($apData['total_payables'] ?? 0)
            );

            $insights = $this->buildInsights($assets, $liabilities, $equity, $income, $expenses, $netProfit, $ratios, $arData, $apData);

            return [
                'filters' => $filters,
                'currency' => config('erpnext.default_currency', 'PKR'),
                'statement_of_financial_position' => [
                    'total_assets' => $assets,
                    'current_assets' => $currentAssets,
                    'non_current_assets' => $nonCurrentAssets,
                    'total_liabilities' => $liabilities,
                    'current_liabilities' => $currentLiabilities,
                    'non_current_liabilities' => $nonCurrentLiabilities,
                    'total_equity' => $equity,
                    'total_liabilities_and_equity' => $liabilities + $equity,
                    'accounting_equation_balanced' => abs($assets - ($liabilities + $equity)) < max($assets * 0.02, 1),
                ],
                'statement_of_profit_or_loss' => [
                    'total_income' => $income,
                    'total_expenses' => $expenses,
                    'gross_profit' => $income - $this->estimateCogs($pl),
                    'operating_expenses' => $expenses,
                    'profit_before_tax' => $netProfit,
                    'net_profit_loss' => $netProfit,
                    'net_profit_margin_pct' => $income > 0 ? round(($netProfit / $income) * 100, 2) : 0,
                ],
                'ratios' => $ratios,
                'insights' => $insights,
                'operational' => [
                    'total_receivables' => $arData['total_receivables'] ?? 0,
                    'overdue_receivables' => $arData['overdue_receivables'] ?? 0,
                    'total_payables' => $apData['total_payables'] ?? 0,
                    'overdue_payables' => $apData['overdue_payables'] ?? 0,
                    'recovery_percentage' => $arData['recovery_percentage'] ?? 0,
                ],
                'charts' => [
                    'financial_position' => [
                        'labels' => ['Assets', 'Liabilities', 'Equity'],
                        'series' => [
                            $this->roundChartAmount($assets),
                            $this->roundChartAmount($liabilities),
                            $this->roundChartAmount($equity),
                        ],
                    ],
                    'income_vs_expense' => [
                        'labels' => ['Income', 'Expenses', 'Net Profit'],
                        'series' => [
                            $this->roundChartAmount($income),
                            $this->roundChartAmount($expenses),
                            $this->roundChartAmount(max(0, $netProfit)),
                        ],
                    ],
                    'asset_composition' => $this->chartFromTypes($bs['by_account_type']['Asset'] ?? []),
                    'expense_composition' => $this->chartFromTypes($pl['by_account_type']['Expense'] ?? []),
                    'income_composition' => $this->chartFromTypes($pl['by_account_type']['Income'] ?? []),
                ],
                'tables' => [
                    'top_assets' => $this->tableRows($raw['breakdown']['assets_top'] ?? []),
                    'top_liabilities' => $this->tableRows($raw['breakdown']['liabilities_top'] ?? []),
                    'top_equity' => $this->tableRows($raw['breakdown']['equity_top'] ?? []),
                    'top_income' => $this->tableRows($raw['breakdown']['income_top'] ?? []),
                    'top_expenses' => $this->tableRows($raw['breakdown']['expense_top'] ?? []),
                ],
                'health_score' => $this->healthScore($netProfit, $income, $ratios, $arData, $apData),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    /**
     * Lightweight AR KPIs (no full dashboard / monthly trend loops).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function safeAr(array $filters): array
    {
        try {
            $cacheKey = 'ceo.ar.kpis.'.md5(json_encode($filters));

            return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
                $outstanding = $this->arRepository->getOutstandingReceivables($filters);
                $total = (float) collect($outstanding)->sum('outstanding');
                $overdue = (float) collect($outstanding)
                    ->filter(fn ($r) => ! empty($r['due_date']) && Carbon::parse($r['due_date'])->isPast())
                    ->sum('outstanding');
                $monthly = (float) $this->arRepository->getMonthlyCollections($filters);
                $recoveryBase = $monthly + $total;

                return [
                    'total_receivables' => $total,
                    'overdue_receivables' => $overdue,
                    'recovery_percentage' => $recoveryBase > 0 ? round(($monthly / $recoveryBase) * 100, 1) : 0,
                ];
            });
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Lightweight AP KPIs (no full dashboard / monthly trend loops).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function safeAp(array $filters): array
    {
        try {
            $cacheKey = 'ceo.ap.kpis.'.md5(json_encode($filters));

            return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
                $outstanding = $this->apRepository->getOutstandingPayables($filters);
                $total = (float) collect($outstanding)->sum('outstanding');
                $overdue = (float) collect($outstanding)
                    ->filter(fn ($r) => ! empty($r['due_date']) && Carbon::parse($r['due_date'])->isPast())
                    ->sum('outstanding');

                return [
                    'total_payables' => $total,
                    'overdue_payables' => $overdue,
                ];
            });
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, float>  $typeMap
     * @param  array<int, string>  $types
     */
    protected function sumAccountTypes(array $typeMap, array $types): float
    {
        $sum = 0.0;
        foreach ($typeMap as $type => $amount) {
            if (in_array($type, $types, true)) {
                $sum += abs((float) $amount);
            }
        }

        return $sum;
    }

    /**
     * @param  array<string, mixed>  $pl
     */
    protected function estimateCogs(array $pl): float
    {
        $expenseTypes = $pl['by_account_type']['Expense'] ?? [];
        foreach (['Cost of Goods Sold', 'COGS', 'Stock Expenses', 'Direct Expense'] as $cogsType) {
            if (isset($expenseTypes[$cogsType])) {
                return abs((float) $expenseTypes[$cogsType]);
            }
        }

        return abs((float) ($expenseTypes['Stock Expenses'] ?? $expenseTypes['Cost of Goods Sold'] ?? 0));
    }

    /**
     * @return array<string, float|int|string>
     */
    protected function computeRatios(
        float $assets, float $liabilities, float $equity, float $income, float $expenses,
        float $netProfit, float $currentAssets, float $currentLiabilities,
        float $receivables, float $payables
    ): array {
        $workingCapital = $currentAssets - $currentLiabilities;
        $quickAssets = max(0, $currentAssets - 0); // stock excluded if in current assets - simplified

        return [
            'current_ratio' => $currentLiabilities > 0 ? round($currentAssets / $currentLiabilities, 2) : null,
            'quick_ratio' => $currentLiabilities > 0 ? round($quickAssets / $currentLiabilities, 2) : null,
            'debt_to_equity' => $equity > 0 ? round($liabilities / $equity, 2) : null,
            'debt_ratio' => $assets > 0 ? round($liabilities / $assets, 2) : null,
            'equity_ratio' => $assets > 0 ? round($equity / $assets, 2) : null,
            'net_profit_margin' => $income > 0 ? round(($netProfit / $income) * 100, 2) : 0,
            'expense_ratio' => $income > 0 ? round(($expenses / $income) * 100, 2) : 0,
            'return_on_equity' => $equity > 0 ? round(($netProfit / $equity) * 100, 2) : null,
            'return_on_assets' => $assets > 0 ? round(($netProfit / $assets) * 100, 2) : null,
            'working_capital' => $workingCapital,
            'asset_turnover' => $assets > 0 ? round($income / $assets, 2) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $arData
     * @param  array<string, mixed>  $apData
     * @return array<int, array<string, string>>
     */
    protected function buildInsights(
        float $assets, float $liabilities, float $equity, float $income, float $expenses,
        float $netProfit, array $ratios, array $arData, array $apData
    ): array {
        $insights = [];

        if ($netProfit > 0) {
            $insights[] = ['type' => 'positive', 'title' => 'Profitable Period', 'message' => 'Net profit for the period is positive per IAS 1 P&L presentation.'];
        } else {
            $insights[] = ['type' => 'negative', 'title' => 'Period Loss', 'message' => 'Expenses exceed income for the selected period — review cost controls.'];
        }

        if (abs($assets - ($liabilities + $equity)) > $assets * 0.05 && $assets > 0) {
            $insights[] = ['type' => 'warning', 'title' => 'Accounting Equation Variance', 'message' => 'Assets vs Liabilities+Equity differ — may reflect GL cut-off or unposted entries.'];
        }

        if (($ratios['current_ratio'] ?? 0) < 1 && ($ratios['current_ratio'] ?? null) !== null) {
            $insights[] = ['type' => 'negative', 'title' => 'Liquidity Risk', 'message' => 'Current ratio below 1.0 indicates potential short-term liquidity pressure (IAS 1 current/non-current).'];
        }

        if (($apData['overdue_payables'] ?? 0) > 500000) {
            $insights[] = ['type' => 'warning', 'title' => 'Payables Attention', 'message' => 'Material overdue supplier balances — monitor covenant and cash planning.'];
        }

        if (($arData['overdue_receivables'] ?? 0) > ($income * 0.1) && $income > 0) {
            $insights[] = ['type' => 'warning', 'title' => 'Receivables Quality', 'message' => 'Overdue receivables are significant relative to period income.'];
        }

        if (($ratios['debt_to_equity'] ?? 0) > 2) {
            $insights[] = ['type' => 'warning', 'title' => 'Leverage', 'message' => 'Debt-to-equity above 2.0 — elevated financial leverage per balance sheet structure.'];
        }

        return $insights;
    }

    protected function roundChartAmount(float $value): float
    {
        return round($value, 2);
    }

    /**
     * @param  array<string, float>  $types
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    protected function chartFromTypes(array $types): array
    {
        $sorted = collect($types)->map(fn ($v) => abs((float) $v))->sortDesc()->take(8);

        return [
            'labels' => $sorted->keys()->values()->all(),
            'series' => $sorted->values()->all(),
        ];
    }

    /**
     * @param  array<string, float>  $accounts
     * @return array<int, array{account: string, amount: float}>
     */
    protected function tableRows(array $accounts): array
    {
        return collect($accounts)->map(fn ($amount, $account) => [
            'account' => $account,
            'amount' => abs((float) $amount),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $arData
     * @param  array<string, mixed>  $apData
     */
    protected function healthScore(float $netProfit, float $income, array $ratios, array $arData, array $apData): int
    {
        $score = 70;
        if ($netProfit > 0) {
            $score += 15;
        } else {
            $score -= 20;
        }
        if (($ratios['current_ratio'] ?? 0) >= 1.2) {
            $score += 10;
        } elseif (($ratios['current_ratio'] ?? 0) < 1) {
            $score -= 15;
        }
        if (($ratios['net_profit_margin'] ?? 0) > 5) {
            $score += 5;
        }
        if (($apData['overdue_payables'] ?? 0) > 1000000) {
            $score -= 10;
        }
        if (($arData['recovery_percentage'] ?? 0) > 70) {
            $score += 5;
        }

        return max(0, min(100, $score));
    }
}
