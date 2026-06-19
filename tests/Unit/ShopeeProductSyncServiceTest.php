<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ShopeeProductSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_minimum_price_is_saved_as_product_base_price(): void
    {
        config()->set('shopee.product_item_statuses', ['NORMAL']);

        $token = new ShopeeToken([
            'env' => 'prod',
            'shop_id' => 123,
            'access_token' => 'token',
        ]);

        $client = Mockery::mock(ShopeeClient::class);
        $client->shouldReceive('requestPrivate')->once()
            ->with('GET', '/api/v2/product/get_item_list', Mockery::type('array'), $token)
            ->andReturn([
                'item' => [['item_id' => 456]],
                'has_next_page' => false,
            ]);
        $client->shouldReceive('requestPrivate')->once()
            ->with('GET', '/api/v2/product/get_item_base_info', Mockery::type('array'), $token)
            ->andReturn([
                'item_list' => [[
                    'item_id' => 456,
                    'item_name' => 'Produk Variasi',
                    'item_status' => 'NORMAL',
                ]],
            ]);
        $client->shouldReceive('requestPrivate')->once()
            ->with('GET', '/api/v2/product/get_model_list', ['item_id' => 456], $token)
            ->andReturn([
                'model' => [
                    [
                        'model_id' => 1,
                        'model_name' => 'A',
                        'price_info' => [[
                            'current_price' => 60000,
                            'original_price' => 60000,
                        ]],
                    ],
                    [
                        'model_id' => 2,
                        'model_name' => 'B',
                        'price_info' => [[
                            'current_price' => 45000,
                            'original_price' => 45000,
                        ]],
                    ],
                ],
            ]);

        (new ShopeeProductSyncService($client))->syncAll($token);

        $product = Product::where('external_item_id', 456)->firstOrFail();
        $this->assertSame('45000.00', $product->base_price);
        $this->assertSame(['45000.00', '60000.00'], $product->variants()->orderBy('price')->pluck('price')->all());
    }
}
