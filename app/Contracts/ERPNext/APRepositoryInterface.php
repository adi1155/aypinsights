<?php

namespace App\Contracts\ERPNext;

interface APRepositoryInterface
{
    public function getOutstandingPayables(array $filters = []): array;

    public function getPurchaseInvoices(array $filters = []): array;

    public function getSupplierPayments(array $filters = []): array;

    public function getAgingBuckets(array $filters = []): array;
}
