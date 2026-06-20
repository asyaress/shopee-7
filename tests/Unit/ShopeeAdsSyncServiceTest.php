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

    public function test_sync_tries_ads_product_data_after_ams_failure_and_replaces_shop_aggregate(): void
    {
        $mainToken = new ShopeeToken([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_MAIN,
            'shop_id' => 495488171,
            'access_token' => 'main-token',
        ]);
        $adsToken = new ShopeeToken([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_ADS,
            'shop_id' => 495488171,
            'access_token' => 'ads-token',
        ]);
        $amsToken = new ShopeeToken([
            'env' => 'prod',
            'app_type' => ShopeeToken::APP_AMS,
            'shop_id' => 495488171,
            'access_token' => 'ams-token',
        ]);

        $product = Product::create([
            'name' => 'Produk Ads Campaign',
            'external_platform' => 'shopee',
            'external_shop_id' => 495488171,
            'external_item_id' => 12345,
            'is_active' => true,
        ]);

        ShopeeProductAdsDaily::query()->create([
            'shop_id' => 495488171,
            'product_id' => null,
            'external_item_id' => 'shop_aggregate',
            'report_date' => '2026-06-20',
            'spend' => 12500,
        ]);

        $mainClient = Mockery::mock(ShopeeClient::class);
        $adsClient = Mockery::mock(ShopeeClient::class);
        $amsClient = Mockery::mock(ShopeeClient::class);

        $adsClient->shouldReceive('requestPrivate')
            ->once()
            ->with('GET', '/api/v2/ads/get_product_level_campaign_id_list', Mockery::type('array'), $adsToken)
            ->andReturn([
                'response' => [
                    'campaign_id_list' => [['campaign_id' => 777]],
                    'total_count' => 1,
                ],
            ]);
        $adsClient->shouldReceive('requestPrivate')
            ->once()
            ->with('GET', '/api/v2/ads/get_product_level_campaign_setting_info', Mockery::type('array'), $adsToken)
            ->andReturn([
                'response' => [
                    'campaign_list' => [[
                        'campaign_id' => 777,
                        'common_info' => ['item_id' => 12345],
                    ]],
                ],
            ]);

        $amsClient->shouldReceive('requestPrivate')
            ->once()
            ->with('GET', '/api/v2/ams/get_product_performance', Mockery::type('array'), $amsToken)
            ->andThrow(new \RuntimeException(
                'Shopee API error (error_rate_limit): Too many requests.'
            ));

        $adsClient->shouldReceive('requestPrivate')
            ->once()
            ->with('GET', '/api/v2/ads/get_product_campaign_daily_performance', Mockery::type('array'), $adsToken)
            ->andReturn([
                'response' => [
                    'daily_performance_list' => [[
                        'campaign_id' => 777,
                        'date' => '2026-06-20',
                        'expense' => 12500,
                        'broad_gmv' => 50000,
                        'impression' => 321,
                        'click' => 12,
                        'order' => 3,
                    ]],
                ],
            ]);

        $service = new ShopeeAdsSyncService($mainClient, $adsClient, $adsToken, $amsClient, $amsToken);
        $result = $service->syncBetween(
            $mainToken,
            Carbon::create(2026, 6, 20)->startOfDay(),
            Carbon::create(2026, 6, 20)->endOfDay(),
            0
        );

        $this->assertSame(1, $result['saved']);
        $this->assertFalse(ShopeeProductAdsDaily::query()
            ->where('shop_id', 495488171)
            ->where('external_item_id', 'shop_aggregate')
            ->exists());

        $row = ShopeeProductAdsDaily::query()->where('external_item_id', '12345')->firstOrFail();
        $this->assertSame($product->id, (int) $row->product_id);
        $this->assertSame('12500.00', (string) $row->spend);
        $this->assertSame('2026-06-20', $row->report_date->toDateString());
        $this->assertSame(1, ShopeeProductAdsDaily::query()->count());
    }

    public function test_sync_keeps_earlier_ams_rows_when_latest_date_is_not_ready(): void
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
            'name' => 'Produk AMS Tertunda',
            'external_platform' => 'shopee',
            'external_shop_id' => 495488171,
            'external_item_id' => 12345,
            'is_active' => true,
        ]);

        $mainClient = Mockery::mock(ShopeeClient::class);
        $mainClient->shouldReceive('requestPrivate')
            ->once()
            ->with('GET', '/api/v2/ads/get_product_level_campaign_id_list', Mockery::type('array'), $mainToken)
            ->andThrow(new \RuntimeException('ads metadata unavailable'));

        $amsClient = Mockery::mock(ShopeeClient::class);
        $amsClient->shouldReceive('requestPrivate')
            ->once()
            ->ordered()
            ->with('GET', '/api/v2/ams/get_product_performance', Mockery::on(
                fn (array $params) => ($params['start_date'] ?? null) === '20260619'
            ), $amsToken)
            ->andReturn([
                'response' => [
                    'list' => [[
                        'item_id' => 12345,
                        'expense' => 12500,
                        'sales' => 50000,
                        'clicks' => 12,
                    ]],
                    'has_more' => false,
                    'total_count' => 1,
                ],
            ]);
        $amsClient->shouldReceive('requestPrivate')
            ->once()
            ->ordered()
            ->with('GET', '/api/v2/ams/get_product_performance', Mockery::on(
                fn (array $params) => ($params['start_date'] ?? null) === '20260620'
            ), $amsToken)
            ->andThrow(new \RuntimeException(
                'Shopee API error (error_param): invalid time range, detail:start_date cannot be later than latest data date'
            ));

        $service = new ShopeeAdsSyncService($mainClient, null, null, $amsClient, $amsToken);
        $result = $service->syncBetween(
            $mainToken,
            Carbon::create(2026, 6, 19)->startOfDay(),
            Carbon::create(2026, 6, 20)->endOfDay(),
            0
        );

        $this->assertSame(1, $result['saved']);
        $row = ShopeeProductAdsDaily::query()->firstOrFail();
        $this->assertSame($product->id, (int) $row->product_id);
        $this->assertSame('12345', (string) $row->external_item_id);
        $this->assertSame('2026-06-19', $row->report_date->toDateString());
    }
}
