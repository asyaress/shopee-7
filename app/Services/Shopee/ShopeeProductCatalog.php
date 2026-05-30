<?php

namespace App\Services\Shopee;

use App\Models\ShopeeToken;
use Illuminate\Support\Arr;

/**
 * Shared helpers for listing Shopee products across item_status values.
 */
class ShopeeProductCatalog
{
    /** @return list<string> */
    public static function itemStatuses(): array
    {
        $statuses = config('shopee.product_item_statuses', ['NORMAL']);

        return array_values(array_filter(array_map(
            static fn ($s) => strtoupper(trim((string) $s)),
            is_array($statuses) ? $statuses : ['NORMAL']
        )));
    }

    public static function isActiveStatus(string $status): bool
    {
        return strtoupper($status) === 'NORMAL';
    }

    /**
     * @return list<int>
     */
    public static function fetchAllItemIds(ShopeeClient $client, ShopeeToken $token, int $pageSize = 100): array
    {
        $ids = [];

        foreach (self::itemStatuses() as $status) {
            $offset = 0;
            $hasNext = true;

            while ($hasNext) {
                $listResp = $client->requestPrivate('GET', '/api/v2/product/get_item_list', [
                    'page_size' => $pageSize,
                    'offset' => $offset,
                    'item_status' => $status,
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
        }

        return array_values(array_unique($ids));
    }
}
