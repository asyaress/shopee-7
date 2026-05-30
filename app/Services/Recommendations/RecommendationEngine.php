<?php

namespace App\Services\Recommendations;

use App\Models\Product;
use App\Services\Reports\ProductActionEngine;
use App\Services\Reports\ProductSkuClassifier;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;

class RecommendationEngine
{
    public function __construct(
        private readonly AdsMetricsService $adsMetrics,
        private readonly ProductPricingAdvisor $pricing,
        private readonly ProductSkuClassifier $classifier,
        private readonly ProductActionEngine $actions,
    ) {
    }

    public function enrichReport(array &$report, Carbon $start, Carbon $end): void
    {
        $shopId = ShopeeShopContext::shopId();
        $summary = $report['summary'] ?? [];
        $products = &$report['products'];

        $adsByProduct = $this->adsMetrics->loadByProduct($shopId, $start, $end);
        $takeRate = (float) ($summary['take_rate'] ?? 0);

        $productModels = Product::query()
            ->whereIn('id', array_filter(array_column($products, 'product_id')))
            ->get()
            ->keyBy('id');

        $hppAllowed = app(\App\Services\Hpp\HppCompletenessService::class)
            ->shopSummary()['recommendations_allowed'];

        foreach ($products as &$row) {
            $pid = (int) ($row['product_id'] ?? 0);
            $ext = $row['external_item_id'] ?? '';
            $ads = $adsByProduct[$pid] ?? $adsByProduct['ext:' . $ext] ?? null;

            $row['tier'] = $row['tier'] ?? $this->classifier->classify($row);
            $row['ads_metrics'] = $ads ? $this->formatAdsMetrics($ads, $row) : null;
            $row['pricing'] = $this->pricing->analyze($row, $productModels->get($pid), $takeRate);
            $row['ads_rec'] = $ads ? $this->adsProductRecommendation($ads, $row) : null;
            $row['action'] = $this->resolvePrimaryAction($row, $hppAllowed);
        }
        unset($row);

        $report['recommendations'] = [
            'ads_shop' => $this->adsMetrics->shopSummary($shopId, $start, $end, $summary),
        ];
    }

    private function formatAdsMetrics(array $ads, array $row): array
    {
        $businessRoas = ($row['ads_spend'] ?? 0) > 0 ? ($row['gross'] ?? 0) / $row['ads_spend'] : null;
        $gp = ($row['gross'] ?? 0) > 0 ? ($row['gross_profit'] ?? 0) / $row['gross'] : 0;
        $targetRoas = $gp > 0 ? (1 / $gp) * (float) config('monitoring.roas_advisor.safety_multiplier', 1.25) : null;

        return [
            'spend' => (int) round($ads['spend']),
            'clicks' => $ads['clicks'],
            'impressions' => $ads['impressions'],
            'cpc' => $ads['cpc'] !== null ? (int) round($ads['cpc']) : null,
            'ctr' => $ads['ctr'] !== null ? round($ads['ctr'], 2) : null,
            'cpm' => $ads['cpm'] !== null ? (int) round($ads['cpm']) : null,
            'shopee_roas' => $ads['shopee_roas'] !== null ? round($ads['shopee_roas'], 2) : null,
            'business_roas' => $businessRoas ? round($businessRoas, 2) : null,
            'target_roas' => $targetRoas ? round($targetRoas, 2) : null,
            'cpa' => $ads['cpa'] !== null ? (int) round($ads['cpa']) : null,
        ];
    }

    private function adsProductRecommendation(array $ads, array $row): array
    {
        $spend = (float) ($row['ads_spend'] ?? 0);
        if ($spend <= 0) {
            return [
                'severity' => 'info',
                'title' => 'Tanpa spend iklan',
                'lines' => ['Produk ini tidak terdeteksi spend iklan pada periode.'],
            ];
        }

        $businessRoas = $row['roas'] ?? null;
        $shopeeRoas = $ads['shopee_roas'] ?? null;
        $gp = ($row['gross'] ?? 0) > 0 ? ($row['gross_profit'] ?? 0) / $row['gross'] : 0;
        $target = $gp > 0 ? (1 / $gp) * (float) config('monitoring.roas_advisor.safety_multiplier', 1.25) : null;
        $cpc = $ads['cpc'];

        $lines = [];
        if ($cpc !== null) {
            $lines[] = 'Biaya per klik (CPC) aktual: **' . number_format($cpc, 0, ',', '.') . '**';
        }
        if ($businessRoas !== null) {
            $lines[] = 'ROAS bisnis **' . number_format($businessRoas, 2) . 'x** (kotor ÷ spend)';
        }
        if ($shopeeRoas !== null) {
            $lines[] = 'ROAS GMV Shopee **' . number_format($shopeeRoas, 2) . 'x**';
        }

        $severity = 'info';
        $title = 'Iklan dalam batas wajar';

        if ($target && $businessRoas !== null && $businessRoas < $target * 0.9) {
            $severity = 'danger';
            $title = 'Turunkan iklan atau naikkan harga';
            $lines[] = 'ROAS di bawah target **' . number_format($target, 2) . 'x** dari data aktual.';
        } elseif ($target && $businessRoas !== null && $businessRoas >= $target) {
            $severity = 'success';
            $title = 'Iklan efisien — boleh scale bertahap';
        }

        if (($row['net_profit'] ?? 0) < 0 && $spend > 50000) {
            $severity = 'danger';
            $title = 'Potong iklan — SKU masih rugi';
        }

        return compact('severity', 'title', 'lines');
    }

    private function resolvePrimaryAction(array $row, bool $hppAllowed): array
    {
        if (!$hppAllowed) {
            return $this->actions->forProduct($row, false);
        }

        $pricing = $row['pricing']['recommendation'] ?? [];
        $pricingStatus = $row['pricing']['status'] ?? '';

        if (in_array($pricingStatus, ['too_low', 'not_covering'], true)) {
            $rec = $row['pricing']['prices']['recommended_gross'] ?? 0;
            return [
                'code' => 'raise_price_calc',
                'severity' => $pricing['severity'] ?? 'danger',
                'title' => $pricing['title'] ?? 'Naikkan harga',
                'summary' => 'Berdasarkan COGS + iklan + operasional per unit.',
                'reasons' => $pricing['lines'] ?? [],
                'route' => null,
                'meta' => ['recommended_price' => $rec],
            ];
        }

        $adsRec = $row['ads_rec'] ?? null;
        if ($adsRec && ($adsRec['severity'] ?? '') === 'danger') {
            return [
                'code' => 'cut_ads_data',
                'severity' => 'danger',
                'title' => $adsRec['title'],
                'summary' => implode(' ', $adsRec['lines'] ?? []),
                'reasons' => $adsRec['lines'] ?? [],
                'route' => null,
                'meta' => [],
            ];
        }

        if ($pricingStatus === 'ok') {
            return [
                'code' => 'price_ok',
                'severity' => 'success',
                'title' => 'Harga & unit economics OK',
                'summary' => 'Biaya tertutup pada periode ini.',
                'reasons' => $pricing['lines'] ?? [],
                'route' => null,
                'meta' => [],
            ];
        }

        return $this->actions->forProduct($row, true);
    }
}
