<?php

namespace App\Repositories\ERPNext;

use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Aggregates ERPNext GL data into IAS-aligned financial statement categories.
 */
class ERPNextIASRepository
{
    use BuildsErpNextFilters;

    /** @var array<string, array<string, mixed>> */
    protected array $accountIndex = [];

    public function __construct(protected ERPNextClient $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getFinancialStatements(array $filters): array
    {
        $company = $this->defaultCompany($filters);
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();
        $cacheKey = 'ias.statements.'.md5("{$company}|{$fromDate}|{$toDate}");

        return Cache::remember($cacheKey, config('erpnext.cache_ttl', 300), function () use ($company, $fromDate, $toDate) {
            $this->loadAccountIndex($company);

            if ($this->canUseDirectDb()) {
                $bsGl = $this->fetchGlBalancesFromDb($company, null, $toDate);
                $plGl = $this->fetchGlBalancesFromDb($company, $fromDate, $toDate);
            } else {
                $bsMax = (int) config('erpnext.ias_bs_gl_max_rows', 6000);
                $plMax = (int) config('erpnext.ias_pl_gl_max_rows', 4000);
                $pageSize = (int) config('erpnext.ias_gl_page_size', 500);

                $bsGl = $this->fetchGlEntries($company, null, $toDate, $pageSize, $bsMax);
                $plGl = $this->fetchGlEntries($company, $fromDate, $toDate, $pageSize, $plMax);
            }

            $balanceSheet = $this->aggregateByRootType($bsGl, ['Asset', 'Liability', 'Equity']);
            $profitLoss = $this->aggregateByRootType($plGl, ['Income', 'Expense']);

            return [
                'balance_sheet' => $balanceSheet,
                'profit_loss' => $profitLoss,
                'breakdown' => [
                    'assets_top' => $balanceSheet['top_accounts']['Asset'] ?? [],
                    'liabilities_top' => $balanceSheet['top_accounts']['Liability'] ?? [],
                    'equity_top' => $balanceSheet['top_accounts']['Equity'] ?? [],
                    'income_top' => $profitLoss['top_accounts']['Income'] ?? [],
                    'expense_top' => $profitLoss['top_accounts']['Expense'] ?? [],
                ],
            ];
        });
    }

    protected function canUseDirectDb(): bool
    {
        if (config('erpnext.use_dummy_data', true)) {
            return false;
        }

        return (bool) config('erpnext.db.database');
    }

    /**
     * Fast path: aggregate GL in MariaDB (one query per statement scope).
     *
     * @return array<int, array{account: string, debit: float, credit: float}>
     */
    protected function fetchGlBalancesFromDb(string $company, ?string $fromDate, string $toDate): array
    {
        try {
            $connection = config('erpnext.connection', 'erpnext');

            $query = DB::connection($connection)
                ->table('tabGL Entry')
                ->select('account')
                ->selectRaw('SUM(debit) as debit')
                ->selectRaw('SUM(credit) as credit')
                ->where('company', $company)
                ->where('is_cancelled', 0)
                ->where('posting_date', '<=', $toDate);

            if ($fromDate) {
                $query->where('posting_date', '>=', $fromDate);
            }

            return $query
                ->groupBy('account')
                ->get()
                ->map(fn ($row) => [
                    'account' => $row->account,
                    'debit' => (float) $row->debit,
                    'credit' => (float) $row->credit,
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('IAS direct DB read failed, falling back to API', ['message' => $e->getMessage()]);

            $bsMax = (int) config('erpnext.ias_bs_gl_max_rows', 6000);
            $plMax = (int) config('erpnext.ias_pl_gl_max_rows', 4000);
            $pageSize = (int) config('erpnext.ias_gl_page_size', 500);

            return $this->fetchGlEntries($company, $fromDate, $toDate, $pageSize, $fromDate ? $plMax : $bsMax);
        }
    }

    protected function loadAccountIndex(string $company): void
    {
        $this->accountIndex = Cache::remember("ias.accounts.{$company}", 3600, function () use ($company) {
            $rows = $this->client->getList('Account', [
                ['company', '=', $company],
            ], ['name', 'account_name', 'root_type', 'account_type', 'is_group'], 2000);

            $index = [];
            foreach ($rows as $row) {
                $index[$row['name']] = $row;
            }

            return $index;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchGlEntries(
        string $company,
        ?string $fromDate,
        string $toDate,
        int $pageSize = 500,
        int $maxRows = 6000
    ): array {
        $filters = [
            ['company', '=', $company],
            ['is_cancelled', '=', 0],
            ['posting_date', '<=', $toDate],
        ];
        if ($fromDate) {
            $filters[] = ['posting_date', '>=', $fromDate];
        }

        return $this->client->getListPaginated('GL Entry', $filters, ['account', 'debit', 'credit'], $pageSize, $maxRows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $glEntries
     * @param  array<int, string>  $rootTypes
     * @return array<string, mixed>
     */
    protected function aggregateByRootType(array $glEntries, array $rootTypes): array
    {
        $totals = array_fill_keys($rootTypes, 0.0);
        $byType = [];
        $byAccount = [];

        foreach ($glEntries as $entry) {
            $accountName = $entry['account'] ?? '';
            $meta = $this->accountIndex[$accountName] ?? null;
            if (! $meta || (int) ($meta['is_group'] ?? 0) === 1) {
                continue;
            }

            $rootType = $meta['root_type'] ?? '';
            if (! in_array($rootType, $rootTypes, true)) {
                continue;
            }

            $amount = $this->signedAmount(
                $rootType,
                (float) ($entry['debit'] ?? 0),
                (float) ($entry['credit'] ?? 0)
            );

            $totals[$rootType] += $amount;

            $accountType = $meta['account_type'] ?? 'Other';
            $byType[$rootType][$accountType] = ($byType[$rootType][$accountType] ?? 0) + $amount;

            $label = $meta['account_name'] ?? $accountName;
            $byAccount[$rootType][$label] = ($byAccount[$rootType][$label] ?? 0) + $amount;
        }

        foreach ($byType as $root => $types) {
            arsort($byType[$root]);
        }
        foreach ($byAccount as $root => $accounts) {
            arsort($byAccount[$root]);
            $byAccount[$root] = array_slice($byAccount[$root], 0, 10, true);
        }

        return [
            'totals' => $totals,
            'by_account_type' => $byType,
            'top_accounts' => $byAccount,
        ];
    }

    protected function signedAmount(string $rootType, float $debit, float $credit): float
    {
        return match ($rootType) {
            'Asset', 'Expense' => $debit - $credit,
            'Liability', 'Equity', 'Income' => $credit - $debit,
            default => $debit - $credit,
        };
    }
}
