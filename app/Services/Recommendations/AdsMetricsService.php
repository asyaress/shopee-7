<?php

namespace App\Services\Recommendations;

use App\Models\ShopMonthlyCost;
use App\Models\ShopeeProductAdsDaily;
use Carbon\Carbon;

class AdsMetricsService
{
    /**
     * @return array<int|string, array{spend: float, clicks: int, impressions: int, gmv: float, orders: int, cpc: ?float, ctr: ?float, cpm: ?float, shopee_roas: ?float, cpa: ?float}>
     */
    public function loadByProduct(int $shopId, Carbon $start, Carbon $end): array
    {
        $rows = ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('product_id, external_item_id,
                SUM(spend) as spend,
                SUM(clicks) as clicks,
                SUM(impressions) as impressions,
                SUM(gmv) as gmv,
                SUM(orders) as orders')
            ->groupBy('product_id', 'external_item_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $spend = (float) $r->spend;
            $clicks = (int) $r->clicks;
            $impressions = (int) $r->impressions;
            $gmv = (float) $r->gmv;
            $orders = (int) $r->orders;

            $metrics = [
                'spend' => $spend,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'gmv' => $gmv,
                'orders' => $orders,
                'cpc' => $clicks > 0 ? $spend / $clicks : null,
                'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : null,
                'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : null,
                'shopee_roas' => $spend > 0 && $gmv > 0 ? $gmv / $spend : null,
                'cpa' => $orders > 0 ? $spend / $orders : null,
            ];

            if ($r->product_id) {
                $map[(int) $r->product_id] = $this->mergeMetrics($map[(int) $r->product_id] ?? null, $metrics);
            }
            if ($r->external_item_id) {
                $map['ext:' . $r->external_item_id] = $this->mergeMetrics($map['ext:' . $r->external_item_id] ?? null, $metrics);
            }
        }

        return $map;
    }

    public function shopSummary(int $shopId, Carbon $start, Carbon $end, array $reportSummary): array
    {
        $row = ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(spend) as s, SUM(clicks) as c, SUM(impressions) as i, SUM(gmv) as g')
            ->first();

        $totalSpend = (float) ($row->s ?? 0);
        $clicks = (int) ($row->c ?? 0);
        $impressions = (int) ($row->i ?? 0);
        $gmv = (float) ($row->g ?? 0);

        $ym = now()->format('Y-m');
        $monthly = ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->where('year_month', $ym)
            ->first();

        $budget = (float) ($monthly?->ad_budget_cap ?? config('monitoring.ad_budget_monthly')[$shopId] ?? 0);
        $businessRoas = ($reportSummary['ads_total'] ?? 0) > 0
            ? ($reportSummary['gross'] ?? 0) / $reportSummary['ads_total']
            : null;

        $contribution = ($reportSummary['gross'] ?? 0) > 0
            ? ($reportSummary['gross_profit'] ?? 0) / $reportSummary['gross']
            : 0;
        $targetRoas = $contribution > 0 ? (1 / $contribution) * (float) config('monitoring.roas_advisor.safety_multiplier', 1.25) : null;

        return [
            'spend_period' => (int) round($totalSpend),
            'budget_monthly' => (int) round($budget),
            'budget_remaining' => $budget > 0 ? (int) round(max(0, $budget - ($reportSummary['ads_total'] ?? 0))) : null,
            'budget_used_pct' => $budget > 0 ? ($reportSummary['ads_total'] ?? 0) / $budget : null,
            'cpc_shop' => $clicks > 0 ? round($totalSpend / $clicks) : null,
            'ctr_shop' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
            'shopee_roas_gmv' => $totalSpend > 0 && $gmv > 0 ? round($gmv / $totalSpend, 2) : null,
            'business_roas' => $businessRoas ? round($businessRoas, 2) : null,
            'target_roas_business' => $targetRoas ? round($targetRoas, 2) : null,
            'recommendation' => $this->shopAdsRecommendation($businessRoas, $targetRoas, $budget, $reportSummary['ads_total'] ?? 0),
        ];
    }

    private function shopAdsRecommendation(?float $current, ?float $target, float $budget, float $spent): array
    {
        $lines = [];
        if ($target) {
            $lines[] = 'Target ROAS bisnis (dari data aktual): **' . number_format($target, 2) . 'x**';
        }
        if ($current !== null && $target) {
            if ($current < $target) {
                $lines[] = 'ROAS sekarang **' . number_format($current, 2) . 'x** — di bawah target. Kurangi bid/CPC atau naikkan harga pada SKU bleeder.';
            } else {
                $lines[] = 'ROAS **' . number_format($current, 2) . 'x** — memenuhi target. Scale hati-hati pada SKU star saja.';
            }
        }
        if ($budget > 0) {
            $pct = round(($spent / $budget) * 100, 1);
            $lines[] = "Saldo/budget iklan bulanan: terpakai **{$pct}%** (" . number_format($spent) . ' / ' . number_format($budget) . ').';
        }

        return [
            'title' => 'Rekomendasi iklan toko',
            'lines' => $lines,
            'severity' => ($current !== null && $target && $current < $target) ? 'warning' : 'info',
        ];
    }

    private function mergeMetrics(?array $a, array $b): array
    {
        if ($a === null) {
            return $b;
        }

        $spend = $a['spend'] + $b['spend'];
        $clicks = $a['clicks'] + $b['clicks'];
        $impressions = $a['impressions'] + $b['impressions'];
        $gmv = $a['gmv'] + $b['gmv'];
        $orders = $a['orders'] + $b['orders'];

        return [
            'spend' => $spend,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'gmv' => $gmv,
            'orders' => $orders,
            'cpc' => $clicks > 0 ? $spend / $clicks : null,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : null,
            'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : null,
            'shopee_roas' => $spend > 0 && $gmv > 0 ? $gmv / $spend : null,
            'cpa' => $orders > 0 ? $spend / $orders : null,
        ];
    }
}
