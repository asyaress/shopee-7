<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ShopeeAdsSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_can_continue_with_ams_when_ads_metadata_is_unavailable(): void
    {
        $mainToken = new ShopeeToken([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_MAIN,
            'shop_id' => 495488171,
            'access_token' => 'main-token',
        ]);

        $amsToken = new ShopeeToken([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_AMS,
            'shop_id' => 495488171,
            'access_token' => 'ams-token',
        ]);

        $product = Product::create([
            'name' => 'Produk Iklan',
            'external_platform' => 'shopee',
            'external_shop_id' => 495488171,
            'external_item_id' => 12345,
            'is_active' => true,
        ]);

        $mainClient = Mockery::mock(ShopeeClient::class);
        $mainClient->shouldReceive('requestPrivate')
            ->once()
            ->with(
                'GET',
                '/api/v2/ads/get_product_level_campaign_id_list',
                Mockery::type('array'),
                $mainToken
            )
            ->andThrow(new \RuntimeException('ads token missing'));

        $amsClient = Mockery::mock(ShopeeClient::class);
        $amsClient->shouldReceive('requestPrivate')
            ->once()
            ->with(
                'GET',
                '/api/v2/ams/get_product_performance',
                Mockery::type('array'),
                $amsToken
            )
            ->andReturn([
                'response' => [
                    'list' => [[
                        'item_id' => 12345,
                        'expense' => 12500,
                        'sales' => 50000,
                        'impressions' => 321,
                        'clicks' => 12,
                        'orders' => 3,
                    ]],
                    'has_more' => false,
                    'total_count' => 1,
                ],
            ]);

        $service = new ShopeeAdsSyncService($mainClient, null, null, $amsClient, $amsToken);

        $result = $service->syncBetween(
            $mainToken,
            Carbon::create(2026, 6, 20)->startOfDay(),
            Carbon::create(2026, 6, 20)->endOfDay(),
            0
        );

        $this->assertSame(1, $result['saved']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);

        $row = ShopeeProductAdsDaily::query()->firstOrFail();
        $this->assertSame(495488171, (int) $row->shop_id);
        $this->assertSame($product->id, (int) $row->product_id);
        $this->assertSame('12345', (string) $row->external_item_id);
        $this->assertSame('12500.00', (string) $row->spend);
        $this->assertSame('50000.00', (string) $row->gmv);
        $this->assertSame(3, (int) $row->orders);
        $this->assertSame('2026-06-20', $row->report_date->toDateString());
    }
}
