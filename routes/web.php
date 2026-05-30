<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HppController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\CeoController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfitReportController;
use App\Http\Controllers\ShopSwitchController;
use App\Http\Controllers\ShopeeIntegrationController;
use App\Http\Controllers\SimpleAuthController;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// -----------------------------------------------------------------------------
// Simple login (minimal) - cocok untuk kebutuhan review/testing Shopee
// -----------------------------------------------------------------------------
Route::get('/login', [SimpleAuthController::class, 'show'])->name('login');
Route::post('/login', [SimpleAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [SimpleAuthController::class, 'logout'])->name('logout');

// Shopee callback dibiarkan TANPA login supaya tidak kehilangan parameter ?code=...
Route::prefix('integrations/shopee')->name('shopee.')->group(function () {
    Route::get('/callback', [ShopeeIntegrationController::class, 'callback'])->name('callback');
});

// Semua halaman aplikasi diproteksi dengan middleware simple.auth
Route::middleware('simple.auth')->group(function () {
    // Hub utama — Monitoring per section
    Route::post('/shop/switch', [ShopSwitchController::class, 'switch'])->name('shop.switch');

    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [MonitoringController::class, 'overview'])->name('index');
        Route::get('/aksi', [MonitoringController::class, 'actions'])->name('actions');
        Route::get('/shopee', [MonitoringController::class, 'shopee'])->name('shopee');
        Route::get('/pendapatan', [MonitoringController::class, 'revenue'])->name('revenue');
        Route::get('/iklan', [MonitoringController::class, 'ads'])->name('ads');
        Route::get('/laba', [MonitoringController::class, 'profit'])->name('profit');
        Route::get('/matrix', [MonitoringController::class, 'matrix'])->name('matrix');
        Route::get('/rekap', [MonitoringController::class, 'rekap'])->name('rekap');
        Route::get('/bcg', [MonitoringController::class, 'bcg'])->name('bcg');
        Route::post('/bcg/import', [MonitoringController::class, 'importBcgPerformance'])->name('bcg.import');
        Route::post('/bcg/sync', [MonitoringController::class, 'syncBcgPerformance'])->name('bcg.sync');
        Route::post('/bcg/targets', [MonitoringController::class, 'saveProductTargets'])->name('bcg.targets');
        Route::get('/ceo', [MonitoringController::class, 'executive'])->name('executive');
        Route::get('/produk/{product}', [MonitoringController::class, 'product'])->name('product');
    });
    Route::get('/', [MonitoringController::class, 'overview']);

    Route::prefix('manage')->name('manage.')->group(function () {
        Route::get('/', [ManageController::class, 'index'])->name('index');
        Route::post('/operational', [ManageController::class, 'saveOperational'])->name('operational.save');
        Route::post('/costs', [ManageController::class, 'saveCosts'])->name('costs.save');
        Route::post('/sync/orders', [ManageController::class, 'syncOrders'])->name('sync.orders');
        Route::post('/sync/products', [ManageController::class, 'syncProducts'])->name('sync.products');
        Route::post('/sync/ads', [ManageController::class, 'syncAds'])->name('sync.ads');
        Route::post('/sync/all', [ManageController::class, 'syncAll'])->name('sync.all');
    });

    Route::prefix('ceo')->name('ceo.')->group(function () {
        Route::get('/target', [CeoController::class, 'targets'])->name('targets');
        Route::post('/target', [CeoController::class, 'saveTargets'])->name('targets.save');
        Route::get('/settlement', [CeoController::class, 'settlement'])->name('settlement');
        Route::post('/settlement/import', [MonitoringController::class, 'importSettlement'])->name('settlement.import');
        Route::get('/promo', [CeoController::class, 'promo'])->name('promo');
        Route::get('/roas', [CeoController::class, 'roas'])->name('roas');
        Route::get('/decisions', [CeoController::class, 'decisions'])->name('decisions');
        Route::post('/decisions', [CeoController::class, 'storeDecision'])->name('decisions.store');
        Route::get('/export/journal', [CeoController::class, 'exportJournal'])->name('export.journal');
        Route::post('/alerts/run', [CeoController::class, 'runAlerts'])->name('alerts.run');
    });

    Route::get('/hpp', [HppController::class, 'index'])->name('hpp.index');
    Route::post('/hpp', [HppController::class, 'save'])->name('hpp.save');

    // Product costs (HPP + packaging) - editor varian lengkap
    Route::get('/products/costs', [ProductController::class, 'costsIndex'])->name('products.costs');

    // Profit report (Pro) — legacy
    Route::get('/reports/profit', [ProfitReportController::class, 'index'])->name('reports.profit');
    Route::get('/reports/profit/export', [ProfitReportController::class, 'export'])->name('reports.profit.export');

    // Dashboard legacy
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/product-chart-data', [DashboardController::class, 'productChartData'])->name('dashboard.product-chart-data');

    // Analysis routes
    Route::get('/dashboard/weekly-analysis', [DashboardController::class, 'weeklyAnalysis'])->name('dashboard.weekly-analysis');
    Route::get('/dashboard/weekly-in-month-analysis', [DashboardController::class, 'weeklyInMonthAnalysis'])->name('dashboard.weekly-in-month-analysis');
    Route::get('/dashboard/monthly-analysis', [DashboardController::class, 'monthlyAnalysis'])->name('dashboard.monthly-analysis');

    // Resource routes untuk CRUD
    Route::resource('orders', OrderController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class);

    // Additional routes untuk Orders
    Route::patch('/orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/orders-export', [OrderController::class, 'export'])->name('orders.export');

    // API routes untuk AJAX calls
    Route::prefix('api')->group(function () {
        Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
        Route::get('/products', function () {
            return response()->json(Product::active()->get());
        })->name('products.api');
    });

    // Shopee Integration (Sandbox/Production)
    Route::prefix('integrations/shopee')->name('shopee.')->group(function () {
        Route::get('/', [ShopeeIntegrationController::class, 'index'])->name('index');
        Route::get('/connect', [ShopeeIntegrationController::class, 'connect'])->name('connect');
        Route::post('/sync', [ShopeeIntegrationController::class, 'sync'])->name('sync');
        Route::post('/sync-products', [ShopeeIntegrationController::class, 'syncProducts'])->name('sync-products');
        Route::post('/sync-all', [ShopeeIntegrationController::class, 'syncAll'])->name('sync-all');
    });
    Route::patch('/products/{product}/costs', [ProductController::class, 'updateCosts'])
    ->name('products.update-costs');
    


});


Route::get('/debug/shopee/auth-check', [ShopeeIntegrationController::class, 'debugAuthCheck']);
