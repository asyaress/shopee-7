<?php

namespace App\Services\Shopee;

use App\Models\Product;
use App\Models\ShopeeProductPerformance;
use App\Models\ShopeeToken;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Semi-otomatis BCG: views dari get_item_extra_info + qty order lokal.
 * Tidak menimpa baris source=import (Seller Center).
 */
class ShopeeBcgSyncService
{
    public const SOURCE_AUTO = 'auto';
    public const SOURCE_IMPORT = 'import';

    public function __construct(private readonly ShopeeClient $client)
    {
    }

    public function sync(ShopeeToken $token, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $shopId = (int) $token->shop_id;
        $periodEnd = ($periodEnd ?? now())->copy()->endOfDay();
        $days = (int) config('monitoring.bcg_funnel.sync_days', 30);
        $periodStart = ($periodStart ?? now()->subDays($days))->copy()->startOfDay();

        $itemIds = $this->collectItemIds($token);
        if (empty($itemIds)) {
            return [
                'saved' => 0,
                'skipped' => 0,
                'items' => 0,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'message' => 'Tidak ada produk Shopee untuk di-sync.',
            ];
        }

        $productsByItem = Product::query()
            ->where('external_platform', 'shopee')
            ->where('external_shop_id', $shopId)
            ->whereIn('external_item_id', $itemIds)
            ->get()
            ->keyBy(fn (Product $p) => (int) $p->external_item_id);

        $orderStats = $this->aggregateOrderStats($shopId, $periodStart, $periodEnd);

        $saved = 0;
        $skipped = 0;

        foreach (array_chunk($itemIds, 50) as $chunk) {
            try {
                $response = $this->client->requestPrivate('GET', '/api/v2/product/get_item_extra_info', [
                    'item_id_list' => implode(',', $chunk),
                ], $token);
            } catch (\Throwable $e) {
                Log::warning('[BCG sync] get_item_extra_info failed', [
                    'shop_id' => $shopId,
                    'chunk_size' => count($chunk),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $itemList = Arr::get($response, 'item_list', []);
            if (!is_array($itemList)) {
                continue;
            }

            foreach ($itemList as $item) {
                $itemId = (int) Arr::get($item, 'item_id', 0);
                if ($itemId <= 0) {
                    continue;
                }

                if ($this->shouldSkipAutoUpdate($shopId, $itemId, $periodStart, $periodEnd)) {
                    $skipped++;
                    continue;
                }

                $views = max(0, (int) Arr::get($item, 'views', 0));
                $stats = $orderStats[$itemId] ?? ['units' => 0, 'gmv' => 0.0];
                $units = (int) $stats['units'];
                $gmv = (float) $stats['gmv'];
                $conv = $views > 0 ? round($units / $views, 4) : null;

                /** @var Product|null $product */
                $product = $productsByItem->get($itemId);

                ShopeeProductPerformance::updateOrCreate(
                    [
                        'shop_id' => $shopId,
                        'external_item_id' => $itemId,
                        'period_start' => $this->periodKey($periodStart),
                        'period_end' => $this->periodKey($periodEnd),
                    ],
                    [
                        'source' => self::SOURCE_AUTO,
                        'product_id' => $product?->id,
                        'product_name' => $product?->name,
                        'parent_sku' => $product?->external_sku,
                        'visitors' => $views,
                        'page_views' => $views,
                        'units_sold' => $units,
                        'sales_gmv' => $gmv,
                        'conversion_rate' => $conv,
                        'raw' => [
                            'api' => 'get_item_extra_info',
                            'views' => $views,
                            'sale_cumulative' => (int) Arr::get($item, 'sale', 0),
                            'synced_at' => now()->toIso8601String(),
                        ],
                    ]
                );
                $saved++;
            }
        }

        return [
            'saved' => $saved,
            'skipped' => $skipped,
            'items' => count($itemIds),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ];
    }

    /** @return list<int> */
    private function collectItemIds(ShopeeToken $token): array
    {
        $shopId = (int) $token->shop_id;

        $fromDb = Product::query()
            ->where('external_platform', 'shopee')
            ->where('external_shop_id', $shopId)
            ->whereNotNull('external_item_id')
            ->pluck('external_item_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($fromDb)) {
            return $fromDb;
        }

        return $this->fetchItemIdsFromApi($token);
    }

    /** @return list<int> */
    private function fetchItemIdsFromApi(ShopeeToken $token): array
    {
        $ids = [];
        $offset = 0;
        $pageSize = 100;
        $hasNext = true;

        while ($hasNext) {
            $listResp = $this->client->requestPrivate('GET', '/api/v2/product/get_item_list', [
                'page_size' => $pageSize,
                'offset' => $offset,
                'item_status' => 'NORMAL',
            ], $token);

            $items = Arr::get($listResp, 'item', Arr::get($listResp, 'item_list', []));
            if (!is_array($items) || empty($items)) {
                break;
            }

            foreach ($items as $it) {
                $id = (int) Arr::get($it, 'item_id', 0);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }

            $hasNext = (bool) (Arr::get($listResp, 'has_next_page', false) || Arr::get($listResp, 'more', false));
            $nextOffset = Arr::get($listResp, 'next_offset');
            $offset = $nextOffset !== null ? (int) $nextOffset : $offset + $pageSize;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, array{units: int, gmv: float}>
     */
    private function aggregateOrderStats(int $shopId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('shopee_order_financials as f', 'f.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->whereRaw('LOWER(COALESCE(orders.jenis_transaksi, "")) = ?', ['shopee'])
            ->where(function ($q) use ($shopId) {
                $q->where('f.shop_id', $shopId)
                    ->orWhere('products.external_shop_id', $shopId);
            })
            ->selectRaw('
                COALESCE(
                    NULLIF(order_items.external_item_id, 0),
                    products.external_item_id,
                    0
                ) as item_id,
                SUM(order_items.quantity) as units,
                SUM(COALESCE(order_items.total_amount, order_items.price * order_items.quantity, 0)) as gmv
            ')
            ->groupBy('item_id')
            ->having('item_id', '>', 0)
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $itemId = (int) $row->item_id;
            if ($itemId <= 0) {
                continue;
            }
            $stats[$itemId] = [
                'units' => (int) $row->units,
                'gmv' => (float) $row->gmv,
            ];
        }

        return $stats;
    }

    private function shouldSkipAutoUpdate(int $shopId, int $itemId, Carbon $periodStart, Carbon $periodEnd): bool
    {
        $existing = ShopeeProductPerformance::query()
            ->where('shop_id', $shopId)
            ->where('external_item_id', $itemId)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->first();

        return $existing && $existing->source === self::SOURCE_IMPORT;
    }

    private function periodKey(Carbon $date): string
    {
        return $date->toDateString();
    }
}
