<?php

namespace App\Services\Ceo;

use App\Models\ShopeeProductAdsDaily;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;

/**
 * ROAS Shopee Ads (API): GMV atribusi iklan / spend.
 * ROAS bisnis (app): penjualan kotor order / spend.
 * ROAS impas: 1 / (laba kotor / kotor) sebelum iklan.
 */
class RoasAdvisorService
{
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
        if ($contributionRatio > 0 && $gross > 0 && $adsGmv > 0 && $adsSpendApi > 0) {
            $gmvToGross = $gross / max(1, $adsGmv);
            $breakevenShopeeRoas = (1 / $contributionRatio) / max(0.01, $gmvToGross);
        }

        return [
            'definitions' => [
                'shopee_ads' => 'ROAS di dashboard Shopee Ads ≈ GMV atribusi iklan (broad/direct) ÷ spend.',
                'business' => 'ROAS bisnis di app = penjualan kotor order ÷ spend iklan (sudah termasuk organik + iklan).',
                'breakeven' => 'ROAS impas = 1 ÷ (laba kotor ÷ kotor). Di bawah ini, iklan memakan margin sebelum operasional.',
            ],
            'metrics' => [
                'shopee_ads_roas' => $shopeeAdsRoas,
                'business_roas' => $businessRoas,
                'breakeven_roas_gross' => $breakevenRoasGross,
                'target_roas_gross' => $targetRoasGross,
                'breakeven_roas_shopee_gmv' => $breakevenShopeeRoas,
                'target_roas_shopee_gmv' => $breakevenShopeeRoas ? $breakevenShopeeRoas * $safety : null,
                'contribution_margin' => $contributionRatio,
                'gmv_to_gross_ratio' => ($adsGmv > 0 && $gross > 0) ? $gross / $adsGmv : null,
            ],
            'recommendation' => $this->recommendation(
                $businessRoas,
                $targetRoasGross,
                $shopeeAdsRoas,
                $breakevenShopeeRoas ? $breakevenShopeeRoas * $safety : null,
                $netProfit
            ),
            'products' => $this->productAdvice($report['products'] ?? [], $safety),
        ];
    }

    private function recommendation(
        ?float $businessRoas,
        ?float $targetGross,
        ?float $shopeeRoas,
        ?float $targetShopee,
        float $netProfit
    ): array {
        $lines = [];

        if ($targetGross) {
            $lines[] = 'Set target ROAS bisnis (kotor÷spend) minimal **' . number_format($targetGross, 2) . 'x** agar laba kotor menutupi spend.';
        }
        if ($targetShopee) {
            $lines[] = 'Di Shopee Ads (basis GMV), pertimbangkan target ROAS kampanye **≥ ' . number_format($targetShopee, 2) . 'x** (estimasi).';
        }
        if ($businessRoas !== null && $targetGross) {
            if ($businessRoas < $targetGross) {
                $lines[] = 'ROAS bisnis saat ini **' . number_format($businessRoas, 2) . 'x** — di bawah target. Kurangi spend atau naikkan harga/HPP.';
            } else {
                $lines[] = 'ROAS bisnis **' . number_format($businessRoas, 2) . 'x** — di atas target impas. Scale iklan bisa dipertimbangkan pada SKU star.';
            }
        }
        if ($shopeeRoas !== null) {
            $lines[] = 'ROAS GMV Shopee API periode ini: **' . number_format($shopeeRoas, 2) . 'x**.';
        }
        if ($netProfit < 0) {
            $lines[] = 'Laba bersih masih negatif — ROAS saja tidak cukup; cek HPP, fee promo, dan operasional.';
        }

        return [
            'title' => 'Rekomendasi target ROAS',
            'lines' => $lines,
        ];
    }

    private function productAdvice(array $products, float $safety): array
    {
        $out = [];
        foreach (array_slice($products, 0, 30) as $p) {
            $gross = (float) ($p['gross'] ?? 0);
            $gp = (float) ($p['gross_profit'] ?? 0);
            $spend = (float) ($p['ads_spend'] ?? 0);
            if ($spend <= 0 || $gross <= 0) {
                continue;
            }
            $cr = $gp / $gross;
            $be = $cr > 0 ? 1 / $cr : null;
            $target = $be ? $be * $safety : null;
            $current = (float) ($p['roas'] ?? ($gross / $spend));

            $out[] = [
                'product_id' => $p['product_id'],
                'name' => $p['name'],
                'current_roas' => round($current, 2),
                'breakeven_roas' => $be ? round($be, 2) : null,
                'target_roas' => $target ? round($target, 2) : null,
                'gap' => ($target && $current) ? round($target - $current, 2) : null,
                'net_profit' => (int) round($p['net_profit'] ?? 0),
            ];
        }

        usort($out, fn ($a, $b) => ($a['gap'] ?? 0) <=> ($b['gap'] ?? 0));

        return array_slice($out, 0, 15);
    }
}
