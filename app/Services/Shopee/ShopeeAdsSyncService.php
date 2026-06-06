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

        $rows = $this->fetchRowsByChunks($token, $start, $end, $pauseSeconds);

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
     * Shopee ads performance endpoints only accept about 1 month per request.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchRowsByChunks(ShopeeToken $token, Carbon $start, Carbon $end, int $pauseSeconds = 2): array
    {
        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $maxChunkDays = 28;

        while ($cursor->lte($end)) {
            $chunkStart = $cursor->copy()->startOfDay();
            $chunkEnd = $chunkStart->copy()->addDays($maxChunkDays - 1)->endOfDay();
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunkRows = $this->fetchChunkWithRetry($token, $chunkStart, $chunkEnd);

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
    private function fetchChunkWithRetry(ShopeeToken $token, Carbon $chunkStart, Carbon $chunkEnd): array
    {
        $attempts = 0;
        $maxAttempts = 3;
        $sleepSeconds = 5;

        while (true) {
            try {
                return $this->fetchChunk($token, $chunkStart, $chunkEnd);
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
    private function fetchChunk(ShopeeToken $token, Carbon $chunkStart, Carbon $chunkEnd): array
    {
        try {
            return $this->fetchProductDailyPerformance($token, $chunkStart, $chunkEnd);
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
    private function fetchProductDailyPerformance(ShopeeToken $token, Carbon $start, Carbon $end): array
    {
        $path = config('shopee.ads_endpoints.product_daily');

        $body = [
            'start_date' => $start->format('d-m-Y'),
            'end_date' => $end->format('d-m-Y'),
        ];

        $response = $this->client->requestPrivate('GET', $path, $body, $token);

        return $this->normalizeRows($response);
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
