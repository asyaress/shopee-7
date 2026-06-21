<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeProductPerformance;
use App\Services\Recommendations\AdsMetricsService;
use App\Services\Recommendations\RecommendationEngine;
use App\Support\ShopeeLinkHelper;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Analisis end-to-end satu produk: keuangan, iklan/ROAS, BCG, HPP, variant.
 */
class ProductAnalysisService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
        private readonly ProductSkuClassifier $classifier,
        private readonly ProductActionEngine $actionEngine,
        private readonly BcgFunnelService $bcgFunnel,
        private readonly AdsMetricsService $adsMetrics,
    ) {
    }

    public function productPicker(int $shopId, ?string $search = null, int $limit = 80): array
    {
        $q = Product::query()
            ->with(['variants:id,product_id,name,external_model_id,hpp_amount,price'])
            ->select(['id', 'name', 'external_item_id', 'external_sku', 'base_price', 'hpp_amount', 'image_url']);

        if ($shopId > 0) {
            ShopeeShopContext::scopeProducts($q);
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $q->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('external_sku', 'like', $term)
                    ->orWhere('external_item_id', 'like', $term);
            });
        }

        $products = $q->orderBy('name')->limit($limit)->get();

        return $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->external_sku,
            'item_id' => $p->external_item_id,
            'base_price' => (float) ($p->base_price ?? 0),
            'hpp_ok' => $p->hpp_amount !== null || $p->variants->contains(fn ($v) => $v->hpp_amount !== null),
            'variant_count' => $p->variants->count(),
            'image_url' => $p->image_url,
        ])->all();
    }

    public function build(Request $request, Product $product): array
    {
        $shopId = ShopeeShopContext::shopId();
        $report = $this->reportService->build($request);
        $filters = $report['filters'] ?? [];
        $start = Carbon::parse($filters['start'] ?? now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($filters['end'] ?? now())->endOfDay();

        $product->load(['variants']);

        $row = collect($report['products'] ?? [])->firstWhere('product_id', $product->id);
        if (!$row) {
            $row = $this->emptyProductRow($product);
            $row['tier'] = $this->classifier->classify($row);
        }

        $enriched = ['products' => [$row], 'summary' => $report['summary'] ?? []];
        app(RecommendationEngine::class)->enrichReport($enriched, $start, $end);
        $row = $enriched['products'][0];

        $itemId = (int) ($product->external_item_id ?? 0);
        $row['links'] = [
            'product' => $itemId ? ShopeeLinkHelper::productUrl($shopId, $itemId) : null,
            'ads' => $itemId ? ShopeeLinkHelper::adsProductUrl($shopId, $itemId) : null,
        ];

        $adsRaw = $this->adsMetrics->loadByProduct($shopId, $start, $end);
        $ads = $adsRaw[(int) $product->id] ?? $adsRaw['ext:' . $itemId] ?? null;

        $roasDetail = $this->roasForProduct($row, $ads, $report['summary'] ?? []);
        $bcg = $this->bcgForProduct($shopId, $product, $start, $end);
        $variants = $this->variantBreakdown($product, $row, $start, $end);
        $monthly = $this->monthlyTrend($product, $shopId, 6);
        $adsDaily = $this->adsDailyTrend($shopId, $itemId, $start, $end);

        return [
            'report' => $report,
            'product' => $product,
            'sku' => $row,
            'roas' => $roasDetail,
            'bcg' => $bcg,
            'variants' => $variants,
            'monthly' => $monthly,
            'ads_daily' => $adsDaily,
            'simulations' => $this->actionEngine->simulate($row, $row['action']['meta'] ?? []),
            'costing' => $this->costingSummary($product, $row),
            'filters' => $filters,
            'shop' => [
                'id' => $shopId,
                'label' => ShopeeShopContext::shopLabel($shopId),
            ],
        ];
    }

    private function emptyProductRow(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->external_sku ?? '',
            'external_item_id' => $product->external_item_id,
            'qty' => 0,
            'gross' => 0,
            'net' => 0,
            'cogs' => 0,
            'ads_spend' => 0,
            'operational' => 0,
            'gross_profit' => 0,
            'net_profit' => 0,
            'margin' => 0,
            'roas' => null,
            'acos' => null,
            'missing_cost' => $product->hpp_amount === null && $product->variants->every(fn ($v) => $v->hpp_amount === null),
        ];
    }

    private function roasForProduct(array $row, ?array $ads, array $summary): array
    {
        $gross = (float) ($row['gross'] ?? 0);
        $gp = (float) ($row['gross_profit'] ?? 0);
        $spend = (float) ($row['ads_spend'] ?? 0);
        $safety = (float) config('monitoring.roas_advisor.safety_multiplier', 1.25);

        $contribution = $gross > 0 ? $gp / $gross : 0;
        $breakevenBusiness = $contribution > 0 ? 1 / $contribution : null;
        $targetBusiness = $breakevenBusiness ? $breakevenBusiness * $safety : null;
        $businessRoas = $spend > 0 ? $gross / $spend : null;

        $gmv = (float) ($ads['gmv'] ?? 0);
        $shopeeRoas = ($ads['shopee_roas'] ?? null) ?? ($spend > 0 && $gmv > 0 ? $gmv / $spend : null);
        $gmvToGross = $gmv > 0 && $gross > 0 ? $gross / $gmv : null;
        $breakevenShopee = ($breakevenBusiness && $gmvToGross) ? $breakevenBusiness / max(0.01, $gmvToGross) : null;
        $setRoasShopee = $breakevenShopee ? $breakevenShopee / 0.70 : null;

        return [
            'spend' => (int) round($spend),
            'gmv_ams' => (int) round($gmv),
            'orders_ads' => (int) ($ads['orders'] ?? 0),
            'clicks' => (int) ($ads['clicks'] ?? 0),
            'impressions' => (int) ($ads['impressions'] ?? 0),
            'cpc' => ($ads['cpc'] ?? null) !== null ? (int) round($ads['cpc']) : null,
            'ctr' => ($ads['ctr'] ?? null) !== null ? round($ads['ctr'], 2) : null,
            'cpa' => ($ads['cpa'] ?? null) !== null ? (int) round($ads['cpa']) : null,
            'shopee_roas' => $shopeeRoas !== null ? round($shopeeRoas, 2) : null,
            'business_roas' => $businessRoas !== null ? round($businessRoas, 2) : null,
            'breakeven_business' => $breakevenBusiness !== null ? round($breakevenBusiness, 2) : null,
            'target_business' => $targetBusiness !== null ? round($targetBusiness, 2) : null,
            'breakeven_shopee' => $breakevenShopee !== null ? round($breakevenShopee, 2) : null,
            'set_roas_shopee' => $setRoasShopee !== null ? round($setRoasShopee, 2) : null,
            'gap_business' => ($targetBusiness && $businessRoas) ? round($targetBusiness - $businessRoas, 2) : null,
            'acos' => $gross > 0 && $spend > 0 ? round($spend / $gross, 4) : null,
            'shop_business_roas' => ($summary['ads_total'] ?? 0) > 0
                ? round(($summary['gross'] ?? 0) / $summary['ads_total'], 2)
                : null,
        ];
    }

    private function bcgForProduct(int $shopId, Product $product, Carbon $start, Carbon $end): ?array
    {
        $itemId = (string) ($product->external_item_id ?? '');
        if ($itemId === '') {
            return null;
        }

        $row = ShopeeProductPerformance::query()
            ->where('shop_id', $shopId)
            ->where('external_item_id', $itemId)
            ->whereDate('period_start', '<=', $end->toDateString())
            ->whereDate('period_end', '>=', $start->toDateString())
            ->orderByDesc('period_end')
            ->first();

        if (!$row) {
            $row = ShopeeProductPerformance::query()
                ->where('shop_id', $shopId)
                ->where('external_item_id', $itemId)
                ->orderByDesc('period_end')
                ->first();
        }

        if (!$row) {
            return null;
        }

        $funnel = $this->bcgFunnel->build($shopId, $start, $end);
        $convThreshold = (float) config('monitoring.bcg_funnel.conversion_threshold', 0.02);

        $conv = (float) ($row->conversion_rate ?? 0);
        if ($conv <= 0 && $row->visitors > 0) {
            $conv = $row->units_sold / max(1, $row->visitors);
        }

        $traffic = (int) $row->visitors;
        $baseline = (int) ($funnel['settings']['traffic_baseline'] ?? 0);
        $highConv = $conv >= $convThreshold;
        $highTraffic = $traffic >= $baseline;

        $quadrant = match (true) {
            $highConv && $highTraffic => BcgFunnelService::STAR,
            $highConv && !$highTraffic => BcgFunnelService::CASH_COW,
            !$highConv && $highTraffic => BcgFunnelService::QUESTION_MARK,
            default => BcgFunnelService::DOG,
        };

        return [
            'quadrant' => $quadrant,
            'quadrant_label' => match ($quadrant) {
                BcgFunnelService::STAR => 'STAR',
                BcgFunnelService::CASH_COW => 'Cash Cow',
                BcgFunnelService::QUESTION_MARK => 'Question Mark',
                default => 'Dog',
            },
            'visitors' => $traffic,
            'page_views' => (int) $row->page_views,
            'units_sold' => (int) $row->units_sold,
            'sales_gmv' => (int) round((float) $row->sales_gmv),
            'conversion_rate_pct' => round($conv * 100, 2),
            'traffic_baseline' => $baseline,
            'source' => $row->source,
            'period' => $row->period_start?->format('d M Y') . ' — ' . $row->period_end?->format('d M Y'),
        ];
    }

    private function variantBreakdown(Product $product, array $productRow, Carbon $start, Carbon $end): array
    {
        $variantByModel = $product->variants->keyBy(fn (ProductVariant $v) => (string) $v->external_model_id);
        $totalGross = 0.0;
        $groups = [];

        $lines = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $product->id)
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->whereRaw('LOWER(COALESCE(orders.jenis_transaksi, "")) = ?', ['shopee'])
            ->select([
                'order_items.external_model_id',
                'order_items.product_name',
                'order_items.quantity',
                'order_items.price',
                'order_items.total_amount',
            ])
            ->get();

        foreach ($lines as $line) {
            $modelId = (string) ($line->external_model_id ?? '');
            $key = $modelId !== '' ? $modelId : '_default';
            if (!isset($groups[$key])) {
                $variant = $modelId !== '' ? $variantByModel->get($modelId) : null;
                $groups[$key] = [
                    'variant_id' => $variant?->id,
                    'model_id' => $modelId !== '' ? $modelId : null,
                    'name' => $variant?->name ?? ($modelId !== '' ? 'Varian ' . $modelId : 'Tanpa varian'),
                    'hpp' => $variant?->hpp_amount ?? $product->hpp_amount,
                    'price_catalog' => (float) ($variant?->price ?? $product->base_price ?? 0),
                    'qty' => 0,
                    'gross' => 0.0,
                    'cogs' => 0.0,
                    'orders' => 0,
                ];
            }

            $qty = (int) ($line->quantity ?? 0);
            $gross = (float) ($line->total_amount ?? 0);
            $unitPrice = (float) ($line->price ?? 0);
            $variant = $modelId !== '' ? $variantByModel->get($modelId) : null;

            $hpp = $variant?->hpp_amount ?? $product->hpp_amount;
            $packType = $variant?->packaging_type ?? $product->packaging_type ?? 'fixed';
            $packVal = $variant?->packaging_value ?? $product->packaging_value;
            $unitHpp = is_null($hpp) ? 0.0 : (float) $hpp;
            $unitPack = 0.0;
            if ($packVal !== null) {
                $unitPack = $packType === 'percent'
                    ? round($unitPrice * ((float) $packVal / 100), 2)
                    : (float) $packVal;
            }

            $groups[$key]['qty'] += $qty;
            $groups[$key]['gross'] += $gross;
            $groups[$key]['cogs'] += ($unitHpp + $unitPack) * $qty;
            $groups[$key]['orders']++;
            $totalGross += $gross;
        }

        $productAds = (float) ($productRow['ads_spend'] ?? 0);
        $productOpr = (float) ($productRow['operational'] ?? 0);
        $productNet = (float) ($productRow['net'] ?? 0);

        $out = [];
        foreach ($groups as $g) {
            $share = $totalGross > 0 ? $g['gross'] / $totalGross : 0;
            $netAlloc = $productNet * $share;
            $adsAlloc = $productAds * $share;
            $oprAlloc = $productOpr * $share;
            $gp = $netAlloc - $g['cogs'];
            $np = $gp - $adsAlloc - $oprAlloc;

            $out[] = [
                'variant_id' => $g['variant_id'],
                'model_id' => $g['model_id'],
                'name' => $g['name'],
                'hpp' => $g['hpp'],
                'hpp_missing' => $g['hpp'] === null,
                'price_catalog' => (int) round($g['price_catalog']),
                'qty' => (int) $g['qty'],
                'gross' => (int) round($g['gross']),
                'net' => (int) round($netAlloc),
                'cogs' => (int) round($g['cogs']),
                'ads' => (int) round($adsAlloc),
                'operational' => (int) round($oprAlloc),
                'gross_profit' => (int) round($gp),
                'net_profit' => (int) round($np),
                'margin' => $netAlloc > 0 ? $np / $netAlloc : null,
                'avg_price' => $g['qty'] > 0 ? (int) round($g['gross'] / $g['qty']) : null,
                'share_pct' => $share,
            ];
        }

        usort($out, fn ($a, $b) => ($b['gross'] ?? 0) <=> ($a['gross'] ?? 0));

        foreach ($product->variants as $variant) {
            $exists = collect($out)->contains(fn ($r) => (int) ($r['variant_id'] ?? 0) === (int) $variant->id);
            if (!$exists) {
                $out[] = [
                    'variant_id' => $variant->id,
                    'model_id' => $variant->external_model_id,
                    'name' => $variant->name,
                    'hpp' => $variant->hpp_amount ?? $product->hpp_amount,
                    'hpp_missing' => ($variant->hpp_amount ?? $product->hpp_amount) === null,
                    'price_catalog' => (int) round((float) ($variant->price ?? 0)),
                    'qty' => 0,
                    'gross' => 0,
                    'net' => 0,
                    'cogs' => 0,
                    'ads' => 0,
                    'operational' => 0,
                    'gross_profit' => 0,
                    'net_profit' => 0,
                    'margin' => null,
                    'avg_price' => null,
                    'share_pct' => 0,
                    'no_sales' => true,
                ];
            }
        }

        return $out;
    }

    private function monthlyTrend(Product $product, int $shopId, int $months): array
    {
        $out = [];
        $cursor = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $mk = $cursor->format('Y-m');
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd = $cursor->copy()->endOfMonth();

            $agg = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.product_id', $product->id)
                ->whereBetween('orders.order_date', [$mStart->toDateString(), min($mEnd, now())->toDateString()])
                ->whereRaw('LOWER(COALESCE(orders.jenis_transaksi, "")) = ?', ['shopee'])
                ->selectRaw('SUM(order_items.quantity) as qty, SUM(order_items.total_amount) as gross')
                ->first();

            $itemId = (string) ($product->external_item_id ?? '');
            $ads = 0.0;
            if ($itemId !== '' && $shopId > 0) {
                $ads = (float) ShopeeProductAdsDaily::query()
                    ->where('shop_id', $shopId)
                    ->where('external_item_id', $itemId)
                    ->whereBetween('report_date', [$mStart->toDateString(), min($mEnd, now())->toDateString()])
                    ->sum('spend');
            }

            $gross = (float) ($agg->gross ?? 0);
            $out[] = [
                'month' => $mk,
                'label' => $mStart->translatedFormat('M Y'),
                'qty' => (int) ($agg->qty ?? 0),
                'gross' => (int) round($gross),
                'ads' => (int) round($ads),
                'roas' => $ads > 0 ? round($gross / $ads, 2) : null,
            ];

            $cursor->addMonth();
        }

        return $out;
    }

    private function adsDailyTrend(int $shopId, int $itemId, Carbon $start, Carbon $end): array
    {
        if ($shopId <= 0 || $itemId <= 0) {
            return [];
        }

        return ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->where('external_item_id', (string) $itemId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('report_date')
            ->get(['report_date', 'spend', 'gmv', 'clicks', 'impressions', 'orders', 'roas'])
            ->map(fn ($r) => [
                'date' => $r->report_date?->format('Y-m-d'),
                'spend' => (int) round((float) $r->spend),
                'gmv' => (int) round((float) $r->gmv),
                'clicks' => (int) $r->clicks,
                'orders' => (int) $r->orders,
                'roas' => $r->roas !== null ? round((float) $r->roas, 2) : null,
            ])
            ->all();
    }

    private function costingSummary(Product $product, array $row): array
    {
        $qty = max(1, (int) ($row['qty'] ?? 0));

        return [
            'product_hpp' => $product->hpp_amount,
            'packaging_type' => $product->packaging_type ?? 'fixed',
            'packaging_value' => $product->packaging_value,
            'base_price' => (float) ($product->base_price ?? 0),
            'per_unit' => [
                'cogs' => (int) round(((float) ($row['cogs'] ?? 0)) / $qty),
                'ads' => (int) round(((float) ($row['ads_spend'] ?? 0)) / $qty),
                'operational' => (int) round(((float) ($row['operational'] ?? 0)) / $qty),
                'net_profit' => (int) round(((float) ($row['net_profit'] ?? 0)) / $qty),
            ],
        ];
    }
}
