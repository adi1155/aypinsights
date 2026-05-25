<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\APRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;

class ERPNextAPRepository implements APRepositoryInterface
{
    use BuildsErpNextFilters;

    public function __construct(protected ERPNextClient $client) {}

    public function getOutstandingPayables(array $filters = []): array
    {
        $invoices = $this->getPurchaseInvoices($filters);

        return collect($invoices)
            ->groupBy('supplier')
            ->map(fn ($group, $supplier) => [
                'supplier' => $supplier,
                'outstanding' => (float) $group->sum('outstanding_amount'),
                'due_date' => $group->min('due_date'),
            ])
            ->sortByDesc('outstanding')
            ->values()
            ->all();
    }

    public function getPurchaseInvoices(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['outstanding_amount', '>', 0],
        ]);

        return $this->client->getList('Purchase Invoice', $erpFilters, [
            'name', 'supplier', 'grand_total', 'outstanding_amount', 'due_date', 'posting_date', 'status',
        ], 500);
    }

    public function getSupplierPayments(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['payment_type', '=', 'Pay'],
            ['party_type', '=', 'Supplier'],
        ]);

        $rows = $this->client->getList('Payment Entry', $erpFilters, [
            'name', 'party', 'paid_amount', 'posting_date',
        ], 500);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'party' => $row['party'],
            'paid_amount' => (float) ($row['paid_amount'] ?? 0),
            'posting_date' => $row['posting_date'],
        ], $rows);
    }

    public function getAgingBuckets(array $filters = []): array
    {
        return $this->calculateAgingBuckets($this->getPurchaseInvoices($filters));
    }
}
