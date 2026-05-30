<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\OrderItem;

class ProfitReportService
{
    /**
     * Hitung metrik profit untuk 1 order.
     *
     * NOTE:
     * - Untuk Shopee: net = orders.total_amount (diisi dari shopee_order_financials.seller_income saat sync)
     * - Untuk non-Shopee: net biasanya sama dengan total invoice/order (tanpa fee marketplace)
     *
     * Output:
     *  - gross: total harga item (sebelum fee)
     *  - net: total penghasilan seller
     *  - fees: breakdown fee Shopee (best-effort) + total
     *  - cogs: total HPP + Packaging (per item * qty)
     *  - profit: net - cogs
     *  - margin_pct: profit / net
     *  - take_rate_pct: fees / gross
     *  - missing_cost: ada item yang tidak bisa dihitung biaya (hpp/pack kosong, atau % tapi harga 0)
     *  - alloc_by_product: alokasi net & profit per product (proporsional berdasarkan gross line)
     */
    public function compute(Order $order): array
    {
        // Pastikan relasi sudah ada (controller biasanya sudah with())
        $items = $order->relationLoaded('orderItems') ? $order->orderItems : collect();
        $fin = $order->relationLoaded('shopeeFinancial') ? $order->shopeeFinancial : null;

        // Gross: prefer sum line item
        $gross = 0.0;
        if ($items->count() > 0) {
            foreach ($items as $it) {
                $qty = (int) ($it->quantity ?? 1);
                $unitPrice = $this->resolveUnitPrice($it);
                $gross += ($unitPrice * $qty);
            }
        } else {
            // fallback
            $gross = (float) ($order->price ?? 0);
        }

        // Net: prefer order.total_amount (untuk Shopee = seller_income)
        $net = (float) ($order->total_amount ?? 0);
        if ($net <= 0 && $fin && $fin->seller_income !== null) {
            $net = (float) $fin->seller_income;
        }

        // Fees breakdown
        $fees = [
            'commission' => 0.0,
            'service' => 0.0,
            'transaction' => 0.0,
            'other' => 0.0,
            'total' => 0.0,
        ];

        if ($fin) {
            $fees['commission'] = abs((float) ($fin->commission_fee ?? 0));
            $fees['service'] = abs((float) ($fin->service_fee ?? 0));
            $fees['transaction'] = abs((float) ($fin->transaction_fee ?? 0));

            // other_fee_raw (json string) best-effort sum
            $other = 0.0;
            if (!empty($fin->other_fee_raw)) {
                try {
                    $decoded = json_decode($fin->other_fee_raw, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $row) {
                            if (is_array($row)) {
                                $amt = $row['amount'] ?? $row['fee'] ?? $row['value'] ?? null;
                                if ($amt !== null) $other += abs((float) $amt);
                            } elseif (is_numeric($row)) {
                                $other += abs((float) $row);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $fees['other'] = $other;
            $fees['total'] = $fees['commission'] + $fees['service'] + $fees['transaction'] + $fees['other'];

            // fallback: kalau ada data gross & net tapi total fee 0, coba gross - net
            if ($fees['total'] <= 0 && $gross > 0 && $net > 0) {
                $fees['total'] = max(0, $gross - $net);
            }
        } else {
            // non-shopee: fee dianggap 0
            $fees['total'] = 0.0;
        }

        // COGS
        $cogs = 0.0;
        $missingCost = false;

        // Untuk alokasi per product: kita alokasikan net order ke product berdasarkan proporsi gross line
        $allocByProduct = []; // product_id => sums
        $lineGrossByProduct = []; // product_id => gross

        if ($items->count() > 0) {
            foreach ($items as $it) {
                $qty = (int) ($it->quantity ?? 1);
                $unitPrice = $this->resolveUnitPrice($it);

                $cost = $this->resolveUnitCosts($it, $unitPrice);
                if (!$cost['ok']) {
                    $missingCost = true;
                }

                $lineGross = $unitPrice * $qty;
                $gross += 0; // already counted above

                $lineCogs = ($cost['hpp'] + $cost['pack']) * $qty;
                $cogs += $lineCogs;

                $pid = $it->product_id ?: null;
                if ($pid) {
                    $name = $it->product?->name ?? $it->product_name ?? 'Unknown';

                    $lineGrossByProduct[$pid] = ($lineGrossByProduct[$pid] ?? 0) + $lineGross;

                    if (!isset($allocByProduct[$pid])) {
                        $allocByProduct[$pid] = [
                            'product_id' => $pid,
                            'name' => $name,
                            'qty' => 0,
                            'gross' => 0.0,
                            'net' => 0.0,   // diisi setelah proporsi
                            'cogs' => 0.0,
                            'profit' => 0.0,
                        ];
                    }

                    $allocByProduct[$pid]['qty'] += $qty;
                    $allocByProduct[$pid]['gross'] += $lineGross;
                    $allocByProduct[$pid]['cogs'] += $lineCogs;
                } else {
                    $missingCost = true; // item tidak bisa dipetakan ke product
                }
            }
        }

        $profit = $net - $cogs;
        $marginPct = $net > 0 ? ($profit / $net) * 100 : 0;
        $takeRatePct = $gross > 0 ? ($fees['total'] / $gross) * 100 : 0;

        // Allocate net/profit by product proportional to gross line
        $grossDenom = array_sum($lineGrossByProduct);
        if ($grossDenom > 0 && count($allocByProduct) > 0) {
            foreach ($allocByProduct as $pid => $row) {
                $ratio = ($lineGrossByProduct[$pid] ?? 0) / $grossDenom;
                $allocNet = $net * $ratio;
                $allocByProduct[$pid]['net'] = $allocNet;
                $allocByProduct[$pid]['profit'] = $allocNet - ($allocByProduct[$pid]['cogs'] ?? 0);
            }
        }

        return [
            'gross' => $gross,
            'net' => $net,
            'fees' => $fees,
            'cogs' => $cogs,
            'profit' => $profit,
            'margin_pct' => $marginPct,
            'take_rate_pct' => $takeRatePct,
            'missing_cost' => $missingCost,
            'alloc_by_product' => $allocByProduct,
        ];
    }

    private function resolveUnitPrice(OrderItem $item): float
    {
        // order_items.price = unit price (sudah dipakai di sync Shopee)
        if ($item->price !== null && $item->price !== '') {
            return (float) $item->price;
        }

        // fallback: product.base_price
        if ($item->product && $item->product->base_price !== null) {
            return (float) $item->product->base_price;
        }

        return 0.0;
    }

    /**
     * Resolve HPP + Packaging per unit
     *
     * Rules:
     * - HPP: variant.hpp_amount ?? product.hpp_amount
     * - Packaging: variant.packaging_* ?? product.packaging_*
     * - Percent packaging dihitung dari unitPrice; jika unitPrice 0 => dianggap missing
     */
    private function resolveUnitCosts(OrderItem $item, float $unitPrice): array
    {
        $product = $item->product;

        if (!$product) {
            return ['hpp' => 0.0, 'pack' => 0.0, 'ok' => false];
        }

        $variant = null;

        // Shopee mapping: external_model_id di order_items -> product_variants.external_model_id
        if (!empty($item->external_model_id) && $product->relationLoaded('variants')) {
            $variant = $product->variants->firstWhere('external_model_id', $item->external_model_id);
        }

        // HPP
        $hppVal = null;
        if ($variant && $variant->hpp_amount !== null && $variant->hpp_amount !== '') {
            $hppVal = (float) $variant->hpp_amount;
        } elseif ($product->hpp_amount !== null && $product->hpp_amount !== '') {
            $hppVal = (float) $product->hpp_amount;
        }

        // Packaging
        $packType = null;
        $packVal = null;

        if ($variant && $variant->packaging_type !== null && $variant->packaging_type !== '') {
            $packType = $variant->packaging_type;
            $packVal = $variant->packaging_value;
        } else {
            $packType = $product->packaging_type ?? 'fixed';
            $packVal = $product->packaging_value;
        }

        $packCost = 0.0;
        $packOk = true;

        if ($packVal === null || $packVal === '') {
            $packOk = false;
        } else {
            if ($packType === 'percent') {
                if ($unitPrice <= 0) {
                    $packOk = false;
                } else {
                    $pct = (float) $packVal;
                    if ($pct < 0) $pct = 0;
                    if ($pct > 100) $pct = 100;
                    $packCost = round(($unitPrice * $pct) / 100);
                }
            } else {
                $packCost = (float) $packVal;
            }
        }

        $hppOk = $hppVal !== null;

        return [
            'hpp' => $hppOk ? (float) $hppVal : 0.0,
            'pack' => $packCost,
            'ok' => ($hppOk && $packOk),
        ];
    }
}
