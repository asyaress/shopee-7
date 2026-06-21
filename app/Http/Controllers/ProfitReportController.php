<?php

namespace App\Http\Controllers;

use App\Exports\ProfitOrdersExport;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Finance\ShopeeFinancialExtractor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProfitReportController extends Controller
{
    public function index(Request $request)
    {
        // allow export from same page (link: ?export=xlsx)
        if (strtolower((string) $request->query('export')) === 'xlsx') {
            return $this->export($request);
        }

        [$report, $filters] = $this->buildReport($request);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $totals = $report['totals'] ?? [];
        $feeBreakdown = $report['feeBreakdown'] ?? [
            'admin' => 0,
            'program_hemat' => 0,
            'layanan' => 0,
            'proses' => 0,
            'lainnya' => 0,
        ];

        $gross  = (float)($totals['gross'] ?? 0);
        $feeTot = (float)($totals['fee_total'] ?? 0);
        $net    = (float)($totals['net'] ?? 0);
        $cogs   = (float)($totals['cogs'] ?? 0);
        $profit = (float)($totals['profit'] ?? 0);

        // ✅ IMPORTANT: margin & take_rate disimpan sebagai RASIO (0..1), bukan persen.
        $summary = [
            'gross_sales' => (int) round($gross),
            'orders_count' => (int)($totals['orders_count'] ?? 0),
            'fees_total' => (int) round($feeTot),
            'take_rate' => $gross > 0 ? ($feeTot / $gross) : 0, // ratio
            'net_income' => (int) round($net),
            'cogs' => (int) round($cogs),
            'missing_cost_orders' => (int)($totals['missing_cost_orders'] ?? 0),
            'profit' => (int) round($profit),
            'margin' => $net > 0 ? ($profit / $net) : 0, // ratio
        ];

        $filtersMeta = [
            'status_options' => ['all', 'pending', 'in_progress', 'completed', 'cancelled'],
            'jenis_options' => ['all', 'shopee', 'non_shopee'],
            'products' => $products,
        ];

        // View uses 4-bucket breakdown
        $fees_breakdown = [
            'admin' => (int) round($feeBreakdown['admin'] ?? 0),
            'layanan' => (int) round($feeBreakdown['layanan'] ?? 0),
            'proses' => (int) round($feeBreakdown['proses'] ?? 0),
            'lainnya' => (int) round(($feeBreakdown['program_hemat'] ?? 0) + ($feeBreakdown['lainnya'] ?? 0)),
        ];

        $ordersModels = $report['orders'] ?? collect();
        $orderRows = collect($report['orderRows'] ?? []);

        $trend = $report['trendData'] ?? ['labels' => [], 'gross' => [], 'net' => [], 'profit' => []];
        $chart_trend = [
            'labels' => $trend['labels'] ?? [],
            'gross' => $trend['gross'] ?? [],
            'net' => $trend['net'] ?? [],
            'profit' => $trend['profit'] ?? [],
        ];

        $topProducts = $report['topProducts'] ?? [];
        $chart_top_products = [
            'labels' => array_values(array_map(fn($x) => $x['name'], $topProducts)),
            'profit' => array_values(array_map(fn($x) => (float)($x['profit'] ?? 0), $topProducts)),
            'net'    => array_values(array_map(fn($x) => (float)($x['net'] ?? 0), $topProducts)),
            'cogs'   => array_values(array_map(fn($x) => (float)($x['cogs'] ?? 0), $topProducts)),
        ];

        return view('reports.profit_pro', [
            ...$report,

            'filters' => $filters,
            'filtersMeta' => $filtersMeta,

            'summary' => $summary,
            'fees_breakdown' => $fees_breakdown,
            'top_products' => $topProducts,

            // Orders table uses computed rows
            'orders' => $orderRows,
            'ordersModels' => $ordersModels,

            'chart_trend' => $chart_trend,
            'chart_top_products' => $chart_top_products,

            'products' => $products,
        ]);
    }

    public function export(Request $request)
    {
        [$report, $filters] = $this->buildReport($request);

        $start = str_replace('-', '', $filters['start']);
        $end = str_replace('-', '', $filters['end']);

        return Excel::download(
            new ProfitOrdersExport($report['exportRows']),
            "profit_orders_{$start}_{$end}.xlsx"
        );
    }

    /**
     * Build profit report data (used by index + export).
     *
     * Profit = Net (Penghasilan) - (HPP + Packaging)
     * - Shopee: Net = order_income.escrow_amount_after_adjustment (fallback seller_income)
     * - Fee breakdown from order_income (fallback)
     */
    private function buildReport(Request $request): array
    {
        $today = Carbon::now();

        // Support old query params: start_date/end_date/jenis_transaksi
        $startRaw = (string) ($request->query('start') ?? $request->query('start_date') ?? '');
        $endRaw   = (string) ($request->query('end') ?? $request->query('end_date') ?? '');

        $startDate = $startRaw !== ''
            ? Carbon::parse($startRaw)->startOfDay()
            : $today->copy()->startOfMonth()->startOfDay();

        $endDate = $endRaw !== ''
            ? Carbon::parse($endRaw)->endOfDay()
            : $today->copy()->endOfDay();

        $status = (string) ($request->query('status') ?? 'completed');

        $jenisRaw = (string) ($request->query('jenis') ?? $request->query('jenis_transaksi') ?? 'all');
        $jenis = strtolower(trim($jenisRaw));
        // normalize values
        if ($jenis === '' || $jenis === 'all' || $jenis === 'all ') $jenis = 'all';
        if ($jenis === 'shopee') $jenis = 'shopee';
        if (in_array($jenis, ['nonshopee','non-shopee','non shopee','non_shopee'], true)) $jenis = 'non_shopee';
        if (!in_array($jenis, ['all','shopee','non_shopee'], true)) $jenis = 'all';

        $productId = $request->query('product_id') !== null
            ? (int) $request->query('product_id')
            : null;

        $q = Order::query()
            ->with(['orderItems', 'orderItems.product', 'shopeeFinancial'])
            ->whereBetween('order_date', [$startDate, $endDate]);

        if ($status !== 'all') {
            $q->where('status', $status);
        }

        if ($jenis !== 'all') {
            if ($jenis === 'shopee') {
                $q->whereRaw('LOWER(COALESCE(jenis_transaksi, "")) = ?', ['shopee']);
            } elseif ($jenis === 'non_shopee') {
                $q->whereRaw('LOWER(COALESCE(jenis_transaksi, "")) <> ?', ['shopee']);
            }
        }

        if ($productId) {
            $q->whereHas('orderItems', function ($qq) use ($productId) {
                $qq->where('product_id', $productId);
            });
        }

        $orders = $q->orderByDesc('order_date')->get();

        // caches (avoid N+1)
        $productByExternalItemId = [];
        $productByExternalSku = [];
        $productByNormName = [];
        $variantByKey = [];
        $variantByExternalModelId = [];

        // Preload products for map
        try {
            $productsForMap = Product::query()
                ->select(['id','name','external_item_id','external_sku','hpp_amount','packaging_type','packaging_value'])
                ->get();

            foreach ($productsForMap as $p) {
                if (!empty($p->external_item_id)) {
                    $productByExternalItemId[(string) $p->external_item_id] = $p;
                }
                if (!empty($p->external_sku)) {
                    $productByExternalSku[(string) $p->external_sku] = $p;
                }
                $norm = $this->normalizeName($p->name ?? '');
                if ($norm !== '' && !isset($productByNormName[$norm])) {
                    $productByNormName[$norm] = $p;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $orderRows = [];
        $exportRows = [];

        $trend = []; // date => [gross, net, profit]

        $feeBreakdown = [
            'admin' => 0.0,
            'program_hemat' => 0.0,
            'layanan' => 0.0,
            'proses' => 0.0,
            'lainnya' => 0.0,
        ];

        $totals = [
            'gross' => 0.0,
            'fee_total' => 0.0,
            'net' => 0.0,
            'cogs' => 0.0,
            'profit' => 0.0,
            'missing_cost_orders' => 0,
            'orders_count' => 0,
        ];

        $productAgg = []; // product_id => [name, qty, net, cogs, profit, missing_cost_lines]

        foreach ($orders as $order) {
            $totals['orders_count']++;

            $isShopee = strtolower((string) ($order->jenis_transaksi ?? '')) === 'shopee';
            $fin = $isShopee ? ShopeeFinancialExtractor::extract($order->shopeeFinancial) : ShopeeFinancialExtractor::empty();

            $items = $order->orderItems ?? collect();

            // gross from items
            $lineGrossSum = (float) $items->sum('total_amount');

            // Gross sales (Harga Produk): prefer Shopee gross_product, fallback to sum, fallback to order total
            $gross = $isShopee && (float)($fin['gross_product'] ?? 0) > 0
                ? (float) $fin['gross_product']
                : ($lineGrossSum > 0 ? $lineGrossSum : (float)($order->total_amount ?? 0));

            // Fee + Net
            $feeTotal = $isShopee ? (float) ($fin['fee_total'] ?? 0) : 0.0;
            $net = $isShopee ? (float) ($fin['net'] ?? 0) : (float) ($order->total_amount ?? 0);

            // fallback net
            if ($isShopee && $net <= 0 && $order->shopeeFinancial) {
                $net = (float) ($order->shopeeFinancial->seller_income ?? 0);
            }
            if (!$isShopee && $net <= 0) {
                $net = $gross;
            }

            // per-order buffers for allocation + detail UI
            $perOrderProductLines = []; // pid => [ [gross,cogs], ... ]
            $unknownLines = [];         // lines without product mapping
            $itemDetails = [];
            $missingCostLines = 0;

            // Compute COGS
            $cogs = 0.0;
            $missingCost = false;

            foreach ($items as $item) {
                $qty = (int) ($item->quantity ?? 0);
                if ($qty <= 0) continue;

                $unitPrice = (float) ($item->price ?? 0);
                $lineGross = (float) ($item->total_amount ?? 0);

                // Resolve product
                $product = $item->product;

                if (!$product && !empty($item->external_item_id)) {
                    $extItemId = (string) $item->external_item_id;
                    if (!array_key_exists($extItemId, $productByExternalItemId)) {
                        $productByExternalItemId[$extItemId] = Product::query()
                            ->where('external_platform', 'shopee')
                            ->where('external_item_id', $extItemId)
                            ->first();
                    }
                    $product = $productByExternalItemId[$extItemId] ?? null;
                }

                $extSku = (string) ($item->external_sku ?? '');
                if (!$product && $extSku !== '') {
                    if (isset($productByExternalSku[$extSku])) {
                        $product = $productByExternalSku[$extSku];
                    } else {
                        $product = Product::query()->where('external_sku', $extSku)->first();
                        if ($product) $productByExternalSku[$extSku] = $product;
                    }
                }

                $normName = $this->normalizeName($item->product_name ?? '');
                if (!$product && $normName !== '' && isset($productByNormName[$normName])) {
                    $product = $productByNormName[$normName];
                }

                if (!$product && !empty($item->product_name)) {
                    $product = Product::query()->where('name', $item->product_name)->first();
                    if ($product) {
                        $norm = $this->normalizeName($product->name ?? '');
                        if ($norm !== '') $productByNormName[$norm] = $product;
                        if (!empty($product->external_item_id)) $productByExternalItemId[(string) $product->external_item_id] = $product;
                        if (!empty($product->external_sku)) $productByExternalSku[(string) $product->external_sku] = $product;
                    }
                }

                // Resolve variant
                $variant = null;

                if ($product && !empty($item->external_model_id)) {
                    $key = $product->id . '|' . $item->external_model_id;
                    if (!array_key_exists($key, $variantByKey)) {
                        $variantByKey[$key] = ProductVariant::query()
                            ->where('product_id', $product->id)
                            ->where('external_model_id', $item->external_model_id)
                            ->first();
                    }
                    $variant = $variantByKey[$key] ?? null;
                } elseif (!empty($item->external_model_id)) {
                    $extModelId = (string) $item->external_model_id;
                    if (!array_key_exists($extModelId, $variantByExternalModelId)) {
                        $variantByExternalModelId[$extModelId] = ProductVariant::query()
                            ->where('external_model_id', $extModelId)
                            ->first();
                    }
                    $variant = $variantByExternalModelId[$extModelId] ?? null;
                    if (!$product && $variant) {
                        $product = Product::query()->find($variant->product_id);
                    }
                }

                // Compute COGS unit
                $hpp = $variant?->hpp_amount ?? $product?->hpp_amount;
                $packType = $variant?->packaging_type ?? $product?->packaging_type ?? 'fixed';
                $packVal = $variant?->packaging_value ?? $product?->packaging_value;

                $hpp = is_null($hpp) ? null : (float) $hpp;
                $packVal = is_null($packVal) ? null : (float) $packVal;

                $unitHpp = $hpp ?? 0.0;
                $unitPack = 0.0;

                if ($packVal !== null) {
                    $unitPack = $packType === 'percent'
                        ? round($unitPrice * ($packVal / 100), 2)
                        : $packVal;
                }

                $lineCogs = ($unitHpp + $unitPack) * $qty;
                $cogs += $lineCogs;

                // Missing cost rules
                $missingCostLine = (!$product || ($hpp === null && $packVal === null));
                if ($missingCostLine) {
                    $missingCost = true;
                    $missingCostLines++;
                }

                // ✅ Detail items for collapse UI
                $itemDetails[] = [
                    'product_name' => (string) ($item->product_name ?? ($product?->name ?? '-')),
                    'qty' => (int) $qty,
                    'unit_price' => (float) $unitPrice,
                    'subtotal' => (float) $lineGross,

                    'hpp_unit' => (float) $unitHpp,
                    'pack_unit' => (float) $unitPack,
                    'cogs' => (float) $lineCogs,

                    'missing_cost' => (bool) $missingCostLine,
                ];

                // Aggregation (mapped products)
                if ($product) {
                    $pid = (int) $product->id;

                    if (!isset($productAgg[$pid])) {
                        $productAgg[$pid] = [
                            'name' => $product->name,
                            'qty' => 0,
                            'net' => 0.0,
                            'cogs' => 0.0,
                            'profit' => 0.0,
                            'missing_cost_lines' => 0,
                        ];
                    }

                    $productAgg[$pid]['qty'] += $qty;
                    if ($missingCostLine) $productAgg[$pid]['missing_cost_lines']++;

                    if (!isset($perOrderProductLines[$pid])) $perOrderProductLines[$pid] = [];
                    $perOrderProductLines[$pid][] = [
                        'gross' => $lineGross,
                        'cogs' => $lineCogs,
                        'qty' => $qty,
                    ];
                } else {
                    // unmapped / unknown product line
                    $unknownLines[] = [
                        'gross' => $lineGross,
                        'cogs' => $lineCogs,
                        'qty' => $qty,
                        'missing_cost' => $missingCostLine,
                    ];
                }
            }

            // Allocate Net proportionally based on gross (include unknown lines too)
            $allocBase = $lineGrossSum > 0 ? $lineGrossSum : ($gross > 0 ? $gross : 0);

            if ($allocBase > 0) {
                // known products
                foreach ($perOrderProductLines as $pid => $lines) {
                    if (!isset($productAgg[$pid])) {
                        // guard (avoid undefined key)
                        $productAgg[$pid] = [
                            'name' => 'Produk #' . $pid,
                            'qty' => 0,
                            'net' => 0.0,
                            'cogs' => 0.0,
                            'profit' => 0.0,
                            'missing_cost_lines' => 0,
                        ];
                    }

                    foreach ($lines as $ln) {
                        $lineGross = (float)($ln['gross'] ?? 0);
                        $ratio = $lineGross > 0 ? ($lineGross / $allocBase) : 0.0;

                        $allocNet = $net * $ratio;
                        $allocProfit = $allocNet - (float)($ln['cogs'] ?? 0);

                        $productAgg[$pid]['net'] += $allocNet;
                        $productAgg[$pid]['cogs'] += (float)($ln['cogs'] ?? 0);
                        $productAgg[$pid]['profit'] += $allocProfit;
                    }
                }

                // unknown bucket (pid=0)
                if (!empty($unknownLines)) {
                    $pid = 0;
                    if (!isset($productAgg[$pid])) {
                        $productAgg[$pid] = [
                            'name' => '(Unmapped / Unknown Product)',
                            'qty' => 0,
                            'net' => 0.0,
                            'cogs' => 0.0,
                            'profit' => 0.0,
                            'missing_cost_lines' => 0,
                        ];
                    }

                    foreach ($unknownLines as $ln) {
                        $productAgg[$pid]['qty'] += (int)($ln['qty'] ?? 0);
                        if (!empty($ln['missing_cost'])) $productAgg[$pid]['missing_cost_lines']++;

                        $lineGross = (float)($ln['gross'] ?? 0);
                        $ratio = $lineGross > 0 ? ($lineGross / $allocBase) : 0.0;

                        $allocNet = $net * $ratio;
                        $allocProfit = $allocNet - (float)($ln['cogs'] ?? 0);

                        $productAgg[$pid]['net'] += $allocNet;
                        $productAgg[$pid]['cogs'] += (float)($ln['cogs'] ?? 0);
                        $productAgg[$pid]['profit'] += $allocProfit;
                    }
                }
            } else {
                // If cannot allocate by gross (all 0), put all net to unknown bucket to keep totals consistent
                if ($net > 0) {
                    $pid = 0;
                    if (!isset($productAgg[$pid])) {
                        $productAgg[$pid] = [
                            'name' => '(Unmapped / Unknown Product)',
                            'qty' => 0,
                            'net' => 0.0,
                            'cogs' => 0.0,
                            'profit' => 0.0,
                            'missing_cost_lines' => 0,
                        ];
                    }
                    $productAgg[$pid]['net'] += $net;
                    $productAgg[$pid]['cogs'] += $cogs;
                    $productAgg[$pid]['profit'] += ($net - $cogs);
                }
            }

            // Profit calculation
            $profit = $net - $cogs;
            $margin = $net > 0 ? ($profit / $net) : 0;     // ratio
            $takeRate = $gross > 0 ? ($feeTotal / $gross) : 0; // ratio

            // Update totals
            $totals['gross'] += $gross;
            $totals['fee_total'] += $feeTotal;
            $totals['net'] += $net;
            $totals['cogs'] += $cogs;
            $totals['profit'] += $profit;

            if ($missingCost) {
                $totals['missing_cost_orders'] += 1;
            }

            // Fee breakdown (Shopee only)
            if ($isShopee) {
                $feeBreakdown['admin'] += (float) ($fin['fee_admin'] ?? 0);
                $feeBreakdown['program_hemat'] += (float) ($fin['fee_program_hemat'] ?? 0);
                $feeBreakdown['layanan'] += (float) ($fin['fee_service'] ?? 0);
                $feeBreakdown['proses'] += (float) ($fin['fee_process'] ?? 0);
            }

            // Trend
            $dayKey = $order->order_date ? $order->order_date->format('Y-m-d') : 'unknown';
            if (!isset($trend[$dayKey])) {
                $trend[$dayKey] = ['gross' => 0.0, 'net' => 0.0, 'profit' => 0.0];
            }
            $trend[$dayKey]['gross'] += $gross;
            $trend[$dayKey]['net'] += $net;
            $trend[$dayKey]['profit'] += $profit;

            // Row for display (✅ includes items + detail url)
            $orderRows[] = [
                'date' => $order->order_date ? $order->order_date->format('d M Y') : '-',
                'order_number' => $order->order_number,
                'jenis' => $order->jenis_transaksi ?? '-',
                'status' => $order->status,

                'gross' => $gross,
                'fee' => $feeTotal,
                'net' => $net,
                'cogs' => $cogs,
                'profit' => $profit,
                'margin' => $margin,
                'take_rate' => $takeRate,

                'missing_cost' => $missingCost,
                'missing_cost_lines' => $missingCostLines,
                'items_count' => $items->count(),

                'detail_url' => url('/orders/' . $order->id),
                'items' => $itemDetails,

                'shopee' => $isShopee ? [
                    'gross_product' => (float) ($fin['gross_product'] ?? 0),
                    'fee_admin' => (float) ($fin['fee_admin'] ?? 0),
                    'fee_program_hemat' => (float) ($fin['fee_program_hemat'] ?? 0),
                    'fee_service' => (float) ($fin['fee_service'] ?? 0),
                    'fee_process' => (float) ($fin['fee_process'] ?? 0),
                    'fee_total' => (float) $feeTotal,
                    'net' => (float) $net,
                ] : null,
            ];

            // Export rows
            $exportRows[] = [
                'Tanggal' => $order->order_date ? $order->order_date->format('Y-m-d') : '',
                'Order #' => $order->order_number,
                'Jenis' => $order->jenis_transaksi ?? '',
                'Status' => $order->status,
                'Gross (Harga Produk)' => $gross,
                'Fee Total' => $feeTotal,
                'Net (Penghasilan)' => $net,
                'COGS (HPP+Packaging)' => $cogs,
                'Profit' => $profit,
                'Margin' => $margin,      // ratio
                'Take Rate' => $takeRate, // ratio
                'Missing Cost?' => $missingCost ? 'Missing' : 'OK',
            ];
        }

        // Final fee breakdown (seller-side)
        $feeBreakdown['lainnya'] = max(0, $totals['fee_total'] - (
            $feeBreakdown['admin'] + $feeBreakdown['program_hemat'] + $feeBreakdown['layanan'] + $feeBreakdown['proses']
        ));

        // Trend arrays ordered by date asc
        ksort($trend);
        $trendLabels = array_keys($trend);
        $trendGross  = array_map(fn ($k) => (float)$trend[$k]['gross'], $trendLabels);
        $trendNet    = array_map(fn ($k) => (float)$trend[$k]['net'], $trendLabels);
        $trendProfit = array_map(fn ($k) => (float)$trend[$k]['profit'], $trendLabels);

        $trendData = [
            'labels' => $trendLabels,
            'gross' => $trendGross,
            'net' => $trendNet,
            'profit' => $trendProfit,
        ];

        // Top products by profit
        uasort($productAgg, fn ($a, $b) => ($b['profit'] ?? 0) <=> ($a['profit'] ?? 0));
        $topProducts = array_slice($productAgg, 0, 15, true);

        $filters = [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'status' => $status,
            'jenis' => $jenis,
            'product_id' => $productId,
        ];

        return [[
            'orders' => $orders,
            'orderRows' => $orderRows,
            'exportRows' => $exportRows,
            'totals' => $totals,
            'feeBreakdown' => $feeBreakdown,
            'trendData' => $trendData,
            'topProducts' => array_values($topProducts),
        ], $filters];
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') return '';

        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/[^a-z0-9]+/u', ' ', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';

        return trim($name);
    }
}
