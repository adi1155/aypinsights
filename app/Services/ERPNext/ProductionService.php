<?php

namespace App\Services\ERPNext;

use App\Contracts\ERPNext\ProductionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductionService
{
    public function __construct(protected ProductionRepositoryInterface $repository) {}

    public function getDashboard(array $filters = []): array
    {
        $cacheKey = 'production_dashboard:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, config('erpnext.cache_ttl'), function () use ($filters) {
            $workOrders = collect($this->repository->getWorkOrders($filters));
            $jobCards = collect($this->repository->getJobCards($filters));
            $workstations = collect($this->repository->getWorkstations($filters));
            $plans = collect($this->repository->getProductionPlans($filters));
            $stockEntries = collect($this->repository->getManufactureStockEntries($filters));

            $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
            $toDate = $filters['to_date'] ?? now()->toDateString();
            $today = now()->toDateString();

            $activeWorkOrders = $workOrders->filter(fn ($w) => in_array($w['status'], ['Not Started', 'In Process'], true));
            $inProgressJobCards = $jobCards->filter(fn ($j) => in_array($j['status'], ['Open', 'Work In Progress'], true));
            $machinesWithOrders = $inProgressJobCards->pluck('workstation')->unique()->filter()->count();
            $totalWorkstations = max(1, $workstations->count());

            $totalPlannedQty = (float) $workOrders->sum('qty');
            $totalProducedQty = (float) $workOrders->sum('produced_qty');
            $completionPct = $totalPlannedQty > 0 ? round(($totalProducedQty / $totalPlannedQty) * 100, 1) : 0;

            $delayedOrders = $workOrders->filter(function ($w) use ($today) {
                return ! in_array($w['status'], ['Completed', 'Stopped'], true)
                    && ! empty($w['planned_end'])
                    && $w['planned_end'] < $today;
            });

            $profitabilityIndex = round($workOrders->avg('profitability_index') ?: 0, 1);
            $totalRevenue = (float) $workOrders->sum('revenue_proxy');
            $totalCost = (float) $workOrders->sum('total_cost');
            $grossMarginPct = $totalRevenue > 0 ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 1) : 0;

            $machineOrders = $this->buildMachineOrders($inProgressJobCards, $workOrders);
            $workstationLoad = $this->buildWorkstationLoad($workstations, $inProgressJobCards);

            return [
                'filters' => $filters,
                'kpis' => [
                    'active_work_orders' => $activeWorkOrders->count(),
                    'orders_on_machines' => $inProgressJobCards->count(),
                    'machines_in_use' => $machinesWithOrders,
                    'total_workstations' => $totalWorkstations,
                    'machine_utilization_pct' => round(($machinesWithOrders / $totalWorkstations) * 100, 1),
                    'planned_quantity' => $totalPlannedQty,
                    'produced_quantity' => $totalProducedQty,
                    'production_completion_pct' => $completionPct,
                    'profitability_index' => $profitabilityIndex,
                    'gross_margin_pct' => $grossMarginPct,
                    'delayed_orders' => $delayedOrders->count(),
                    'stopped_orders' => $workOrders->where('status', 'Stopped')->count(),
                    'pending_work_orders' => $workOrders->where('status', 'Not Started')->count(),
                    'production_value_period' => (float) $stockEntries->sum('total_amount'),
                    'avg_order_profitability' => $profitabilityIndex,
                ],
                'kpi_types' => [
                    'active_work_orders' => 'count',
                    'orders_on_machines' => 'count',
                    'machines_in_use' => 'count',
                    'total_workstations' => 'count',
                    'machine_utilization_pct' => 'percent',
                    'production_completion_pct' => 'percent',
                    'profitability_index' => 'percent',
                    'gross_margin_pct' => 'percent',
                    'delayed_orders' => 'count',
                    'stopped_orders' => 'count',
                    'pending_work_orders' => 'count',
                    'avg_order_profitability' => 'percent',
                ],
                'charts' => [
                    'machine_utilization' => [
                        'labels' => $workstationLoad->pluck('workstation')->all(),
                        'series' => $workstationLoad->pluck('active_orders')->all(),
                    ],
                    'work_order_status' => [
                        'labels' => $workOrders->groupBy('status')->keys()->values()->all(),
                        'series' => $workOrders->groupBy('status')->map->count()->values()->all(),
                    ],
                    'planned_vs_actual' => [
                        'labels' => $workOrders->take(8)->pluck('item_name')->map(fn ($n) => strlen($n) > 18 ? substr($n, 0, 18).'…' : $n)->all(),
                        'planned' => $workOrders->take(8)->pluck('qty')->all(),
                        'actual' => $workOrders->take(8)->pluck('produced_qty')->all(),
                    ],
                    'profitability_by_item' => [
                        'labels' => $workOrders->sortByDesc('profitability_index')->take(8)->pluck('item_name')->map(fn ($n) => strlen($n) > 18 ? substr($n, 0, 18).'…' : $n)->all(),
                        'series' => $workOrders->sortByDesc('profitability_index')->take(8)->pluck('profitability_index')->all(),
                    ],
                    'daily_output' => $this->dailyOutputTrend($stockEntries, $fromDate, $toDate),
                    'monthly_production' => $this->monthlyProductionTrend($stockEntries),
                ],
                'tables' => [
                    'current_orders_on_machines' => $machineOrders->values()->all(),
                    'active_work_orders' => $activeWorkOrders->take(20)->values()->all(),
                    'delayed_work_orders' => $delayedOrders->values()->all(),
                    'job_cards_in_progress' => $inProgressJobCards->take(20)->values()->all(),
                    'workstation_load' => $workstationLoad->values()->all(),
                    'production_plans' => $plans->sortByDesc('posting_date')->values()->all(),
                    'low_profitability_orders' => $workOrders->sortBy('profitability_index')->take(10)->values()->all(),
                    'top_producing_orders' => $workOrders->sortByDesc('produced_qty')->take(10)->values()->all(),
                    'manufacture_entries' => $stockEntries->sortByDesc('posting_date')->take(15)->values()->all(),
                ],
                'currency' => config('erpnext.default_currency', 'PKR'),
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $jobCards
     * @param  Collection<int, array<string, mixed>>  $workOrders
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildMachineOrders(Collection $jobCards, Collection $workOrders): Collection
    {
        $woMap = $workOrders->keyBy('name');

        return $jobCards->map(function ($card) use ($woMap) {
            $wo = $woMap->get($card['work_order']);
            $progress = ($card['for_quantity'] ?? 0) > 0
                ? round((($card['completed_qty'] ?? 0) / $card['for_quantity']) * 100, 1)
                : 0;

            return [
                'machine' => $card['workstation'],
                'work_order' => $card['work_order'],
                'item' => $wo['item_name'] ?? $card['item'] ?? '',
                'operation' => $card['operation'],
                'order_qty' => $card['for_quantity'],
                'completed_qty' => $card['completed_qty'],
                'progress_pct' => $progress,
                'status' => $card['status'],
                'employee' => $card['employee'] ?? '',
                'profitability_index' => $wo['profitability_index'] ?? 0,
            ];
        })->sortBy('machine')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workstations
     * @param  Collection<int, array<string, mixed>>  $jobCards
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildWorkstationLoad(Collection $workstations, Collection $jobCards): Collection
    {
        $byMachine = $jobCards->groupBy('workstation');

        return $workstations->map(function ($ws) use ($byMachine) {
            $cards = $byMachine->get($ws['name'], collect());
            $capacity = max(1, (float) ($ws['production_capacity'] ?? 1));
            $loadQty = (float) $cards->sum('for_quantity');
            $completed = (float) $cards->sum('completed_qty');

            return [
                'workstation' => $ws['workstation_name'] ?? $ws['name'],
                'type' => $ws['workstation_type'] ?? '',
                'active_orders' => $cards->count(),
                'load_qty' => $loadQty,
                'completed_qty' => $completed,
                'capacity' => $capacity,
                'utilization_pct' => round(min(100, ($loadQty / $capacity) * 100), 1),
                'hour_rate' => $ws['hour_rate'] ?? 0,
            ];
        })->sortByDesc('active_orders')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    protected function dailyOutputTrend(Collection $entries, string $from, string $to): array
    {
        $byDate = $entries
            ->filter(fn ($e) => ($e['posting_date'] ?? '') >= $from && ($e['posting_date'] ?? '') <= $to)
            ->groupBy('posting_date')
            ->map->sum('fg_qty')
            ->sortKeys();

        return [
            'labels' => $byDate->keys()->take(30)->values()->all(),
            'series' => $byDate->values()->take(30)->map(fn ($v) => (float) $v)->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    protected function monthlyProductionTrend(Collection $entries): array
    {
        $labels = [];
        $series = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();
            $series[] = (float) $entries
                ->filter(fn ($e) => ($e['posting_date'] ?? '') >= $start && ($e['posting_date'] ?? '') <= $end)
                ->sum('fg_qty');
        }

        return compact('labels', 'series');
    }
}
