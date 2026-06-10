<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\ProductionRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Illuminate\Support\Facades\Log;

class ERPNextProductionRepository implements ProductionRepositoryInterface
{
    use BuildsErpNextFilters;

    public function __construct(protected ERPNextClient $client) {}

    /**
     * @param  array<int, array<int, mixed>>  $filters
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function safeList(string $doctype, array $filters, array $fields, int $limit): array
    {
        try {
            return $this->client->getList($doctype, $filters, $fields, $limit);
        } catch (\Throwable $e) {
            Log::warning("Production dashboard: {$doctype} fetch failed", ['message' => $e->getMessage()]);

            return [];
        }
    }

    public function getWorkOrders(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'planned_start_date', [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
        ]);

        $rows = $this->safeList('Work Order', $erpFilters, [
            'name', 'production_item', 'item_name', 'qty', 'produced_qty', 'status', 'docstatus',
            'planned_start_date', 'planned_end_date', 'actual_start_date', 'actual_end_date',
            'sales_order', 'project', 'total_operating_cost', 'actual_operating_cost',
            'additional_operating_cost', 'operating_cost', 'material_transferred_for_manufacturing',
        ], 500);

        return array_map(fn ($row) => $this->mapWorkOrder($row), $rows);
    }

    public function getJobCards(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'posting_date', [
            ['company', '=', $company],
            ['docstatus', '!=', 2],
        ]);

        $rows = $this->safeList('Job Card', $erpFilters, [
            'name', 'work_order', 'production_item', 'operation', 'workstation', 'status',
            'for_quantity', 'total_completed_qty', 'posting_date', 'employee', 'employee_name',
            'time_required', 'total_time_in_mins', 'docstatus',
        ], 1000);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'work_order' => $row['work_order'] ?? '',
            'item' => $row['production_item'] ?? '',
            'operation' => $row['operation'] ?? '',
            'workstation' => $row['workstation'] ?? 'Unassigned',
            'status' => $row['status'] ?? 'Open',
            'docstatus' => (int) ($row['docstatus'] ?? 0),
            'for_quantity' => (float) ($row['for_quantity'] ?? 0),
            'completed_qty' => (float) ($row['total_completed_qty'] ?? 0),
            'posting_date' => $row['posting_date'] ?? null,
            'employee' => $row['employee_name'] ?? $row['employee'] ?? '',
            'time_required' => (float) ($row['time_required'] ?? 0),
            'time_in_mins' => (float) ($row['total_time_in_mins'] ?? 0),
        ], $rows);
    }

    public function getWorkstations(array $filters = []): array
    {
        $rows = $this->safeList('Workstation', [], [
            'name', 'workstation_name', 'production_capacity', 'hour_rate', 'workstation_type', 'plant_floor',
        ], 200);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'workstation_name' => $row['workstation_name'] ?? $row['name'],
            'production_capacity' => (float) ($row['production_capacity'] ?? 0),
            'hour_rate' => (float) ($row['hour_rate'] ?? 0),
            'workstation_type' => $row['workstation_type'] ?? 'General',
            'plant_floor' => $row['plant_floor'] ?? '',
        ], $rows);
    }

    public function getProductionPlans(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildDateRangeOnField($filters, 'posting_date', [
            ['company', '=', $company],
            ['docstatus', '=', 1],
        ]);

        $rows = $this->safeList('Production Plan', $erpFilters, [
            'name', 'posting_date', 'status', 'total_planned_qty', 'total_produced_qty', 'get_items_from',
        ], 200);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'posting_date' => $row['posting_date'] ?? null,
            'status' => $row['status'] ?? 'Submitted',
            'planned_qty' => (float) ($row['total_planned_qty'] ?? 0),
            'produced_qty' => (float) ($row['total_produced_qty'] ?? 0),
            'source' => $row['get_items_from'] ?? 'Sales Order',
        ], $rows);
    }

    public function getManufactureStockEntries(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['stock_entry_type', '=', 'Manufacture'],
        ]);

        $rows = $this->safeList('Stock Entry', $erpFilters, [
            'name', 'posting_date', 'work_order', 'fg_completed_qty', 'total_amount',
        ], 500);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'posting_date' => $row['posting_date'] ?? null,
            'work_order' => $row['work_order'] ?? '',
            'fg_qty' => (float) ($row['fg_completed_qty'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
        ], $rows);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapWorkOrder(array $row): array
    {
        $qty = (float) ($row['qty'] ?? 0);
        $produced = (float) ($row['produced_qty'] ?? 0);
        $plannedCost = (float) ($row['total_operating_cost'] ?? $row['operating_cost'] ?? 0);
        $actualCost = (float) ($row['actual_operating_cost'] ?? $plannedCost);
        $materialCost = (float) ($row['material_transferred_for_manufacturing'] ?? 0);
        $totalCost = $actualCost + $materialCost;
        $unitCost = $qty > 0 ? $totalCost / $qty : 0;
        $unitMargin = max(0, $unitCost * 0.18);
        $revenueProxy = $produced * ($unitCost + $unitMargin);
        $profit = $revenueProxy - ($produced * $unitCost);
        $profitability = $revenueProxy > 0 ? round(($profit / $revenueProxy) * 100, 1) : 0;

        return [
            'name' => $row['name'],
            'item' => $row['production_item'] ?? '',
            'item_name' => $row['item_name'] ?? $row['production_item'] ?? '',
            'qty' => $qty,
            'produced_qty' => $produced,
            'pending_qty' => max(0, $qty - $produced),
            'completion_pct' => $qty > 0 ? round(($produced / $qty) * 100, 1) : 0,
            'status' => $row['status'] ?? 'Not Started',
            'docstatus' => (int) ($row['docstatus'] ?? 0),
            'planned_start' => $row['planned_start_date'] ?? null,
            'planned_end' => $row['planned_end_date'] ?? null,
            'actual_start' => $row['actual_start_date'] ?? null,
            'actual_end' => $row['actual_end_date'] ?? null,
            'sales_order' => $row['sales_order'] ?? '',
            'project' => $row['project'] ?? '',
            'planned_cost' => $plannedCost,
            'actual_cost' => $actualCost,
            'material_cost' => $materialCost,
            'total_cost' => $totalCost,
            'profitability_index' => $profitability,
            'revenue_proxy' => round($revenueProxy),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<int, mixed>>  $defaults
     * @return array<int, array<int, mixed>>
     */
    protected function buildDateRangeOnField(array $filters, string $dateField, array $defaults = []): array
    {
        $built = $defaults;

        if (! empty($filters['from_date'])) {
            $built[] = [$dateField, '>=', $filters['from_date']];
        }
        if (! empty($filters['to_date'])) {
            $built[] = [$dateField, '<=', $filters['to_date']];
        }

        return $built;
    }
}
