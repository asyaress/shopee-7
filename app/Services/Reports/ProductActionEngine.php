<?php

namespace App\Services\Reports;

class ProductActionEngine
{
    public function forProduct(array $row, bool $recommendationsAllowed = true): array
    {
        if (!$recommendationsAllowed) {
            return $this->make(
                'fix_data',
                'warning',
                'Lengkapi HPP dulu',
                'Kelengkapan data toko di bawah ambang — perbaiki HPP sebelum mengubah iklan atau harga.',
                [],
                'hpp.index'
            );
        }

        if ($row['missing_cost'] ?? false) {
            return $this->make(
                'fix_hpp',
                'danger',
                'Isi HPP produk ini',
                'Tanpa HPP, laba bersih tidak valid.',
                ['Produk terjual tanpa biaya pokok'],
                'hpp.index'
            );
        }

        $tier = $row['tier'] ?? ProductSkuClassifier::REVIEW;
        $margin = (float) ($row['margin'] ?? 0);
        $netProfit = (float) ($row['net_profit'] ?? 0);
        $adsSpend = (float) ($row['ads_spend'] ?? 0);
        $roas = $row['roas'] ?? null;
        $acos = $row['acos'] ?? null;
        $cfg = config('monitoring.actions', []);

        $minAds = (float) ($cfg['min_ads_spend_significant'] ?? 50000);
        $roasCut = (float) ($cfg['roas_cut'] ?? 1.5);
        $roasKill = (float) ($cfg['roas_kill'] ?? 1.0);
        $roasScale = (float) ($cfg['roas_scale_up'] ?? 2.5);
        $marginLow = (float) ($cfg['margin_low'] ?? 0.05);
        $pricePct = (int) ($cfg['price_increase_suggest_pct'] ?? 12);
        $adsCutPct = (int) ($cfg['ads_cut_suggest_pct'] ?? 50);

        $reasons = [];

        if ($tier === ProductSkuClassifier::BLEEDER) {
            if ($adsSpend >= $minAds) {
                $reasons[] = 'Laba bersih negatif dengan spend iklan ' . $this->rp($adsSpend);
                if ($roas !== null && $roas < $roasKill) {
                    $reasons[] = 'ROAS ' . number_format($roas, 2) . 'x di bawah 1x';
                }
                if ($acos !== null && $margin > 0 && $acos > $margin) {
                    $reasons[] = 'ACOS lebih tinggi dari margin bersih';
                }

                return $this->make(
                    'cut_ads',
                    'danger',
                    "Kurangi atau matikan iklan (~{$adsCutPct}%)",
                    'SKU merugikan; iklan memperburuk.',
                    $reasons,
                    null,
                    ['ads_cut_pct' => $adsCutPct]
                );
            }

            $reasons[] = 'Laba bersih negatif tanpa iklan signifikan';

            return $this->make(
                'raise_price_or_stop',
                'danger',
                "Naikkan harga ~{$pricePct}% atau pertimbangkan stop SKU",
                'Margin tidak menutup biaya.',
                $reasons,
                null,
                ['price_increase_pct' => $pricePct]
            );
        }

        if ($tier === ProductSkuClassifier::FIX_PRICE) {
            $reasons[] = 'Margin ' . $this->pct($margin) . ' tipis dengan volume tinggi';

            return $this->make(
                'raise_price',
                'warning',
                "Naikkan harga min. ~{$pricePct}%",
                'Volume bagus tapi margin terlalu rendah.',
                $reasons,
                null,
                ['price_increase_pct' => $pricePct]
            );
        }

        if ($tier === ProductSkuClassifier::STAR && $adsSpend > 0 && $roas !== null && $roas >= $roasScale) {
            $reasons[] = 'ROAS ' . number_format($roas, 2) . 'x · Laba ' . $this->rp($netProfit);

            return $this->make(
                'scale_ads',
                'success',
                'Naikkan iklan bertahap (+20%)',
                'Produk profitable dengan iklan efisien.',
                $reasons
            );
        }

        if ($tier === ProductSkuClassifier::STAR && $adsSpend <= 0) {
            return $this->make(
                'test_ads',
                'info',
                'Uji iklan kecil',
                'Star tanpa iklan — bisa scale dengan budget kecil.',
                ['Margin ' . $this->pct($margin), 'Laba ' . $this->rp($netProfit)]
            );
        }

        if ($adsSpend >= $minAds && $roas !== null && $roas < $roasCut) {
            $reasons[] = 'ROAS ' . number_format($roas, 2) . 'x di bawah target ' . $roasCut . 'x';

            return $this->make(
                'cut_ads',
                'warning',
                "Kurangi iklan ~{$adsCutPct}%",
                'Iklan tidak efisien untuk margin saat ini.',
                $reasons,
                null,
                ['ads_cut_pct' => $adsCutPct]
            );
        }

        if ($margin < $marginLow && $netProfit >= 0) {
            return $this->make(
                'raise_price',
                'warning',
                'Review harga jual',
                'Margin di bawah ' . $this->pct($marginLow) . '.',
                ['Margin saat ini ' . $this->pct($margin)]
            );
        }

        return $this->make(
            'hold',
            'info',
            'Pertahankan strategi',
            'Tidak ada perubahan mendesak.',
            ['Tier: ' . $tier, 'Margin ' . $this->pct($margin)]
        );
    }

    public function simulate(array $row, array $params = []): array
    {
        $net = (float) ($row['net'] ?? 0);
        $cogs = (float) ($row['cogs'] ?? 0);
        $ads = (float) ($row['ads_spend'] ?? 0);
        $opr = (float) ($row['operational'] ?? 0);
        $gross = (float) ($row['gross'] ?? 0);

        $scenarios = [];

        $pricePct = (float) ($params['price_increase_pct'] ?? config('monitoring.actions.price_increase_suggest_pct', 12));
        $newGross = $gross * (1 + $pricePct / 100);
        $newNet = $net * (1 + $pricePct / 100);
        $scenarios['price_up'] = [
            'label' => "Harga +{$pricePct}%",
            'net_profit' => (int) round($newNet - $cogs - $ads - $opr),
            'margin' => $newNet > 0 ? ($newNet - $cogs - $ads - $opr) / $newNet : 0,
        ];

        $adsCut = (float) ($params['ads_cut_pct'] ?? 50);
        $newAds = $ads * (1 - $adsCut / 100);
        $scenarios['ads_cut'] = [
            'label' => "Iklan -{$adsCut}%",
            'net_profit' => (int) round($net - $cogs - $newAds - $opr),
            'margin' => $net > 0 ? ($net - $cogs - $newAds - $opr) / $net : 0,
        ];

        return $scenarios;
    }

    private function make(
        string $code,
        string $severity,
        string $title,
        string $summary,
        array $reasons = [],
        ?string $route = null,
        array $meta = []
    ): array {
        return [
            'code' => $code,
            'severity' => $severity,
            'title' => $title,
            'summary' => $summary,
            'reasons' => $reasons,
            'route' => $route,
            'meta' => $meta,
        ];
    }

    private function rp(float $n): string
    {
        return 'Rp ' . number_format(round($n));
    }

    private function pct(float $r): string
    {
        return number_format($r * 100, 1) . '%';
    }
}
