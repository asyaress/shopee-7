<?php

namespace App\Services\Ceo;

use App\Models\ShopeeProductAdsDaily;
use App\Services\Recommendations\AdsMetricsService;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;

/**
 * Analisis ROAS untuk CEO — selaras Shopee Ads (GMV/spend) & template Excel ROAS HLP.
 */
class RoasAdvisorService
{
    public function __construct(
        private readonly AdsMetricsService $adsMetrics,
    ) {
    }

    public function shopAdvice(array $report): array
    {
        $s = $report['summary'] ?? [];
        $filters = $report['filters'] ?? [];
        $shopId = ShopeeShopContext::shopId();

        $gross = (float) ($s['gross'] ?? 0);
        $grossProfit = (float) ($s['gross_profit'] ?? 0);
        $cogs = (float) ($s['cogs'] ?? 0);
        $adsSpend = (float) ($s['ads_total'] ?? 0);
        $netProfit = (float) ($s['net_profit'] ?? 0);

        $marginProfit = $gross > 0 ? $grossProfit / $gross : 0;
        $breakevenRoasGross = $marginProfit > 0 ? 1 / $marginProfit : null;
        $safety = (float) config('monitoring.roas_advisor.safety_multiplier', 1.25);
        $targetRoasGross = $breakevenRoasGross ? $breakevenRoasGross * $safety : null;

        $start = Carbon::parse($filters['start'] ?? now()->startOfMonth());
        $end = Carbon::parse($filters['end'] ?? now());

        $adsGmv = (float) ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->sum('gmv');

        $adsSpendApi = (float) ShopeeProductAdsDaily::query()
            ->where('shop_id', $shopId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->sum('spend');

        $shopeeRoas = $adsSpendApi > 0 ? $adsGmv / $adsSpendApi : null;
        $businessRoas = $adsSpend > 0 ? $gross / $adsSpend : ($s['roas'] ?? null);

        $acos = $gross > 0 && $adsSpend > 0 ? $adsSpend / $gross : null;
        $targetAcos = $targetRoasGross ? 1 / $targetRoasGross : null;

        $breakevenShopeeRoas = null;
        $setRoasShop = null;
        if ($marginProfit > 0 && $gross > 0 && $adsGmv > 0) {
            $gmvToGross = $gross / max(1, $adsGmv);
            $breakevenShopeeRoas = (1 / $marginProfit) / max(0.01, $gmvToGross);
            $setRoasShop = ($breakevenShopeeRoas * $safety) / 0.70;
        }

        $adsByProduct = $this->adsMetrics->loadByProduct($shopId, $start, $end);
        $products = $this->productAdvice($report['products'] ?? [], $safety, $adsByProduct);

        $ceoAction = $this->ceoAction(
            $businessRoas,
            $targetRoasGross,
            $shopeeRoas,
            $setRoasShop,
            $netProfit,
            $products
        );

        return [
            'period_label' => $report['meta']['period_label'] ?? null,
            'glossary' => $this->glossary(),
            'formulas' => $this->formulasReference(),
            'scorecard' => [
                'set_roas_shopee' => $setRoasShop ? round($setRoasShop, 2) : null,
                'shopee_roas_now' => $shopeeRoas !== null ? round($shopeeRoas, 2) : null,
                'business_roas_now' => $businessRoas !== null ? round($businessRoas, 2) : null,
                'target_roas' => $targetRoasGross ? round($targetRoasGross, 2) : null,
                'breakeven_roas' => $breakevenRoasGross ? round($breakevenRoasGross, 2) : null,
                'margin_profit_pct' => round($marginProfit * 100, 1),
                'acos_pct' => $acos !== null ? round($acos * 100, 1) : null,
                'target_acos_pct' => $targetAcos !== null ? round($targetAcos * 100, 1) : null,
                'ads_spend' => (int) round($adsSpendApi),
                'ads_gmv' => (int) round($adsGmv),
                'gross' => (int) round($gross),
                'cogs' => (int) round($cogs),
                'gross_profit' => (int) round($grossProfit),
                'net_profit' => (int) round($netProfit),
            ],
            'ceo_action' => $ceoAction,
            'products' => $products,
            'counts' => [
                'scale' => count(array_filter($products, fn ($p) => ($p['action']['code'] ?? '') === 'scale')),
                'cut' => count(array_filter($products, fn ($p) => in_array($p['action']['code'] ?? '', ['cut', 'stop'], true))),
                'ok' => count(array_filter($products, fn ($p) => ($p['action']['code'] ?? '') === 'ok')),
            ],
            // legacy keys for any old references
            'metrics' => [
                'shopee_ads_roas' => $shopeeRoas,
                'business_roas' => $businessRoas,
                'target_roas_gross' => $targetRoasGross,
                'set_roas_shopee' => $setRoasShop ? round($setRoasShop, 2) : null,
                'ads_gmv' => (int) round($adsGmv),
                'ads_spend' => (int) round($adsSpendApi),
            ],
            'recommendation' => [
                'title' => $ceoAction['title'],
                'lines' => $ceoAction['steps'],
            ],
        ];
    }

    /** @return list<array{term: string, plain: string, formula: string}> */
    private function glossary(): array
    {
        return [
            [
                'term' => 'HPP / COGS',
                'plain' => 'Biaya pokok barang (bahan + kemasan) per produk terjual.',
                'formula' => 'COGS = (HPP + packaging) × qty',
            ],
            [
                'term' => 'Margin profit',
                'plain' => 'Sisa dari harga jual setelah HPP — belum potong fee & iklan.',
                'formula' => '(Penjualan kotor − COGS) ÷ Penjualan kotor',
            ],
            [
                'term' => 'ROAS Shopee',
                'plain' => 'Angka di dashboard Shopee Ads — omzet atribusi iklan dibagi biaya iklan.',
                'formula' => 'GMV iklan (AMS) ÷ Spend iklan',
            ],
            [
                'term' => 'ROAS bisnis',
                'plain' => 'Omzet order nyata (termasuk organik) dibagi biaya iklan — cek untung riil.',
                'formula' => 'Penjualan kotor order ÷ Spend iklan',
            ],
            [
                'term' => 'ACOS',
                'plain' => 'Persentase iklan dari omzet — semakin kecil semakin efisien.',
                'formula' => 'Spend iklan ÷ Penjualan kotor',
            ],
            [
                'term' => 'Set ROAS',
                'plain' => 'Angka yang CEO input di Shopee Ads agar iklan tidak boros.',
                'formula' => 'Target ROAS ÷ 70% (buffer Shopee)',
            ],
        ];
    }

    /** @return list<array{label: string, formula: string}> */
    private function formulasReference(): array
    {
        return [
            ['label' => 'Margin profit', 'formula' => '(Harga jual − HPP) ÷ Harga jual'],
            ['label' => 'Target ACOS', 'formula' => 'Margin − Fee% − Ops% − Target laba%'],
            ['label' => 'Target ROAS', 'formula' => '1 ÷ Target ACOS'],
            ['label' => 'Set ROAS (Shopee)', 'formula' => 'Target ROAS ÷ 70%'],
            ['label' => 'ROAS Shopee (AMS)', 'formula' => 'GMV iklan ÷ Spend'],
            ['label' => 'ROAS impas bisnis', 'formula' => '1 ÷ (Laba kotor ÷ Kotor)'],
        ];
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return array{severity: string, title: string, headline: string, steps: list<string>, cta: ?array{label: string, route: string}}
     */
    private function ceoAction(
        ?float $businessRoas,
        ?float $targetRoas,
        ?float $shopeeRoas,
        ?float $setRoas,
        float $netProfit,
        array $products
    ): array {
        $stopCount = count(array_filter($products, fn ($p) => ($p['action']['code'] ?? '') === 'stop'));
        $cutCount = count(array_filter($products, fn ($p) => ($p['action']['code'] ?? '') === 'cut'));

        if ($netProfit < 0) {
            return [
                'severity' => 'danger',
                'title' => 'Toko masih rugi',
                'headline' => 'Iklan saja tidak cukup — cek HPP, fee, dan operasional dulu.',
                'steps' => array_values(array_filter([
                    $setRoas ? 'Di Shopee Ads, set ROAS minimal **' . number_format($setRoas, 1) . 'x**.' : null,
                    'Potong iklan produk merah (Stop/Kurangi) di bawah.',
                    'Buka Kalkulator Harga untuk simulasi naik harga.',
                ])),
                'cta' => ['label' => 'Buka Kalkulator', 'route' => 'ceo.kalkulator'],
            ];
        }

        if ($businessRoas !== null && $targetRoas && $businessRoas < $targetRoas * 0.9) {
            return [
                'severity' => 'warning',
                'title' => 'Iklan terlalu agresif',
                'headline' => 'ROAS bisnis ' . number_format($businessRoas, 1) . 'x — di bawah aman ' . number_format($targetRoas, 1) . 'x.',
                'steps' => array_values(array_filter([
                    $setRoas ? 'Set ROAS di Shopee: **' . number_format($setRoas, 1) . 'x**.' : null,
                    $cutCount + $stopCount > 0 ? "Review **{$cutCount}** produk kurangi & **{$stopCount}** stop iklan." : 'Review produk dengan spend tertinggi.',
                ])),
                'cta' => ['label' => 'Lihat produk', 'route' => '#roas-products'],
            ];
        }

        if ($businessRoas !== null && $targetRoas && $businessRoas >= $targetRoas) {
            return [
                'severity' => 'success',
                'title' => 'Iklan sehat',
                'headline' => 'ROAS bisnis ' . number_format($businessRoas, 1) . 'x — boleh scale produk hijau (Star) saja.',
                'steps' => array_values(array_filter([
                    $setRoas ? 'Pertahankan Set ROAS ≥ **' . number_format($setRoas, 1) . 'x**.' : null,
                    $shopeeRoas ? 'Di dashboard Shopee, ROAS GMV saat ini **' . number_format($shopeeRoas, 1) . 'x**.' : null,
                ])),
                'cta' => null,
            ];
        }

        return [
            'severity' => 'info',
            'title' => 'Lengkapi data iklan',
            'headline' => 'Sync AMS atau pilih periode yang ada spend iklan.',
            'steps' => ['Buka Integrasi Shopee → pastikan AMS terhubung.', 'Jalankan sync iklan dari Kelola Data.'],
            'cta' => ['label' => 'Kelola Data', 'route' => 'manage.index'],
        ];
    }

    /**
     * @param array<int|string, array<string, mixed>> $adsByProduct
     * @return list<array<string, mixed>>
     */
    private function productAdvice(array $products, float $safety, array $adsByProduct): array
    {
        $out = [];
        foreach ($products as $p) {
            $gross = (float) ($p['gross'] ?? 0);
            $gp = (float) ($p['gross_profit'] ?? 0);
            $spend = (float) ($p['ads_spend'] ?? 0);
            if ($spend <= 0) {
                continue;
            }

            $pid = (int) ($p['product_id'] ?? 0);
            $ext = $p['external_item_id'] ?? '';
            $ads = $adsByProduct[$pid] ?? $adsByProduct['ext:' . $ext] ?? null;

            $gmv = (float) ($ads['gmv'] ?? 0);
            $shopeeRoas = ($ads['shopee_roas'] ?? null) ?? ($spend > 0 && $gmv > 0 ? $gmv / $spend : null);
            $businessRoas = $gross > 0 ? $gross / $spend : null;

            $margin = $gross > 0 ? $gp / $gross : 0;
            $targetRoas = $margin > 0 ? (1 / $margin) * $safety : null;

            $setRoas = null;
            if ($margin > 0 && $gross > 0 && $gmv > 0) {
                $beShopee = (1 / $margin) / max(0.01, $gross / $gmv);
                $setRoas = ($beShopee * $safety) / 0.70;
            }

            $netProfit = (int) round($p['net_profit'] ?? 0);
            $action = $this->productAction(
                $businessRoas,
                $targetRoas,
                $shopeeRoas,
                $setRoas,
                $netProfit,
                $p['tier'] ?? null
            );

            $out[] = [
                'product_id' => $pid,
                'name' => $p['name'],
                'spend' => (int) round($spend),
                'gmv_ams' => (int) round($gmv),
                'shopee_roas' => $shopeeRoas !== null ? round($shopeeRoas, 2) : null,
                'business_roas' => $businessRoas !== null ? round($businessRoas, 2) : null,
                'set_roas_shopee' => $setRoas !== null ? round($setRoas, 2) : null,
                'target_roas' => $targetRoas ? round($targetRoas, 2) : null,
                'net_profit' => $netProfit,
                'tier' => $p['tier'] ?? null,
                'action' => $action,
            ];
        }

        usort($out, fn ($a, $b) => ($b['spend'] ?? 0) <=> ($a['spend'] ?? 0));

        return array_slice($out, 0, 30);
    }

    private function productAction(
        ?float $businessRoas,
        ?float $targetRoas,
        ?float $shopeeRoas,
        ?float $setRoas,
        int $netProfit,
        ?string $tier
    ): array {
        if ($netProfit < 0 && ($businessRoas === null || ($targetRoas && $businessRoas < $targetRoas))) {
            return [
                'code' => 'stop',
                'label' => 'Stop iklan',
                'hint' => 'Produk rugi — matikan atau potong drastis.',
                'severity' => 'danger',
            ];
        }

        if ($targetRoas && $businessRoas !== null && $businessRoas < $targetRoas * 0.9) {
            return [
                'code' => 'cut',
                'label' => 'Kurangi iklan',
                'hint' => $setRoas ? 'Naikkan Set ROAS ke ' . number_format($setRoas, 1) . 'x.' : 'Turunkan budget.',
                'severity' => 'warning',
            ];
        }

        if ($tier === 'star' && $netProfit >= 0 && $businessRoas && $targetRoas && $businessRoas >= $targetRoas) {
            return [
                'code' => 'scale',
                'label' => 'Boleh scale',
                'hint' => 'Produk sehat — tambah budget pelan-pelan.',
                'severity' => 'success',
            ];
        }

        return [
            'code' => 'ok',
            'label' => 'Pertahankan',
            'hint' => 'Monitor mingguan.',
            'severity' => 'info',
        ];
    }
}
