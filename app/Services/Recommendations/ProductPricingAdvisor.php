<?php

namespace App\Services\Recommendations;

use App\Models\Product;

class ProductPricingAdvisor
{
    /**
     * Harga impas dari data aktual: COGS + operasional + iklan per unit, disesuaikan fee Shopee.
     */
    public function analyze(array $row, ?Product $product, float $shopTakeRate): array
    {
        $qty = (int) ($row['qty'] ?? 0);
        $gross = (float) ($row['gross'] ?? 0);
        $net = (float) ($row['net'] ?? 0);
        $cogs = (float) ($row['cogs'] ?? 0);
        $ads = (float) ($row['ads_spend'] ?? 0);
        $opr = (float) ($row['operational'] ?? 0);
        $netProfit = (float) ($row['net_profit'] ?? 0);

        $cfg = config('monitoring.pricing', []);
        $marginBuffer = (float) ($cfg['margin_buffer_pct'] ?? 10) / 100;
        $minNetRatio = (float) ($cfg['min_net_to_gross_ratio'] ?? 0.55);

        $netRatio = $gross > 0 ? $net / $gross : max($minNetRatio, 1 - $shopTakeRate);
        $netRatio = max(0.05, min(0.95, $netRatio));

        $qtyUnit = max(1, $qty);
        $cogsUnit = $cogs / $qtyUnit;
        $adsUnit = $ads / $qtyUnit;
        $oprUnit = $opr / $qtyUnit;
        $loadedCostUnit = $cogsUnit + $adsUnit + $oprUnit;

        $breakevenGrossUnit = $loadedCostUnit / $netRatio;
        $targetGrossUnit = $breakevenGrossUnit * (1 + $marginBuffer);

        $avgSellingPrice = $qty > 0 ? $gross / $qty : 0;
        $catalogPrice = $product ? (float) ($product->base_price ?? 0) : 0;
        $referencePrice = $avgSellingPrice > 0 ? $avgSellingPrice : $catalogPrice;

        $gapPct = ($breakevenGrossUnit > 0 && $referencePrice > 0)
            ? (($referencePrice - $breakevenGrossUnit) / $breakevenGrossUnit) * 100
            : null;

        $status = $this->status($row, $referencePrice, $breakevenGrossUnit, $targetGrossUnit, $netProfit);

        return [
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'per_unit' => [
                'cogs' => (int) round($cogsUnit),
                'ads' => (int) round($adsUnit),
                'operational' => (int) round($oprUnit),
                'total_cost' => (int) round($loadedCostUnit),
                'net_ratio' => round($netRatio, 3),
            ],
            'prices' => [
                'avg_selling' => (int) round($avgSellingPrice),
                'catalog' => (int) round($catalogPrice),
                'breakeven_gross' => (int) round($breakevenGrossUnit),
                'recommended_gross' => (int) round($targetGrossUnit),
                'gap_vs_breakeven_pct' => $gapPct !== null ? round($gapPct, 1) : null,
            ],
            'recommendation' => $this->recommendation($status, $referencePrice, $breakevenGrossUnit, $targetGrossUnit, $row, $gapPct),
        ];
    }

    private function status(array $row, float $refPrice, float $breakeven, float $target, float $netProfit): string
    {
        if ($row['missing_cost'] ?? false) {
            return 'invalid_hpp';
        }
        if (($row['qty'] ?? 0) === 0) {
            return 'no_sales';
        }
        if ($refPrice <= 0) {
            return 'unknown_price';
        }
        if ($refPrice < $breakeven * 0.98) {
            return 'too_low';
        }
        if ($refPrice >= $target && $netProfit >= 0) {
            return 'ok';
        }
        if ($netProfit < 0) {
            return 'not_covering';
        }

        return 'review';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'Harga OK',
            'too_low' => 'Terlalu rendah',
            'not_covering' => 'Belum menutup biaya',
            'no_sales' => 'Tanpa penjualan',
            'invalid_hpp' => 'HPP belum valid',
            default => 'Perlu review',
        };
    }

    private function recommendation(string $status, float $ref, float $be, float $target, array $row, ?float $gapPct): array
    {
        $severity = match ($status) {
            'ok' => 'success',
            'too_low', 'not_covering' => 'danger',
            'invalid_hpp' => 'warning',
            default => 'info',
        };

        $title = match ($status) {
            'ok' => 'Harga sudah menutup COGS + iklan + operasional',
            'too_low' => 'Naikkan harga jual',
            'not_covering' => 'Harga belum menutup semua biaya',
            'invalid_hpp' => 'Perbaiki HPP dulu',
            'no_sales' => 'Belum ada penjualan pada periode',
            default => 'Review harga jual',
        };

        $lines = [];
        if ($status === 'ok') {
            $lines[] = 'Harga rata-rata **' . $this->rp($ref) . '** di atas target **' . $this->rp($target) . '** (impas ' . $this->rp($be) . ').';
        } elseif (in_array($status, ['too_low', 'not_covering'], true)) {
            $increase = $target > $ref && $ref > 0 ? round((($target / $ref) - 1) * 100) : null;
            $lines[] = 'Harga rata-rata **' . $this->rp($ref) . '** · impas **' . $this->rp($be) . '** · disarankan **≥ ' . $this->rp($target) . '**.';
            if ($increase) {
                $lines[] = "Kenaikan sekitar **+{$increase}%** agar menutup COGS, operasional, iklan, dan fee Shopee.";
            }
            $lines[] = 'Per unit: HPP ' . $this->rp($row['cogs'] / max(1, $row['qty']))
                . ' + iklan ' . $this->rp($row['ads_spend'] / max(1, $row['qty']))
                . ' + ops ' . $this->rp($row['operational'] / max(1, $row['qty']));
        } elseif ($status === 'invalid_hpp') {
            $lines[] = 'Tanpa HPP valid, perhitungan harga impas tidak bisa dipercaya.';
        }

        return [
            'severity' => $severity,
            'title' => $title,
            'lines' => $lines,
        ];
    }

    private function rp(float $n): string
    {
        return 'Rp ' . number_format(round($n));
    }
}
