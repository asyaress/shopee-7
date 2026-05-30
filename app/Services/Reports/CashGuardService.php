<?php

namespace App\Services\Reports;

use App\Models\ShopeeProductAdsDaily;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;

class CashGuardService
{
    public function build(array $report): array
    {
        $shopId = ShopeeShopContext::shopId();
        $summary = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];

        $end = Carbon::parse($filters['end'] ?? now());
        $weeks = (int) config('monitoring.cash_guard.ads_spend_weeks_lookback', 4);
        $start = $end->copy()->subWeeks($weeks)->startOfDay();

        $adsSpend = (float) ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->sum('spend');

        $netIn = (float) ($summary['net'] ?? 0);
        $operational = (float) ($summary['operational_total'] ?? 0);
        $netProfit = (float) ($summary['net_profit'] ?? 0);

        $multiplier = (float) config('monitoring.cash_guard.safe_ads_multiplier', 0.25);
        $safeWeeklyAds = $netIn > 0 ? ($netIn / max(1, $weeks)) * $multiplier : 0;

        $monthly = \App\Models\ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->where('year_month', now()->format('Y-m'))
            ->first();
        $budgetMonthly = (float) ($monthly?->ad_budget_cap ?? config('monitoring.ad_budget_monthly')[$shopId] ?? 0);
        $adsTotal = (float) ($summary['ads_total'] ?? 0);
        $budgetUsedPct = $budgetMonthly > 0 ? min(1.5, $adsTotal / $budgetMonthly) : null;

        return [
            'period_weeks' => $weeks,
            'ads_spend_period' => (int) round($adsSpend),
            'net_income_period' => (int) round($netIn),
            'operational_period' => (int) round($operational),
            'net_profit_period' => (int) round($netProfit),
            'safe_weekly_ads_suggest' => (int) round($safeWeeklyAds),
            'budget_monthly' => (int) round($budgetMonthly),
            'budget_used_pct' => $budgetUsedPct,
            'message' => $this->message($netProfit, $adsTotal, $budgetUsedPct, $safeWeeklyAds),
        ];
    }

    private function message(float $netProfit, float $adsTotal, ?float $budgetPct, float $safeWeekly): string
    {
        if ($budgetPct !== null && $budgetPct >= 0.85) {
            return 'Spend iklan sudah ≥85% budget bulanan — pertimbangkan pause pada SKU bleeder.';
        }
        if ($netProfit < 0 && $adsTotal > 0) {
            return 'Laba bersih negatif dengan iklan aktif — prioritaskan potong iklan SKU rugi.';
        }
        if ($safeWeekly > 0) {
            return 'Pace iklan mingguan aman (estimasi): sekitar Rp ' . number_format($safeWeekly) . ' berdasarkan net masuk periode.';
        }

        return 'Pantau spend iklan vs net penghasilan secara mingguan.';
    }
}
