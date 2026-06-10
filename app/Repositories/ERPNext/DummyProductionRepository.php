<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\ProductionRepositoryInterface;

class DummyProductionRepository implements ProductionRepositoryInterface
{
    public function getWorkOrders(array $filters = []): array
    {
        $items = [
            ['code' => 'FG-001', 'name' => 'Frozen Paratha 12-Pack'],
            ['code' => 'FG-002', 'name' => 'Chicken Samosa 24-Pack'],
            ['code' => 'FG-003', 'name' => 'Beef Kebab 10-Pack'],
            ['code' => 'FG-004', 'name' => 'Veg Spring Roll 20-Pack'],
        ];
        $statuses = ['Not Started', 'In Process', 'In Process', 'Completed', 'Stopped'];

        return array_map(function ($i) use ($items, $statuses) {
            $item = $items[$i % count($items)];
            $qty = rand(500, 5000);
            $produced = $statuses[$i % 5] === 'Completed' ? $qty : rand(0, (int) ($qty * 0.85));
            $plannedCost = rand(800000, 3500000);
            $actualCost = $plannedCost * (rand(88, 115) / 100);
            $materialCost = $plannedCost * 0.55;
            $totalCost = $actualCost + $materialCost;
            $revenue = $produced * (rand(450, 920));
            $profit = $revenue - ($produced * ($totalCost / max($qty, 1)));

            return [
                'name' => 'MFG-WO-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'item' => $item['code'],
                'item_name' => $item['name'],
                'qty' => $qty,
                'produced_qty' => $produced,
                'pending_qty' => max(0, $qty - $produced),
                'completion_pct' => round(($produced / max($qty, 1)) * 100, 1),
                'status' => $statuses[$i % 5],
                'docstatus' => in_array($statuses[$i % 5], ['Completed', 'Stopped']) ? 1 : 0,
                'planned_start' => now()->subDays(rand(1, 20))->toDateString(),
                'planned_end' => now()->addDays(rand(-3, 10))->toDateString(),
                'actual_start' => now()->subDays(rand(1, 15))->toDateString(),
                'actual_end' => $statuses[$i % 5] === 'Completed' ? now()->subDays(rand(0, 2))->toDateString() : null,
                'sales_order' => 'SO-'.rand(1000, 9999),
                'project' => '',
                'planned_cost' => $plannedCost,
                'actual_cost' => $actualCost,
                'material_cost' => $materialCost,
                'total_cost' => $totalCost,
                'profitability_index' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
                'revenue_proxy' => $revenue,
            ];
        }, range(0, 19));
    }

    public function getJobCards(array $filters = []): array
    {
        $machines = ['Mixer-01', 'Oven Line A', 'Oven Line B', 'Packaging-01', 'Freezer Tunnel', 'QC Station'];
        $ops = ['Mixing', 'Baking', 'Cooling', 'Packaging', 'Inspection'];

        return array_map(function ($i) use ($machines, $ops) {
            $qty = rand(200, 2000);
            $done = rand(0, $qty);

            return [
                'name' => 'JOB-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'work_order' => 'MFG-WO-'.str_pad((string) (rand(1, 12)), 5, '0', STR_PAD_LEFT),
                'item' => 'FG-00'.rand(1, 4),
                'operation' => $ops[$i % count($ops)],
                'workstation' => $machines[$i % count($machines)],
                'status' => $done >= $qty ? 'Completed' : ($done > 0 ? 'Work In Progress' : 'Open'),
                'docstatus' => $done >= $qty ? 1 : 0,
                'for_quantity' => $qty,
                'completed_qty' => $done,
                'posting_date' => now()->subDays(rand(0, 5))->toDateString(),
                'employee' => ['Ahmed Khan', 'Sara Ali', 'Omar Farooq'][$i % 3],
                'time_required' => rand(60, 480),
                'time_in_mins' => rand(30, 420),
            ];
        }, range(0, 35));
    }

    public function getWorkstations(array $filters = []): array
    {
        return [
            ['name' => 'Mixer-01', 'workstation_name' => 'Industrial Mixer 01', 'production_capacity' => 1200, 'hour_rate' => 4500, 'workstation_type' => 'Mixing', 'plant_floor' => 'Floor A'],
            ['name' => 'Oven Line A', 'workstation_name' => 'Oven Line A', 'production_capacity' => 2400, 'hour_rate' => 8200, 'workstation_type' => 'Baking', 'plant_floor' => 'Floor A'],
            ['name' => 'Oven Line B', 'workstation_name' => 'Oven Line B', 'production_capacity' => 2400, 'hour_rate' => 8200, 'workstation_type' => 'Baking', 'plant_floor' => 'Floor A'],
            ['name' => 'Packaging-01', 'workstation_name' => 'Auto Packaging Line', 'production_capacity' => 3600, 'hour_rate' => 5600, 'workstation_type' => 'Packaging', 'plant_floor' => 'Floor B'],
            ['name' => 'Freezer Tunnel', 'workstation_name' => 'Blast Freezer Tunnel', 'production_capacity' => 1800, 'hour_rate' => 6100, 'workstation_type' => 'Freezing', 'plant_floor' => 'Floor B'],
            ['name' => 'QC Station', 'workstation_name' => 'Quality Control', 'production_capacity' => 900, 'hour_rate' => 3200, 'workstation_type' => 'QC', 'plant_floor' => 'Floor B'],
        ];
    }

    public function getProductionPlans(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'MFG-PP-'.now()->subMonths($i)->format('Y-m'),
            'posting_date' => now()->subMonths($i)->startOfMonth()->toDateString(),
            'status' => $i === 0 ? 'In Process' : 'Completed',
            'planned_qty' => rand(8000, 25000),
            'produced_qty' => rand(6000, 24000),
            'source' => 'Sales Order',
        ], range(0, 5));
    }

    public function getManufactureStockEntries(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'STE-MFG-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
            'posting_date' => now()->subDays(rand(0, 28))->toDateString(),
            'work_order' => 'MFG-WO-'.str_pad((string) rand(1, 15), 5, '0', STR_PAD_LEFT),
            'fg_qty' => rand(100, 2500),
            'total_amount' => rand(150000, 2800000),
        ], range(0, 24));
    }
}
