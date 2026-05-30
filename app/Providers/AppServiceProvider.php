<?php

namespace App\Providers;

use App\Support\ShopeeShopContext;
use Illuminate\Pagination\Paginator;
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
        if ($url = config('app.url')) {
            if (str_starts_with($url, 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.hub');
        Paginator::defaultSimpleView('vendor.pagination.hub');

        View::composer('layouts.hub', function ($view) {
            $view->with('shopeeShopOptions', ShopeeShopContext::shopOptions());
            $view->with('activeShopeeShopId', ShopeeShopContext::shopId());
            $view->with('activeShopeeShopLabel', ShopeeShopContext::shopLabel(ShopeeShopContext::shopId()));
        });
    }
}
