<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\APRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class APService
{
    public function __construct(protected APRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $cacheKey = 'ap_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            $outstanding = $this->repository->getOutstandingPayables($filters);
            $invoices = $this->repository->getPurchaseInvoices($filters);
            $weekFilters = array_merge($filters, [
                'from_date' => now()->startOfWeek()->toDateString(),
                'to_date' => now()->endOfWeek()->toDateString(),
            ]);
            $payments = $this->repository->getSupplierPayments($weekFilters);
            $aging = $this->repository->getAgingBuckets($filters);
            $total = (float) collect($outstanding)->sum('outstanding');
            $overdue = (float) collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isPast())->sum('outstanding');
            $dueToday = (float) collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isToday())->sum('outstanding');
            $paidWeek = (float) collect($payments)->sum('paid_amount');

            return [
                'filters' => $filters,
                'kpis' => [
                    'total_payables' => $total,
                    'due_today' => $dueToday,
                    'overdue_payables' => $overdue,
                    'upcoming_payments' => (float) collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isFuture())->sum('outstanding'),
                    'supplier_advances' => 0,
                    'average_payment_days' => $this->averagePaymentDays($invoices),
                    'unpaid_purchase_invoices' => count($invoices),
                    'payment_this_week' => $paidWeek,
                ],
                'charts' => [
                    'aging' => ['labels' => array_keys($aging), 'series' => array_values($aging)],
                    'supplier_wise' => [
                        'labels' => collect($outstanding)->take(15)->pluck('supplier')->all(),
                        'series' => collect($outstanding)->take(15)->pluck('outstanding')->all(),
                    ],
                    'monthly_payable' => $this->monthlyTrend($filters),
                    'due_vs_paid' => [
                        'labels' => ['Due', 'Paid'],
                        'series' => [$total, collect($payments)->sum('paid_amount')],
                    ],
                ],
                'tables' => [
                    'overdue_suppliers' => collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isPast())->values()->all(),
                    'upcoming_due' => collect($outstanding)->filter(fn ($r) => Carbon::parse($r['due_date'])->isFuture())->values()->all(),
                    'unpaid_invoices' => $invoices,
                    'payment_history' => $payments,
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
            $series[] = (float) collect($this->repository->getSupplierPayments($monthFilters))->sum('paid_amount');
        }

        return compact('labels', 'series');
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     */
    protected function averagePaymentDays(array $invoices): int
    {
        $days = collect($invoices)
            ->filter(fn ($inv) => ! empty($inv['due_date']) && ! empty($inv['posting_date']))
            ->map(fn ($inv) => Carbon::parse($inv['posting_date'])->diffInDays(Carbon::parse($inv['due_date'])));

        return (int) round($days->avg() ?: 0);
    }
}
