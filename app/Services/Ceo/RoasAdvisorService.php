<?php

namespace App\Services\Ceo;

use App\Models\ShopeeProductAdsDaily;
use App\Services\Recommendations\AdsMetricsService;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;

/**
 * ROAS Shopee Ads (API): GMV atribusi iklan / spend.
 * ROAS bisnis (app): penjualan kotor order / spend.
 * ROAS impas: 1 / (laba kotor / kotor) sebelum iklan.
 */
class RoasAdvisorService
{
    public function __construct(
        private readonly AdsMetricsService $adsMetrics,
    ) {
    }

    public function shopAdvice(array $report): array
    {
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];
        $shopId = ShopeeShopContext::shopId();

        $gross = (float) ($s['gross'] ?? 0);
        $grossProfit = (float) ($s['gross_profit'] ?? 0);
        $adsSpend = (float) ($s['ads_total'] ?? 0);
        $netProfit = (float) ($s['net_profit'] ?? 0);

        $contributionRatio = $gross > 0 ? $grossProfit / $gross : 0;
        $breakevenRoasGross = $contributionRatio > 0 ? 1 / $contributionRatio : null;
        $safety = (float) config('monitoring.roas_advisor.safety_multiplier', 1.25);
        $targetRoasGross = $breakevenRoasGross ? $breakevenRoasGross * $safety : null;

        $start = Carbon::parse($filters['start'] ?? now()->startOfMonth());
        $end = Carbon::parse($filters['end'] ?? now());

        $adsGmv = (float) ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->sum('gmv');

        $adsSpendApi = (float) ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->sum('spend');

        $shopeeAdsRoas = $adsSpendApi > 0 ? $adsGmv / $adsSpendApi : null;
        $businessRoas = $adsSpend > 0 ? $gross / $adsSpend : ($s['roas'] ?? null);

        $breakevenShopeeRoas = null;
        $setRoasShop = null;
        if ($contributionRatio > 0 && $gross > 0 && $adsGmv > 0 && $adsSpendApi > 0) {
            $gmvToGross = $gross / max(1, $adsGmv);
            $breakevenShopeeRoas = (1 / $contributionRatio) / max(0.01, $gmvToGross);
            $setRoasShop = $breakevenShopeeRoas * $safety / 0.70;
        }

        $adsByProduct = $this->adsMetrics->loadByProduct($shopId, $start, $end);

        return [
            'definitions' => [
                'shopee_ads' => 'ROAS Shopee (AMS) = GMV atribusi iklan ÷ spend — angka yang muncul di dashboard Shopee Ads.',
                'business' => 'ROAS bisnis = penjualan kotor order ÷ spend iklan (termasuk penjualan organik + iklan).',
                'set_roas' => 'Rekomendasi Set ROAS = target impas Shopee ÷ 70% — angka praktis untuk input di dashboard iklan.',
                'breakeven' => 'ROAS impas bisnis = 1 ÷ (laba kotor ÷ kotor). Di bawah ini iklan memakan margin sebelum operasional.',
            ],
            'metrics' => [
                'shopee_ads_roas' => $shopeeAdsRoas,
                'business_roas' => $businessRoas,
                'breakeven_roas_gross' => $breakevenRoasGross,
                'target_roas_gross' => $targetRoasGross,
                'breakeven_roas_shopee_gmv' => $breakevenShopeeRoas,
                'target_roas_shopee_gmv' => $breakevenShopeeRoas ? $breakevenShopeeRoas * $safety : null,
                'set_roas_shopee' => $setRoasShop ? round($setRoasShop, 2) : null,
                'contribution_margin' => $contributionRatio,
                'gmv_to_gross_ratio' => ($adsGmv > 0 && $gross > 0) ? $gross / $adsGmv : null,
                'ads_gmv' => (int) round($adsGmv),
                'ads_spend' => (int) round($adsSpendApi),
            ],
            'recommendation' => $this->recommendation(
                $businessRoas,
                $targetRoasGross,
                $shopeeAdsRoas,
                $breakevenShopeeRoas ? $breakevenShopeeRoas * $safety : null,
                $setRoasShop,
                $netProfit
            ),
            'products' => $this->productAdvice($report['products'] ?? [], $safety, $adsByProduct),
        ];
    }

    private function recommendation(
        ?float $businessRoas,
        ?float $targetGross,
        ?float $shopeeRoas,
        ?float $targetShopee,
        ?float $setRoas,
        float $netProfit
    ): array {
        $lines = [];

        if ($targetGross) {
            $lines[] = 'Target ROAS bisnis minimal **' . number_format($targetGross, 2) . 'x** agar laba kotor menutupi spend iklan.';
        }
        if ($setRoas) {
            $lines[] = 'Rekomendasi **Set ROAS** di dashboard Shopee Ads: **' . number_format($setRoas, 2) . 'x** (estimasi, basis GMV AMS).';
        } elseif ($targetShopee) {
            $lines[] = 'Target ROAS Shopee (GMV): **≥ ' . number_format($targetShopee, 2) . 'x**.';
        }
        if ($businessRoas !== null && $targetGross) {
            if ($businessRoas < $targetGross) {
                $lines[] = 'ROAS bisnis **' . number_format($businessRoas, 2) . 'x** — di bawah target. Kurangi spend atau perbaiki harga/HPP.';
            } else {
                $lines[] = 'ROAS bisnis **' . number_format($businessRoas, 2) . 'x** — di atas impas. Scale iklan hanya pada SKU star.';
            }
        }
        if ($shopeeRoas !== null) {
            $lines[] = 'ROAS GMV AMS periode ini: **' . number_format($shopeeRoas, 2) . 'x**.';
        }
        if ($netProfit < 0) {
            $lines[] = 'Laba bersih negatif — selain ROAS, cek HPP, fee promo, dan operasional bulan ini.';
        }

        return [
            'title' => 'Rekomendasi ROAS toko',
            'lines' => $lines,
        ];
    }

    /**
     * @param array<int|string, array<string, mixed>> $adsByProduct
     */
    private function productAdvice(array $products, float $safety, array $adsByProduct): array
    {
        $out = [];
        foreach ($products as $p) {
            $gross = (float) ($p['gross'] ?? 0);
            $gp = (float) ($p['gross_profit'] ?? 0);
            $spend = (float) ($p['ads_spend'] ?? 0);
            if ($spend <= 0) {
                continue;
            }

            $pid = (int) ($p['product_id'] ?? 0);
            $ext = $p['external_item_id'] ?? '';
            $ads = $adsByProduct[$pid] ?? $adsByProduct['ext:' . $ext] ?? null;

            $gmv = (float) ($ads['gmv'] ?? 0);
            $shopeeRoas = ($ads['shopee_roas'] ?? null) ?? ($spend > 0 && $gmv > 0 ? $gmv / $spend : null);
            $businessRoas = $gross > 0 ? $gross / $spend : null;

            $cr = $gross > 0 ? $gp / $gross : 0;
            $be = $cr > 0 ? 1 / $cr : null;
            $targetBusiness = $be ? $be * $safety : null;

            $setRoas = null;
            $beShopee = null;
            if ($cr > 0 && $gross > 0 && $gmv > 0) {
                $gmvToGross = $gross / $gmv;
                $beShopee = (1 / $cr) / max(0.01, $gmvToGross);
                $setRoas = ($beShopee * $safety) / 0.70;
            }

            $out[] = [
                'product_id' => $pid,
                'name' => $p['name'],
                'spend' => (int) round($spend),
                'gmv_ams' => (int) round($gmv),
                'shopee_roas' => $shopeeRoas !== null ? round($shopeeRoas, 2) : null,
                'business_roas' => $businessRoas !== null ? round($businessRoas, 2) : null,
                'set_roas_shopee' => $setRoas !== null ? round($setRoas, 2) : null,
                'target_business' => $targetBusiness ? round($targetBusiness, 2) : null,
                'gap_business' => ($targetBusiness && $businessRoas) ? round($targetBusiness - $businessRoas, 2) : null,
                'net_profit' => (int) round($p['net_profit'] ?? 0),
                'tier' => $p['tier'] ?? null,
            ];
        }

        usort($out, fn ($a, $b) => ($b['spend'] ?? 0) <=> ($a['spend'] ?? 0));

        return array_slice($out, 0, 50);
    }
}
