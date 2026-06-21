<?php

namespace App\Http\Controllers;

use App\Exports\ProfitOrdersExport;
use App\Models\Product;
use App\Services\Ceo\MonthlyTargetService;
use App\Services\Hpp\HppCompletenessService;
use App\Services\Reports\ActionCenterService;
use App\Services\Reports\MonitoringChartService;
use App\Services\Reports\MultiShopCompareService;
use App\Services\Imports\ProductPerformanceImportService;
use App\Services\Reports\BcgFunnelService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use App\Services\Reports\RetailRekapService;
use App\Services\Reports\ProductActionEngine;
use App\Services\Reports\ProductAnalysisService;
use App\Services\Reports\ProductProfitReportService;
use App\Services\Reports\ProductSkuClassifier;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringController extends Controller
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
        private readonly MonitoringChartService $chartService,
        private readonly ActionCenterService $actionCenter,
        private readonly MultiShopCompareService $multiShopCompare,
        private readonly ProductSkuClassifier $classifier,
        private readonly ProductActionEngine $actionEngine,
        private readonly RetailRekapService $retailRekap,
        private readonly BcgFunnelService $bcgFunnel,
        private readonly ProductPerformanceImportService $performanceImport,
        private readonly ProductAnalysisService $productAnalysis,
    ) {
    }

    public function actions(Request $request)
    {
        $report = $this->reportService->build($request);
        $actionCenter = $this->actionCenter->build($report);

        return view('hub.monitoring.actions', array_merge($report, [
            'action_center' => $actionCenter,
            'activeSection' => 'actions',
            'navZone' => 'harian',
        ]));
    }

    public function overview(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForOverview($report);
        $shopCompare = count(\App\Support\ShopeeShopContext::tokens()) > 1
            ? $this->multiShopCompare->compare($request)
            : [];
        $actionCenter = $this->actionCenter->build($report);
        $hppSummary = app(HppCompletenessService::class)->shopSummary();
        $targetDashboard = app(MonthlyTargetService::class)->dashboard($request);

        return view('hub.monitoring.overview', array_merge($report, [
            'charts' => $charts,
            'shop_compare' => $shopCompare,
            'action_center' => $actionCenter,
            'hpp_summary' => $hppSummary,
            'target_dashboard' => $targetDashboard,
            'activeSection' => 'overview',
            'navZone' => 'harian',
        ]));
    }

    public function shopee(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForShopee($report);

        return view('hub.monitoring.shopee', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'shopee',
            'navZone' => 'laporan',
        ]));
    }

    public function revenue(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForRevenue($report);

        return view('hub.monitoring.revenue', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'revenue',
            'navZone' => 'laporan',
        ]));
    }

    public function ads(Request $request)
    {
        $request->attributes->set('include_all_products', true);
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForAds($report, $request);

        return view('hub.monitoring.ads', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'ads',
            'navZone' => 'marketing',
        ]));
    }

    public function profit(Request $request)
    {
        if (strtolower((string) $request->query('export')) === 'xlsx') {
            return $this->export($request);
        }

        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForProfit($report);

        return view('hub.monitoring', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'profit',
            'navZone' => 'laporan',
        ]));
    }

    public function matrix(Request $request)
    {
        $report = $this->reportService->build($request);
        $products = $report['products'] ?? [];

        $quadrants = [
            'stars' => [],
            'maintain' => [],
            'fix_price' => [],
            'bleeders' => [],
            'other' => [],
        ];

        foreach ($products as $p) {
            $tier = $p['tier'] ?? $this->classifier->classify($p);
            $key = match ($tier) {
                ProductSkuClassifier::STAR => 'stars',
                ProductSkuClassifier::BLEEDER => 'bleeders',
                ProductSkuClassifier::FIX_PRICE => 'fix_price',
                ProductSkuClassifier::MAINTAIN => 'maintain',
                default => 'other',
            };
            $quadrants[$key][] = $p;
        }

        return view('hub.monitoring.matrix', array_merge($report, [
            'quadrants' => $quadrants,
            'activeSection' => 'matrix',
            'navZone' => 'marketing',
        ]));
    }

    public function productAnalysisIndex(Request $request)
    {
        $shopId = \App\Support\ShopeeShopContext::shopId();
        $search = $request->query('q');

        return view('hub.monitoring.product-analysis-index', [
            'products' => $this->productAnalysis->productPicker($shopId, is_string($search) ? $search : null),
            'search' => $search,
            'activeSection' => 'product-analysis',
            'navZone' => 'tools',
            'shop' => [
                'id' => $shopId,
                'label' => \App\Support\ShopeeShopContext::shopLabel($shopId),
            ],
        ]);
    }

    public function productAnalysis(Request $request, Product $product)
    {
        $data = $this->productAnalysis->build($request, $product);

        return view('hub.monitoring.product-analysis', array_merge($data, [
            'activeSection' => 'product-analysis',
            'navZone' => 'tools',
        ]));
    }

    public function product(Request $request, Product $product)
    {
        return redirect()->route('monitoring.product-analysis.show', array_merge(
            ['product' => $product->id],
            $request->query()
        ));
    }

    public function rekap(Request $request)
    {
        $mode = $request->query('mode') === 'compare' ? 'compare' : 'detail';
        $status = $request->query('status', 'completed');
        $available = $this->retailRekap->availableMonthKeys(24);

        $monthKeys = [];
        $selectedMonth = null;

        if ($mode === 'compare') {
            $monthKeys = $this->retailRekap->filterValidMonths((array) $request->query('compare', []), $available);
        } else {
            $selectedMonth = $this->retailRekap->normalizeMonth($request->query('month'));
            if ($selectedMonth && in_array($selectedMonth, $available, true)) {
                $monthKeys = [$selectedMonth];
            }
        }

        $rekap = count($monthKeys) > 0
            ? $this->retailRekap->buildForMonths($request, $monthKeys)
            : $this->retailRekap->emptyStructure($available);

        $summary = [];
        if (count($monthKeys) === 1) {
            $col = $rekap['columns'][$monthKeys[0]] ?? [];
            $summary = $this->retailRekap->summaryFromColumn($col);
        } elseif (count($monthKeys) > 1) {
            $summary = $this->aggregateCompareSummary($rekap['columns'] ?? []);
        }

        return view('hub.monitoring.rekap', [
            'rekap' => $rekap,
            'summary' => $summary,
            'rekap_mode' => $mode,
            'rekap_selected_month' => $selectedMonth,
            'rekap_compare_months' => $mode === 'compare' ? $monthKeys : [],
            'rekap_has_data' => count($monthKeys) > 0,
            'filters' => ['status' => $status],
            'shop' => ['label' => \App\Support\ShopeeShopContext::shopLabel(\App\Support\ShopeeShopContext::shopId())],
            'activeSection' => 'rekap',
            'navZone' => 'laporan',
        ]);
    }

    private function aggregateCompareSummary(array $columns): array
    {
        if ($columns === []) {
            return [];
        }

        $gross = array_sum(array_column($columns, 'gross'));
        $netProfit = array_sum(array_column($columns, 'net_profit'));
        $orders = array_sum(array_column($columns, 'orders'));

        return [
            'gross' => $gross,
            'net_profit' => $netProfit,
            'orders_count' => $orders,
            'aov_gross' => $orders > 0 ? (int) round($gross / $orders) : null,
            'gross_margin_pct' => $gross > 0
                ? array_sum(array_column($columns, 'gross_profit')) / $gross
                : null,
            'net_margin_pct' => $gross > 0 ? $netProfit / $gross : null,
        ];
    }

    public function bcg(Request $request)
    {
        $report = $this->reportService->build($request);
        $shopId = \App\Support\ShopeeShopContext::shopId();
        $bcg = $this->bcgFunnel->build(
            $shopId,
            \Carbon\Carbon::parse($report['filters']['start'])->startOfDay(),
            \Carbon\Carbon::parse($report['filters']['end'])->endOfDay(),
        );

        return view('hub.monitoring.bcg', array_merge($report, [
            'bcg' => $bcg,
            'activeSection' => 'bcg',
            'navZone' => 'marketing',
        ]));
    }

    public function importBcgPerformance(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $start = !empty($validated['period_start']) ? \Carbon\Carbon::parse($validated['period_start']) : null;
        $end = !empty($validated['period_end']) ? \Carbon\Carbon::parse($validated['period_end']) : null;

        $result = $this->performanceImport->import($validated['file'], $start, $end);

        return redirect()->route('monitoring.bcg')
            ->with('success', "Import Seller Center: {$result['imported']} SKU ({$result['period_start']} — {$result['period_end']}). Data import menimpa sync otomatis untuk periode yang sama.");
    }

    public function syncBcgPerformance(Request $request)
    {
        $shopId = \App\Support\ShopeeShopContext::shopId();
        if ($shopId <= 0) {
            return back()->with('error', 'Pilih toko aktif terlebih dahulu.');
        }

        $token = \App\Models\ShopeeToken::query()
            ->where('env', config('shopee.env', 'test'))
            ->forApp(\App\Models\ShopeeToken::APP_MAIN)
            ->where('shop_id', $shopId)
            ->orderByDesc('id')
            ->first();

        if (!$token) {
            return back()->with('error', 'Toko belum terhubung ke Shopee. Buka Integrasi Shopee.');
        }

        try {
            $client = ShopeeClient::fromConfig();
            $result = (new ShopeeBcgSyncService($client))->sync($token);

            $msg = "Sync BCG otomatis: {$result['saved']} SKU";
            if (($result['skipped'] ?? 0) > 0) {
                $msg .= " · {$result['skipped']} dilewati (sudah di-import Seller Center)";
            }
            $msg .= " · {$result['period_start']} — {$result['period_end']}";

            return redirect()->route('monitoring.bcg')->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Sync BCG gagal: ' . $e->getMessage());
        }
    }

    public function importSettlement(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $result = $this->performanceImport->importSettlementCsv($validated['file']);

        return redirect()->route('ceo.settlement')
            ->with('success', "Import dana dilepaskan: {$result['imported']} baris.");
    }

    public function saveProductTargets(Request $request)
    {
        $validated = $request->validate([
            'year_month' => 'required|date_format:Y-m',
            'targets' => 'required|array',
            'targets.*.product_id' => 'required|integer|exists:products,id',
            'targets.*.target_gross' => 'nullable|numeric|min:0',
            'targets.*.target_units' => 'nullable|integer|min:0',
        ]);

        $shopId = \App\Support\ShopeeShopContext::shopId();
        foreach ($validated['targets'] as $t) {
            \App\Models\ProductSalesTarget::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'product_id' => $t['product_id'],
                    'year_month' => $validated['year_month'],
                ],
                [
                    'target_gross' => $t['target_gross'] ?? null,
                    'target_units' => $t['target_units'] ?? null,
                ]
            );
        }

        return redirect()->route('monitoring.bcg')
            ->with('success', 'Target penjualan per SKU disimpan.');
    }

    public function executive(Request $request)
    {
        return redirect()->route('monitoring.index', $request->query());
    }

    /** @deprecated use overview() */
    public function index(Request $request)
    {
        return $this->overview($request);
    }

    public function export(Request $request)
    {
        $report = $this->reportService->build($request);
        $filters = $report['filters'];

        $rows = array_map(function ($p) {
            return [
                'Produk' => $p['name'] ?? '',
                'SKU' => $p['sku'] ?? '',
                'Tier' => $p['tier'] ?? '',
                'Rekomendasi' => $p['action']['title'] ?? '',
                'Qty' => $p['qty'] ?? 0,
                'Gross' => round($p['gross'] ?? 0),
                'Net' => round($p['net'] ?? 0),
                'HPP+Pack' => round($p['cogs'] ?? 0),
                'Ads' => round($p['ads_spend'] ?? 0),
                'Operasional' => round($p['operational'] ?? 0),
                'Laba Bersih' => round($p['net_profit'] ?? 0),
                'Margin' => $p['margin'] ?? 0,
                'ROAS' => $p['roas'] ?? '',
                'ACOS' => $p['acos'] ?? '',
            ];
        }, $report['products'] ?? []);

        $start = str_replace('-', '', $filters['start']);
        $end = str_replace('-', '', $filters['end']);

        return Excel::download(
            new ProfitOrdersExport($rows),
            "monitoring_produk_{$start}_{$end}.xlsx"
        );
    }
}
