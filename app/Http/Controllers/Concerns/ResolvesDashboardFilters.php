<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DashboardPreference;
use App\Services\ERPNext\CompanyListService;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait ResolvesDashboardFilters
{
    /**
     * @return array{company: string, from_date: string, to_date: string, trend_days: int}
     */
    protected function sharedFilters(Request $request): array
    {
        $prefs = DashboardPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['theme' => 'dark']
        );

        $toDate = $request->get('to_date', now()->toDateString());
        $fromDate = $request->get('from_date', Carbon::parse($toDate)->startOfMonth()->toDateString());

        if (Carbon::parse($fromDate)->gt(Carbon::parse($toDate))) {
            $fromDate = Carbon::parse($toDate)->startOfMonth()->toDateString();
        }

        $companies = app(CompanyListService::class)->list();
        $defaultCompany = $prefs->default_company
            ?? $request->user()->company
            ?? config('erpnext.default_company')
            ?? ($companies[0] ?? '');

        $company = $request->get('company', $defaultCompany);
        if (! in_array($company, $companies, true) && ! empty($companies)) {
            $company = $companies[0];
        }

        return [
            'company' => $company,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'trend_days' => (int) $request->get('trend_days', 7),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function erpCompanies(): array
    {
        return app(CompanyListService::class)->list();
    }
}
