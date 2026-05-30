<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ShopeeProductPerformance;
use App\Services\Reports\BcgFunnelService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BcgHybridTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_sync_saves_rows_and_skips_import_source(): void
    {
        $shopId = 9001;
        $itemImport = 111;
        $itemAuto = 222;
        $periodStart = now()->subDays(30)->startOfDay();
        $periodEnd = now()->endOfDay();

        Product::create([
            'name' => 'Produk Import',
            'external_platform' => 'shopee',
            'external_shop_id' => $shopId,
            'external_item_id' => $itemImport,
        ]);
        Product::create([
            'name' => 'Produk Auto',
            'external_platform' => 'shopee',
            'external_shop_id' => $shopId,
            'external_item_id' => $itemAuto,
        ]);

        ShopeeProductPerformance::create([
            'shop_id' => $shopId,
            'external_item_id' => $itemImport,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'source' => ShopeeProductPerformance::SOURCE_IMPORT,
            'visitors' => 500,
            'page_views' => 600,
            'units_sold' => 20,
            'sales_gmv' => 1000000,
            'conversion_rate' => 0.04,
            'product_name' => 'Produk Import',
        ]);

        $client = Mockery::mock(ShopeeClient::class);
        $client->shouldReceive('requestPrivate')
            ->once()
            ->with('GET', '/api/v2/product/get_item_extra_info', Mockery::type('array'), Mockery::any())
            ->andReturn([
                'item_list' => [
                    ['item_id' => $itemImport, 'views' => 50, 'sale' => 10],
                    ['item_id' => $itemAuto, 'views' => 200, 'sale' => 5],
                ],
            ]);

        $token = new \App\Models\ShopeeToken([
            'shop_id' => $shopId,
            'env' => 'test',
            'access_token' => 'x',
            'refresh_token' => 'y',
        ]);
        $token->id = 1;

        Carbon::setTestNow($periodEnd);
        $result = (new ShopeeBcgSyncService($client))->sync($token, $periodStart, $periodEnd);
        Carbon::setTestNow();

        $this->assertSame(1, $result['saved']);
        $this->assertSame(1, $result['skipped']);

        $importRow = ShopeeProductPerformance::query()
            ->where('external_item_id', $itemImport)
            ->first();
        $this->assertSame(ShopeeProductPerformance::SOURCE_IMPORT, $importRow->source);
        $this->assertSame(500, $importRow->visitors);

        $autoRow = ShopeeProductPerformance::query()
            ->where('external_item_id', $itemAuto)
            ->first();
        $this->assertNotNull($autoRow);
        $this->assertSame(ShopeeProductPerformance::SOURCE_AUTO, $autoRow->source);
        $this->assertSame(200, $autoRow->visitors);
        $this->assertSame(0, $autoRow->units_sold);
    }

    public function test_import_overrides_auto_row(): void
    {
        $shopId = 9002;
        $itemId = 333;
        $periodStart = '2026-04-01';
        $periodEnd = '2026-05-01';

        ShopeeProductPerformance::create([
            'shop_id' => $shopId,
            'external_item_id' => $itemId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'source' => ShopeeProductPerformance::SOURCE_AUTO,
            'visitors' => 100,
            'page_views' => 100,
            'units_sold' => 2,
            'sales_gmv' => 50000,
            'conversion_rate' => 0.02,
            'product_name' => 'Before',
        ]);

        $row = ShopeeProductPerformance::query()->where('external_item_id', $itemId)->first();
        $this->assertNotNull($row);

        $row->update([
            'source' => ShopeeProductPerformance::SOURCE_IMPORT,
            'visitors' => 800,
            'page_views' => 900,
            'units_sold' => 40,
            'sales_gmv' => 2000000,
            'conversion_rate' => 0.05,
            'product_name' => 'After Import',
        ]);

        $row->refresh();
        $this->assertSame(ShopeeProductPerformance::SOURCE_IMPORT, $row->source);
        $this->assertSame(800, $row->visitors);
        $this->assertSame(40, $row->units_sold);
    }

    public function test_bcg_funnel_reports_mixed_data_source(): void
    {
        $shopId = 9003;
        $periodStart = now()->subDays(30)->startOfDay();
        $periodEnd = now()->endOfDay();

        ShopeeProductPerformance::create([
            'shop_id' => $shopId,
            'external_item_id' => 1,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'source' => ShopeeProductPerformance::SOURCE_AUTO,
            'visitors' => 150,
            'units_sold' => 3,
            'conversion_rate' => 0.02,
            'product_name' => 'Auto SKU',
        ]);
        ShopeeProductPerformance::create([
            'shop_id' => $shopId,
            'external_item_id' => 2,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'source' => ShopeeProductPerformance::SOURCE_IMPORT,
            'visitors' => 300,
            'units_sold' => 12,
            'conversion_rate' => 0.04,
            'product_name' => 'Import SKU',
        ]);

        $bcg = (new BcgFunnelService())->build($shopId, $periodStart, $periodEnd);

        $this->assertSame('mixed', $bcg['data_source']);
        $this->assertSame('Campuran — import + auto', $bcg['data_source_label']);
        $this->assertSame(1, $bcg['source_counts']['auto']);
        $this->assertSame(1, $bcg['source_counts']['import']);
        $this->assertTrue($bcg['has_data']);
    }
}
