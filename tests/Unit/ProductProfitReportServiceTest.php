<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeToken;
use App\Services\Reports\ProductProfitReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductProfitReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_period_uses_current_month_to_date(): void
    {
        Carbon::setTestNow('2026-06-21 12:00:00');

        try {
            $report = app(ProductProfitReportService::class)->build(
                Request::create('/monitoring', 'GET')
            );

            $this->assertSame('2026-06-01', $report['filters']['start']);
            $this->assertSame('2026-06-21', $report['filters']['end']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_summary_includes_shop_ads_without_matching_order_product(): void
    {
        config(['shopee.env' => 'prod']);

        ShopeeToken::query()->create([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_MAIN,
            'partner_id' => 100001,
            'shop_id' => 495488171,
            'access_token' => 'main-token',
        ]);

        ShopeeProductAdsDaily::query()->create([
            'shop_id' => 495488171,
            'product_id' => null,
            'external_item_id' => '12345',
            'report_date' => '2026-06-20',
            'spend' => 12500,
        ]);

        $request = Request::create('/monitoring', 'GET', [
            'start' => '2026-06-01',
            'end' => '2026-06-30',
            'status' => 'completed',
        ]);

        $report = app(ProductProfitReportService::class)->build($request);

        $this->assertSame(12500, $report['summary']['ads_total']);
        $this->assertSame(-12500, $report['summary']['net_profit']);
    }

    public function test_ads_mode_includes_all_shop_products_without_orders(): void
    {
        config(['shopee.env' => 'prod']);

        ShopeeToken::query()->create([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_MAIN,
            'partner_id' => 100001,
            'shop_id' => 495488171,
            'access_token' => 'main-token',
        ]);

        $first = Product::query()->create([
            'name' => 'Produk Tanpa Order A',
            'external_platform' => 'shopee',
            'external_shop_id' => 495488171,
            'external_item_id' => 11111,
            'is_active' => true,
        ]);
        $second = Product::query()->create([
            'name' => 'Produk Tanpa Order B',
            'external_platform' => 'shopee',
            'external_shop_id' => 495488171,
            'external_item_id' => 22222,
            'is_active' => true,
        ]);

        $request = Request::create('/monitoring/ads', 'GET', [
            'start' => '2026-06-01',
            'end' => '2026-06-30',
            'status' => 'completed',
        ]);
        $request->attributes->set('include_all_products', true);

        $report = app(ProductProfitReportService::class)->build($request);
        $productIds = collect($report['products'])->pluck('product_id')->sort()->values()->all();

        $this->assertSame([$first->id, $second->id], $productIds);
        $this->assertSame(2, $report['summary']['products_count']);
    }
}
