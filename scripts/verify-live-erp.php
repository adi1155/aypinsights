<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = $app->make(App\Services\ERPNext\ERPNextClient::class);
echo 'Live API: '.($client->isConfigured() ? 'YES' : 'NO')."\n";

$ar = $app->make(App\Services\ERPNext\ARService::class)->getDashboard(['company' => 'GMP Foods (Pvt.) Ltd']);
echo 'AR receivables: '.number_format($ar['kpis']['total_receivables'] ?? 0)."\n";
echo 'Outstanding invoices: '.count($ar['tables']['outstanding_invoices'] ?? [])."\n";

$closing = $app->make(App\Services\ERPNext\FinancialService::class)->getDailyClosingDashboard(['company' => 'GMP Foods (Pvt.) Ltd']);
echo 'Bank balance: '.number_format($closing['kpis']['bank_balance'] ?? 0)."\n";
echo 'Today receipts: '.number_format($closing['kpis']['todays_receipts'] ?? 0)."\n";
