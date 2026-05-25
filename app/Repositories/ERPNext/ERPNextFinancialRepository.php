<?php

namespace App\Repositories\ERPNext;

use App\Contracts\ERPNext\FinancialRepositoryInterface;
use App\Repositories\ERPNext\Concerns\BuildsErpNextFilters;
use App\Services\ERPNext\ERPNextClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ERPNextFinancialRepository implements FinancialRepositoryInterface
{
    use BuildsErpNextFilters;

    public function __construct(protected ERPNextClient $client) {}

    public function getGLEntries(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['is_cancelled', '=', 0],
        ]);

        return $this->client->getList('GL Entry', $erpFilters, [
            'name', 'posting_date', 'account', 'party', 'debit', 'credit', 'voucher_type', 'voucher_no',
        ], 100);
    }

    public function getPaymentEntries(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
            ['docstatus', '=', 1],
        ]);

        return $this->client->getList('Payment Entry', $erpFilters, [
            'name', 'posting_date', 'party', 'party_type', 'payment_type', 'paid_amount',
            'mode_of_payment', 'reference_no',
        ], 500);
    }

    public function getJournalEntries(array $filters = []): array
    {
        $company = $this->defaultCompany($filters);
        $erpFilters = $this->buildFilters($filters, [
            ['company', '=', $company],
        ]);

        $rows = $this->client->getList('Journal Entry', $erpFilters, [
            'name', 'posting_date', 'total_debit', 'total_credit', 'company', 'docstatus',
        ], 50);

        return array_map(fn ($row) => [
            'name' => $row['name'],
            'posting_date' => $row['posting_date'],
            'total_debit' => $row['total_debit'] ?? 0,
            'total_credit' => $row['total_credit'] ?? 0,
            'company' => $row['company'],
            'status' => match ((int) ($row['docstatus'] ?? 0)) {
                0 => 'Draft',
                1 => 'Submitted',
                2 => 'Cancelled',
                default => 'Unknown',
            },
        ], $rows);
    }

    public function getBankBalances(string $company, ?string $branch = null): array
    {
        return Cache::remember("erpnext.banks.{$company}", 900, function () use ($company) {
            $accounts = $this->client->getList('Account', [
                ['company', '=', $company],
                ['account_type', '=', 'Bank'],
                ['is_group', '=', 0],
            ], ['name', 'account_name'], 12);

            $date = now()->toDateString();

            return array_map(function ($acc) use ($company, $date) {
                return [
                    'bank' => $acc['account_name'] ?? $acc['name'],
                    'balance' => $this->client->getAccountBalance($acc['name'], $date, $company),
                ];
            }, $accounts);
        });
    }

    public function getCashFlowTrend(string $company, array $filters = []): array
    {
        $from = Carbon::parse($filters['from_date'] ?? now()->subDays(6))->startOfDay();
        $to = Carbon::parse($filters['to_date'] ?? now())->startOfDay();
        $days = min(max($from->diffInDays($to) + 1, 1), 31);

        $entries = $this->getPaymentEntries(array_merge($filters, [
            'company' => $company,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
        ]));

        $labels = [];
        $receipts = [];
        $payments = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            if ($date->gt($to)) {
                break;
            }
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $dayRows = collect($entries)->where('posting_date', $key);
            $receipts[] = (float) $dayRows->where('payment_type', 'Receive')->sum('paid_amount');
            $payments[] = (float) $dayRows->where('payment_type', 'Pay')->sum('paid_amount');
        }

        return compact('labels', 'receipts', 'payments');
    }

    public function getCashInHandBalance(string $company): float
    {
        return Cache::remember("erpnext.cash.{$company}", 900, function () use ($company) {
            $accounts = $this->client->getList('Account', [
                ['company', '=', $company],
                ['account_type', '=', 'Cash'],
                ['is_group', '=', 0],
            ], ['name'], 10);

            return (float) collect($accounts)->sum(
                fn ($acc) => $this->client->getAccountBalance($acc['name'], now()->toDateString(), $company)
            );
        });
    }
}
