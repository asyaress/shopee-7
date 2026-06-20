<?php

namespace Tests\Unit;

use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeToken;
use App\Services\Reports\ProductProfitReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductProfitReportServiceTest extends TestCase
{
    use RefreshDatabase;

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
}
