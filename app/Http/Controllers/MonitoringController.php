<?php

namespace App\Http\Controllers;

use App\Exports\ProfitOrdersExport;
use App\Models\Product;
use App\Services\Reports\ActionCenterService;
use App\Services\Reports\MonitoringChartService;
use App\Services\Reports\MultiShopCompareService;
use App\Services\Imports\ProductPerformanceImportService;
use App\Services\Reports\BcgFunnelService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use App\Services\Reports\RetailRekapService;
use App\Services\Reports\ProductActionEngine;
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
    ) {
    }

    public function actions(Request $request)
    {
        $report = $this->reportService->build($request);
        $actionCenter = $this->actionCenter->build($report);

        return view('hub.monitoring.actions', array_merge($report, [
            'action_center' => $actionCenter,
            'activeSection' => 'actions',
        ]));
    }

    public function overview(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForOverview($report);
        $shopCompare = count(\App\Support\ShopeeShopContext::tokens()) > 1
            ? $this->multiShopCompare->compare($request)
            : [];

        return view('hub.monitoring.overview', array_merge($report, [
            'charts' => $charts,
            'shop_compare' => $shopCompare,
            'activeSection' => 'overview',
        ]));
    }

    public function shopee(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForShopee($report);

        return view('hub.monitoring.shopee', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'shopee',
        ]));
    }

    public function revenue(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForRevenue($report);

        return view('hub.monitoring.revenue', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'revenue',
        ]));
    }

    public function ads(Request $request)
    {
        $report = $this->reportService->build($request);
        $charts = $this->chartService->chartsForAds($report, $request);

        return view('hub.monitoring.ads', array_merge($report, [
            'charts' => $charts,
            'activeSection' => 'ads',
        ]));
    }

    public function profit(Request $request)
    {
        if (strtolower((string) $request->query('export')) === 'xlsx') {
            return $this->export($request);
        }

        $report = $this->reportService->build($request);

        return view('hub.monitoring', array_merge($report, [
            'activeSection' => 'profit',
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
        ]));
    }

    public function product(Request $request, Product $product)
    {
        $report = $this->reportService->build($request);
        $row = collect($report['products'] ?? [])->firstWhere('product_id', $product->id);

        if (!$row) {
            $row = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->external_sku ?? '',
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
                'missing_cost' => true,
            ];
            $row['tier'] = $this->classifier->classify($row);
            $row['action'] = $this->actionEngine->forProduct($row, false);
        }

        $shopId = \App\Support\ShopeeShopContext::shopId();
        $itemId = (int) ($product->external_item_id ?? 0);
        $row['links'] = [
            'product' => $itemId ? \App\Support\ShopeeLinkHelper::productUrl($shopId, $itemId) : null,
            'ads' => $itemId ? \App\Support\ShopeeLinkHelper::adsProductUrl($shopId, $itemId) : null,
        ];

        $simulations = $this->actionEngine->simulate($row, $row['action']['meta'] ?? []);

        return view('hub.monitoring.product', array_merge($report, [
            'product' => $product,
            'sku' => $row,
            'simulations' => $simulations,
            'activeSection' => 'profit',
        ]));
    }

    public function rekap(Request $request)
    {
        $report = $this->reportService->build($request);
        $rekap = $this->retailRekap->build($request);

        return view('hub.monitoring.rekap', array_merge($report, [
            'rekap' => $rekap,
            'activeSection' => 'rekap',
        ]));
    }

    public function bcg(Request $request)
    {
        $report = $this->reportService->build($request);
        $shopId = \App\Support\ShopeeShopContext::shopId();
        $bcg = $this->bcgFunnel->build($shopId);

        return view('hub.monitoring.bcg', array_merge($report, [
            'bcg' => $bcg,
            'activeSection' => 'bcg',
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
        $report = $this->reportService->build($request);
        $actionCenter = $this->actionCenter->build($report);
        $shopCompare = $this->multiShopCompare->compare($request);

        return view('hub.monitoring.executive', array_merge($report, [
            'action_center' => $actionCenter,
            'shop_compare' => $shopCompare,
            'activeSection' => 'executive',
        ]));
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
