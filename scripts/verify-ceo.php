<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$filters = [
    'company' => 'GMP Foods (Pvt.) Ltd',
    'from_date' => date('Y-m-01'),
    'to_date' => date('Y-m-d'),
];

$data = $app->make(App\Services\ERPNext\DashboardAggregator::class)->getCeoDashboard($filters);
$bs = $data['statement_of_financial_position'] ?? [];
$pl = $data['statement_of_profit_or_loss'] ?? [];

echo "Health: ".($data['health_score'] ?? 0)."\n";
echo "Assets: ".number_format($bs['total_assets'] ?? 0)."\n";
echo "Liabilities: ".number_format($bs['total_liabilities'] ?? 0)."\n";
echo "Equity: ".number_format($bs['total_equity'] ?? 0)."\n";
echo "Income: ".number_format($pl['total_income'] ?? 0)."\n";
echo "Expenses: ".number_format($pl['total_expenses'] ?? 0)."\n";
echo "Net P/L: ".number_format($pl['net_profit_loss'] ?? 0)."\n";
