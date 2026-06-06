<?php

namespace App\Services\Shopee;

use App\Models\Product;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeToken;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ShopeeAdsSyncService
{
    public function __construct(
        private readonly ShopeeClient $client,
    ) {
    }

    /**
     * Sync product-level ads performance for a date range.
     *
     * @return array{saved:int, skipped:int, errors:array<int,string>}
     */
    public function sync(ShopeeToken $token, int $days): array
    {
        $days = max(1, min(90, $days));
        $end = Carbon::now()->endOfDay();
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        return $this->syncBetween($token, $start, $end, 2);
    }

    /**
     * Sync product-level ads performance for an explicit date range.
     *
     * @return array{saved:int, skipped:int, errors:array<int,string>}
     */
    public function syncBetween(ShopeeToken $token, Carbon $start, Carbon $end, int $pauseSeconds = 2): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $saved = 0;
        $skipped = 0;
        $errors = [];

        $rows = $this->aggregateRowsByItemAndDate($this->fetchRowsByChunks($token, $start, $end, $pauseSeconds));

        if (empty($rows)) {
            return ['saved' => 0, 'skipped' => 0, 'errors' => ['Tidak ada data ads pada rentang tanggal ini.']];
        }

        $productMap = Product::query()
            ->whereNotNull('external_item_id')
            ->where('external_item_id', '!=', '')
            ->get(['id', 'external_item_id'])
            ->keyBy(fn ($p) => (string) $p->external_item_id);

        foreach ($rows as $row) {
            $itemId = (string) ($row['item_id'] ?? '');
            $date = $row['date'] ?? null;

            if ($itemId === '' || !$date) {
                $skipped++;
                continue;
            }

            $product = $productMap->get($itemId);

            ShopeeProductAdsDaily::updateOrCreate(
                [
                    'shop_id' => (int) $token->shop_id,
                    'external_item_id' => $itemId,
                    'report_date' => $date,
                ],
                [
                    'product_id' => $product?->id,
                    'spend' => (float) ($row['spend'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'gmv' => (float) ($row['gmv'] ?? 0),
                    'orders' => (int) ($row['orders'] ?? 0),
                    'roas' => isset($row['roas']) ? (float) $row['roas'] : null,
                    'raw' => $row['raw'] ?? null,
                ]
            );

            $saved++;
        }

        return compact('saved', 'skipped', 'errors');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function aggregateRowsByItemAndDate(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemId = (string) ($row['item_id'] ?? '');
            $date = (string) ($row['date'] ?? '');
            if ($itemId === '' || $date === '') {
                continue;
            }

            $key = $itemId . '|' . $date;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'item_id' => $itemId,
                    'date' => $date,
                    'spend' => 0.0,
                    'impressions' => 0,
                    'clicks' => 0,
                    'gmv' => 0.0,
                    'orders' => 0,
                    'roas' => null,
                    'raw' => [],
                ];
            }

            $grouped[$key]['spend'] += (float) ($row['spend'] ?? 0);
            $grouped[$key]['impressions'] += (int) ($row['impressions'] ?? 0);
            $grouped[$key]['clicks'] += (int) ($row['clicks'] ?? 0);
            $grouped[$key]['gmv'] += (float) ($row['gmv'] ?? 0);
            $grouped[$key]['orders'] += (int) ($row['orders'] ?? 0);

            if (!array_key_exists('raw_sources', $grouped[$key])) {
                $grouped[$key]['raw_sources'] = [];
            }
            $grouped[$key]['raw_sources'][] = $row['raw'] ?? $row;
        }

        foreach ($grouped as &$row) {
            $row['roas'] = ($row['spend'] > 0 && $row['gmv'] > 0) ? ($row['gmv'] / $row['spend']) : null;
            $row['raw'] = [
                'sources' => $row['raw_sources'] ?? [],
                'sources_count' => isset($row['raw_sources']) ? count($row['raw_sources']) : 0,
            ];
            unset($row['raw_sources']);
        }
        unset($row);

        return array_values($grouped);
    }

    /**
     * Shopee ads performance endpoints only accept about 1 month per request.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchRowsByChunks(ShopeeToken $token, Carbon $start, Carbon $end, int $pauseSeconds = 2): array
    {
        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $maxChunkDays = 28;
        $campaignIds = $this->fetchProductCampaignIds($token);
        $campaignItemMap = !empty($campaignIds) ? $this->fetchProductCampaignItemMap($token, $campaignIds) : [];

        while ($cursor->lte($end)) {
            $chunkStart = $cursor->copy()->startOfDay();
            $chunkEnd = $chunkStart->copy()->addDays($maxChunkDays - 1)->endOfDay();
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunkRows = $this->fetchChunkWithRetry($token, $chunkStart, $chunkEnd, $campaignIds, $campaignItemMap);

            $rows = array_merge($rows, $chunkRows);

            if ($pauseSeconds > 0 && $cursor->lt($end)) {
                sleep($pauseSeconds);
            }

            $cursor = $chunkEnd->copy()->addDay()->startOfDay();
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchChunkWithRetry(
        ShopeeToken $token,
        Carbon $chunkStart,
        Carbon $chunkEnd,
        array $campaignIds = [],
        array $campaignItemMap = []
    ): array
    {
        $attempts = 0;
        $maxAttempts = 3;
        $sleepSeconds = 5;

        while (true) {
            try {
                return $this->fetchChunk($token, $chunkStart, $chunkEnd, $campaignIds, $campaignItemMap);
            } catch (\Throwable $e) {
                $attempts++;
                $message = strtolower($e->getMessage());
                $isRateLimit = str_contains($message, 'rate_limit') || str_contains($message, 'too many requests');

                if (!$isRateLimit || $attempts >= $maxAttempts) {
                    throw $e;
                }

                Log::warning('Shopee ads rate limit hit, retrying chunk', [
                    'start' => $chunkStart->toDateString(),
                    'end' => $chunkEnd->toDateString(),
                    'attempt' => $attempts,
                    'sleep_seconds' => $sleepSeconds,
                    'error' => $e->getMessage(),
                ]);

                sleep($sleepSeconds);
                $sleepSeconds = min(30, $sleepSeconds * 2);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchChunk(
        ShopeeToken $token,
        Carbon $chunkStart,
        Carbon $chunkEnd,
        array $campaignIds = [],
        array $campaignItemMap = []
    ): array
    {
        try {
            $rows = $this->fetchProductCampaignDailyRows($token, $chunkStart, $chunkEnd, $campaignIds, $campaignItemMap);
            if (!empty($rows)) {
                return $rows;
            }

            throw new \RuntimeException('No product-level rows returned from campaign performance API.');
        } catch (\Throwable $e) {
            Log::warning('Shopee ads product API failed, trying shop-level fallback', [
                'start' => $chunkStart->toDateString(),
                'end' => $chunkEnd->toDateString(),
                'error' => $e->getMessage(),
            ]);

            try {
                return $this->fetchShopDailyAsProductRows($token, $chunkStart, $chunkEnd);
            } catch (\Throwable $e2) {
                throw new \RuntimeException(
                    'Ads API belum dapat diakses: ' . $e2->getMessage()
                    . ' — pastikan permission Marketing/Ads sudah disetujui Shopee.'
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchProductCampaignDailyRows(
        ShopeeToken $token,
        Carbon $start,
        Carbon $end,
        array $campaignIds = [],
        array $campaignItemMap = []
    ): array
    {
        if (empty($campaignIds)) {
            return [];
        }

        $path = config('shopee.ads_endpoints.product_daily');
        $rows = [];

        foreach (array_chunk($campaignIds, 50) as $chunk) {
            $body = [
                'start_date' => $start->format('d-m-Y'),
                'end_date' => $end->format('d-m-Y'),
                'campaign_id_list' => implode(',', $chunk),
            ];

            $response = $this->client->requestPrivate('GET', $path, $body, $token);
            $rows = array_merge($rows, $this->normalizeCampaignPerformanceRows($response, $campaignItemMap));
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function fetchProductCampaignIds(ShopeeToken $token): array
    {
        $path = config('shopee.ads_endpoints.product_campaign_list');
        $offset = 0;
        $limit = 50;
        $campaignIds = [];

        while (true) {
            $response = $this->client->requestPrivate('GET', $path, [
                'ad_type' => 'all',
                'offset' => $offset,
                'limit' => $limit,
            ], $token);

            $payload = $this->responsePayload($response);
            $items = $this->extractList($payload, [
                'campaign_list',
                'campaigns',
                'item_list',
                'list',
                'items',
            ]);

            $count = 0;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $campaignId = Arr::get($item, 'campaign_id') ?? Arr::get($item, 'id');
                if ($campaignId === null || $campaignId === '') {
                    continue;
                }

                $campaignIds[(string) $campaignId] = (string) $campaignId;
                $count++;
            }

            $totalCount = (int) Arr::get($payload, 'total_count', 0);
            $hasMore = (bool) Arr::get($payload, 'has_more', false);

            if ($hasMore) {
                $offset += $limit;
                continue;
            }

            if ($totalCount > 0) {
                $offset += $limit;
                if ($offset >= $totalCount) {
                    break;
                }
                continue;
            }

            if ($count < $limit) {
                break;
            }

            $offset += $limit;
        }

        return array_values($campaignIds);
    }

    /**
     * @param array<int, string> $campaignIds
     * @return array<string, array<int, string>>
     */
    private function fetchProductCampaignItemMap(ShopeeToken $token, array $campaignIds): array
    {
        $path = config('shopee.ads_endpoints.product_campaign_setting');
        $infoTypes = config('shopee.product_campaign_setting_info_types', [1, 2, 3, 4]);
        $infoTypeList = implode(',', array_map('intval', is_array($infoTypes) ? $infoTypes : [1, 2, 3, 4]));
        $map = [];

        foreach (array_chunk($campaignIds, 50) as $chunk) {
            $response = $this->client->requestPrivate('GET', $path, [
                'campaign_id_list' => implode(',', $chunk),
                'info_type_list' => $infoTypeList,
            ], $token);

            $payload = $this->responsePayload($response);
            $items = $this->extractList($payload, [
                'campaign_list',
                'campaigns',
                'list',
                'items',
            ]);

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $campaignId = (string) (Arr::get($item, 'campaign_id') ?? Arr::get($item, 'id') ?? '');
                if ($campaignId === '') {
                    continue;
                }

                $itemIds = $this->extractItemIds($item);
                if (empty($itemIds)) {
                    continue;
                }

                $map[$campaignId] = array_values(array_unique(array_merge($map[$campaignId] ?? [], $itemIds)));
            }
        }

        if (empty($map)) {
            $map = $this->fetchOpenCampaignItemMap($token, $campaignIds);
        }

        return $map;
    }

    /**
     * Fallback mapping source when campaign setting info does not expose item lists.
     *
     * @param array<int, string> $campaignIds
     * @return array<string, array<int, string>>
     */
    private function fetchOpenCampaignItemMap(ShopeeToken $token, array $campaignIds): array
    {
        $path = config('shopee.ads_endpoints.open_campaign_added_product');
        $map = [];
        $cursor = '';

        do {
            $params = [
                'page_size' => 100,
            ];

            if ($cursor !== '') {
                $params['cursor'] = $cursor;
            }

            $response = $this->client->requestPrivate('GET', $path, $params, $token);
            $payload = $this->responsePayload($response);
            $items = $this->extractList($payload, ['item_list', 'products', 'product_list', 'list', 'items']);

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $campaignId = (string) (Arr::get($item, 'campaign_id') ?? '');
                $itemId = $this->normalizeItemId(Arr::get($item, 'item_id') ?? Arr::get($item, 'product_id'));

                if ($campaignId === '' || $itemId === '') {
                    continue;
                }

                if (!empty($campaignIds) && !in_array($campaignId, $campaignIds, true)) {
                    continue;
                }

                $map[$campaignId] = array_values(array_unique(array_merge($map[$campaignId] ?? [], [$itemId])));
            }

            $cursor = (string) (Arr::get($payload, 'cursor') ?? '');
            $hasMore = (bool) Arr::get($payload, 'has_more', false);
        } while ($hasMore && $cursor !== '');

        return $map;
    }

    /**
     * @param array<string, array<int, string>> $campaignItemMap
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCampaignPerformanceRows(array $response, array $campaignItemMap): array
    {
        $payload = $this->responsePayload($response);
        $items = $this->extractList($payload, [
            'campaign_list',
            'product_campaign_list',
            'performance_list',
            'daily_performance_list',
            'item_list',
            'product_list',
            'products',
            'list',
            'rows',
        ]);

        $rows = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $dateRaw = Arr::get($item, 'date')
                ?? Arr::get($item, 'report_date')
                ?? Arr::get($item, 'stat_date')
                ?? Arr::get($item, 'day')
                ?? Arr::get($item, 'fetched_date_range')
                ?? Arr::get($item, 'period_end_time')
                ?? Arr::get($item, 'period_start_time');

            $date = $this->parseDate($dateRaw);
            if (!$date) {
                continue;
            }

            $spend = (float) (
                Arr::get($item, 'expense')
                ?? Arr::get($item, 'spend')
                ?? Arr::get($item, 'cost')
                ?? Arr::get($item, 'ads_expense')
                ?? 0
            );

            $gmv = (float) (
                Arr::get($item, 'broad_gmv')
                ?? Arr::get($item, 'gmv')
                ?? Arr::get($item, 'direct_gmv')
                ?? 0
            );

            $impressions = (int) (Arr::get($item, 'impression') ?? Arr::get($item, 'impressions') ?? 0);
            $clicks = (int) (Arr::get($item, 'click') ?? Arr::get($item, 'clicks') ?? 0);
            $orders = (int) (Arr::get($item, 'order') ?? Arr::get($item, 'orders') ?? Arr::get($item, 'conversion') ?? 0);
            $campaignId = (string) (Arr::get($item, 'campaign_id') ?? Arr::get($item, 'campaignid') ?? Arr::get($item, 'id') ?? '');
            $itemId = $this->normalizeItemId(Arr::get($item, 'item_id') ?? Arr::get($item, 'product_id') ?? Arr::get($item, 'itemid'));

            $roas = null;
            if ($spend > 0 && $gmv > 0) {
                $roas = $gmv / $spend;
            } elseif (($r = Arr::get($item, 'roas')) !== null) {
                $roas = (float) $r;
            }

            $baseRow = [
                'date' => $date,
                'spend' => abs($spend),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'gmv' => $gmv,
                'orders' => $orders,
                'roas' => $roas,
                'raw' => $item,
            ];

            if ($itemId !== '') {
                $rows[] = $baseRow + ['item_id' => $itemId];
                continue;
            }

            $mappedItems = $campaignId !== '' ? ($campaignItemMap[$campaignId] ?? []) : [];
            if (empty($mappedItems)) {
                $syntheticId = $campaignId !== '' ? 'campaign:' . $campaignId : '';
                if ($syntheticId !== '') {
                    $rows[] = $baseRow + [
                        'item_id' => $syntheticId,
                        'raw' => array_merge($item, ['campaign_id' => $campaignId, 'allocation_mode' => 'campaign_only']),
                    ];
                }
                continue;
            }

            $parts = max(1, count($mappedItems));
            foreach ($mappedItems as $index => $mappedItemId) {
                $rows[] = [
                    'item_id' => $mappedItemId,
                    'date' => $date,
                    'spend' => $this->splitDecimal($spend, $parts, 2),
                    'impressions' => $this->splitInteger($impressions, $parts),
                    'clicks' => $this->splitInteger($clicks, $parts),
                    'gmv' => $this->splitDecimal($gmv, $parts, 2),
                    'orders' => $this->splitInteger($orders, $parts),
                    'roas' => $roas,
                    'raw' => array_merge($item, [
                        'campaign_id' => $campaignId,
                        'allocation_mode' => 'equal_split',
                        'allocation_index' => $index + 1,
                        'allocation_total' => $parts,
                    ]),
                ];
            }
        }

        return $rows;
    }

    /**
     * Fallback: shop-level daily — item_id may be missing (stored as shop aggregate key "0").
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchShopDailyAsProductRows(ShopeeToken $token, Carbon $start, Carbon $end): array
    {
        $path = config('shopee.ads_endpoints.shop_daily');

        $body = [
            'start_date' => $start->format('d-m-Y'),
            'end_date' => $end->format('d-m-Y'),
        ];

        $response = $this->client->requestPrivate('GET', $path, $body, $token);

        $rows = $this->normalizeRows($response);

        // Mark as unallocated shop-level if no item_id
        foreach ($rows as &$r) {
            if (empty($r['item_id'])) {
                $r['item_id'] = 'shop_aggregate';
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function responsePayload(array $response): array
    {
        $payload = Arr::get($response, 'response');

        return is_array($payload) ? $payload : $response;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $candidates
     * @return array<int, mixed>
     */
    private function extractList(array $payload, array $candidates): array
    {
        foreach ($candidates as $candidate) {
            $value = Arr::get($payload, $candidate);
            if (is_array($value) && !empty($value)) {
                return $value;
            }
        }

        if (is_array($payload) && array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<int, string>
     */
    private function extractItemIds(array $item): array
    {
        $values = [
            Arr::get($item, 'common_info.item_id_list'),
            Arr::get($item, 'item_id_list'),
            Arr::get($item, 'auto_product_ads_info'),
            Arr::get($item, 'products'),
            Arr::get($item, 'item_list'),
        ];

        $ids = [];
        foreach ($values as $value) {
            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $entry) {
                if (is_array($entry)) {
                    $entryId = Arr::get($entry, 'item_id') ?? Arr::get($entry, 'product_id');
                    if ($entryId !== null && $entryId !== '') {
                        $ids[] = (string) $entryId;
                    }
                } elseif ($entry !== null && $entry !== '') {
                    $ids[] = (string) $entry;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizeItemId(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    private function splitDecimal(float $value, int $parts, int $precision = 2): float
    {
        if ($parts <= 1) {
            return round($value, $precision);
        }

        return round($value / $parts, $precision);
    }

    private function splitInteger(int $value, int $parts): int
    {
        if ($parts <= 1) {
            return $value;
        }

        return (int) round($value / $parts);
    }

    /**
     * Normalize various Shopee ads response shapes into flat rows.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $response): array
    {
        $lists = [
            Arr::get($response, 'ads_list'),
            Arr::get($response, 'campaign_list'),
            Arr::get($response, 'product_campaign_list'),
            Arr::get($response, 'performance_list'),
            Arr::get($response, 'daily_performance_list'),
            Arr::get($response, 'list'),
            is_array($response) && array_is_list($response) ? $response : null,
        ];

        $items = [];
        foreach ($lists as $list) {
            if (is_array($list) && !empty($list)) {
                $items = $list;
                break;
            }
        }

        if (empty($items)) {
            return [];
        }

        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $dateRaw = Arr::get($item, 'date')
                ?? Arr::get($item, 'report_date')
                ?? Arr::get($item, 'stat_date');

            $date = $this->parseDate($dateRaw);
            if (!$date) {
                continue;
            }

            $spend = (float) (
                Arr::get($item, 'expense')
                ?? Arr::get($item, 'spend')
                ?? Arr::get($item, 'cost')
                ?? Arr::get($item, 'ads_expense')
                ?? 0
            );

            $gmv = (float) (
                Arr::get($item, 'broad_gmv')
                ?? Arr::get($item, 'gmv')
                ?? Arr::get($item, 'direct_gmv')
                ?? 0
            );

            $impressions = (int) (Arr::get($item, 'impression') ?? Arr::get($item, 'impressions') ?? 0);
            $clicks = (int) (Arr::get($item, 'click') ?? Arr::get($item, 'clicks') ?? 0);
            $orders = (int) (Arr::get($item, 'order') ?? Arr::get($item, 'orders') ?? Arr::get($item, 'conversion') ?? 0);

            $itemId = Arr::get($item, 'item_id')
                ?? Arr::get($item, 'product_id')
                ?? Arr::get($item, 'itemid');

            $roas = null;
            if ($spend > 0 && $gmv > 0) {
                $roas = $gmv / $spend;
            } elseif ($r = Arr::get($item, 'roas')) {
                $roas = (float) $r;
            }

            $out[] = [
                'item_id' => $itemId !== null ? (string) $itemId : '',
                'date' => $date,
                'spend' => abs($spend),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'gmv' => $gmv,
                'orders' => $orders,
                'roas' => $roas,
                'raw' => $item,
            ];
        }

        return $out;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestamp((int) $value)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        $str = (string) $value;

        if (preg_match('/^(\d{8})-(\d{8})$/', $str, $m)) {
            try {
                return Carbon::createFromFormat('Ymd', $m[2])->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/^\d{8}$/', $str)) {
            try {
                return Carbon::createFromFormat('Ymd', $str)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $str)->toDateString();
            } catch (\Throwable) {
                // continue
            }
        }

        try {
            return Carbon::parse($str)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
