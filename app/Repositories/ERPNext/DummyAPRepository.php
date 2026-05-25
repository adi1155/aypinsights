<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\APRepositoryInterface;

class DummyAPRepository implements APRepositoryInterface
{
    public function getOutstandingPayables(array $filters = []): array
    {
        return [
            ['supplier' => 'Steel Works Ltd', 'outstanding' => 1850000, 'due_date' => now()->subDays(8)->toDateString()],
            ['supplier' => 'Packaging Solutions', 'outstanding' => 640000, 'due_date' => now()->addDays(5)->toDateString()],
            ['supplier' => 'Logistics Express', 'outstanding' => 1120000, 'due_date' => now()->subDays(35)->toDateString()],
            ['supplier' => 'Raw Materials Co', 'outstanding' => 2780000, 'due_date' => now()->toDateString()],
        ];
    }

    public function getPurchaseInvoices(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'PINV-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'supplier' => ['Steel Works', 'Packaging Sol', 'Logistics Exp'][$i % 3],
            'grand_total' => rand(200000, 1500000),
            'outstanding_amount' => rand(80000, 500000),
            'due_date' => now()->addDays(rand(-25, 10))->toDateString(),
        ], range(1, 15));
    }

    public function getSupplierPayments(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'PAY-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'party' => ['Steel Works', 'Raw Materials Co'][$i % 2],
            'paid_amount' => rand(150000, 900000),
            'posting_date' => now()->toDateString(),
        ], range(1, 10));
    }

    public function getAgingBuckets(array $filters = []): array
    {
        return [
            '0-30' => 3120000,
            '31-60' => 1450000,
            '61-90' => 780000,
            '90+' => 1120000,
        ];
    }
}
