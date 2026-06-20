<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopMonthlyCost;
use App\Services\Finance\ShopeeFinancialExtractor;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** @see ProductSkuClassifier @see ProductActionEngine */

class ProductProfitReportService
{
    /**
     * Build full monitoring report: shop summary, monthly pivot, per-product rows.
     */
    public function build(Request $request): array
    {
        [$startDate, $endDate, $status, $jenis] = $this->parseFilters($request);

        $shopId = ShopeeShopContext::shopId();

        $orders = $this->queryOrders($startDate, $endDate, $status, $jenis);

        $productByExternalItemId = [];
        $productByExternalSku = [];
        $productByNormName = [];
        $variantByKey = [];
        $variantByExternalModelId = [];

        $this->preloadProductMaps($productByExternalItemId, $productByExternalSku, $productByNormName);

        $productAgg = [];
        $feeBreakdown = [
            'admin' => 0.0,
            'program_hemat' => 0.0,
            'layanan' => 0.0,
            'proses' => 0.0,
            'ams' => 0.0,
            'campaign' => 0.0,
            'premi' => 0.0,
            'seller_transaction' => 0.0,
            'buyer_transaction' => 0.0,
            'seller_discount' => 0.0,
            'voucher_seller' => 0.0,
            'refund' => 0.0,
        ];

        $totals = [
            'gross' => 0.0,
            'fee_total' => 0.0,
            'net' => 0.0,
            'cogs' => 0.0,
            'gross_profit' => 0.0,
            'orders_count' => 0,
            'units_sold' => 0,
            'missing_cost_orders' => 0,
        ];

        $monthly = [];
        $orderRows = [];

        foreach ($orders as $order) {
            $totals['orders_count']++;
            $isShopee = strtolower((string) ($order->jenis_transaksi ?? '')) === 'shopee';
            $fin = $isShopee ? ShopeeFinancialExtractor::extract($order->shopeeFinancial) : ShopeeFinancialExtractor::empty();

            $items = $order->orderItems ?? collect();
            $lineGrossSum = (float) $items->sum('total_amount');

            $gross = $isShopee && (float) ($fin['gross_product'] ?? 0) > 0
                ? (float) $fin['gross_product']
                : ($lineGrossSum > 0 ? $lineGrossSum : (float) ($order->total_amount ?? 0));

            $feeTotal = $isShopee ? (float) ($fin['fee_total'] ?? 0) : 0.0;
            $net = $isShopee ? (float) ($fin['net'] ?? 0) : (float) ($order->total_amount ?? 0);

            if ($isShopee && $net <= 0 && $order->shopeeFinancial) {
                $net = (float) ($order->shopeeFinancial->seller_income ?? 0);
            }
            if (!$isShopee && $net <= 0) {
                $net = $gross;
            }

            $cogs = 0.0;
            $missingCost = false;
            $perOrderProductLines = [];
            $unknownLines = [];

            $orderUnits = 0;
            foreach ($items as $item) {
                $qty = (int) ($item->quantity ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $orderUnits += $qty;

                $unitPrice = (float) ($item->price ?? 0);
                $lineGross = (float) ($item->total_amount ?? 0);

                [$product, $variant, $unitHpp, $unitPack, $lineCogs, $missingLine] = $this->resolveLineCost(
                    $item,
                    $unitPrice,
                    $qty,
                    $productByExternalItemId,
                    $productByExternalSku,
                    $productByNormName,
                    $variantByKey,
                    $variantByExternalModelId
                );

                $cogs += $lineCogs;
                if ($missingLine) {
                    $missingCost = true;
                }

                $line = ['gross' => $lineGross, 'cogs' => $lineCogs, 'qty' => $qty];

                if ($product) {
                    $pid = (int) $product->id;
                    if (!isset($perOrderProductLines[$pid])) {
                        $perOrderProductLines[$pid] = [];
                    }
                    $perOrderProductLines[$pid][] = $line;

                    if (!isset($productAgg[$pid])) {
                        $productAgg[$pid] = $this->emptyProductRow($product);
                    }
                    $productAgg[$pid]['qty'] += $qty;
                    if ($missingLine) {
                        $productAgg[$pid]['missing_cost'] = true;
                    }
                } else {
                    $unknownLines[] = array_merge($line, ['missing_cost' => $missingLine]);
                }
            }

            $allocBase = $lineGrossSum > 0 ? $lineGrossSum : ($gross > 0 ? $gross : 0);
            $this->allocateNetToProducts($productAgg, $perOrderProductLines, $unknownLines, $net, $allocBase);

            $grossProfit = $net - $cogs;
            $profit = $grossProfit;

            $totals['gross'] += $gross;
            $totals['fee_total'] += $feeTotal;
            $totals['net'] += $net;
            $totals['cogs'] += $cogs;
            $totals['gross_profit'] += $grossProfit;
            $totals['units_sold'] += $orderUnits;

            if ($missingCost) {
                $totals['missing_cost_orders']++;
            }

            if ($isShopee) {
                $feeBreakdown['admin'] += (float) ($fin['fee_admin'] ?? 0);
                $feeBreakdown['program_hemat'] += (float) ($fin['fee_program_hemat'] ?? 0);
                $feeBreakdown['layanan'] += (float) ($fin['fee_service'] ?? 0);
                $feeBreakdown['proses'] += (float) ($fin['fee_process'] ?? 0);
                $feeBreakdown['ams'] += (float) ($fin['fee_ams'] ?? 0);
                $feeBreakdown['campaign'] += (float) ($fin['fee_campaign'] ?? 0);
                $feeBreakdown['premi'] += (float) ($fin['fee_premi'] ?? 0);
                $feeBreakdown['seller_transaction'] += (float) ($fin['fee_seller_transaction'] ?? 0);
                $feeBreakdown['buyer_transaction'] += (float) ($fin['fee_buyer_transaction'] ?? 0);
                $feeBreakdown['seller_discount'] += (float) ($fin['seller_discount'] ?? 0);
                $feeBreakdown['voucher_seller'] += (float) ($fin['voucher_seller'] ?? 0);
                $feeBreakdown['refund'] += (float) ($fin['refund'] ?? 0);
            }

            $monthKey = $order->order_date ? $order->order_date->format('Y-m') : 'unknown';
            if (!isset($monthly[$monthKey])) {
                $monthly[$monthKey] = [
                    'label' => $order->order_date ? $order->order_date->translatedFormat('M Y') : $monthKey,
                    'gross' => 0.0,
                    'net' => 0.0,
                    'cogs' => 0.0,
                    'gross_profit' => 0.0,
                    'orders' => 0,
                    'units' => 0,
                ];
            }
            $monthly[$monthKey]['gross'] += $gross;
            $monthly[$monthKey]['net'] += $net;
            $monthly[$monthKey]['cogs'] += $cogs;
            $monthly[$monthKey]['gross_profit'] += $grossProfit;
            $monthly[$monthKey]['orders']++;
            $monthly[$monthKey]['units'] += $orderUnits;

            $orderRows[] = [
                'date' => $order->order_date?->format('d M Y') ?? '-',
                'order_number' => $order->order_number,
                'gross' => $gross,
                'net' => $net,
                'cogs' => $cogs,
                'profit' => $profit,
                'missing_cost' => $missingCost,
                'detail_url' => url('/orders/' . $order->id),
            ];
        }

        $operationalTotal = $this->sumOperationalForRange($shopId, $startDate, $endDate);
        $adsByProduct = $this->loadAdsByProduct($shopId, $startDate, $endDate);

        $netShop = max(0.00001, $totals['net']);

        $totalAds = $this->sumAdsForRange($shopId, $startDate, $endDate);
        foreach ($productAgg as $pid => &$row) {
            $extId = $row['external_item_id'] ?? '';
            $adsSpend = (float) ($adsByProduct[$pid] ?? $adsByProduct['ext:' . $extId] ?? 0);
            $row['ads_spend'] = $adsSpend;
            $itemId = (int) $extId;
            $row['links'] = [
                'product' => $itemId ? \App\Support\ShopeeLinkHelper::productUrl($shopId, $itemId) : null,
                'ads' => $itemId ? \App\Support\ShopeeLinkHelper::adsProductUrl($shopId, $itemId) : null,
            ];
            $row['operational'] = $operationalTotal > 0
                ? $operationalTotal * ((float) $row['net'] / $netShop)
                : 0.0;

            $row['gross_profit'] = (float) $row['net'] - (float) $row['cogs'];
            $row['net_profit'] = $row['gross_profit'] - $row['ads_spend'] - $row['operational'];
            $row['margin'] = (float) $row['net'] > 0 ? $row['net_profit'] / (float) $row['net'] : 0;
            $row['roas'] = $adsSpend > 0 ? (float) $row['gross'] / $adsSpend : null;
            $row['acos'] = (float) $row['gross'] > 0 ? $adsSpend / (float) $row['gross'] : null;
        }
        unset($row);

        uasort($productAgg, fn ($a, $b) => ($b['net_profit'] ?? 0) <=> ($a['net_profit'] ?? 0));

        $productsList = array_values($productAgg);

        $netProfitShop = $totals['gross_profit'] - $totalAds - $operationalTotal;

        ksort($monthly);

        foreach ($monthly as $mk => &$m) {
            $mStart = Carbon::createFromFormat('Y-m', $mk)->startOfMonth();
            $mEnd = Carbon::createFromFormat('Y-m', $mk)->endOfMonth();
            $mOpr = $this->sumOperationalForRange($shopId, $mStart, $mEnd);
            $mAds = $this->sumAdsForRange($shopId, $mStart, $mEnd);
            $m['operational'] = $mOpr;
            $m['ads'] = $mAds;
            $m['net_profit'] = $m['gross_profit'] - $mAds - $mOpr;
            $mOrders = (int) ($m['orders'] ?? 0);
            $mGross = (float) ($m['gross'] ?? 0);
            $m['aov_gross'] = $mOrders > 0 ? $mGross / $mOrders : null;
            $m['basket_size'] = $mOrders > 0 ? ($m['units'] ?? 0) / $mOrders : null;
            $m['gross_margin_pct'] = $mGross > 0 ? ($m['gross_profit'] ?? 0) / $mGross : null;
            $m['net_margin_pct'] = $mGross > 0 ? ($m['net_profit'] ?? 0) / $mGross : null;
        }
        unset($m);

        $productTotals = $this->sumProductRows($productsList);

        $ordersCount = (int) ($totals['orders_count'] ?? 0);
        $grossTotal = (float) ($totals['gross'] ?? 0);

        $summary = [
                'gross' => (int) round($totals['gross']),
                'fee_total' => (int) round($totals['fee_total']),
                'net' => (int) round($totals['net']),
                'cogs' => (int) round($totals['cogs']),
                'gross_profit' => (int) round($totals['gross_profit']),
                'ads_total' => (int) round($totalAds),
                'operational_total' => (int) round($operationalTotal),
                'net_profit' => (int) round($netProfitShop),
                'margin' => $totals['net'] > 0 ? $netProfitShop / $totals['net'] : 0,
                'roas' => $totalAds > 0 ? $totals['gross'] / $totalAds : null,
                'acos' => $totals['gross'] > 0 ? $totalAds / $totals['gross'] : null,
                'orders_count' => $ordersCount,
                'units_sold' => (int) ($totals['units_sold'] ?? 0),
                'missing_cost_orders' => $totals['missing_cost_orders'],
                'products_count' => count($productsList),
                'take_rate' => $totals['gross'] > 0 ? $totals['fee_total'] / $totals['gross'] : 0,
                'avg_order_net' => $ordersCount > 0 ? $totals['net'] / $ordersCount : 0,
                'avg_order_gross' => $ordersCount > 0 ? $grossTotal / $ordersCount : 0,
                'aov_gross' => $ordersCount > 0 ? (int) round($grossTotal / $ordersCount) : null,
                'basket_size' => $ordersCount > 0 ? round(($totals['units_sold'] ?? 0) / $ordersCount, 2) : null,
                'gross_margin_pct' => $grossTotal > 0 ? ($totals['gross_profit'] ?? 0) / $grossTotal : null,
                'net_margin_pct' => $grossTotal > 0 ? $netProfitShop / $grossTotal : null,
                'cost_ratio' => $grossTotal > 0 ? ($totals['cogs'] + $totalAds + $operationalTotal) / $grossTotal : 0,
            ];

        $feeBreakdownRounded = array_map(fn ($v) => (int) round($v), $feeBreakdown);
        $feeTotalBreakdown = max(1, array_sum($feeBreakdownRounded));

        $enrichPayload = ['products' => $productsList, 'summary' => $summary];
        app(\App\Services\Recommendations\RecommendationEngine::class)
            ->enrichReport($enrichPayload, $startDate, $endDate);
        $productsList = $enrichPayload['products'];
        $recommendations = $enrichPayload['recommendations'] ?? [];

        return [
            'shop' => [
                'id' => $shopId,
                'label' => $shopId > 0 ? ShopeeShopContext::shopLabel($shopId) : 'Semua toko',
            ],
            'meta' => [
                'generated_at' => now()->format('d M Y H:i'),
                'period_label' => $startDate->format('d M Y') . ' — ' . $endDate->format('d M Y'),
                'days' => max(1, $startDate->diffInDays($endDate) + 1),
            ],
            'filters' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'status' => $status,
                'jenis' => $jenis,
            ],
            'summary' => $summary,
            'pl_statement' => $this->buildPlStatement($summary, $feeBreakdownRounded),
            'fee_breakdown' => $feeBreakdownRounded,
            'fee_breakdown_pct' => array_map(
                fn ($v) => $v / $feeTotalBreakdown,
                $feeBreakdownRounded
            ),
            'monthly' => array_values($monthly),
            'products' => $productsList,
            'product_totals' => $productTotals,
            'top_products' => array_slice($productsList, 0, 5),
            'bottom_products' => array_slice(array_reverse($productsList), 0, 5),
            'analysis' => $this->buildAnalysis($summary, $productsList, array_values($monthly), $totals),
            'recommendations' => $recommendations,
            'orders' => $orderRows,
        ];
    }

    private function sumProductRows(array $products): array
    {
        $t = [
            'qty' => 0, 'gross' => 0, 'net' => 0, 'cogs' => 0,
            'ads_spend' => 0, 'operational' => 0, 'net_profit' => 0,
        ];
        foreach ($products as $p) {
            $t['qty'] += (int) ($p['qty'] ?? 0);
            $t['gross'] += (float) ($p['gross'] ?? 0);
            $t['net'] += (float) ($p['net'] ?? 0);
            $t['cogs'] += (float) ($p['cogs'] ?? 0);
            $t['ads_spend'] += (float) ($p['ads_spend'] ?? 0);
            $t['operational'] += (float) ($p['operational'] ?? 0);
            $t['net_profit'] += (float) ($p['net_profit'] ?? 0);
        }
        foreach ($t as $k => $v) {
            $t[$k] = in_array($k, ['qty'], true) ? (int) $v : (int) round($v);
        }
        return $t;
    }

    private function buildPlStatement(array $s, array $fees): array
    {
        $gross = (int) ($s['gross'] ?? 0);
        $feeTotal = (int) ($s['fee_total'] ?? 0);

        $feeChildren = [];
        foreach (ShopeeFinancialExtractor::feeLabels() as $key => $label) {
            $amt = (int) round($fees[$key] ?? 0);
            if ($amt !== 0) {
                $feeChildren[] = ['label' => $label, 'amount' => -$amt];
            }
        }
        if (empty($feeChildren)) {
            $feeChildren = [
                ['label' => 'Biaya administrasi', 'amount' => -($fees['admin'] ?? 0)],
                ['label' => 'Biaya layanan', 'amount' => -($fees['layanan'] ?? 0)],
                ['label' => 'Biaya proses', 'amount' => -($fees['proses'] ?? 0)],
                ['label' => 'Program hemat ongkir', 'amount' => -($fees['program_hemat'] ?? 0)],
            ];
        }

        return [
            ['label' => 'Pendapatan kotor (penjualan)', 'amount' => $gross, 'type' => 'revenue'],
            ['label' => 'Biaya platform Shopee', 'amount' => -$feeTotal, 'type' => 'fee', 'children' => $feeChildren],
            ['label' => 'Penghasilan bersih (net)', 'amount' => (int) ($s['net'] ?? 0), 'type' => 'subtotal'],
            ['label' => 'HPP + packaging', 'amount' => -((int) ($s['cogs'] ?? 0)), 'type' => 'cost'],
            ['label' => 'Laba kotor operasional', 'amount' => (int) ($s['gross_profit'] ?? 0), 'type' => 'subtotal'],
            ['label' => 'Biaya iklan (Ads)', 'amount' => -((int) ($s['ads_total'] ?? 0)), 'type' => 'cost'],
            ['label' => 'Biaya operasional', 'amount' => -((int) ($s['operational_total'] ?? 0)), 'type' => 'cost'],
            ['label' => 'Laba / rugi bersih', 'amount' => (int) ($s['net_profit'] ?? 0), 'type' => 'total'],
        ];
    }

    private function buildAnalysis(array $s, array $products, array $monthly, array $totals): array
    {
        $insights = [];
        $gross = (float) ($s['gross'] ?? 0);
        $netProfit = (float) ($s['net_profit'] ?? 0);
        $margin = (float) ($s['margin'] ?? 0);

        if (($s['orders_count'] ?? 0) === 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Tidak ada transaksi',
                'text' => 'Tidak ditemukan pesanan pada filter periode ini. Perluas rentang tanggal atau ubah status pesanan.',
            ];
        }

        if (($s['missing_cost_orders'] ?? 0) > 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Data HPP belum lengkap',
                'text' => ($s['missing_cost_orders'] ?? 0) . ' pesanan memiliki produk tanpa HPP/packaging. Lengkapi di menu Kelola Data agar laba akurat.',
            ];
        }

        if ($netProfit < 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Toko rugi pada periode ini',
                'text' => 'Laba bersih negatif ' . hub_rp(abs($netProfit)) . '. Tinjau biaya iklan, HPP, dan operasional.',
            ];
        } elseif ($margin >= 0.15) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Margin sehat',
                'text' => 'Margin laba bersih ' . hub_pct($margin) . ' dari penghasilan net — di atas ambang 15%.',
            ];
        }

        if (($s['ads_total'] ?? 0) > 0 && ($s['roas'] ?? null) !== null) {
            $roas = (float) $s['roas'];
            $insights[] = [
                'type' => $roas >= 3 ? 'success' : ($roas >= 1.5 ? 'info' : 'warning'),
                'title' => 'Efisiensi iklan (ROAS)',
                'text' => 'ROAS ' . number_format($roas, 2) . ' — setiap Rp 1 iklan menghasilkan Rp ' . number_format($roas, 2) . ' penjualan kotor.',
            ];
        } elseif ($gross > 0 && ($s['ads_total'] ?? 0) <= 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Data iklan kosong',
                'text' => 'Belum ada spend iklan tersinkron. Jalankan Sync Iklan setelah permission Ads aktif.',
            ];
        }

        $missingHppProducts = count(array_filter($products, fn ($p) => !empty($p['missing_cost'])));
        if ($missingHppProducts > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Produk tanpa HPP',
                'text' => $missingHppProducts . ' SKU aktif di periode ini belum memiliki biaya pokok lengkap.',
            ];
        }

        $monthlyList = array_values($monthly);
        if (count($monthlyList) >= 2) {
            $last = $monthlyList[count($monthlyList) - 1];
            $prev = $monthlyList[count($monthlyList) - 2];
            $profitDelta = ((float) ($last['net_profit'] ?? 0)) - ((float) ($prev['net_profit'] ?? 0));
            if (abs($profitDelta) > 0) {
                $dir = $profitDelta >= 0 ? 'naik' : 'turun';
                $insights[] = [
                    'type' => $profitDelta >= 0 ? 'success' : 'warning',
                    'title' => 'Tren laba bulan terakhir',
                    'text' => 'Laba bersih ' . $dir . ' ' . hub_rp(abs($profitDelta)) . ' dibanding bulan sebelumnya dalam data.',
                ];
            }
        }

        $concentration = 0.0;
        if ($gross > 0 && !empty($products)) {
            $top = (float) ($products[0]['gross'] ?? 0);
            $concentration = $top / $gross;
            if ($concentration >= 0.5) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Konsentrasi penjualan',
                    'text' => 'Produk teratas "' . ($products[0]['name'] ?? '-') . '" menyumbang ' . hub_pct($concentration) . ' dari omzet.',
                ];
            }
        }

        return [
            'insights' => $insights,
            'health_score' => $this->healthScore($s),
        ];
    }

    private function healthScore(array $s): int
    {
        $score = 100;
        if (($s['missing_cost_orders'] ?? 0) > 0) {
            $score -= min(30, ($s['missing_cost_orders'] ?? 0) * 3);
        }
        if (($s['net_profit'] ?? 0) < 0) {
            $score -= 25;
        }
        if (($s['margin'] ?? 0) < 0.05 && ($s['net'] ?? 0) > 0) {
            $score -= 15;
        }
        if (($s['ads_total'] ?? 0) > 0 && ($s['roas'] ?? 0) < 1.5) {
            $score -= 10;
        }
        return max(0, min(100, $score));
    }

    private function parseFilters(Request $request): array
    {
        $today = Carbon::now();
        $startRaw = (string) ($request->query('start') ?? $request->query('start_date') ?? '');
        $endRaw = (string) ($request->query('end') ?? $request->query('end_date') ?? '');

        $startDate = $startRaw !== ''
            ? Carbon::parse($startRaw)->startOfDay()
            : $today->copy()->subDays(30)->startOfDay();

        $endDate = $endRaw !== ''
            ? Carbon::parse($endRaw)->endOfDay()
            : $today->copy()->endOfDay();

        $status = (string) ($request->query('status') ?? 'completed');
        $jenis = strtolower(trim((string) ($request->query('jenis', 'shopee'))));

        return [$startDate, $endDate, $status, $jenis];
    }

    private function queryOrders(Carbon $start, Carbon $end, string $status, string $jenis): Collection
    {
        $q = Order::query()
            ->with(['orderItems', 'orderItems.product', 'shopeeFinancial'])
            ->whereBetween('order_date', [$start, $end]);

        if ($status !== 'all') {
            $q->where('status', $status);
        }

        if ($jenis === 'shopee') {
            $q->whereRaw('LOWER(COALESCE(jenis_transaksi, "")) = ?', ['shopee']);
        } elseif (in_array($jenis, ['non_shopee', 'non-shopee'], true)) {
            $q->whereRaw('LOWER(COALESCE(jenis_transaksi, "")) <> ?', ['shopee']);
        }

        $shopId = ShopeeShopContext::shopId();
        if ($shopId > 0) {
            $q->where(function ($query) use ($shopId) {
                $query->whereHas('shopeeFinancial', fn ($f) => $f->where('shop_id', $shopId))
                    ->orWhereHas('orderItems.product', fn ($p) => $p->where('external_shop_id', $shopId));
            });
        }

        return $q->orderByDesc('order_date')->get();
    }

    private function preloadProductMaps(array &$byItem, array &$bySku, array &$byName): void
    {
        $query = Product::query()->select(['id', 'name', 'external_item_id', 'external_sku', 'hpp_amount', 'packaging_type', 'packaging_value']);
        $shopId = ShopeeShopContext::shopId();
        if ($shopId > 0) {
            ShopeeShopContext::scopeProducts($query);
        }

        foreach ($query->get() as $p) {
            if ($p->external_item_id) {
                $byItem[(string) $p->external_item_id] = $p;
            }
            if ($p->external_sku) {
                $bySku[(string) $p->external_sku] = $p;
            }
            $norm = $this->normalizeName($p->name ?? '');
            if ($norm !== '' && !isset($byName[$norm])) {
                $byName[$norm] = $p;
            }
        }
    }

    private function emptyProductRow(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'external_item_id' => (string) ($product->external_item_id ?? ''),
            'sku' => (string) ($product->external_sku ?? ''),
            'qty' => 0,
            'gross' => 0.0,
            'net' => 0.0,
            'cogs' => 0.0,
            'ads_spend' => 0.0,
            'operational' => 0.0,
            'gross_profit' => 0.0,
            'net_profit' => 0.0,
            'margin' => 0.0,
            'roas' => null,
            'acos' => null,
            'missing_cost' => false,
        ];
    }

    private function resolveLineCost(
        $item,
        float $unitPrice,
        int $qty,
        array &$productByExternalItemId,
        array &$productByExternalSku,
        array &$productByNormName,
        array &$variantByKey,
        array &$variantByExternalModelId,
    ): array {
        $product = $item->product;

        if (!$product && !empty($item->external_item_id)) {
            $ext = (string) $item->external_item_id;
            if (!array_key_exists($ext, $productByExternalItemId)) {
                $productByExternalItemId[$ext] = Product::query()
                    ->where('external_item_id', $ext)->first();
            }
            $product = $productByExternalItemId[$ext] ?? null;
        }

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
        }

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
        $missing = !$product || ($hpp === null && $packVal === null);

        return [$product, $variant, $unitHpp, $unitPack, $lineCogs, $missing];
    }

    private function allocateNetToProducts(
        array &$productAgg,
        array $perOrderProductLines,
        array $unknownLines,
        float $net,
        float $allocBase,
    ): void {
        if ($allocBase <= 0) {
            return;
        }

        foreach ($perOrderProductLines as $pid => $lines) {
            foreach ($lines as $ln) {
                $ratio = ((float) ($ln['gross'] ?? 0)) / $allocBase;
                $allocNet = $net * $ratio;
                if (!isset($productAgg[$pid])) {
                    continue;
                }
                $productAgg[$pid]['gross'] += (float) ($ln['gross'] ?? 0);
                $productAgg[$pid]['net'] += $allocNet;
                $productAgg[$pid]['cogs'] += (float) ($ln['cogs'] ?? 0);
            }
        }

        foreach ($unknownLines as $ln) {
            $pid = 0;
            if (!isset($productAgg[$pid])) {
                $productAgg[$pid] = [
                    'product_id' => 0,
                    'name' => '(Produk belum terpetakan)',
                    'external_item_id' => '',
                    'sku' => '',
                    'qty' => 0,
                    'gross' => 0.0,
                    'net' => 0.0,
                    'cogs' => 0.0,
                    'missing_cost' => true,
                ];
            }
            $ratio = ((float) ($ln['gross'] ?? 0)) / $allocBase;
            $productAgg[$pid]['qty'] += (int) ($ln['qty'] ?? 0);
            $productAgg[$pid]['gross'] += (float) ($ln['gross'] ?? 0);
            $productAgg[$pid]['net'] += $net * $ratio;
            $productAgg[$pid]['cogs'] += (float) ($ln['cogs'] ?? 0);
        }
    }

    private function sumOperationalForRange(int $shopId, Carbon $start, Carbon $end): float
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor <= $end) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return (float) ShopMonthlyCost::query()
            ->where('shop_id', $shopId)
            ->whereIn('year_month', $months)
            ->sum('operational_amount');
    }

  private function sumAdsForRange(int $shopId, Carbon $start, Carbon $end): float
    {
        return (float) ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->sum('spend');
    }

    /**
     * @return array<int|string, float> keyed by product_id and ext:item_id
     */
    private function loadAdsByProduct(int $shopId, Carbon $start, Carbon $end): array
    {
        $rows = ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('product_id, external_item_id, SUM(spend) as total_spend')
            ->groupBy('product_id', 'external_item_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $spend = (float) $r->total_spend;
            if ($r->product_id) {
                $map[(int) $r->product_id] = ($map[(int) $r->product_id] ?? 0) + $spend;
            }
            if ($r->external_item_id) {
                $map['ext:' . $r->external_item_id] = ($map['ext:' . $r->external_item_id] ?? 0) + $spend;
            }
        }

        return $map;
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return $name;
    }
}
