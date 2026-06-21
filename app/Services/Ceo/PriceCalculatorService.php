<?php

namespace App\Services\Ceo;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopMonthlyCost;
use App\Models\ShopeeProductAdsDaily;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kalkulator harga — mirror Excel ROAS / ROAS HLP dengan data aktual toko.
 */
class PriceCalculatorService
{
    public function productOptions(?int $shopId = null): array
    {
        $shopId = $shopId ?? ShopeeShopContext::shopId();
        $q = Product::query()->select(['id', 'name', 'external_sku', 'base_price', 'hpp_amount']);

        if ($shopId > 0) {
            ShopeeShopContext::scopeProducts($q);
        }

        return $q->orderBy('name')->limit(500)->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->external_sku,
            'base_price' => (float) ($p->base_price ?? 0),
            'hpp' => $p->hpp_amount !== null ? (float) $p->hpp_amount : null,
        ])->all();
    }

    public function defaults(Request $request, ?Product $product = null): array
    {
        $shopId = ShopeeShopContext::shopId();
        $ym = now()->format('Y-m');
        $monthly = ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->where('year_month', $ym)
            ->first();

        $start = now()->startOfMonth();
        $end = now();

        $takeRate = $this->shopTakeRate($shopId, $start, $end);
        $operationalMonthly = (float) ($monthly?->operational_amount ?? 0);
        $targetGrossMonth = (float) ($monthly?->target_gross ?? 0);

        $hpp = 0.0;
        $packaging = 0.0;
        $sellPrice = 0.0;
        $adsPerUnit = 0.0;
        $unitsMonth = 0;

        if ($product) {
            $product->load('variants');
            $hpp = (float) ($product->hpp_amount ?? $product->variants->first()?->hpp_amount ?? 0);
            $sellPrice = (float) ($product->base_price ?? 0);

            $packType = $product->packaging_type ?? 'fixed';
            $packVal = (float) ($product->packaging_value ?? 0);
            $packaging = $packType === 'percent' && $sellPrice > 0
                ? $sellPrice * ($packVal / 100)
                : $packVal;

            $stats = $this->productStats($product->id, $start, $end);
            $unitsMonth = max(1, (int) ($stats['units'] ?? 0));
            if ($stats['avg_price'] > 0) {
                $sellPrice = $stats['avg_price'];
            }
            $adsPerUnit = $stats['ads'] / max(1, $stats['units']);
        }

        $operationalPct = $targetGrossMonth > 0
            ? min(0.50, $operationalMonthly / $targetGrossMonth)
            : (float) config('monitoring.price_calculator.default_operational_pct', 0.08);

        return [
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'hpp' => round($hpp),
            'packaging' => round($packaging),
            'sell_price' => round($sellPrice),
            'admin_pct' => round($takeRate * 100, 2),
            'operational_pct' => round($operationalPct * 100, 2),
            'operational_monthly' => (int) round($operationalMonthly),
            'target_net_margin_pct' => (float) config('monitoring.price_calculator.default_target_net_pct', 15),
            'target_gross_monthly' => (int) round($targetGrossMonth ?: 5_000_000),
            'ads_per_unit' => (int) round($adsPerUnit),
            'units_month_estimate' => $unitsMonth,
            'shopee_roas_discount' => 70,
        ];
    }

    public function calculate(array $input): array
    {
        $hpp = max(0, (float) ($input['hpp'] ?? 0));
        $packaging = max(0, (float) ($input['packaging'] ?? 0));
        $sellPrice = max(0.01, (float) ($input['sell_price'] ?? 0));
        $adminPct = max(0, min(50, (float) ($input['admin_pct'] ?? 18))) / 100;
        $operationalPct = max(0, min(50, (float) ($input['operational_pct'] ?? 8))) / 100;
        $targetNetPct = max(0, min(40, (float) ($input['target_net_margin_pct'] ?? 15))) / 100;
        $adsPerUnit = max(0, (float) ($input['ads_per_unit'] ?? 0));
        $targetGrossMonth = max(0, (float) ($input['target_gross_monthly'] ?? 0));
        $operationalMonthly = max(0, (float) ($input['operational_monthly'] ?? 0));
        $roasDiscount = max(50, min(90, (float) ($input['shopee_roas_discount'] ?? 70))) / 100;

        $cogsUnit = $hpp + $packaging;
        $marginProfit = ($sellPrice - $cogsUnit) / $sellPrice;
        $netAfterAdmin = $marginProfit - $adminPct;
        $netAfterOps = $netAfterAdmin - $operationalPct;
        $targetAcos = max(0, $netAfterOps - $targetNetPct);
        $targetRoas = $targetAcos > 0 ? 1 / $targetAcos : null;
        $setRoasShopee = $targetRoas ? $targetRoas / $roasDiscount : null;

        $loadedCostUnit = $cogsUnit + $adsPerUnit;
        $netRatioEst = max(0.05, 1 - $adminPct);
        $breakevenGross = $loadedCostUnit / $netRatioEst;
        $netProfitUnit = ($sellPrice * $netRatioEst) - $loadedCostUnit - ($sellPrice * $operationalPct);

        $estAdsMonthly = $targetRoas && $targetGrossMonth > 0 ? $targetGrossMonth / $targetRoas : 0;
        $estProfitMonthly = $targetGrossMonth * $targetNetPct;
        $estAdsDaily = $estAdsMonthly / 30;

        return [
            'inputs' => $input,
            'results' => [
                'margin_profit_pct' => round($marginProfit * 100, 2),
                'cogs_unit' => (int) round($cogsUnit),
                'net_after_admin_pct' => round($netAfterAdmin * 100, 2),
                'net_after_ops_pct' => round($netAfterOps * 100, 2),
                'target_acos_pct' => round($targetAcos * 100, 2),
                'target_roas' => $targetRoas ? round($targetRoas, 2) : null,
                'set_roas_shopee' => $setRoasShopee ? round($setRoasShopee, 2) : null,
                'breakeven_gross_unit' => (int) round($breakevenGross),
                'net_profit_unit' => (int) round($netProfitUnit),
                'est_profit_monthly' => (int) round($estProfitMonthly),
                'est_ads_monthly' => (int) round($estAdsMonthly),
                'est_ads_daily' => (int) round($estAdsDaily),
            ],
            'margin_category' => $this->marginCategory($marginProfit),
        ];
    }

    private function marginCategory(float $margin): array
    {
        $pct = $margin * 100;

        return match (true) {
            $pct < 30 => ['label' => 'Bahaya', 'class' => 'danger', 'npm' => 'Rugi / tipis'],
            $pct < 40 => ['label' => 'Kecil', 'class' => 'warning', 'npm' => '~5%'],
            $pct < 50 => ['label' => 'Normal', 'class' => 'info', 'npm' => '~10%'],
            default => ['label' => 'Besar', 'class' => 'success', 'npm' => '15%+'],
        };
    }

    private function shopTakeRate(int $shopId, Carbon $start, Carbon $end): float
    {
        if ($shopId <= 0) {
            return (float) config('monitoring.price_calculator.default_admin_pct', 0.18);
        }

        try {
            $req = Request::create('/', 'GET', [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]);
            $report = app(\App\Services\Reports\ProductProfitReportService::class)->build($req);
            $tr = (float) ($report['summary']['take_rate'] ?? 0);

            return $tr > 0 ? $tr : (float) config('monitoring.price_calculator.default_admin_pct', 0.18);
        } catch (\Throwable) {
            return (float) config('monitoring.price_calculator.default_admin_pct', 0.18);
        }
    }

    private function productStats(int $productId, Carbon $start, Carbon $end): array
    {
        $row = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->whereRaw('LOWER(COALESCE(orders.jenis_transaksi, "")) = ?', ['shopee'])
            ->selectRaw('SUM(order_items.quantity) as units, SUM(order_items.total_amount) as gross')
            ->first();

        $product = Product::find($productId);
        $ads = 0.0;
        if ($product?->external_item_id) {
            $ads = (float) ShopeeProductAdsDaily::query()
                ->where('shop_id', ShopeeShopContext::shopId())
                ->where('external_item_id', (string) $product->external_item_id)
                ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
                ->sum('spend');
        }

        $units = (int) ($row->units ?? 0);
        $gross = (float) ($row->gross ?? 0);

        return [
            'units' => $units,
            'gross' => $gross,
            'avg_price' => $units > 0 ? $gross / $units : 0,
            'ads' => $ads,
        ];
    }
}
