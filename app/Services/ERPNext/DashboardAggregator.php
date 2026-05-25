<?php

namespace App\Services\ERPNext;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CEO dashboard — IAS-aligned financial statements via IASFinancialService.
 */
class DashboardAggregator
{
    public function __construct(
        protected IASFinancialService $iasFinancial,
        protected FinancialService $financial,
    ) {}

    public function getCeoDashboard(array $filters = []): array
    {
        $cacheKey = 'ceo_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            return $this->iasFinancial->getCeoIasDashboard($filters);
        });
    }

    public function createSnapshot(string $type, array $data, array $filters, ?int $userId = null): void
    {
        \App\Models\DashboardSnapshot::create([
            'dashboard_type' => $type,
            'company' => $filters['company'] ?? null,
            'branch' => null,
            'snapshot_date' => $filters['to_date'] ?? now()->toDateString(),
            'kpi_data' => $data['statement_of_financial_position'] ?? $data['summary'] ?? $data,
            'chart_data' => $data['charts'] ?? null,
            'table_data' => $data['tables'] ?? null,
            'currency' => $data['currency'] ?? config('erpnext.default_currency'),
            'created_by' => $userId,
        ]);
    }
}
