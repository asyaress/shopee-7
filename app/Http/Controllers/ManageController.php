<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShopeeToken;
use App\Models\ShopMonthlyCost;
use App\Support\ShopeeShopContext;
use App\Services\Shopee\ShopeeAppContextResolver;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeOrderSyncService;
use App\Services\Shopee\ShopeeProductSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageController extends Controller
{
    public function index(Request $request): View
    {
        $resolver = new ShopeeAppContextResolver();
        $token = $this->currentToken(ShopeeToken::APP_MAIN);
        $shopId = (int) (ShopeeShopContext::shopId() ?: config('shopee.shop_id'));
        $adsConfigured = ShopeeClient::isConfigured(ShopeeToken::APP_ADS);
        $amsConfigured = ShopeeClient::isConfigured(ShopeeToken::APP_AMS);
        $adsToken = $adsConfigured ? $resolver->token(ShopeeToken::APP_ADS, null, $shopId) : null;
        $amsToken = $amsConfigured ? $resolver->token(ShopeeToken::APP_AMS, null, $shopId) : null;

        $yearMonth = $request->query('month', now()->format('Y-m'));

        $operational = ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->where('year_month', $yearMonth)
            ->first();

        $productsQuery = Product::query()
            ->with(['variants' => fn ($q) => $q->select('id', 'product_id', 'name', 'external_model_id', 'hpp_amount', 'packaging_type', 'packaging_value')])
            ->orderBy('name');
        ShopeeShopContext::scopeProducts($productsQuery);
        $products = $productsQuery->get();

        $missingHpp = $products->filter(function ($p) {
            if ($p->variants->isNotEmpty()) {
                return $p->variants->every(fn ($v) => $v->hpp_amount === null);
            }
            return $p->hpp_amount === null;
        })->count();

        $unmappedItems = OrderItem::query()
            ->whereNull('product_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $withHpp = $products->count() - $missingHpp;
        $hppCompletePct = $products->count() > 0 ? round(($withHpp / $products->count()) * 100) : 0;

        return view('hub.manage', [
            'token' => $token,
            'mainToken' => $token,
            'adsToken' => $adsToken,
            'amsToken' => $amsToken,
            'adsConfigured' => $adsConfigured,
            'amsConfigured' => $amsConfigured,
            'env' => config('shopee.env', 'test'),
            'yearMonth' => $yearMonth,
            'operational' => $operational,
            'products' => $products,
            'stats' => [
                'products_total' => $products->count(),
                'missing_hpp' => $missingHpp,
                'with_hpp' => $withHpp,
                'hpp_complete_pct' => $hppCompletePct,
                'unmapped_items' => $unmappedItems,
                'orders_total' => Order::query()->count(),
                'shopee_orders' => Order::query()->whereRaw('LOWER(COALESCE(jenis_transaksi,"")) = ?', ['shopee'])->count(),
            ],
            'meta' => [
                'generated_at' => now()->format('d M Y H:i'),
            ],
        ]);
    }

    public function saveOperational(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year_month' => 'required|date_format:Y-m',
            'operational_amount' => 'required|numeric|min:0',
            'target_net_profit' => 'nullable|numeric|min:0',
            'target_gross' => 'nullable|numeric|min:0',
            'ad_budget_cap' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $shopId = ShopeeShopContext::shopId();

        ShopMonthlyCost::updateOrCreate(
            ['shop_id' => $shopId, 'year_month' => $data['year_month']],
            [
                'operational_amount' => $data['operational_amount'],
                'target_net_profit' => $data['target_net_profit'] ?? null,
                'target_gross' => $data['target_gross'] ?? null,
                'ad_budget_cap' => $data['ad_budget_cap'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()->route('manage.index', ['month' => $data['year_month']])
            ->with('success', 'Biaya operasional bulan ' . $data['year_month'] . ' disimpan.');
    }

    public function saveCosts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.hpp_amount' => 'nullable|numeric|min:0',
            'products.*.packaging_type' => 'nullable|in:fixed,percent',
            'products.*.packaging_value' => 'nullable|numeric|min:0',
        ]);

        foreach ($data['products'] as $row) {
            $product = Product::find($row['id']);
            if (!$product) {
                continue;
            }
            $product->update([
                'hpp_amount' => $row['hpp_amount'] !== '' && $row['hpp_amount'] !== null ? $row['hpp_amount'] : null,
                'packaging_type' => $row['packaging_type'] ?: 'fixed',
                'packaging_value' => $row['packaging_value'] !== '' && $row['packaging_value'] !== null ? $row['packaging_value'] : null,
            ]);
        }

        return redirect()->route('manage.index')
            ->with('success', 'HPP & packaging berhasil diperbarui.');
    }

    public function syncOrders(Request $request): RedirectResponse
    {
        return $this->runSync($request, 'orders');
    }

    public function syncProducts(Request $request): RedirectResponse
    {
        return $this->runSync($request, 'products');
    }

    public function syncAds(Request $request): RedirectResponse
    {
        return $this->runSync($request, 'ads');
    }

    public function syncAll(Request $request): RedirectResponse
    {
        return $this->runSync($request, 'all');
    }

    private function runSync(Request $request, string $type): RedirectResponse
    {
        $mainToken = $this->currentToken(ShopeeToken::APP_MAIN);
        if (!$mainToken) {
            return back()->with('error', 'Belum terhubung ke Shopee. Klik Connect Shopee.');
        }

        $days = max(1, min(90, (int) $request->input('days', config('shopee.sync_days', 7))));
        $adsDays = max(1, min(90, (int) $request->input('ads_days', config('shopee.ads_sync_days', 30))));
        $mainClient = ShopeeClient::fromConfig(ShopeeToken::APP_MAIN);

        $messages = [];

        try {
            if (in_array($type, ['orders', 'all'], true)) {
                $summary = (new ShopeeOrderSyncService($mainClient))->syncRecent($mainToken, $days);
                $messages[] = "Order: {$summary['created']} baru, {$summary['updated']} update";
            }

            if (in_array($type, ['products', 'all'], true)) {
                $ps = (new ShopeeProductSyncService($mainClient))->syncAll($mainToken, (int) $request->input('page_size', 100));
                $messages[] = "Produk: {$ps['created']} baru, {$ps['updated']} update";
            }

            if (in_array($type, ['ads', 'all'], true)) {
                [$service, $syncToken, $appSources] = (new ShopeeAppContextResolver())
                    ->buildAdsSyncService((int) $mainToken->shop_id);
                $summary = $service->sync($syncToken, $adsDays);
                $messages[] = "Ads ({$appSources}): {$summary['saved']} baris tersimpan";
            }

            if (in_array($type, ['all'], true)) {
                $bcg = (new ShopeeBcgSyncService($mainClient))->sync($mainToken);
                $messages[] = "BCG: {$bcg['saved']} SKU (skip import: {$bcg['skipped']})";
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Sync gagal: ' . $e->getMessage());
        }

        return back()->with('success', implode(' · ', $messages));
    }

    private function currentToken(string $appType = ShopeeToken::APP_MAIN): ?ShopeeToken
    {
        $env = config('shopee.env', 'test');
        $shopId = ShopeeShopContext::shopId() ?: config('shopee.shop_id');

        $q = ShopeeToken::query()->where('env', $env)->forApp($appType);
        if ($shopId) {
            $q->where('shop_id', (int) $shopId);
        }

        return $q->orderByDesc('id')->first();
    }

}
