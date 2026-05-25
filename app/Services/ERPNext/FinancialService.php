<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\FinancialRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FinancialService
{
    public function __construct(protected FinancialRepositoryInterface $repository) {}

    /**
     * @param  array{company?: string, from_date?: string, to_date?: string, lightweight?: bool}  $filters
     */
    public function getDailyClosingDashboard(array $filters = []): array
    {
        $company = $filters['company'] ?? config('erpnext.default_company', 'GMP Foods (Pvt.) Ltd');
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();
        $lightweight = ! empty($filters['lightweight']);
        $cacheKey = 'daily_closing:'.md5(json_encode([$company, $fromDate, $toDate, $lightweight]));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($company, $fromDate, $toDate, $filters, $lightweight) {
            $rangeFilters = array_merge($filters, [
                'company' => $company,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]);

            $payments = $this->repository->getPaymentEntries($rangeFilters);
            $receipts = (float) collect($payments)->where('payment_type', 'Receive')->sum('paid_amount');
            $paid = (float) collect($payments)->where('payment_type', 'Pay')->sum('paid_amount');

            $banks = $lightweight ? [] : $this->repository->getBankBalances($company);
            $bankTotal = (float) collect($banks)->sum('balance');
            $cashInHand = $lightweight ? 0.0 : $this->repository->getCashInHandBalance($company);
            $previousClosing = $this->getPreviousClosing($company, $toDate, $bankTotal + $cashInHand, $receipts - $paid);
            $closing = $previousClosing + $receipts - $paid;
            $trend = $this->repository->getCashFlowTrend($company, $rangeFilters);

            return [
                'filters' => [
                    'company' => $company,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
                'kpis' => [
                    'opening_balance' => $previousClosing,
                    'period_receipts' => $receipts,
                    'period_payments' => $paid,
                    'net_cash_flow' => $receipts - $paid,
                    'closing_balance' => $closing,
                    'bank_balance' => $bankTotal,
                    'cash_in_hand' => $cashInHand,
                    'pending_deposits' => 0,
                    'outstanding_cheques' => 0,
                    'period_profit_loss' => $receipts - $paid,
                ],
                'charts' => [
                    'cash_flow_trend' => $trend,
                    'receipts_vs_payments' => [
                        'labels' => ['Receipts', 'Payments'],
                        'series' => [$receipts, $paid],
                    ],
                    'bank_wise' => [
                        'labels' => collect($banks)->pluck('bank')->all(),
                        'series' => collect($banks)->pluck('balance')->all(),
                    ],
                    'payment_mode_wise' => $this->paymentModeWise($payments),
                    'hourly_collections' => $this->periodCollections($payments),
                ],
                'tables' => [
                    'top_receipts' => collect($payments)->where('payment_type', 'Receive')->sortByDesc('paid_amount')->take(10)->values()->all(),
                    'top_payments' => collect($payments)->where('payment_type', 'Pay')->sortByDesc('paid_amount')->take(10)->values()->all(),
                    'bank_ledger' => $banks,
                    'unreconciled' => $lightweight ? [] : $this->repository->getGLEntries(['company' => $company, 'from_date' => $fromDate, 'to_date' => $toDate]),
                    'pending_journals' => collect($this->repository->getJournalEntries(['company' => $company]))->where('status', 'Draft')->values()->all(),
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
            ];
        });
    }

    protected function getPreviousClosing(string $company, string $toDate, float $currentPosition, float $netPeriod): float
    {
        $stored = \App\Models\DailyClosing::query()
            ->where('company', $company)
            ->where('closing_date', Carbon::parse($toDate)->subDay()->toDateString())
            ->value('closing_balance');

        if ($stored !== null) {
            return (float) $stored;
        }

        return max(0, $currentPosition - $netPeriod);
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    protected function paymentModeWise(array $payments): array
    {
        $grouped = collect($payments)
            ->where('payment_type', 'Receive')
            ->groupBy(fn ($p) => $p['mode_of_payment'] ?? 'Other')
            ->map->sum('paid_amount');

        return [
            'labels' => $grouped->keys()->all(),
            'series' => $grouped->values()->map(fn ($v) => (float) $v)->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    protected function periodCollections(array $payments): array
    {
        $total = (float) collect($payments)->where('payment_type', 'Receive')->sum('paid_amount');

        return [
            'labels' => ['Period Collections'],
            'series' => [$total],
        ];
    }
}
