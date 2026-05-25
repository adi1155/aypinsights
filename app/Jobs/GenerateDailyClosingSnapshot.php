<?php

namespace App\Jobs;

use App\Models\DailyClosing;
use App\Services\ERPNext\DashboardAggregator;
use App\Services\ERPNext\FinancialService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateDailyClosingSnapshot implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $company,
        public ?string $branch = null,
        public ?string $date = null,
    ) {}

    public function handle(FinancialService $financial, DashboardAggregator $aggregator): void
    {
        $filters = [
            'company' => $this->company,
            'branch' => $this->branch,
            'date' => $this->date ?? now()->toDateString(),
        ];

        $data = $financial->getDailyClosingDashboard($filters);
        $kpis = $data['kpis'];

        DailyClosing::updateOrCreate(
            [
                'closing_date' => $filters['date'],
                'company' => $this->company,
                'branch' => $this->branch,
            ],
            [
                'opening_balance' => $kpis['opening_balance'],
                'receipts' => $kpis['todays_receipts'],
                'payments' => $kpis['todays_payments'],
                'closing_balance' => $kpis['closing_balance'],
                'bank_balance' => $kpis['bank_balance'],
                'cash_in_hand' => $kpis['cash_in_hand'],
                'pending_deposits' => $kpis['pending_deposits'],
                'outstanding_cheques' => $kpis['outstanding_cheques'],
                'daily_profit_loss' => $kpis['daily_profit_loss'],
            ]
        );

        $aggregator->createSnapshot('daily_closing', $data, $filters);
    }
}
