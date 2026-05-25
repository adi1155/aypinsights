<?php

namespace App\Contracts\ERPNext;

interface FinancialRepositoryInterface
{
    public function getGLEntries(array $filters = []): array;

    public function getPaymentEntries(array $filters = []): array;

    public function getJournalEntries(array $filters = []): array;

    public function getBankBalances(string $company, ?string $branch = null): array;

    public function getCashFlowTrend(string $company, array $filters = []): array;

    public function getCashInHandBalance(string $company): float;
}
