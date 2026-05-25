<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ERPNext\APService;
use App\Services\ERPNext\ARService;
use App\Services\ERPNext\DashboardAggregator;
use App\Services\ERPNext\ExpenseService;
use App\Services\ERPNext\FinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    protected function filters(Request $request): array
    {
        $validated = $request->validate([
            'company' => ['nullable', 'string'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'trend_days' => ['nullable', 'integer', 'in:7,30'],
        ]);

        $validated['from_date'] = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $validated['to_date'] = $validated['to_date'] ?? now()->toDateString();

        return $validated;
    }

    public function dailyClosing(Request $request, FinancialService $service): JsonResponse
    {
        return response()->json($service->getDailyClosingDashboard($this->filters($request)));
    }

    public function ap(Request $request, APService $service): JsonResponse
    {
        return response()->json($service->getDashboard($this->filters($request)));
    }

    public function ar(Request $request, ARService $service): JsonResponse
    {
        return response()->json($service->getDashboard($this->filters($request)));
    }

    public function expense(Request $request, ExpenseService $service): JsonResponse
    {
        return response()->json($service->getDashboard($this->filters($request)));
    }

    public function ceo(Request $request, DashboardAggregator $aggregator): JsonResponse
    {
        return response()->json($aggregator->getCeoDashboard($this->filters($request)));
    }
}
