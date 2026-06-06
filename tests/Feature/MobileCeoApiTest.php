<?php

namespace Tests\Feature;

use App\Models\CeoAlertLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShopeeOrderFinancial;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeToken;
use App\Models\ShopMonthlyCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileCeoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mobile.ceo.allow_all_users' => true,
            'mobile.ceo.allowed_emails' => [],
            'shopee.env' => 'test',
        ]);
    }

    public function test_mobile_ceo_login_returns_token_for_allowed_user(): void
    {
        $user = User::factory()->create([
            'email' => 'ceo@example.com',
        ]);

        $response = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'ceo@example.com',
            'password' => 'password',
            'device_name' => 'CEO Android',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'ceo@example.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_mobile_ceo_login_rejects_user_outside_allowlist(): void
    {
        config([
            'mobile.ceo.allow_all_users' => false,
            'mobile.ceo.allowed_emails' => ['allowed@example.com'],
        ]);

        User::factory()->create([
            'email' => 'blocked@example.com',
        ]);

        $response = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
            'device_name' => 'CEO Android',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Akses mobile CEO belum diizinkan untuk akun ini.');
    }

    public function test_mobile_ceo_can_switch_shop_and_fetch_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'ceo@example.com',
        ]);

        $this->seedDashboardData(shopId: 2001, gross: 100000, net: 90000, hpp: 40000, ads: 10000, operational: 5000);
        $this->seedDashboardData(shopId: 2002, gross: 150000, net: 120000, hpp: 50000, ads: 15000, operational: 7000);

        Sanctum::actingAs($user, ['mobile:ceo']);

        $switchResponse = $this->postJson('/api/v1/mobile/ceo/shops/active', [
            'shop_id' => 2002,
        ]);

        $switchResponse->assertOk()
            ->assertJsonPath('data.active_shop_id', 2002);

        $shopsResponse = $this->getJson('/api/v1/mobile/ceo/shops');
        $shopsResponse->assertOk()
            ->assertJsonPath('data.active_shop_id', 2002)
            ->assertJsonCount(2, 'data.shops');

        $dashboardResponse = $this->getJson('/api/v1/mobile/ceo/dashboard?month=' . now()->format('Y-m'));

        $dashboardResponse->assertOk()
            ->assertJsonPath('data.shop.shop_id', 2002)
            ->assertJsonPath('data.summary.gross', 150000)
            ->assertJsonPath('data.targets.gross', 300000)
            ->assertJsonPath('meta.active_shop_id', 2002);

        $this->assertGreaterThan(0, (int) $dashboardResponse->json('data.summary.net_profit'));
        $this->assertNotEmpty($dashboardResponse->json('data.top_profit_products'));
        $this->assertNotEmpty($dashboardResponse->json('data.alerts'));
    }

    public function test_mobile_ceo_can_read_and_save_monthly_targets(): void
    {
        $user = User::factory()->create([
            'email' => 'ceo@example.com',
        ]);

        $this->seedDashboardData(shopId: 2001, gross: 100000, net: 90000, hpp: 40000, ads: 10000, operational: 5000);

        Sanctum::actingAs($user, ['mobile:ceo']);

        $getResponse = $this->getJson('/api/v1/mobile/ceo/targets?month=' . now()->format('Y-m'));

        $getResponse->assertOk()
            ->assertJsonPath('data.year_month', now()->format('Y-m'))
            ->assertJsonPath('data.form.target_gross', 200000)
            ->assertJsonPath('data.form.operational_amount', 5000);

        $saveResponse = $this->postJson('/api/v1/mobile/ceo/targets', [
            'year_month' => now()->format('Y-m'),
            'operational_amount' => 9000,
            'target_net_profit' => 180000,
            'target_gross' => 350000,
            'target_units' => 7,
            'ad_budget_cap' => 60000,
            'notes' => 'Push gross dan jaga cash burn.',
        ]);

        $saveResponse->assertOk()
            ->assertJsonPath('data.message', 'Target bulanan berhasil disimpan.')
            ->assertJsonPath('data.form.target_units', 7)
            ->assertJsonPath('data.form.notes', 'Push gross dan jaga cash burn.');

        $this->assertDatabaseHas('shop_monthly_costs', [
            'shop_id' => 2001,
            'year_month' => now()->format('Y-m'),
            'target_gross' => 350000,
            'target_units' => 7,
            'notes' => 'Push gross dan jaga cash burn.',
        ]);
    }

    public function test_mobile_ceo_can_fetch_and_save_quick_hpp(): void
    {
        $user = User::factory()->create([
            'email' => 'ceo@example.com',
        ]);

        $this->seedDashboardData(shopId: 2001, gross: 100000, net: 90000, hpp: 40000, ads: 10000, operational: 5000);
        $missingProduct = Product::query()->create([
            'name' => 'Produk Missing HPP',
            'external_platform' => 'shopee',
            'external_shop_id' => 2001,
            'external_item_id' => '200102',
            'external_sku' => 'SKU-MISS',
            'base_price' => 120000,
            'hpp_amount' => null,
            'is_active' => true,
        ]);
        Product::query()->create([
            'name' => 'Produk Shop Lain',
            'external_platform' => 'shopee',
            'external_shop_id' => 2002,
            'external_item_id' => '200201',
            'external_sku' => 'SKU-OTHER',
            'base_price' => 130000,
            'hpp_amount' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user, ['mobile:ceo']);

        $listResponse = $this->getJson('/api/v1/mobile/ceo/hpp/priority');

        $listResponse->assertOk()
            ->assertJsonPath('data.shop.shop_id', 2001)
            ->assertJsonPath('data.summary.total', 2);

        $products = collect($listResponse->json('data.products'));
        $this->assertTrue($products->contains(fn (array $item) => $item['id'] === $missingProduct->id && $item['missing_hpp'] === true));
        $this->assertFalse($products->contains(fn (array $item) => $item['sku'] === 'SKU-OTHER'));

        $saveResponse = $this->postJson('/api/v1/mobile/ceo/hpp/bulk', [
            'products' => [
                [
                    'id' => $missingProduct->id,
                    'hpp_amount' => 55000,
                    'packaging_type' => 'fixed',
                    'packaging_value' => 2500,
                ],
            ],
        ]);

        $saveResponse->assertOk()
            ->assertJsonPath('data.message', 'Quick HPP berhasil disimpan.')
            ->assertJsonPath('data.updated_count', 1);

        $this->assertDatabaseHas('products', [
            'id' => $missingProduct->id,
            'hpp_amount' => 55000,
            'packaging_type' => 'fixed',
            'packaging_value' => 2500,
        ]);
    }

    public function test_mobile_ceo_can_fetch_read_alerts_and_log_decisions(): void
    {
        $user = User::factory()->create([
            'email' => 'ceo@example.com',
        ]);

        $this->seedDashboardData(shopId: 2001, gross: 100000, net: 90000, hpp: 40000, ads: 10000, operational: 5000);

        $alert = CeoAlertLog::query()->create([
            'shop_id' => 2001,
            'alert_key' => 'manual_alert_' . now()->format('YmdHis'),
            'severity' => 'danger',
            'title' => 'ROAS di bawah target',
            'message' => 'Periksa kampanye dengan spend tinggi.',
            'sent_at' => now(),
        ]);

        Sanctum::actingAs($user, ['mobile:ceo']);

        $alertsResponse = $this->getJson('/api/v1/mobile/ceo/alerts?month=' . now()->format('Y-m'));

        $alertsResponse->assertOk()
            ->assertJsonPath('data.shop.shop_id', 2001);

        $alertItem = collect($alertsResponse->json('data.alerts'))->firstWhere('id', $alert->id);
        $this->assertNotNull($alertItem);
        $this->assertFalse((bool) $alertItem['is_read']);

        $readResponse = $this->postJson('/api/v1/mobile/ceo/alerts/read', [
            'alert_ids' => [$alert->id],
        ]);

        $readResponse->assertOk()
            ->assertJsonPath('data.updated_count', 1);

        $alertsAfterRead = $this->getJson('/api/v1/mobile/ceo/alerts?month=' . now()->format('Y-m'));
        $readItem = collect($alertsAfterRead->json('data.alerts'))->firstWhere('id', $alert->id);
        $this->assertTrue((bool) $readItem['is_read']);

        $decisionResponse = $this->postJson('/api/v1/mobile/ceo/decisions', [
            'decision_type' => 'ads',
            'title' => 'Turunkan spend kampanye bleeder',
            'note' => 'Fokuskan budget ke SKU profit.',
        ]);

        $decisionResponse->assertOk()
            ->assertJsonPath('data.message', 'Keputusan berhasil dicatat.')
            ->assertJsonPath('data.decision.decision_type', 'ads');

        $listDecisionResponse = $this->getJson('/api/v1/mobile/ceo/decisions');

        $listDecisionResponse->assertOk()
            ->assertJsonPath('data.decisions.0.title', 'Turunkan spend kampanye bleeder');

        $this->assertDatabaseHas('business_decision_logs', [
            'shop_id' => 2001,
            'decision_type' => 'ads',
            'title' => 'Turunkan spend kampanye bleeder',
        ]);
    }

    public function test_mobile_ceo_can_register_device(): void
    {
        $user = User::factory()->create([
            'email' => 'ceo@example.com',
        ]);

        Sanctum::actingAs($user, ['mobile:ceo']);

        $response = $this->postJson('/api/v1/mobile/devices/register', [
            'platform' => 'android',
            'device_name' => 'CEO Android Pixel',
            'push_token' => 'token-demo',
            'push_enabled' => true,
            'app_version' => '0.1.0',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.device.platform', 'android')
            ->assertJsonPath('data.device.push_enabled', true);

        $this->assertDatabaseHas('mobile_push_devices', [
            'user_id' => $user->id,
            'platform' => 'android',
            'device_name' => 'CEO Android Pixel',
            'push_enabled' => 1,
            'app_version' => '0.1.0',
        ]);
    }

    private function seedDashboardData(
        int $shopId,
        int $gross,
        int $net,
        int $hpp,
        int $ads,
        int $operational,
    ): void {
        ShopeeToken::query()->create([
            'env' => 'test',
            'app_type' => ShopeeToken::APP_MAIN,
            'partner_id' => 1,
            'shop_id' => $shopId,
            'access_token' => 'token-' . $shopId,
            'refresh_token' => 'refresh-' . $shopId,
        ]);

        $product = Product::query()->create([
            'name' => 'Produk ' . $shopId,
            'external_platform' => 'shopee',
            'external_shop_id' => $shopId,
            'external_item_id' => $shopId . '01',
            'external_sku' => 'SKU-' . $shopId,
            'base_price' => $gross,
            'hpp_amount' => $hpp,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'order_number' => 'ORD-' . $shopId,
            'customer_name' => 'Customer ' . $shopId,
            'order_date' => now()->startOfMonth()->addDay(),
            'completion_date' => now()->startOfMonth()->addDay(),
            'jenis_transaksi' => 'shopee',
            'status' => 'completed',
            'total_amount' => $net,
            'price' => $gross,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'external_platform' => 'shopee',
            'external_item_id' => $product->external_item_id,
            'external_sku' => $product->external_sku,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $gross,
            'total_amount' => $gross,
        ]);

        ShopeeOrderFinancial::query()->create([
            'order_id' => $order->id,
            'order_sn' => 'SN-' . $shopId,
            'shop_id' => $shopId,
            'seller_income' => $net,
            'raw' => [
                'order_income' => [
                    'order_selling_price' => $gross,
                    'commission_fee' => $gross - $net,
                    'escrow_amount_after_adjustment' => $net,
                ],
            ],
        ]);

        ShopeeProductAdsDaily::query()->create([
            'shop_id' => $shopId,
            'product_id' => $product->id,
            'external_item_id' => (string) $product->external_item_id,
            'report_date' => now()->toDateString(),
            'spend' => $ads,
            'impressions' => 1000,
            'clicks' => 50,
            'gmv' => $gross,
            'orders' => 1,
            'roas' => $ads > 0 ? $gross / $ads : null,
        ]);

        ShopMonthlyCost::query()->create([
            'shop_id' => $shopId,
            'year_month' => now()->format('Y-m'),
            'operational_amount' => $operational,
            'target_net_profit' => $operational * 10,
            'target_gross' => $gross * 2,
            'target_units' => 2,
            'ad_budget_cap' => $ads * 4,
        ]);
    }
}
