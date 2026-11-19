<?php

namespace App\Providers;

use App\Services\PayNowService;
use Illuminate\Support\ServiceProvider;

class PayNowServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PayNowService::class, function ($app) {
            return new PayNowService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
