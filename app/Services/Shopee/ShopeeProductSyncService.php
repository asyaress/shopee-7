<?php

namespace App\Services\Shopee;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopeeToken;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopeeProductSyncService
{
    public function __construct(private readonly ShopeeClient $client)
    {
    }

    /**
     * Sync Shopee products into `products` (+ `product_variants`).
     */
    public function syncAll(ShopeeToken $token, int $pageSize = 100): array
    {
        $pageSize = max(1, min(100, $pageSize));

        $created = 0;
        $updated = 0;
        $processed = 0;

        $offset = 0;
        $hasNext = true;

        while ($hasNext) {
            $listResp = $this->client->requestPrivate('GET', '/api/v2/product/get_item_list', [
                // Region/account berbeda bisa punya param berbeda; ini yang paling umum di v2.
                'page_size' => $pageSize,
                'offset' => $offset,
                'item_status' => 'NORMAL',
            ], $token);

            $items = Arr::get($listResp, 'item', Arr::get($listResp, 'item_list', []));
            $items = is_array($items) ? $items : [];
            $itemIds = array_values(array_filter(array_map(fn($it) => Arr::get($it, 'item_id'), $items)));

            if (empty($itemIds)) {
                break;
            }

            foreach (array_chunk($itemIds, 50) as $chunk) {
                $baseInfo = $this->client->requestPrivate('GET', '/api/v2/product/get_item_base_info', [
                    'item_id_list' => implode(',', $chunk),
                ], $token);

                $itemList = Arr::get($baseInfo, 'item_list', []);
                $itemList = is_array($itemList) ? $itemList : [];

                foreach ($itemList as $it) {
                    $processed++;

                    [$isCreated, $isUpdated] = $this->upsertProduct($token, $it);
                    $created += $isCreated ? 1 : 0;
                    $updated += $isUpdated ? 1 : 0;
                }
            }

            // Pagination handling (Shopee responses vary)
            $hasNext = (bool) (Arr::get($listResp, 'has_next_page', false) || Arr::get($listResp, 'more', false));
            $nextOffset = Arr::get($listResp, 'next_offset');
            if ($nextOffset !== null) {
                $offset = (int) $nextOffset;
            } else {
                $offset += $pageSize;
            }
        }

        return compact('created', 'updated', 'processed');
    }

    private function upsertProduct(ShopeeToken $token, array $it): array
    {
        $itemId = Arr::get($it, 'item_id');
        if (!$itemId) {
            return [false, false];
        }

        $name = (string) (Arr::get($it, 'item_name') ?? Arr::get($it, 'name') ?? 'Shopee Item');
        $desc = Arr::get($it, 'description');

        $status = (string) (Arr::get($it, 'item_status') ?? Arr::get($it, 'status') ?? 'NORMAL');
        $isActive = strtoupper($status) === 'NORMAL';

        $imageUrl = null;
        $images = Arr::get($it, 'image.image_url_list', Arr::get($it, 'image_url_list', []));
        if (is_array($images) && count($images) > 0) {
            $imageUrl = (string) $images[0];
        }

        // Resolve base_price: try to find min price from different shapes
        $basePrice = $this->resolvePrice($it);

        $created = false;
        $updated = false;

        DB::transaction(function () use ($token, $itemId, $name, $desc, $status, $isActive, $imageUrl, $basePrice, $it, &$created, &$updated) {
            $product = Product::where('external_platform', 'shopee')
                ->where('external_shop_id', (int) $token->shop_id)
                ->where('external_item_id', (int) $itemId)
                ->first();

            if (!$product) {
                $product = new Product();
                $created = true;
            } else {
                $updated = true;
            }

            $product->fill([
                'name' => $name,
                'description' => is_string($desc) ? $desc : null,
                'category' => (string) (Arr::get($it, 'category_id') ?? Arr::get($it, 'category') ?? null),
                'base_price' => $basePrice,
                'unit' => $product->unit ?: 'pcs',
                'is_active' => $isActive,
                'external_platform' => 'shopee',
                'external_shop_id' => (int) $token->shop_id,
                'external_item_id' => (int) $itemId,
                'external_sku' => (string) (Arr::get($it, 'item_sku') ?? Arr::get($it, 'sku') ?? null),
                'image_url' => $imageUrl,
                'external_status' => $status,
                'raw' => $it,
            ]);

            $product->save();

            // Link order_items (Shopee) to this product for nicer UI
            try {
                DB::table('order_items')
                    ->whereNull('product_id')
                    ->where('external_platform', 'shopee')
                    ->where('external_item_id', (int) $itemId)
                    ->update(['product_id' => $product->id]);
            } catch (\Throwable $e) {
                // ignore if table not present
            }

            // Sync variants best-effort (some shops might not have access)
            $this->syncVariantsBestEffort($token, $product, (int) $itemId);
        });

        return [$created, $updated];
    }

    private function syncVariantsBestEffort(ShopeeToken $token, Product $product, int $itemId): void
    {
        try {
            $resp = $this->client->requestPrivate('GET', '/api/v2/product/get_model_list', [
                'item_id' => $itemId,
            ], $token);

            $models = Arr::get($resp, 'model', Arr::get($resp, 'model_list', []));
            $models = is_array($models) ? $models : [];

            foreach ($models as $m) {
                $modelId = Arr::get($m, 'model_id');
                if (!$modelId) {
                    continue;
                }

                $price = $this->resolvePrice($m);
                $stock = Arr::get($m, 'stock_info_v2.summary_info.total_available_stock')
                    ?? Arr::get($m, 'stock')
                    ?? null;

                ProductVariant::updateOrCreate([
                    'product_id' => $product->id,
                    'external_platform' => 'shopee',
                    'external_model_id' => (int) $modelId,
                ], [
                    'name' => (string) (Arr::get($m, 'model_name') ?? Arr::get($m, 'name') ?? null),
                    'sku' => (string) (Arr::get($m, 'model_sku') ?? Arr::get($m, 'sku') ?? null),
                    'price' => $price,
                    'stock' => is_null($stock) ? null : (int) $stock,
                    'raw' => $m,
                ]);
            }
        } catch (\Throwable $e) {
            Log::info('Shopee model sync skipped', [
                'item_id' => $itemId,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePrice(array $data): ?float
    {
        // Common v2 shapes: price_info, price_info_v2, or direct price fields
        $candidates = [];

        $direct = Arr::get($data, 'price');
        if (!is_null($direct) && $direct !== '') {
            $candidates[] = (float) $direct;
        }

        foreach (['price_info', 'price_info_v2', 'model_price_info', 'model_price_info_v2'] as $key) {
            $pi = Arr::get($data, $key);
            if (!is_array($pi)) {
                continue;
            }

            // Sometimes it's list
            if (Arr::isList($pi)) {
                foreach ($pi as $row) {
                    $v = Arr::get($row, 'current_price') ?? Arr::get($row, 'original_price') ?? Arr::get($row, 'price');
                    if (!is_null($v) && $v !== '') {
                        $candidates[] = (float) $v;
                    }
                }
            } else {
                $v = Arr::get($pi, 'current_price') ?? Arr::get($pi, 'original_price') ?? Arr::get($pi, 'price');
                if (!is_null($v) && $v !== '') {
                    $candidates[] = (float) $v;
                }
            }
        }

        // Fallback: model_discounted_price etc.
        foreach (['model_discounted_price', 'model_original_price', 'original_price'] as $k) {
            $v = Arr::get($data, $k);
            if (!is_null($v) && $v !== '') {
                $candidates[] = (float) $v;
            }
        }

        $candidates = array_values(array_filter($candidates, fn($n) => $n > 0));
        if (empty($candidates)) {
            return null;
        }

        return min($candidates);
    }
}
