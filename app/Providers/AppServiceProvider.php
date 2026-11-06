<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use App\View\Composers\ShopLayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        View::composer('layouts.shop', ShopLayoutComposer::class);
    }
}
