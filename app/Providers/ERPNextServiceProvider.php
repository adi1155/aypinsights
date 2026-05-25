<?php

namespace App\Providers;

use App\Contracts\ERPNext\APRepositoryInterface;
use App\Contracts\ERPNext\ARRepositoryInterface;
use App\Contracts\ERPNext\ExpenseRepositoryInterface;
use App\Contracts\ERPNext\FinancialRepositoryInterface;
use App\Repositories\ERPNext\DummyAPRepository;
use App\Repositories\ERPNext\DummyARRepository;
use App\Repositories\ERPNext\DummyExpenseRepository;
use App\Repositories\ERPNext\DummyFinancialRepository;
use App\Repositories\ERPNext\ERPNextAPRepository;
use App\Repositories\ERPNext\ERPNextARRepository;
use App\Repositories\ERPNext\ERPNextExpenseRepository;
use App\Repositories\ERPNext\ERPNextFinancialRepository;
use App\Services\ERPNext\ERPNextClient;
use Illuminate\Support\ServiceProvider;

class ERPNextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ERPNextClient::class);

        $useLive = fn () => $this->app->make(ERPNextClient::class)->isConfigured();

        $this->app->bind(FinancialRepositoryInterface::class, function ($app) use ($useLive) {
            $client = $app->make(ERPNextClient::class);

            return $useLive()
                ? new ERPNextFinancialRepository($client)
                : new DummyFinancialRepository;
        });

        $this->app->bind(ARRepositoryInterface::class, function ($app) use ($useLive) {
            $client = $app->make(ERPNextClient::class);

            return $useLive()
                ? new ERPNextARRepository($client)
                : new DummyARRepository;
        });

        $this->app->bind(APRepositoryInterface::class, function ($app) use ($useLive) {
            $client = $app->make(ERPNextClient::class);

            return $useLive()
                ? new ERPNextAPRepository($client)
                : new DummyAPRepository;
        });

        $this->app->bind(ExpenseRepositoryInterface::class, function ($app) use ($useLive) {
            $client = $app->make(ERPNextClient::class);

            return $useLive()
                ? new ERPNextExpenseRepository($client)
                : new DummyExpenseRepository;
        });
    }
}
