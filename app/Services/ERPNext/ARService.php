<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\ARRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ARService
{
    public function __construct(protected ARRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $company = $filters['company'] ?? config('erpnext.default_company', 'GMP Foods (Pvt.) Ltd');
        $cacheKey = 'ar_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters, $company) {
            $outstanding = $this->repository->getOutstandingReceivables($filters);
            $invoices = $this->repository->getSalesInvoices($filters);
            $periodFilters = array_merge($filters, [
                'from_date' => $filters['from_date'] ?? now()->startOfMonth()->toDateString(),
                'to_date' => $filters['to_date'] ?? now()->toDateString(),
            ]);
            $collections = $this->repository->getCollections($periodFilters);
            $aging = $this->repository->getAgingBuckets($filters);
            $totalReceivables = collect($outstanding)->sum('outstanding');
            $collectionToday = (float) collect($collections)->sum('paid_amount');
            $monthlyCollections = $this->repository->getMonthlyCollections($filters);
            $overdue = (float) collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isPast())->sum('outstanding');
            $dueToday = (float) collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isToday())->sum('outstanding');
            $recoveryBase = $monthlyCollections + $totalReceivables;
            $recoveryPct = $recoveryBase > 0 ? round(($monthlyCollections / $recoveryBase) * 100, 1) : 0;

            return [
                'filters' => array_merge($filters, ['company' => $company]),
                'kpis' => [
                    'total_receivables' => $totalReceivables,
                    'due_today' => $dueToday,
                    'overdue_receivables' => $overdue,
                    'collections_in_period' => $collectionToday,
                    'collection_this_month' => $monthlyCollections,
                    'average_collection_days' => $this->averageCollectionDays($invoices),
                    'top_customer_outstanding' => collect($outstanding)->sortByDesc('outstanding')->first()['customer'] ?? 'N/A',
                    'recovery_percentage' => $recoveryPct,
                ],
                'charts' => [
                    'aging' => [
                        'labels' => array_keys($aging),
                        'series' => array_values($aging),
                    ],
                    'collection_trend' => $this->monthlyTrend($filters),
                    'customer_wise' => [
                        'labels' => collect($outstanding)->pluck('customer')->all(),
                        'series' => collect($outstanding)->pluck('outstanding')->all(),
                    ],
                    'branch_recovery' => [
                        'labels' => ['Recovery %'],
                        'series' => [$recoveryPct],
                    ],
                ],
                'tables' => [
                    'overdue_customers' => collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isPast())->values()->all(),
                    'todays_collections' => $collections,
                    'outstanding_invoices' => $invoices,
                    'recovery_followup' => collect($outstanding)->sortByDesc('outstanding')->take(10)->values()->all(),
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
            ];
        });
    }

    protected function monthlyTrend(array $filters = []): array
    {
        $labels = [];
        $series = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $monthFilters = array_merge($filters, [
                'from_date' => $month->copy()->startOfMonth()->toDateString(),
                'to_date' => $month->copy()->endOfMonth()->toDateString(),
            ]);
            $series[] = (float) collect($this->repository->getCollections($monthFilters))->sum('paid_amount');
        }

        return compact('labels', 'series');
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     */
    protected function averageCollectionDays(array $invoices): int
    {
        $days = collect($invoices)
            ->filter(fn ($inv) => ! empty($inv['due_date']) && ! empty($inv['posting_date']))
            ->map(fn ($inv) => Carbon::parse($inv['posting_date'])->diffInDays(Carbon::parse($inv['due_date'])));

        return (int) round($days->avg() ?: 0);
    }
}
