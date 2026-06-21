<?php

namespace App\Services\Reports;

use App\Models\ShopeeProductAdsDaily;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitoringChartService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
    ) {
    }

    public function baseReport(Request $request): array
    {
        return $this->reportService->build($request);
    }

    public function chartsForShopee(array $report): array
    {
        $monthly = $report['monthly'] ?? [];
        $fb = $report['fee_breakdown'] ?? [];
        $labels = array_column($monthly, 'label');
        $feeHeatmap = $this->feeHeatmap($monthly, $fb);
        $feeStacked = $this->feeStackedMonthly($monthly, $fb);

        return [
            'fee_doughnut' => $this->feeDoughnut($fb),
            'fee_heatmap' => $feeHeatmap,
            'fee_stacked' => $feeStacked,
            'fee_monthly' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Biaya platform (Rp)',
                        'data' => array_map(fn ($m) => (int) round(($m['fee_total'] ?? 0) ?: (($m['gross'] ?? 0) - ($m['net'] ?? 0))), $monthly),
                    ],
                ],
            ],
            'take_rate' => [
                'labels' => $labels,
                'data' => array_map(function ($m) {
                    $g = (float) ($m['gross'] ?? 0);
                    $fee = $g - (float) ($m['net'] ?? 0);
                    return $g > 0 ? round(($fee / $g) * 100, 1) : 0;
                }, $monthly),
            ],
            'gross_vs_net' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Kotor', 'data' => array_map(fn ($m) => (int) ($m['gross'] ?? 0), $monthly)],
                    ['label' => 'Net', 'data' => array_map(fn ($m) => (int) ($m['net'] ?? 0), $monthly)],
                ],
            ],
        ];
    }

    public function chartsForRevenue(array $report): array
    {
        $monthly = $report['monthly'] ?? [];
        $labels = array_column($monthly, 'label');
        $s = $report['summary'] ?? [];

        return [
            'revenue_trend' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Penjualan kotor', 'data' => array_map(fn ($m) => (int) ($m['gross'] ?? 0), $monthly)],
                    ['label' => 'Net penghasilan', 'data' => array_map(fn ($m) => (int) ($m['net'] ?? 0), $monthly)],
                ],
            ],
            'orders_bar' => [
                'labels' => $labels,
                'data' => array_map(fn ($m) => (int) ($m['orders'] ?? 0), $monthly),
            ],
            'profit_stack' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Laba kotor', 'data' => array_map(fn ($m) => (int) ($m['gross_profit'] ?? 0), $monthly)],
                    ['label' => 'Setelah iklan & ops', 'data' => array_map(fn ($m) => (int) ($m['net_profit'] ?? 0), $monthly)],
                ],
            ],
            'summary_compare' => [
                'labels' => ['Kotor', 'Net', 'Laba kotor', 'Laba bersih'],
                'data' => [
                    (int) ($s['gross'] ?? 0),
                    (int) ($s['net'] ?? 0),
                    (int) ($s['gross_profit'] ?? 0),
                    (int) ($s['net_profit'] ?? 0),
                ],
            ],
        ];
    }

    public function chartsForAds(array $report, Request $request): array
    {
        $monthly = $report['monthly'] ?? [];
        $products = $report['products'] ?? [];
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];

        $start = Carbon::parse($filters['start'] ?? now()->startOfMonth());
        $end = Carbon::parse($filters['end'] ?? now());
        $shopId = ShopeeShopContext::shopId();

        $daily = ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('report_date, SUM(spend) as spend, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(gmv) as gmv, SUM(orders) as orders')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get();

        $dayLabels = $daily->map(fn ($r) => Carbon::parse($r->report_date)->format('d M'))->all();

        $topAds = collect($products)
            ->sortByDesc('ads_spend')
            ->take(8)
            ->values();

        return [
            'ads_daily' => [
                'labels' => $dayLabels,
                'datasets' => [
                    ['label' => 'Spend iklan (Rp)', 'data' => $daily->map(fn ($r) => (int) round($r->spend))->all()],
                    ['label' => 'GMV iklan (Rp)', 'data' => $daily->map(fn ($r) => (int) round($r->gmv ?? 0))->all()],
                ],
            ],
            'ads_monthly' => [
                'labels' => array_column($monthly, 'label'),
                'data' => array_map(fn ($m) => (int) ($m['ads'] ?? 0), $monthly),
            ],
            'top_spend' => [
                'labels' => $topAds->map(fn ($p) => \Illuminate\Support\Str::limit($p['name'] ?? '—', 22))->all(),
                'data' => $topAds->map(fn ($p) => (int) ($p['ads_spend'] ?? 0))->all(),
            ],
            'roas_acos' => [
                'roas' => $s['roas'] ?? null,
                'acos' => $s['acos'] ?? null,
                'spend' => (int) ($s['ads_total'] ?? 0),
                'gross' => (int) ($s['gross'] ?? 0),
            ],
            'ctr_daily' => [
                'labels' => $dayLabels,
                'data' => $daily->map(function ($r) {
                    $imp = (int) ($r->impressions ?? 0);
                    $clk = (int) ($r->clicks ?? 0);
                    return $imp > 0 ? round(($clk / $imp) * 100, 2) : 0;
                })->all(),
            ],
        ];
    }

    public function chartsForOverview(array $report): array
    {
        return [
            'shopee' => $this->chartsForShopee($report),
            'revenue' => $this->chartsForRevenue($report),
            'ads' => array_intersect_key(
                $this->chartsForAds($report, request()),
                array_flip(['ads_monthly', 'roas_acos'])
            ),
        ];
    }

    private function feeDoughnut(array $fb): array
    {
        $labels = [];
        $data = [];
        $map = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels();

        foreach ($map as $key => $label) {
            $val = (int) round($fb[$key] ?? 0);
            if ($val > 0) {
                $labels[] = $label;
                $data[] = $val;
            }
        }

        if (empty($labels)) {
            return [
                'labels' => ['Administrasi', 'Layanan', 'Proses', 'Program Hemat'],
                'data' => [
                    $fb['admin'] ?? 0,
                    $fb['layanan'] ?? 0,
                    $fb['proses'] ?? 0,
                    $fb['program_hemat'] ?? 0,
                ],
            ];
        }

        return compact('labels', 'data');
    }

    public function chartsForProfit(array $report): array
    {
        $monthly = $report['monthly'] ?? [];
        $fb = $report['fee_breakdown'] ?? [];
        $s = $report['summary'] ?? [];
        $products = collect($report['products'] ?? [])
            ->filter(fn ($p) => ($p['net_profit'] ?? 0) != 0 || ($p['gross'] ?? 0) > 0)
            ->sortByDesc('net_profit')
            ->take(12)
            ->values();

        return [
            'monthly_pl' => [
                'labels' => array_column($monthly, 'label'),
                'datasets' => [
                    ['label' => 'Penjualan kotor', 'data' => array_map(fn ($m) => (int) ($m['gross'] ?? 0), $monthly)],
                    ['label' => 'Laba bersih', 'data' => array_map(fn ($m) => (int) ($m['net_profit'] ?? 0), $monthly)],
                ],
            ],
            'fee_treemap' => [
                'data' => $this->feeTreemapItems($fb),
            ],
            'product_treemap' => [
                'data' => $products->map(fn ($p) => [
                    'label' => \Illuminate\Support\Str::limit($p['name'] ?? '—', 28),
                    'value' => max(0, (int) ($p['net_profit'] ?? 0)),
                ])->filter(fn ($i) => $i['value'] > 0)->values()->all(),
            ],
            'margin_radial' => [
                'series' => [round(max(0, min(100, ($s['margin'] ?? 0) * 100)), 1)],
                'labels' => ['Margin bersih'],
                'max' => 100,
            ],
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function feeTreemapItems(array $fb): array
    {
        $map = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels();
        $out = [];
        foreach ($map as $key => $label) {
            $val = (int) round(abs($fb[$key] ?? 0));
            if ($val > 0) {
                $out[] = ['label' => $label, 'value' => $val];
            }
        }

        return $out;
    }

    /** @param list<array<string, mixed>> $monthly @param array<string, int> $periodFb */
    private function feeHeatmap(array $monthly, array $periodFb): array
    {
        $map = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels();
        $activeKeys = [];
        foreach (array_keys($map) as $key) {
            $periodVal = (int) ($periodFb[$key] ?? 0);
            $monthSum = 0;
            foreach ($monthly as $m) {
                $monthSum += (int) (($m['fee'] ?? [])[$key] ?? 0);
            }
            if ($periodVal > 0 || $monthSum > 0) {
                $activeKeys[] = $key;
            }
        }

        if ($activeKeys === [] || $monthly === []) {
            return ['series' => [], 'max' => 0];
        }

        $max = 0;
        $series = [];
        foreach ($monthly as $m) {
            $points = [];
            foreach ($activeKeys as $key) {
                $val = (int) (($m['fee'] ?? [])[$key] ?? 0);
                $max = max($max, $val);
                $points[] = ['x' => $map[$key], 'y' => $val];
            }
            $series[] = ['name' => $m['label'] ?? '', 'data' => $points];
        }

        return [
            'series' => $series,
            'max' => $max,
            'components' => array_map(fn ($k) => $map[$k], $activeKeys),
        ];
    }

    /** @param list<array<string, mixed>> $monthly @param array<string, int> $periodFb */
    private function feeStackedMonthly(array $monthly, array $periodFb): array
    {
        $map = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels();
        $labels = array_column($monthly, 'label');
        $activeKeys = [];
        foreach (array_keys($map) as $key) {
            if ((int) ($periodFb[$key] ?? 0) > 0) {
                $activeKeys[] = $key;
            }
        }

        $datasets = [];
        foreach ($activeKeys as $key) {
            $datasets[] = [
                'label' => $map[$key],
                'data' => array_map(fn ($m) => (int) (($m['fee'] ?? [])[$key] ?? 0), $monthly),
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'stacked' => true,
        ];
    }
}
