<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\ARRepositoryInterface;

class DummyARRepository implements ARRepositoryInterface
{
    public function getOutstandingReceivables(array $filters = []): array
    {
        return [
            ['customer' => 'Metro Retail Group', 'outstanding' => 2450000, 'due_date' => now()->subDays(12)->toDateString()],
            ['customer' => 'Prime Distributors', 'outstanding' => 1875000, 'due_date' => now()->addDays(3)->toDateString()],
            ['customer' => 'City Wholesale', 'outstanding' => 920000, 'due_date' => now()->subDays(45)->toDateString()],
            ['customer' => 'National Traders', 'outstanding' => 3100000, 'due_date' => now()->toDateString()],
        ];
    }

    public function getSalesInvoices(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'SINV-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'customer' => ['Metro Retail', 'Prime Dist', 'City Wholesale'][$i % 3],
            'grand_total' => rand(150000, 1200000),
            'outstanding_amount' => rand(50000, 400000),
            'due_date' => now()->addDays(rand(-30, 15))->toDateString(),
            'status' => 'Unpaid',
        ], range(1, 15));
    }

    public function getCollections(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'REC-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'party' => ['Metro Retail', 'Prime Dist', 'National Traders'][$i % 3],
            'paid_amount' => rand(100000, 750000),
            'posting_date' => now()->toDateString(),
        ], range(1, 12));
    }

    public function getAgingBuckets(array $filters = []): array
    {
        return [
            '0-30' => 4250000,
            '31-60' => 1870000,
            '61-90' => 920000,
            '90+' => 1580000,
        ];
    }

    public function getMonthlyCollections(array $filters = []): float
    {
        return (float) rand(5000000, 12000000);
    }
}
