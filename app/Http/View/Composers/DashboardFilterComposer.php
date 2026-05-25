<?php

namespace App\Http\View\Composers;

use App\Services\ERPNext\CompanyListService;
use Illuminate\View\View;

class DashboardFilterComposer
{
    public function __construct(protected CompanyListService $companies) {}

    public function compose(View $view): void
    {
        $view->with('erpCompanies', $this->companies->list());
    }
}
