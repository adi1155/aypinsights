<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\ARRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Carbon\Carbon;

class ERPNextARRepository implements ARRepositoryInterface
{
    use BuildsErpNextFilters;

    public function __construct(protected ERPNextClient $client) {}

    public function getOutstandingReceivables(array $filters = []): array
    {
        $invoices = $this->getSalesInvoices($filters);

        return collect($invoices)
            ->groupBy('customer')
            ->map(fn ($group, $customer) => [
                'customer' => $customer,
                'outstanding' => (float) $group->sum('outstanding_amount'),
                'due_date' => $group->min('due_date'),
            ])
            ->sortByDesc('outstanding')
            ->values()
            ->all();
    }

    public function getSalesInvoices(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['outstanding_amount', '>', 0],
        ]);

        return $this->client->getList('Sales Invoice', $erpFilters, [
            'name', 'customer', 'grand_total', 'outstanding_amount', 'due_date', 'posting_date', 'status',
        ], 500);
    }

    public function getCollections(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
            ['payment_type', '=', 'Receive'],
            ['party_type', '=', 'Customer'],
        ]);

        $rows = $this->client->getList('Payment Entry', $erpFilters, [
            'name', 'party', 'paid_amount', 'posting_date', 'mode_of_payment',
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
        return $this->calculateAgingBuckets($this->getSalesInvoices($filters));
    }

    /**
     * Collections received in the current calendar month.
     */
    public function getMonthlyCollections(array $filters = []): float
    {
        $filters['from_date'] = Carbon::now()->startOfMonth()->toDateString();
        $filters['to_date'] = Carbon::now()->endOfMonth()->toDateString();

        return (float) collect($this->getCollections($filters))->sum('paid_amount');
    }
}
