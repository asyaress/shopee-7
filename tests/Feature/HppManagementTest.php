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
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Merah Besar',
            'sku' => 'MUG-RED-L',
            'price' => 28000,
        ]);

        $this->withSession(['simple_auth' => true])
            ->get('/hpp')
            ->assertOk()
            ->assertSee('Pusat HPP &amp; Varian', false)
            ->assertSee('Mug Custom')
            ->assertSee('Merah Besar')
            ->assertSee('Override hanya jika biaya berbeda');
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
}
