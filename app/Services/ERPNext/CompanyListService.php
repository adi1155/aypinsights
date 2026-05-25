<?php

namespace App\Services\ERPNext;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class CompanyListService
{
    public function __construct(protected ERPNextClient $client) {}

    /**
     * @return array<int, string>
     */
    public function list(): array
    {
        return Cache::remember('erpnext.companies.list', 3600, function () {
            if ($this->client->hasCredentials()) {
                try {
                    $rows = $this->client->getCompanies();

                    return collect($rows)->pluck('name')->filter()->values()->all();
                } catch (\Throwable) {
                    report($e);
                }
            }

            return Company::query()->where('is_active', true)->pluck('erpnext_name')->all()
                ?: [config('erpnext.default_company', 'GMP Foods (Pvt.) Ltd')];
        });
    }
}
