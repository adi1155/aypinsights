<?php

namespace App\Contracts\ERPNext;

interface ARRepositoryInterface
{
    public function getOutstandingReceivables(array $filters = []): array;

    public function getSalesInvoices(array $filters = []): array;

    public function getCollections(array $filters = []): array;

    public function getAgingBuckets(array $filters = []): array;

    public function getMonthlyCollections(array $filters = []): float;
}
