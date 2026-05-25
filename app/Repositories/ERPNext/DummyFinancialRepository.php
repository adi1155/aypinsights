<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\FinancialRepositoryInterface;
use Carbon\Carbon;

class DummyFinancialRepository implements FinancialRepositoryInterface
{
    public function getGLEntries(array $filters = []): array
    {
        return $this->generateTransactions('GL', 25);
    }

    public function getPaymentEntries(array $filters = []): array
    {
        return $this->generateTransactions('PE', 20);
    }

    public function getJournalEntries(array $filters = []): array
    {
        return array_map(fn ($i) => [
            'name' => 'JV-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'posting_date' => now()->subDays(rand(0, 5))->toDateString(),
            'total_debit' => rand(50000, 500000),
            'total_credit' => rand(50000, 500000),
            'company' => $filters['company'] ?? 'GMP Holdings',
            'status' => ['Submitted', 'Draft'][rand(0, 1)],
        ], range(1, 8));
    }

    public function getBankBalances(string $company, ?string $branch = null): array
    {
        return [
            ['bank' => 'HBL Main', 'balance' => 12500000],
            ['bank' => 'MCB Operations', 'balance' => 8750000],
            ['bank' => 'UBL Payroll', 'balance' => 3200000],
            ['bank' => 'Meezan Corporate', 'balance' => 15800000],
        ];
    }

    public function getCashFlowTrend(string $company, array $filters = []): array
    {
        $days = (int) ($filters['trend_days'] ?? 7);
        $labels = [];
        $receipts = [];
        $payments = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            $receipts[] = rand(800000, 2500000);
            $payments[] = rand(600000, 2200000);
        }

        return compact('labels', 'receipts', 'payments');
    }

    public function getCashInHandBalance(string $company): float
    {
        return (float) rand(250000, 850000);
    }

    protected function generateTransactions(string $prefix, int $count): array
    {
        $types = ['Receive', 'Pay', 'Internal Transfer'];
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $amount = rand(25000, 850000);
            $rows[] = [
                'name' => "{$prefix}-".str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'posting_date' => now()->toDateString(),
                'party' => ['Alpha Corp', 'Beta Supplies', 'Gamma Retail', 'Delta Logistics'][rand(0, 3)],
                'paid_amount' => $amount,
                'payment_type' => $types[rand(0, 2)],
                'mode_of_payment' => ['Cash', 'Cheque', 'Bank Transfer'][rand(0, 2)],
            ];
        }

        return $rows;
    }
}
