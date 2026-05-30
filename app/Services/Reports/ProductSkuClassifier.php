<?php

namespace App\Services\Reports;

class ProductSkuClassifier
{
    public const STAR = 'star';
    public const MAINTAIN = 'maintain';
    public const FIX_PRICE = 'fix_price';
    public const BLEEDER = 'bleeder';
    public const REVIEW = 'review';
    public const NO_DATA = 'no_data';

    public function classify(array $row): string
    {
        if ($row['missing_cost'] ?? false) {
            return self::NO_DATA;
        }

        $qty = (int) ($row['qty'] ?? 0);
        $margin = (float) ($row['margin'] ?? 0);
        $netProfit = (float) ($row['net_profit'] ?? 0);
        $adsSpend = (float) ($row['ads_spend'] ?? 0);
        $roas = $row['roas'] ?? null;

        $cfg = config('monitoring.sku_classifier', []);
        $starMargin = (float) ($cfg['star_margin'] ?? 0.15);
        $bleederMargin = (float) ($cfg['bleeder_margin'] ?? 0.0);
        $fixMargin = (float) ($cfg['fix_margin'] ?? 0.08);
        $minQtyStar = (int) ($cfg['min_qty_for_star'] ?? 3);
        $starRoas = (float) ($cfg['star_roas'] ?? 2.0);
        $highQty = (int) ($cfg['high_volume_qty'] ?? 20);

        if ($netProfit < 0 && $qty > 0) {
            return self::BLEEDER;
        }

        if ($margin >= $starMargin && $qty >= $minQtyStar) {
            if ($adsSpend <= 0 || ($roas !== null && $roas >= $starRoas)) {
                return self::STAR;
            }
        }

        if ($margin < $fixMargin && $qty >= $highQty) {
            return self::FIX_PRICE;
        }

        if ($margin >= $bleederMargin && $netProfit >= 0) {
            return $adsSpend > 0 ? self::MAINTAIN : self::MAINTAIN;
        }

        if ($qty === 0 && $adsSpend > 0) {
            return self::REVIEW;
        }

        return self::REVIEW;
    }

    public function label(string $tier): string
    {
        return match ($tier) {
            self::STAR => 'Star',
            self::MAINTAIN => 'Maintain',
            self::FIX_PRICE => 'Perbaiki harga',
            self::BLEEDER => 'Bleeder',
            self::NO_DATA => 'Data HPP',
            default => 'Review',
        };
    }

    public function badgeClass(string $tier): string
    {
        return match ($tier) {
            self::STAR => 'tier-star',
            self::BLEEDER => 'tier-bleeder',
            self::FIX_PRICE => 'tier-fix',
            self::NO_DATA => 'tier-nodata',
            default => 'tier-review',
        };
    }
}
