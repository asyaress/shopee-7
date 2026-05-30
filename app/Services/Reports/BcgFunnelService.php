<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\ShopeeProductPerformance;
use App\Models\ProductSalesTarget;
use App\Support\ShopeeLinkHelper;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;

/**
 * BCG klasik berbasis trafik & konversi (Performa Produk Seller Center).
 */
class BcgFunnelService
{
    public const STAR = 'star';
    public const CASH_COW = 'cash_cow';
    public const QUESTION_MARK = 'question_mark';
    public const DOG = 'dog';

    public function build(int $shopId, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $periodEnd = $periodEnd ?? now()->endOfDay();
        $periodStart = $periodStart ?? now()->subDays(30)->startOfDay();

        $rows = ShopeeProductPerformance::query()
            ->where('shop_id', $shopId)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->orderByDesc('sales_gmv')
            ->get();

        if ($rows->isEmpty()) {
            $rows = ShopeeProductPerformance::query()
                ->where('shop_id', $shopId)
                ->orderByDesc('period_end')
                ->orderByDesc('sales_gmv')
                ->get()
                ->groupBy(fn ($r) => $r->external_item_id)
                ->map(fn ($g) => $g->first())
                ->values();
        }

        $convThreshold = (float) config('monitoring.bcg_funnel.conversion_threshold', 0.02);
        $trafficBaseline = $this->trafficBaseline($rows);

        $sourceCounts = [
            ShopeeProductPerformance::SOURCE_AUTO => 0,
            ShopeeProductPerformance::SOURCE_IMPORT => 0,
        ];

        $quadrants = [
            self::STAR => [],
            self::CASH_COW => [],
            self::QUESTION_MARK => [],
            self::DOG => [],
        ];

        $targets = ProductSalesTarget::query()
            ->where('shop_id', $shopId)
            ->where('year_month', now()->format('Y-m'))
            ->get()
            ->keyBy('product_id');

        foreach ($rows as $row) {
            $conv = (float) ($row->conversion_rate ?? 0);
            if ($conv <= 0 && $row->visitors > 0) {
                $conv = $row->units_sold / max(1, $row->visitors);
            }

            $traffic = (int) $row->visitors;
            $highConv = $conv >= $convThreshold;
            $highTraffic = $traffic >= $trafficBaseline;

            $quadrant = match (true) {
                $highConv && $highTraffic => self::STAR,
                $highConv && !$highTraffic => self::CASH_COW,
                !$highConv && $highTraffic => self::QUESTION_MARK,
                default => self::DOG,
            };

            $product = $row->product_id
                ? Product::find($row->product_id)
                : Product::query()
                    ->where('external_platform', 'shopee')
                    ->where('external_shop_id', $shopId)
                    ->where('external_item_id', $row->external_item_id)
                    ->first();

            $pid = $product?->id;
            $target = $pid ? $targets->get($pid) : null;
            $source = $row->source ?: ShopeeProductPerformance::SOURCE_AUTO;
            if (isset($sourceCounts[$source])) {
                $sourceCounts[$source]++;
            }

            $item = [
                'external_item_id' => $row->external_item_id,
                'product_id' => $pid,
                'name' => $row->product_name ?: ($product?->name ?? '—'),
                'parent_sku' => $row->parent_sku ?: ($product?->external_sku ?? ''),
                'source' => $source,
                'source_label' => $this->sourceLabel($source),
                'visitors' => $traffic,
                'page_views' => (int) $row->page_views,
                'units_sold' => (int) $row->units_sold,
                'sales_gmv' => (float) $row->sales_gmv,
                'conversion_rate' => round($conv * 100, 2),
                'quadrant' => $quadrant,
                'quadrant_label' => $this->quadrantLabel($quadrant),
                'ads_action' => $this->adsRecommendation($quadrant),
                'links' => [
                    'product' => ShopeeLinkHelper::productUrl($shopId, (int) $row->external_item_id),
                    'ads' => ShopeeLinkHelper::adsProductUrl($shopId, (int) $row->external_item_id),
                ],
                'target_gross' => $target ? (int) round($target->target_gross) : null,
                'target_units' => $target?->target_units,
                'target_units_progress' => ($target?->target_units && $target->target_units > 0)
                    ? min(1, $row->units_sold / $target->target_units)
                    : null,
            ];

            $quadrants[$quadrant][] = $item;
        }

        $dataSource = $this->resolveDataSource($rows, $sourceCounts);
        $lastAutoSync = ShopeeProductPerformance::query()
            ->where('shop_id', $shopId)
            ->where('source', ShopeeProductPerformance::SOURCE_AUTO)
            ->max('updated_at');

        return [
            'period' => [
                'start' => $periodStart->toDateString(),
                'end' => $periodEnd->toDateString(),
                'label' => $periodStart->format('d M Y') . ' — ' . $periodEnd->format('d M Y'),
            ],
            'settings' => [
                'conversion_threshold_pct' => round($convThreshold * 100, 2),
                'traffic_baseline' => $trafficBaseline,
            ],
            'data_source' => $dataSource,
            'data_source_label' => $this->dataSourceLabel($dataSource),
            'source_counts' => $sourceCounts,
            'last_auto_sync' => $lastAutoSync,
            'counts' => array_map('count', $quadrants),
            'quadrants' => $quadrants,
            'has_data' => $rows->isNotEmpty(),
            'import_url' => route('monitoring.bcg.import'),
            'sync_url' => route('monitoring.bcg.sync'),
            'performance_url' => ShopeeLinkHelper::sellerPerformanceUrl($shopId),
        ];
    }

    private function resolveDataSource($rows, array $sourceCounts): ?string
    {
        if ($rows->isEmpty()) {
            return null;
        }

        $auto = (int) ($sourceCounts[ShopeeProductPerformance::SOURCE_AUTO] ?? 0);
        $import = (int) ($sourceCounts[ShopeeProductPerformance::SOURCE_IMPORT] ?? 0);

        if ($import > 0 && $auto === 0) {
            return ShopeeProductPerformance::SOURCE_IMPORT;
        }
        if ($auto > 0 && $import === 0) {
            return ShopeeProductPerformance::SOURCE_AUTO;
        }

        return 'mixed';
    }

    private function dataSourceLabel(?string $source): string
    {
        return match ($source) {
            ShopeeProductPerformance::SOURCE_IMPORT => 'Import Seller Center (akurat)',
            ShopeeProductPerformance::SOURCE_AUTO => 'Auto (perkiraan)',
            'mixed' => 'Campuran — import + auto',
            default => 'Belum ada data',
        };
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            ShopeeProductPerformance::SOURCE_IMPORT => 'Seller Center',
            default => 'Auto',
        };
    }

    private function trafficBaseline($rows): int
    {
        $mode = config('monitoring.bcg_funnel.traffic_mode', 'median');
        if ($mode === 'fixed') {
            return (int) config('monitoring.bcg_funnel.traffic_fixed', 100);
        }

        $values = $rows->pluck('visitors')->filter(fn ($v) => $v > 0)->sort()->values();
        if ($values->isEmpty()) {
            return (int) config('monitoring.bcg_funnel.traffic_fixed', 100);
        }

        $mid = (int) floor($values->count() / 2);
        return (int) $values[$mid];
    }

    private function quadrantLabel(string $q): string
    {
        return match ($q) {
            self::STAR => 'Star — konversi & trafik kuat',
            self::CASH_COW => 'Cash Cow — konversi bagus, trafik rendah',
            self::QUESTION_MARK => 'Question Mark — trafik tinggi, konversi lemah',
            default => 'Dog — trafik & konversi lemah',
        };
    }

    private function adsRecommendation(string $q): string
    {
        return match ($q) {
            self::STAR => 'Iklan dengan target ROAS agresif — scale',
            self::CASH_COW => 'Iklan ROAS konservatif — tingkatkan trafik',
            self::QUESTION_MARK => 'Perbaiki listing/harga sebelum scale iklan',
            default => 'Hentikan iklan / evaluasi stop SKU',
        };
    }
}
