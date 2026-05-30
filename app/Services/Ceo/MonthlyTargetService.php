<?php

namespace App\Services\Ceo;

use App\Models\ShopMonthlyCost;
use App\Services\Reports\ProductProfitReportService;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyTargetService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
    ) {
    }

    public function dashboard(Request $request, ?string $yearMonth = null): array
    {
        $shopId = ShopeeShopContext::shopId();
        $yearMonth = $yearMonth ?? now()->format('Y-m');

        $start = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

        $req = clone $request;
        $req->merge([
            'start' => $start->toDateString(),
            'end' => min($end, now())->toDateString(),
        ]);

        $report = $this->reportService->build($req);
        $s = $report['summary'] ?? [];

        $targets = ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->where('year_month', $yearMonth)
            ->first();

        $targetNet = (float) ($targets?->target_net_profit ?? 0);
        $targetGross = (float) ($targets?->target_gross ?? 0);
        $adBudget = (float) ($targets?->ad_budget_cap ?? 0);

        $actualNet = (float) ($s['net_profit'] ?? 0);
        $actualGross = (float) ($s['gross'] ?? 0);
        $actualAds = (float) ($s['ads_total'] ?? 0);

        $daysInMonth = $start->daysInMonth;
        $dayOfMonth = min(now()->day, $daysInMonth);
        $paceFactor = $dayOfMonth / max(1, $daysInMonth);

        return [
            'year_month' => $yearMonth,
            'targets' => [
                'net_profit' => (int) round($targetNet),
                'gross' => (int) round($targetGross),
                'units' => (int) ($targets?->target_units ?? 0),
                'ad_budget' => (int) round($adBudget),
            ],
            'actual' => [
                'net_profit' => (int) round($actualNet),
                'gross' => (int) round($actualGross),
                'units' => (int) ($s['units_sold'] ?? 0),
                'ads_total' => (int) round($actualAds),
            ],
            'progress' => [
                'net_pct' => $targetNet > 0 ? $actualNet / $targetNet : null,
                'gross_pct' => $targetGross > 0 ? $actualGross / $targetGross : null,
                'units_pct' => ($targets?->target_units ?? 0) > 0 ? ($s['units_sold'] ?? 0) / $targets->target_units : null,
                'ads_pct' => $adBudget > 0 ? $actualAds / $adBudget : null,
            ],
            'pace' => [
                'expected_net_by_today' => (int) round($targetNet * $paceFactor),
                'on_track_net' => $targetNet > 0 ? $actualNet >= ($targetNet * $paceFactor * 0.9) : null,
                'day' => $dayOfMonth,
                'days_in_month' => $daysInMonth,
            ],
            'report' => $report,
        ];
    }

    public function save(int $shopId, string $yearMonth, array $data): ShopMonthlyCost
    {
        return ShopMonthlyCost::updateOrCreate(
            ['shop_id' => $shopId, 'year_month' => $yearMonth],
            [
                'operational_amount' => $data['operational_amount'] ?? 0,
                'target_net_profit' => $data['target_net_profit'] ?? null,
                'target_gross' => $data['target_gross'] ?? null,
                'target_units' => $data['target_units'] ?? null,
                'ad_budget_cap' => $data['ad_budget_cap'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );
    }
}
