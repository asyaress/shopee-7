<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Services\Ceo\RoasAdvisorService;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Menyusun paket data export (Excel multi-sheet + PDF) untuk halaman monitoring/CEO.
 */
class HubReportExportService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
        private readonly RoasAdvisorService $roasAdvisor,
        private readonly RetailRekapService $retailRekap,
        private readonly ProductAnalysisService $productAnalysis,
        private readonly ActionCenterService $actionCenter,
    ) {
    }

    /** @return array<string, mixed> */
    public function build(string $type, Request $request): array
    {
        return match ($type) {
            'profit' => $this->buildProfit($request),
            'roas' => $this->buildRoas($request),
            'rekap' => $this->buildRekap($request),
            'product-analysis' => $this->buildProductAnalysis($request),
            'overview' => $this->buildOverview($request),
            'actions' => $this->buildActions($request),
            'ads' => $this->buildAds($request),
            'revenue' => $this->buildRevenue($request),
            default => throw new \InvalidArgumentException('Tipe export tidak dikenal: ' . $type),
        };
    }

    /** @return array<string, mixed> */
    private function buildProfit(Request $request): array
    {
        $report = $this->reportService->build($request);
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];
        $meta = $report['meta'] ?? [];
        $fb = $report['fee_breakdown'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());

        $summaryRows = [
            ['Penjualan kotor', (int) round($s['gross'] ?? 0)],
            ['Penghasilan net', (int) round($s['net'] ?? 0)],
            ['Biaya platform', (int) round($s['fee_total'] ?? 0)],
            ['HPP + packaging', (int) round($s['cogs'] ?? 0)],
            ['Laba kotor', (int) round($s['gross_profit'] ?? 0)],
            ['Biaya iklan', (int) round($s['ads_total'] ?? 0)],
            ['Biaya operasional', (int) round($s['operational_total'] ?? 0)],
            ['Laba bersih', (int) round($s['net_profit'] ?? 0)],
            ['Margin bersih', $s['margin'] ?? null],
            ['ROAS bisnis', $s['roas'] ?? null],
            ['ACOS', $s['acos'] ?? null],
            ['Jumlah order', (int) ($s['orders_count'] ?? 0)],
            ['Unit terjual', (int) ($s['units_sold'] ?? 0)],
        ];

        $feeRows = [
            ['Administrasi', (int) round($fb['admin'] ?? 0)],
            ['Layanan', (int) round($fb['layanan'] ?? 0)],
            ['Proses', (int) round($fb['proses'] ?? 0)],
            ['Program hemat', (int) round($fb['program_hemat'] ?? 0)],
            ['Total fee', (int) round($s['fee_total'] ?? 0)],
        ];

        $productHeadings = [
            'Produk', 'SKU', 'Tier', 'Rekomendasi', 'Qty', 'Kotor', 'Net',
            'HPP+Pack', 'Iklan', 'Operasional', 'Laba Bersih', 'Margin', 'ROAS', 'ACOS',
        ];
        $productTypes = ['text', 'text', 'text', 'text', 'num', 'rp', 'rp', 'rp', 'rp', 'rp', 'rp', 'pct', 'x', 'pct'];
        $productRows = [];
        foreach ($report['products'] ?? [] as $p) {
            $productRows[] = [
                $p['name'] ?? '',
                $p['sku'] ?? '',
                $p['tier'] ?? '',
                $p['action']['title'] ?? '',
                (int) ($p['qty'] ?? 0),
                (int) round($p['gross'] ?? 0),
                (int) round($p['net'] ?? 0),
                (int) round($p['cogs'] ?? 0),
                (int) round($p['ads_spend'] ?? 0),
                (int) round($p['operational'] ?? 0),
                (int) round($p['net_profit'] ?? 0),
                $p['margin'] ?? null,
                $p['roas'] ?? null,
                $p['acos'] ?? null,
            ];
        }

        $period = $meta['period_label'] ?? $this->periodLabel($filters);
        $filename = 'laporan_laba_' . $this->slugPeriod($filters);

        return $this->package(
            title: 'Laporan Laba Detail',
            subtitle: 'Analisis profitabilitas per produk',
            filename: $filename,
            meta: $this->baseMeta($shopLabel, $period, $filters),
            kpis: $this->kpiFromSummary($s),
            sections: [
                $this->section('Ringkasan Keuangan', ['Metrik', 'Nilai'], $this->displaySummaryRows($summaryRows)),
                $this->section('Rincian Biaya Platform', ['Komponen', 'Nominal'], $this->displayFeeRows($feeRows)),
                $this->section('Detail Produk', $productHeadings, $this->displayProductRows($productRows, $productTypes)),
            ],
            sheets: [
                [
                    'title' => 'Ringkasan',
                    'headings' => ['Metrik', 'Nilai'],
                    'rows' => $summaryRows,
                    'column_types' => ['text', 'rp'],
                ],
                [
                    'title' => 'Biaya Platform',
                    'headings' => ['Komponen', 'Nominal'],
                    'rows' => $feeRows,
                    'column_types' => ['text', 'rp'],
                ],
                [
                    'title' => 'Produk',
                    'headings' => $productHeadings,
                    'rows' => $productRows,
                    'column_types' => $productTypes,
                ],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildRoas(Request $request): array
    {
        $request->attributes->set('include_all_products', true);
        $report = $this->reportService->build($request);
        $roas = $this->roasAdvisor->shopAdvice($report);
        $sc = $roas['scorecard'] ?? [];
        $filters = $report['filters'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());
        $period = $roas['period_label'] ?? $this->periodLabel($filters);

        $scoreRows = [
            ['Set ROAS Shopee (rekom.)', $sc['set_roas_shopee'] ?? null],
            ['Shopee ROAS sekarang', $sc['shopee_roas_now'] ?? null],
            ['Business ROAS sekarang', $sc['business_roas_now'] ?? null],
            ['Target ROAS bisnis', $sc['target_roas_gross'] ?? null],
            ['Impas ROAS bisnis', $sc['breakeven_roas_gross'] ?? null],
            ['Spend iklan', (int) round($sc['ads_spend'] ?? 0)],
            ['GMV atribusi iklan', (int) round($sc['ads_gmv'] ?? 0)],
            ['Margin profit (HPP)', isset($sc['margin_profit_pct']) ? ((float) $sc['margin_profit_pct'] / 100) : null],
            ['Laba bersih', (int) round($sc['net_profit'] ?? 0)],
            ['ACOS', $sc['acos'] ?? null],
        ];

        $headings = [
            'Produk', 'Aksi', 'Petunjuk', 'Spend Iklan', 'GMV AMS', 'Shopee ROAS',
            'Business ROAS', 'Set ROAS', 'Laba Bersih', 'Margin', 'Qty', 'Kotor',
        ];
        $types = ['text', 'text', 'text', 'rp', 'rp', 'x', 'x', 'x', 'rp', 'pct', 'num', 'rp'];
        $rows = [];
        foreach ($roas['products'] ?? [] as $p) {
            $a = $p['action'] ?? [];
            $rows[] = [
                $p['name'] ?? '',
                $a['label'] ?? '',
                $a['hint'] ?? '',
                (int) round($p['spend'] ?? 0),
                (int) round($p['gmv'] ?? 0),
                $p['shopee_roas'] ?? null,
                $p['business_roas'] ?? null,
                $p['set_roas_shopee'] ?? null,
                (int) round($p['net_profit'] ?? 0),
                $p['margin'] ?? null,
                (int) ($p['qty'] ?? 0),
                (int) round($p['gross'] ?? 0),
            ];
        }

        return $this->package(
            title: 'Laporan Analisa Iklan (ROAS)',
            subtitle: 'Rekomendasi set ROAS & aksi per produk',
            filename: 'laporan_roas_' . $this->slugPeriod($filters),
            meta: $this->baseMeta($shopLabel, $period, $filters),
            kpis: [
                ['label' => 'Set ROAS Shopee', 'value' => isset($sc['set_roas_shopee']) ? number_format($sc['set_roas_shopee'], 1) . 'x' : '—'],
                ['label' => 'Spend iklan', 'value' => hub_rp($sc['ads_spend'] ?? 0, true)],
                ['label' => 'Laba bersih', 'value' => hub_rp($sc['net_profit'] ?? 0, true)],
                ['label' => 'Produk', 'value' => (string) count($rows)],
            ],
            sections: [
                $this->section('Scorecard ROAS', ['Metrik', 'Nilai'], $this->displayScoreRows($scoreRows)),
                $this->section('Rekomendasi Per Produk', $headings, $this->displayProductRows($rows, $types)),
            ],
            sheets: [
                ['title' => 'Scorecard', 'headings' => ['Metrik', 'Nilai'], 'rows' => $scoreRows, 'column_types' => ['text', 'x']],
                ['title' => 'Produk ROAS', 'headings' => $headings, 'rows' => $rows, 'column_types' => $types],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildRekap(Request $request): array
    {
        $mode = $request->query('mode') === 'compare' ? 'compare' : 'detail';
        $available = $this->retailRekap->availableMonthKeys(24);
        $monthKeys = $mode === 'compare'
            ? $this->retailRekap->filterValidMonths((array) $request->query('compare', []), $available)
            : array_values(array_filter([$this->retailRekap->normalizeMonth($request->query('month'))]));

        if ($monthKeys === []) {
            $monthKeys = array_slice($available, -6);
        }

        $rekap = $this->retailRekap->buildForMonths($request, $monthKeys);
        $months = $rekap['months'] ?? [];
        $columns = $rekap['columns'] ?? [];
        $metrics = $rekap['metrics'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());

        $monthLabels = array_map(fn ($mk) => $columns[$mk]['short'] ?? $mk, $months);
        $metricHeadings = array_merge(['Metrik'], $monthLabels);
        $metricTypes = array_merge(['text'], array_fill(0, count($months), 'rp'));
        $metricRows = [];

        foreach ($metrics as $m) {
            $row = [$m['label'] ?? $m['key']];
            $fmt = $m['format'] ?? 'rp';
            foreach ($months as $mk) {
                $val = $columns[$mk][$m['key']] ?? null;
                $row[] = $val;
            }
            $metricRows[] = $row;
            $metricTypes = $this->mergeColumnTypes($metricTypes, $fmt, count($months));
        }

        $bestHeadings = ['Bulan', 'Produk', 'Qty Terjual'];
        $bestRows = [];
        foreach ($rekap['best_sellers'] ?? [] as $mk => $block) {
            $label = $block['label'] ?? $mk;
            foreach ($block['products'] ?? [] as $item) {
                $bestRows[] = [
                    $label,
                    $item['name'] ?? '',
                    (int) ($item['qty'] ?? 0),
                ];
            }
        }

        $periodLabel = count($months) === 1
            ? ($columns[$months[0]]['label'] ?? $months[0])
            : count($months) . ' bulan (' . ($monthLabels[0] ?? '') . ' — ' . ($monthLabels[count($monthLabels) - 1] ?? '') . ')';

        return $this->package(
            title: 'Rekap Bulanan',
            subtitle: $mode === 'compare' ? 'Perbandingan multi-bulan' : 'Detail satu bulan',
            filename: 'rekap_bulanan_' . ($months[0] ?? 'data') . (count($months) > 1 ? '_compare' : ''),
            meta: array_merge($this->baseMeta($shopLabel, $periodLabel, ['status' => $request->query('status', 'completed')]), [
                ['label' => 'Mode', 'value' => $mode === 'compare' ? 'Bandingkan bulan' : 'Detail bulan'],
                ['label' => 'Jumlah bulan', 'value' => (string) count($months)],
            ]),
            kpis: count($months) === 1 && isset($columns[$months[0]])
                ? $this->kpiFromRekapColumn($columns[$months[0]])
                : [],
            sections: [
                $this->section('Metrik Bulanan', $metricHeadings, $this->displayMetricRows($metricRows, $metrics)),
                $this->section('Best Seller', $bestHeadings, $this->displaySimpleRows($bestRows, ['text', 'text', 'num'])),
            ],
            sheets: [
                ['title' => 'Metrik', 'headings' => $metricHeadings, 'rows' => $metricRows, 'column_types' => $metricTypes],
                ['title' => 'Best Seller', 'headings' => $bestHeadings, 'rows' => $bestRows, 'column_types' => ['text', 'text', 'num']],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildProductAnalysis(Request $request): array
    {
        $productId = (int) $request->query('product');
        if ($productId <= 0) {
            throw new \InvalidArgumentException('Parameter product wajib diisi.');
        }

        $product = Product::query()->findOrFail($productId);
        $data = $this->productAnalysis->build($request, $product);
        $s = $data['sku'] ?? [];
        $r = $data['roas'] ?? [];
        $filters = $data['filters'] ?? [];
        $shopLabel = $data['shop']['label'] ?? ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());
        $period = $this->periodLabel($filters);

        $financeRows = [
            ['Penjualan kotor', (int) round($s['gross'] ?? 0)],
            ['Net (alokasi)', (int) round($s['net'] ?? 0)],
            ['HPP + packaging', (int) round($s['cogs'] ?? 0)],
            ['Laba kotor', (int) round($s['gross_profit'] ?? 0)],
            ['Iklan (alokasi)', (int) round($s['ads_spend'] ?? 0)],
            ['Operasional (alokasi)', (int) round($s['operational'] ?? 0)],
            ['Laba bersih', (int) round($s['net_profit'] ?? 0)],
            ['Margin bersih', $s['margin'] ?? null],
            ['Qty terjual', (int) ($s['qty'] ?? 0)],
        ];

        $roasRows = [
            ['Spend iklan', (int) round($r['spend'] ?? 0)],
            ['GMV AMS', (int) round($r['gmv_ams'] ?? 0)],
            ['Shopee ROAS', $r['shopee_roas'] ?? null],
            ['Business ROAS', $r['business_roas'] ?? null],
            ['Set ROAS Shopee', $r['set_roas_shopee'] ?? null],
            ['Target ROAS bisnis', $r['target_business'] ?? null],
            ['ACOS', $r['acos'] ?? null],
        ];

        $variantHeadings = ['Varian', 'Model ID', 'Qty', 'Kotor', 'HPP', 'Iklan', 'Laba Bersih', 'Margin'];
        $variantRows = [];
        foreach ($data['variants'] ?? [] as $v) {
            $variantRows[] = [
                $v['name'] ?? '',
                $v['model_id'] ?? '',
                (int) ($v['qty'] ?? 0),
                (int) round($v['gross'] ?? 0),
                (int) round($v['cogs'] ?? 0),
                (int) round($v['ads'] ?? 0),
                (int) round($v['net_profit'] ?? 0),
                $v['margin'] ?? null,
            ];
        }

        $monthHeadings = ['Bulan', 'Qty', 'Kotor', 'Iklan', 'ROAS'];
        $monthRows = [];
        foreach ($data['monthly'] ?? [] as $m) {
            $monthRows[] = [
                $m['label'] ?? $m['month'],
                (int) ($m['qty'] ?? 0),
                (int) round($m['gross'] ?? 0),
                (int) round($m['ads'] ?? 0),
                $m['roas'] ?? null,
            ];
        }

        $action = $s['action'] ?? [];
        $slug = 'analisis_' . preg_replace('/[^a-z0-9]+/i', '_', mb_strtolower($product->name ?? 'produk'));

        return $this->package(
            title: 'Analisis Produk',
            subtitle: $product->name ?? '',
            filename: mb_substr($slug, 0, 60) . '_' . $this->slugPeriod($filters),
            meta: array_merge($this->baseMeta($shopLabel, $period, $filters), [
                ['label' => 'SKU', 'value' => $s['sku'] ?? $product->external_sku ?? '—'],
                ['label' => 'ID Shopee', 'value' => (string) ($product->external_item_id ?? '—')],
                ['label' => 'Rekomendasi', 'value' => $action['title'] ?? '—'],
            ]),
            kpis: [
                ['label' => 'Laba bersih', 'value' => hub_rp($s['net_profit'] ?? 0, true)],
                ['label' => 'Kotor', 'value' => hub_rp($s['gross'] ?? 0, true)],
                ['label' => 'Set ROAS', 'value' => isset($r['set_roas_shopee']) ? number_format($r['set_roas_shopee'], 2) . 'x' : '—'],
                ['label' => 'Qty', 'value' => hub_num($s['qty'] ?? 0)],
            ],
            sections: [
                $this->section('Keuangan Produk', ['Metrik', 'Nilai'], $this->displaySummaryRows($financeRows)),
                $this->section('Iklan & ROAS', ['Metrik', 'Nilai'], $this->displayScoreRows($roasRows)),
                $this->section('Tren Bulanan', $monthHeadings, $this->displaySimpleRows($monthRows, ['text', 'num', 'rp', 'rp', 'x'])),
                $this->section('Breakdown Varian', $variantHeadings, $this->displaySimpleRows($variantRows, ['text', 'text', 'num', 'rp', 'rp', 'rp', 'rp', 'pct'])),
            ],
            sheets: [
                ['title' => 'Keuangan', 'headings' => ['Metrik', 'Nilai'], 'rows' => $financeRows, 'column_types' => ['text', 'rp']],
                ['title' => 'ROAS', 'headings' => ['Metrik', 'Nilai'], 'rows' => $roasRows, 'column_types' => ['text', 'x']],
                ['title' => 'Tren Bulan', 'headings' => $monthHeadings, 'rows' => $monthRows, 'column_types' => ['text', 'num', 'rp', 'rp', 'x']],
                ['title' => 'Varian', 'headings' => $variantHeadings, 'rows' => $variantRows, 'column_types' => ['text', 'text', 'num', 'rp', 'rp', 'rp', 'rp', 'pct']],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildOverview(Request $request): array
    {
        $report = $this->reportService->build($request);
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());

        $headings = ['Produk', 'Tier', 'Qty', 'Kotor', 'Laba Bersih', 'Margin', 'Rekomendasi'];
        $types = ['text', 'text', 'num', 'rp', 'rp', 'pct', 'text'];
        $rows = [];
        foreach (array_slice($report['products'] ?? [], 0, 50) as $p) {
            $rows[] = [
                $p['name'] ?? '',
                $p['tier'] ?? '',
                (int) ($p['qty'] ?? 0),
                (int) round($p['gross'] ?? 0),
                (int) round($p['net_profit'] ?? 0),
                $p['margin'] ?? null,
                $p['action']['title'] ?? '',
            ];
        }

        return $this->package(
            title: 'Ringkasan Harian (Overview)',
            subtitle: 'Dashboard KPI & produk prioritas',
            filename: 'overview_' . $this->slugPeriod($filters),
            meta: $this->baseMeta($shopLabel, $this->periodLabel($filters), $filters),
            kpis: $this->kpiFromSummary($s),
            sections: [
                $this->section('Top Produk (max 50)', $headings, $this->displayProductRows($rows, $types)),
            ],
            sheets: [
                [
                    'title' => 'Ringkasan KPI',
                    'headings' => ['Metrik', 'Nilai'],
                    'rows' => $this->summaryRowsFromReport($s),
                    'column_types' => ['text', 'rp'],
                ],
                ['title' => 'Produk', 'headings' => $headings, 'rows' => $rows, 'column_types' => $types],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildActions(Request $request): array
    {
        $report = $this->reportService->build($request);
        $center = $this->actionCenter->build($report);
        $filters = $report['filters'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());

        $headings = ['Prioritas', 'Produk', 'Tier', 'Aksi', 'Alasan', 'Laba Bersih', 'Kotor'];
        $types = ['num', 'text', 'text', 'text', 'text', 'rp', 'rp'];
        $rows = [];
        $allItems = array_merge(
            $center['urgent'] ?? [],
            $center['opportunities'] ?? [],
            $center['bleeders'] ?? [],
        );
        foreach ($allItems as $i => $item) {
            $rows[] = [
                $i + 1,
                $item['name'] ?? '',
                $item['tier'] ?? '',
                $item['action']['title'] ?? '',
                implode('; ', $item['action']['reasons'] ?? []),
                (int) round($item['net_profit'] ?? 0),
                (int) round($item['gross'] ?? 0),
            ];
        }

        return $this->package(
            title: 'Pusat Aksi Produk',
            subtitle: 'Prioritas keputusan CEO',
            filename: 'pusat_aksi_' . $this->slugPeriod($filters),
            meta: $this->baseMeta($shopLabel, $this->periodLabel($filters), $filters),
            kpis: [
                ['label' => 'Item aksi', 'value' => (string) count($rows)],
                ['label' => 'Urgent', 'value' => (string) ($center['counts']['urgent'] ?? 0)],
            ],
            sections: [
                $this->section('Daftar Aksi', $headings, $this->displayProductRows($rows, $types)),
            ],
            sheets: [
                ['title' => 'Aksi', 'headings' => $headings, 'rows' => $rows, 'column_types' => $types],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildAds(Request $request): array
    {
        $request->attributes->set('include_all_products', true);
        $report = $this->reportService->build($request);
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());

        $headings = ['Produk', 'Spend Iklan', 'Kotor', 'ROAS', 'ACOS', 'Laba Bersih', 'Qty'];
        $types = ['text', 'rp', 'rp', 'x', 'pct', 'rp', 'num'];
        $rows = [];
        foreach ($report['products'] ?? [] as $p) {
            if ((float) ($p['ads_spend'] ?? 0) <= 0 && (float) ($p['gross'] ?? 0) <= 0) {
                continue;
            }
            $rows[] = [
                $p['name'] ?? '',
                (int) round($p['ads_spend'] ?? 0),
                (int) round($p['gross'] ?? 0),
                $p['roas'] ?? null,
                $p['acos'] ?? null,
                (int) round($p['net_profit'] ?? 0),
                (int) ($p['qty'] ?? 0),
            ];
        }

        usort($rows, fn ($a, $b) => ($b[1] ?? 0) <=> ($a[1] ?? 0));

        return $this->package(
            title: 'Laporan Iklan',
            subtitle: 'Performa iklan per produk',
            filename: 'laporan_iklan_' . $this->slugPeriod($filters),
            meta: $this->baseMeta($shopLabel, $this->periodLabel($filters), $filters),
            kpis: [
                ['label' => 'Total spend', 'value' => hub_rp($s['ads_total'] ?? 0, true)],
                ['label' => 'ROAS toko', 'value' => isset($s['roas']) ? number_format($s['roas'], 2) . 'x' : '—'],
                ['label' => 'Produk', 'value' => (string) count($rows)],
            ],
            sections: [
                $this->section('Iklan Per Produk', $headings, $this->displayProductRows($rows, $types)),
            ],
            sheets: [
                [
                    'title' => 'Ringkasan',
                    'headings' => ['Metrik', 'Nilai'],
                    'rows' => [
                        ['Total spend iklan', (int) round($s['ads_total'] ?? 0)],
                        ['ROAS bisnis', $s['roas'] ?? null],
                        ['ACOS', $s['acos'] ?? null],
                        ['Laba bersih', (int) round($s['net_profit'] ?? 0)],
                    ],
                    'column_types' => ['text', 'rp'],
                ],
                ['title' => 'Per Produk', 'headings' => $headings, 'rows' => $rows, 'column_types' => $types],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function buildRevenue(Request $request): array
    {
        $report = $this->reportService->build($request);
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];
        $fb = $report['fee_breakdown'] ?? [];
        $shopLabel = ShopeeShopContext::shopLabel(ShopeeShopContext::shopId());

        $rows = [
            ['Penjualan kotor', (int) round($s['gross'] ?? 0)],
            ['Penghasilan net', (int) round($s['net'] ?? 0)],
            ['Biaya platform', (int) round($s['fee_total'] ?? 0)],
            ['  Administrasi', (int) round($fb['admin'] ?? 0)],
            ['  Layanan', (int) round($fb['layanan'] ?? 0)],
            ['  Proses', (int) round($fb['proses'] ?? 0)],
            ['  Program hemat', (int) round($fb['program_hemat'] ?? 0)],
            ['Order', (int) ($s['orders_count'] ?? 0)],
            ['Unit', (int) ($s['units_sold'] ?? 0)],
        ];

        return $this->package(
            title: 'Laporan Pendapatan',
            subtitle: 'Alur pendapatan kotor ke net',
            filename: 'laporan_pendapatan_' . $this->slugPeriod($filters),
            meta: $this->baseMeta($shopLabel, $this->periodLabel($filters), $filters),
            kpis: [
                ['label' => 'Kotor', 'value' => hub_rp($s['gross'] ?? 0, true)],
                ['label' => 'Net', 'value' => hub_rp($s['net'] ?? 0, true)],
                ['label' => 'Fee', 'value' => hub_rp($s['fee_total'] ?? 0, true)],
            ],
            sections: [
                $this->section('Rincian Pendapatan', ['Metrik', 'Nilai'], $this->displaySummaryRows($rows)),
            ],
            sheets: [
                ['title' => 'Pendapatan', 'headings' => ['Metrik', 'Nilai'], 'rows' => $rows, 'column_types' => ['text', 'rp']],
            ],
        );
    }

    /** @param list<array{label: string, value: string}> $meta @param list<array{label: string, value: string}> $kpis @param list<array<string, mixed>> $sections @param list<array<string, mixed>> $sheets */
    private function package(
        string $title,
        string $subtitle,
        string $filename,
        array $meta,
        array $kpis,
        array $sections,
        array $sheets,
    ): array {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'filename' => preg_replace('/[^a-z0-9_\-]+/i', '_', $filename) ?: 'laporan',
            'meta' => $meta,
            'kpis' => $kpis,
            'sections' => $sections,
            'sheets' => $sheets,
        ];
    }

    /** @param array<string, mixed> $filters @return list<array{label: string, value: string}> */
    private function baseMeta(string $shop, string $period, array $filters): array
    {
        return [
            ['label' => 'Toko', 'value' => $shop],
            ['label' => 'Periode', 'value' => $period],
            ['label' => 'Status order', 'value' => (string) ($filters['status'] ?? 'completed')],
            ['label' => 'Jenis transaksi', 'value' => (string) ($filters['jenis'] ?? 'Shopee')],
        ];
    }

    /** @param array<string, mixed> $s @return list<array{label: string, value: string}> */
    private function kpiFromSummary(array $s): array
    {
        return [
            ['label' => 'Laba bersih', 'value' => hub_rp($s['net_profit'] ?? 0, true)],
            ['label' => 'Penjualan kotor', 'value' => hub_rp($s['gross'] ?? 0, true)],
            ['label' => 'Margin', 'value' => hub_pct($s['margin'] ?? null)],
            ['label' => 'ROAS', 'value' => isset($s['roas']) ? number_format($s['roas'], 2) . 'x' : '—'],
        ];
    }

    /** @param array<string, mixed> $col @return list<array{label: string, value: string}> */
    private function kpiFromRekapColumn(array $col): array
    {
        return [
            ['label' => 'Laba bersih', 'value' => hub_rp($col['net_profit'] ?? 0, true)],
            ['label' => 'Kotor', 'value' => hub_rp($col['gross'] ?? 0, true)],
            ['label' => 'Order', 'value' => hub_num($col['orders'] ?? 0)],
            ['label' => 'ROAS', 'value' => isset($col['roas']) ? number_format($col['roas'], 2) . 'x' : '—'],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function periodLabel(array $filters): string
    {
        $start = $filters['start'] ?? null;
        $end = $filters['end'] ?? null;
        if (!$start || !$end) {
            return '—';
        }

        try {
            $s = Carbon::parse($start)->translatedFormat('d M Y');
            $e = Carbon::parse($end)->translatedFormat('d M Y');

            return $s === $e ? $s : "{$s} — {$e}";
        } catch (\Throwable) {
            return "{$start} — {$end}";
        }
    }

    /** @param array<string, mixed> $filters */
    private function slugPeriod(array $filters): string
    {
        $start = str_replace('-', '', (string) ($filters['start'] ?? 'start'));
        $end = str_replace('-', '', (string) ($filters['end'] ?? 'end'));

        return $start . '_' . $end;
    }

    /** @param array<string, mixed> $s @return list<list<mixed>> */
    private function summaryRowsFromReport(array $s): array
    {
        return [
            ['Penjualan kotor', (int) round($s['gross'] ?? 0)],
            ['Penghasilan net', (int) round($s['net'] ?? 0)],
            ['Laba bersih', (int) round($s['net_profit'] ?? 0)],
            ['Spend iklan', (int) round($s['ads_total'] ?? 0)],
            ['ROAS', $s['roas'] ?? null],
            ['Order', (int) ($s['orders_count'] ?? 0)],
        ];
    }

    /** @param list<list<mixed>> $rows @return list<list<string>> */
    private function displaySummaryRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $val = $row[1] ?? null;
            if (is_numeric($val) && !str_contains((string) $row[0], 'Margin') && !str_contains((string) $row[0], 'ROAS') && !str_contains((string) $row[0], 'ACOS') && !str_contains((string) $row[0], 'Qty') && !str_contains((string) $row[0], 'order') && !str_contains((string) $row[0], 'Unit')) {
                $display = hub_rp($val);
            } elseif (str_contains((string) $row[0], 'Margin') || str_contains((string) $row[0], 'ACOS')) {
                $display = is_numeric($val) ? hub_pct((float) $val) : '—';
            } elseif (str_contains((string) $row[0], 'ROAS')) {
                $display = is_numeric($val) ? number_format((float) $val, 2) . 'x' : '—';
            } elseif (str_contains((string) $row[0], 'Qty') || str_contains((string) $row[0], 'order') || str_contains((string) $row[0], 'Unit')) {
                $display = hub_num($val);
            } else {
                $display = (string) ($val ?? '—');
            }
            $out[] = [$row[0], $display];
        }

        return $out;
    }

    /** @param list<list<mixed>> $rows @return list<list<string>> */
    private function displayFeeRows(array $rows): array
    {
        return array_map(fn ($r) => [$r[0], hub_rp($r[1] ?? 0)], $rows);
    }

    /** @param list<list<mixed>> $rows @return list<list<string>> */
    private function displayScoreRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $label = (string) $row[0];
            $val = $row[1] ?? null;
            if (str_contains($label, 'ROAS') || str_contains($label, 'Impas') || str_contains($label, 'Target')) {
                $display = is_numeric($val) ? number_format((float) $val, 2) . 'x' : '—';
            } elseif (str_contains($label, 'Margin') || str_contains($label, 'ACOS')) {
                $display = is_numeric($val) ? hub_pct((float) $val) : '—';
            } elseif (is_numeric($val) && (str_contains($label, 'Spend') || str_contains($label, 'GMV') || str_contains($label, 'Laba'))) {
                $display = hub_rp($val);
            } else {
                $display = is_numeric($val) ? hub_rp($val) : (string) ($val ?? '—');
            }
            $out[] = [$label, $display];
        }

        return $out;
    }

    /** @param list<list<mixed>> $rows @param list<string> $types @return list<list<string>> */
    private function displayProductRows(array $rows, array $types): array
    {
        $out = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($row as $i => $val) {
                $line[] = $this->formatDisplay($val, $types[$i] ?? 'text');
            }
            $out[] = $line;
        }

        return $out;
    }

    /** @param list<list<mixed>> $rows @param list<string> $types @return list<list<string>> */
    private function displaySimpleRows(array $rows, array $types): array
    {
        return $this->displayProductRows($rows, $types);
    }

    /** @param list<list<mixed>> $rows @param list<array<string, mixed>> $metrics @return list<list<string>> */
    private function displayMetricRows(array $rows, array $metrics): array
    {
        $out = [];
        foreach ($rows as $idx => $row) {
            $fmt = $metrics[$idx]['format'] ?? 'rp';
            $line = [(string) $row[0]];
            for ($i = 1; $i < count($row); $i++) {
                $line[] = $this->formatDisplay($row[$i], $fmt);
            }
            $out[] = $line;
        }

        return $out;
    }

    private function formatDisplay(mixed $val, string $type): string
    {
        if ($val === null || $val === '') {
            return '—';
        }

        return match ($type) {
            'rp' => hub_rp($val),
            'pct' => hub_pct((float) $val),
            'num' => hub_num($val),
            'x' => is_numeric($val) ? number_format((float) $val, 2) . 'x' : '—',
            default => (string) $val,
        };
    }

    /** @param list<string> $types */
    private function mergeColumnTypes(array $types, string $fmt, int $monthCount): array
    {
        $colType = match ($fmt) {
            'pct', 'x' => $fmt,
            'num' => 'num',
            default => 'rp',
        };
        $out = [$types[0] ?? 'text'];
        for ($i = 0; $i < $monthCount; $i++) {
            $out[] = $colType;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function section(string $title, array $headings, array $rows): array
    {
        return [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
        ];
    }
}
