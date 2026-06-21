<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HppManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hpp_page_shows_products_and_their_variants(): void
    {
        $product = Product::query()->create([
            'name' => 'Mug Custom',
            'external_platform' => 'shopee',
            'external_item_id' => 12345,
            'base_price' => 25000,
            'hpp_amount' => 12000,
            'packaging_type' => 'fixed',
            'packaging_value' => 1500,
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Merah Besar',
            'sku' => 'MUG-RED-L',
            'price' => 28000,
            'hpp_amount' => 16000,
            'packaging_type' => 'fixed',
            'packaging_value' => 2000,
        ]);

        $this->withSession(['simple_auth' => true])
            ->get('/hpp')
            ->assertOk()
            ->assertSee('Pusat HPP &amp; Varian', false)
            ->assertSee('Mug Custom')
            ->assertSee('Merah Besar')
            ->assertSee('Override hanya jika biaya berbeda')
            ->assertSee('value="12.000"', false)
            ->assertSee('value="1.500"', false)
            ->assertSee('value="16.000"', false)
            ->assertSee('value="2.000"', false)
            ->assertSee('Autosave aktif')
            ->assertSee('data-save-state', false)
            ->assertDontSee('Simpan Perubahan');
    }

    public function test_bulk_json_payload_updates_product_and_variant_costs(): void
    {
        $product = Product::query()->create([
            'name' => 'Mug Custom',
            'base_price' => 25000,
            'is_active' => true,
        ]);
        $overrideVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Premium',
            'price' => 30000,
        ]);
        $inheritVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Reguler',
            'price' => 25000,
            'hpp_amount' => 9999,
            'packaging_type' => 'fixed',
            'packaging_value' => 999,
        ]);

        $payload = [[
            'id' => $product->id,
            'hpp_amount' => 12000,
            'packaging_type' => 'fixed',
            'packaging_value' => 1500,
            'variants' => [
                [
                    'id' => $overrideVariant->id,
                    'hpp_amount' => 16000,
                    'packaging_type' => 'percent',
                    'packaging_value' => 2.5,
                ],
                [
                    'id' => $inheritVariant->id,
                    'hpp_amount' => null,
                    'packaging_type' => null,
                    'packaging_value' => null,
                ],
            ],
        ]];

        $this->withSession(['simple_auth' => true])
            ->post('/hpp', ['payload' => json_encode($payload, JSON_THROW_ON_ERROR)])
            ->assertRedirect('/hpp')
            ->assertSessionHas('success');

        $product->refresh();
        $overrideVariant->refresh();
        $inheritVariant->refresh();

        $this->assertSame('12000.00', (string) $product->hpp_amount);
        $this->assertSame('fixed', $product->packaging_type);
        $this->assertSame('1500.00', (string) $product->packaging_value);
        $this->assertSame(16000.0, (float) $overrideVariant->hpp_amount);
        $this->assertSame('percent', $overrideVariant->packaging_type);
        $this->assertSame(2.5, (float) $overrideVariant->packaging_value);
        $this->assertNull($inheritVariant->hpp_amount);
        $this->assertNull($inheritVariant->packaging_type);
        $this->assertNull($inheritVariant->packaging_value);
    }

    public function test_autosave_json_request_updates_one_product_and_returns_status(): void
    {
        $product = Product::query()->create([
            'name' => 'Spanduk Custom',
            'base_price' => 50000,
            'is_active' => true,
        ]);

        $this->withSession(['simple_auth' => true])
            ->postJson('/hpp', [
                'products' => [[
                    'id' => $product->id,
                    'hpp_amount' => 27500,
                    'packaging_type' => 'fixed',
                    'packaging_value' => 1200,
                    'variants' => [],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('saved_products', 1)
            ->assertJsonPath('saved_variants', 0);

        $product->refresh();
        $this->assertSame('27500.00', (string) $product->hpp_amount);
        $this->assertSame('1200.00', (string) $product->packaging_value);
    }

    public function test_hpp_product_status_filter_defaults_to_active_and_supports_all_statuses(): void
    {
        Product::query()->create([
            'name' => 'Produk Aktif',
            'external_status' => 'NORMAL',
            'is_active' => true,
        ]);
        Product::query()->create([
            'name' => 'Produk Archive',
            'external_status' => 'UNLIST',
            'is_active' => false,
        ]);
        Product::query()->create([
            'name' => 'Produk Nonaktif',
            'external_status' => 'BANNED',
            'is_active' => false,
        ]);

        $this->withSession(['simple_auth' => true])
            ->get('/hpp')
            ->assertOk()
            ->assertSee('Produk Aktif')
            ->assertDontSee('Produk Archive')
            ->assertDontSee('Produk Nonaktif')
            ->assertSee('value="active" selected', false);

        $this->withSession(['simple_auth' => true])
            ->get('/hpp?product_status=archive')
            ->assertOk()
            ->assertSee('Produk Archive')
            ->assertDontSee('Produk Aktif')
            ->assertDontSee('Produk Nonaktif');

        $this->withSession(['simple_auth' => true])
            ->get('/hpp?product_status=inactive')
            ->assertOk()
            ->assertSee('Produk Nonaktif')
            ->assertDontSee('Produk Aktif')
            ->assertDontSee('Produk Archive');

        $this->withSession(['simple_auth' => true])
            ->get('/hpp?product_status=all')
            ->assertOk()
            ->assertSee('Produk Aktif')
            ->assertSee('Produk Archive')
            ->assertSee('Produk Nonaktif');
    }
}
