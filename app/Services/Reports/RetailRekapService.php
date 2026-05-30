<?php

namespace App\Services\Reports;

use App\Models\ShopMonthlyCost;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * REKAP grid (setara Excel HASIL/REKAP) + best seller MoM + KPI retail.
 */
class RetailRekapService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
    ) {
    }

    public function build(Request $request, ?int $months = null): array
    {
        $shopId = ShopeeShopContext::shopId();
        $months = $months ?? (int) config('monitoring.rekap_months', 12);
        $end = now()->endOfMonth();
        $start = now()->subMonths($months - 1)->startOfMonth();

        $monthKeys = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $monthKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $columns = [];
        foreach ($monthKeys as $mk) {
            $mStart = Carbon::createFromFormat('Y-m', $mk)->startOfMonth();
            $mEnd = Carbon::createFromFormat('Y-m', $mk)->endOfMonth();
            $req = clone $request;
            $req->merge([
                'start' => $mStart->toDateString(),
                'end' => min($mEnd, now())->toDateString(),
                'status' => $request->query('status', 'completed'),
            ]);
            $report = $this->reportService->build($req);
            $s = $report['summary'] ?? [];

            $orders = (int) ($s['orders_count'] ?? 0);
            $gross = (float) ($s['gross'] ?? 0);
            $net = (float) ($s['net'] ?? 0);
            $grossProfit = (float) ($s['gross_profit'] ?? 0);
            $netProfit = (float) ($s['net_profit'] ?? 0);
            $ads = (float) ($s['ads_total'] ?? 0);
            $feeTotal = (float) ($s['fee_total'] ?? 0);
            $operational = (float) ($s['operational_total'] ?? 0);
            $units = (int) ($s['units_sold'] ?? 0);

            $columns[$mk] = [
                'label' => $mStart->translatedFormat('M Y'),
                'short' => $mStart->translatedFormat('M'),
                'gross' => (int) round($gross),
                'net' => (int) round($net),
                'fee_total' => (int) round($feeTotal),
                'fee_ratio' => $gross > 0 ? $feeTotal / $gross : null,
                'cogs' => (int) round($s['cogs'] ?? 0),
                'gross_profit' => (int) round($grossProfit),
                'operational' => (int) round($operational),
                'ads' => (int) round($ads),
                'net_profit' => (int) round($netProfit),
                'orders' => $orders,
                'units' => $units,
                'aov_gross' => $orders > 0 ? (int) round($gross / $orders) : null,
                'basket_size' => $orders > 0 ? round($units / $orders, 2) : null,
                'operational_ratio' => $gross > 0 ? $operational / $gross : null,
                'ads_ratio' => $gross > 0 ? $ads / $gross : null,
                'roas' => $ads > 0 ? round($gross / $ads, 2) : null,
                'acos' => $gross > 0 ? round($ads / $gross, 4) : null,
                'gross_margin_pct' => $gross > 0 ? round($grossProfit / $gross, 4) : null,
                'net_margin_pct' => $gross > 0 ? round($netProfit / $gross, 4) : null,
            ];
        }

        $metrics = $this->metricRows();

        return [
            'months' => $monthKeys,
            'columns' => $columns,
            'metrics' => $metrics,
            'best_sellers' => $this->bestSellerCompare($shopId, $monthKeys, $request),
            'targets' => $this->monthlyTargets($shopId, $monthKeys),
        ];
    }

    /** @return list<array{key: string, label: string, format: string}> */
    private function metricRows(): array
    {
        return [
            ['key' => 'gross', 'label' => 'Total pendapatan (kotor)', 'format' => 'rp'],
            ['key' => 'net', 'label' => 'Total penghasilan (net)', 'format' => 'rp'],
            ['key' => 'fee_total', 'label' => 'Total biaya admin & layanan', 'format' => 'rp'],
            ['key' => 'fee_ratio', 'label' => 'Rasio admin & layanan', 'format' => 'pct'],
            ['key' => 'cogs', 'label' => 'HPP', 'format' => 'rp'],
            ['key' => 'gross_profit', 'label' => 'Laba kotor', 'format' => 'rp'],
            ['key' => 'operational', 'label' => 'Operasional', 'format' => 'rp'],
            ['key' => 'ads', 'label' => 'Iklan', 'format' => 'rp'],
            ['key' => 'net_profit', 'label' => 'Laba / rugi bersih', 'format' => 'rp'],
            ['key' => 'aov_gross', 'label' => 'AOV aktual (kotor)', 'format' => 'rp'],
            ['key' => 'basket_size', 'label' => 'Basket size aktual', 'format' => 'num'],
            ['key' => 'operational_ratio', 'label' => 'Rasio operasional', 'format' => 'pct'],
            ['key' => 'ads_ratio', 'label' => 'Rasio iklan', 'format' => 'pct'],
            ['key' => 'roas', 'label' => 'ROAS aktual', 'format' => 'x'],
            ['key' => 'acos', 'label' => 'ACOS aktual', 'format' => 'pct'],
            ['key' => 'gross_margin_pct', 'label' => 'Gross profit margin', 'format' => 'pct'],
            ['key' => 'net_margin_pct', 'label' => 'Net profit margin', 'format' => 'pct'],
        ];
    }

    private function bestSellerCompare(int $shopId, array $monthKeys, Request $request): array
    {
        $periods = array_slice($monthKeys, -3);
        $result = [];

        foreach ($periods as $mk) {
            $mStart = Carbon::createFromFormat('Y-m', $mk)->startOfMonth();
            $mEnd = Carbon::createFromFormat('Y-m', $mk)->endOfMonth();

            $rows = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
                ->whereBetween('orders.order_date', [$mStart->toDateString(), min($mEnd, now())->toDateString()])
                ->whereRaw('LOWER(COALESCE(orders.jenis_transaksi, "")) = ?', ['shopee'])
                ->when($shopId > 0, function ($q) use ($shopId) {
                    $q->where(function ($q2) use ($shopId) {
                        $q2->where('products.external_shop_id', $shopId)
                            ->orWhereExists(function ($sub) use ($shopId) {
                                $sub->select(DB::raw(1))
                                    ->from('shopee_order_financials as f')
                                    ->whereColumn('f.order_id', 'orders.id')
                                    ->where('f.shop_id', $shopId);
                            });
                    });
                })
                ->selectRaw('COALESCE(products.name, order_items.product_name) as product_label, SUM(order_items.quantity) as qty')
                ->groupByRaw('COALESCE(products.name, order_items.product_name)')
                ->orderByDesc('qty')
                ->limit(8)
                ->get();

            $result[$mk] = [
                'label' => $mStart->translatedFormat('M Y'),
                'products' => $rows->map(fn ($r) => ['name' => $r->product_label, 'qty' => (int) $r->qty])->all(),
            ];
        }

        return $result;
    }

    private function monthlyTargets(int $shopId, array $monthKeys): array
    {
        if ($shopId <= 0) {
            return [];
        }

        return ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->whereIn('year_month', $monthKeys)
            ->get()
            ->keyBy('year_month')
            ->map(fn ($t) => [
                'target_gross' => (int) round($t->target_gross ?? 0),
                'target_net_profit' => (int) round($t->target_net_profit ?? 0),
                'target_units' => (int) ($t->target_units ?? 0),
                'ad_budget' => (int) round($t->ad_budget_cap ?? 0),
            ])
            ->all();
    }
}
