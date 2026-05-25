<?php

namespace App\Repositories\ERPNext\Concerns;

use Carbon\Carbon;

trait BuildsErpNextFilters
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, mixed>>
     */
    protected function buildFilters(array $filters, array $defaults = []): array
    {
        if (isset($filters['filters']) && is_array($filters['filters'])) {
            return array_merge($defaults, $filters['filters']);
        }

        $built = $defaults;

        foreach (['company', 'cost_center'] as $key) {
            if (! empty($filters[$key])) {
                $built[] = [$key, '=', $filters[$key]];
            }
        }

        if (! empty($filters['from_date'])) {
            $built[] = ['posting_date', '>=', $filters['from_date']];
        }
        if (! empty($filters['to_date'])) {
            $built[] = ['posting_date', '<=', $filters['to_date']];
        }
        if (! empty($filters['date'])) {
            $built[] = ['posting_date', '=', $filters['date']];
        }

        return $built;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, float>
     */
    protected function calculateAgingBuckets(array $items, string $amountKey = 'outstanding_amount', string $dueDateKey = 'due_date'): array
    {
        $buckets = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        $today = Carbon::today();

        foreach ($items as $item) {
            $amount = (float) ($item[$amountKey] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            $due = Carbon::parse($item[$dueDateKey] ?? $today);

            if ($due->gte($today)) {
                $buckets['0-30'] += $amount;
            } else {
                $overdueDays = $due->diffInDays($today);
                if ($overdueDays <= 30) {
                    $buckets['0-30'] += $amount;
                } elseif ($overdueDays <= 60) {
                    $buckets['31-60'] += $amount;
                } elseif ($overdueDays <= 90) {
                    $buckets['61-90'] += $amount;
                } else {
                    $buckets['90+'] += $amount;
                }
            }
        }

        return $buckets;
    }

    protected function defaultCompany(array $filters): string
    {
        return $filters['company'] ?? config('erpnext.default_company', 'GMP Foods (Pvt.) Ltd');
    }
}
