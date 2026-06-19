<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDashboardFilters;
use App\Models\ExecutiveNotification;
use App\Services\Export\ReportExporter;
use App\Services\ERPNext\APService;
use App\Services\ERPNext\ARService;
use App\Services\ERPNext\DashboardAggregator;
use App\Services\ERPNext\ExpenseService;
use App\Services\ERPNext\FinancialService;
use App\Services\ERPNext\AttendanceService;
use App\Services\ERPNext\PayrollService;
use App\Services\ERPNext\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    use ResolvesDashboardFilters;

    public function ceo(Request $request, DashboardAggregator $aggregator)
    {
        set_time_limit((int) config('erpnext.ceo_max_execution', 180));

        $filters = $this->sharedFilters($request);
        $error = null;

        try {
            $data = $aggregator->getCeoDashboard($filters);
        } catch (\Throwable $e) {
            Log::error('CEO IAS dashboard failed', ['message' => $e->getMessage(), 'filters' => $filters]);
            $error = 'Unable to load IAS financial statements from ERPNext. Try a shorter date range or clear cache.';
            $data = $this->emptyIasPayload($filters);
        }

        $notifications = ExecutiveNotification::query()
            ->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
            })
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboards.ceo', compact('data', 'filters', 'notifications', 'error'));
    }

    public function dailyClosing(Request $request, FinancialService $service)
    {
        $filters = $this->sharedFilters($request);

        return view('dashboards.daily-closing', [
            'data' => $service->getDailyClosingDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function ap(Request $request, APService $service)
    {
        $filters = $this->sharedFilters($request);

        return view('dashboards.ap', [
            'data' => $service->getDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function ar(Request $request, ARService $service)
    {
        $filters = $this->sharedFilters($request);

        return view('dashboards.ar', [
            'data' => $service->getDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function expense(Request $request, ExpenseService $service)
    {
        $filters = $this->sharedFilters($request);

        return view('dashboards.expense', [
            'data' => $service->getDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function payroll(Request $request, PayrollService $service)
    {
        set_time_limit((int) config('erpnext.payroll_max_execution', 300));

        $filters = $this->sharedFilters($request);

        return view('dashboards.payroll', [
            'data' => $service->getDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function attendance(Request $request, AttendanceService $service)
    {
        $filters = $this->sharedFilters($request);

        return view('dashboards.attendance', [
            'data' => $service->getDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function production(Request $request, ProductionService $service)
    {
        $filters = $this->sharedFilters($request);

        return view('dashboards.production', [
            'data' => $service->getDashboard($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, ReportExporter $exporter, string $type, string $dashboard)
    {
        $filters = $this->sharedFilters($request);
        $service = match ($dashboard) {
            'daily-closing' => app(FinancialService::class)->getDailyClosingDashboard($filters),
            'ap' => app(APService::class)->getDashboard($filters),
            'ar' => app(ARService::class)->getDashboard($filters),
            'expense' => app(ExpenseService::class)->getDashboard($filters),
            'payroll' => app(PayrollService::class)->getDashboard($filters),
            'attendance' => app(AttendanceService::class)->getDashboard($filters),
            'production' => app(ProductionService::class)->getDashboard($filters),
            'ceo' => app(DashboardAggregator::class)->getCeoDashboard($filters),
            default => abort(404),
        };

        if ($type === 'pdf') {
            return $exporter->toPdf('exports.dashboard', [
                'title' => ucwords(str_replace('-', ' ', $dashboard)),
                'data' => $service,
                'filters' => $filters,
            ], "executive-{$dashboard}-".now()->format('Y-m-d'));
        }

        $rows = [];
        $pl = $service['statement_of_profit_or_loss'] ?? [];
        $bs = $service['statement_of_financial_position'] ?? [];
        foreach (array_merge($bs, $pl) as $key => $value) {
            if (is_numeric($value) || is_string($value)) {
                $rows[] = [ucwords(str_replace('_', ' ', $key)), $value];
            }
        }
        if (empty($rows)) {
            foreach ($service['kpis'] ?? [] as $key => $value) {
                if (is_numeric($value) || is_string($value)) {
                    $rows[] = [ucwords(str_replace('_', ' ', $key)), $value];
                }
            }
        }

        return $exporter->toCsv(['Metric', 'Value'], $rows, "executive-{$dashboard}");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function emptyIasPayload(array $filters): array
    {
        $zero = fn () => array_fill_keys([
            'total_assets', 'current_assets', 'non_current_assets',
            'total_liabilities', 'current_liabilities', 'non_current_liabilities',
            'total_equity', 'total_liabilities_and_equity',
        ], 0);

        return [
            'filters' => $filters,
            'currency' => config('erpnext.default_currency', 'PKR'),
            'statement_of_financial_position' => array_merge($zero(), ['accounting_equation_balanced' => false]),
            'statement_of_profit_or_loss' => [
                'total_income' => 0, 'total_expenses' => 0, 'gross_profit' => 0,
                'operating_expenses' => 0, 'profit_before_tax' => 0, 'net_profit_loss' => 0, 'net_profit_margin_pct' => 0,
            ],
            'ratios' => [],
            'insights' => [],
            'operational' => [],
            'charts' => [],
            'tables' => [],
            'health_score' => 0,
        ];
    }
}
