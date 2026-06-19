<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = app(App\Services\ERPNext\ERPNextClient::class);
echo 'Configured: '.($client->isConfigured() ? 'yes' : 'no')."\n";

$filters = [
    'from_date' => '2026-05-01',
    'to_date' => '2026-05-31',
    'company' => config('erpnext.default_company'),
];
echo 'Company: '.$filters['company']."\n";

$repo = app(App\Contracts\ERPNext\PayrollRepositoryInterface::class);
$slips = $repo->getSalarySlips($filters);
echo 'Slips (payroll period filter): '.count($slips)."\n";

if (count($slips) > 0) {
    $s = $slips[0];
    echo 'Sample slip: '.json_encode([
        'employee_id' => $s['employee_id'] ?? null,
        'posting_date' => $s['posting_date'] ?? null,
        'start_date' => $s['start_date'] ?? null,
        'end_date' => $s['end_date'] ?? null,
        'docstatus' => $s['docstatus'] ?? null,
        'gross_pay' => $s['gross_pay'] ?? null,
    ])."\n";
}

// Raw API test with period overlap
if ($client->isConfigured()) {
    $company = $filters['company'];
    $periodRows = $client->getList('Salary Slip', [
        ['company', '=', $company],
        ['docstatus', '!=', 2],
        ['start_date', '<=', '2026-05-31'],
        ['end_date', '>=', '2026-05-01'],
    ], ['name', 'employee', 'employee_name', 'posting_date', 'start_date', 'end_date', 'docstatus', 'gross_pay', 'net_pay'], 10);
    echo 'Slips (period overlap API): '.count($periodRows)."\n";
    if (count($periodRows) > 0) {
        echo 'Period sample: '.json_encode($periodRows[0])."\n";
    }

    $noDateRows = $client->getList('Salary Slip', [
        ['company', '=', $company],
        ['docstatus', '=', 1],
    ], ['name', 'posting_date', 'start_date', 'end_date'], 5);
    echo 'Recent submitted slips (no date): '.count($noDateRows)."\n";
    foreach ($noDateRows as $row) {
        echo '  - '.$row['name'].' posting='.$row['posting_date'].' start='.$row['start_date'].' end='.$row['end_date']."\n";
    }
}

$summary = $repo->getEmployeePayrollSummary($filters);
echo 'Summary count: '.count($summary)."\n";
