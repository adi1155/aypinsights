<?php

namespace App\Contracts\ERPNext;

interface ProductionRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWorkOrders(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getJobCards(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWorkstations(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getProductionPlans(array $filters = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getManufactureStockEntries(array $filters = []): array;
}
