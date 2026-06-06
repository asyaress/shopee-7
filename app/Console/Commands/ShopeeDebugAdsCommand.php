<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ShopeeDebugAdsCommand extends Command
{
    protected $signature = 'shopee:debug-ads
        {--env= : Override shopee.env}
        {--shop_id= : Override shop_id}
        {--date= : Tanggal YYYY-MM-DD untuk cek performance}
        {--campaign_id= : Campaign ID spesifik untuk dicek}
        {--page_size=50 : Page size untuk response performance}';

    protected $description = 'Dump raw Shopee ads responses for product-level debugging';

    public function handle(): int
    {
        [$client, $token] = $this->resolveAdsContext();
        if (!$token || !$client) {
            $this->error('Tidak ada token Shopee. Connect dulu di halaman Kelola Data.');
            return self::FAILURE;
        }

        $date = $this->option('date') ? Carbon::parse((string) $this->option('date')) : Carbon::now();
        $pageSize = max(1, min(100, (int) ($this->option('page_size') ?: 50)));

        $this->info("DEBUG ads app={$token->app_type} env={$token->env} shop_id={$token->shop_id} date={$date->toDateString()}");

        $campaignListRaw = $client->requestPrivateRaw('GET', '/api/v2/ads/get_product_level_campaign_id_list', [
            'ad_type' => 'all',
            'offset' => 0,
            'limit' => 50,
        ], $token);
        $this->dumpJson('PRODUCT_CAMPAIGN_ID_LIST_RAW', $campaignListRaw);

        $campaignIds = $this->extractCampaignIds($campaignListRaw);
        $campaignId = (string) ($this->option('campaign_id') ?: ($campaignIds[0] ?? ''));

        if ($campaignId === '') {
            $this->warn('Tidak ada campaign_id yang bisa dipakai untuk cek setting info.');
            return self::SUCCESS;
        }

        $this->line("Using campaign_id={$campaignId}");

        $infoTypes = config('shopee.product_campaign_setting_info_types', [1, 2, 3, 4]);
        $infoTypeList = implode(',', array_map('intval', is_array($infoTypes) ? $infoTypes : [1, 2, 3, 4]));

        $settingRaw = $client->requestPrivateRaw('GET', '/api/v2/ads/get_product_level_campaign_setting_info', [
            'campaign_id_list' => $campaignId,
            'info_type_list' => $infoTypeList,
        ], $token);
        $this->dumpJson('PRODUCT_CAMPAIGN_SETTING_INFO_RAW', $settingRaw);

        $performanceRaw = $client->requestPrivateRaw('GET', '/api/v2/ams/get_product_performance', [
            'period_type' => 'Day',
            'start_date' => $date->format('Ymd'),
            'end_date' => $date->format('Ymd'),
            'page_no' => 1,
            'page_size' => $pageSize,
            'order_type' => 'ConfirmedOrder',
            'channel' => 'AllChannel',
        ], $token);
        $this->dumpJson('PRODUCT_PERFORMANCE_RAW', $performanceRaw);

        return self::SUCCESS;
    }

    /**
     * @return array{0:?ShopeeClient,1:?ShopeeToken}
     */
    private function resolveAdsContext(): array
    {
        $env = $this->option('env') ?: config('shopee.env', 'test');
        $shopId = $this->option('shop_id') ?: config('shopee.shop_id');

        if (ShopeeClient::isConfigured(ShopeeToken::APP_ADS)) {
            $adsToken = ShopeeToken::query()
                ->where('env', $env)
                ->forApp(ShopeeToken::APP_ADS);
            if ($shopId) {
                $adsToken->where('shop_id', (int) $shopId);
            }

            $resolved = $adsToken->orderByDesc('id')->first();
            if (!$resolved) {
                throw new \RuntimeException('App Ads Service sudah diisi di .env, tapi token Ads untuk shop ini belum terhubung.');
            }

            return [ShopeeClient::fromConfig(ShopeeToken::APP_ADS), $resolved];
        }

        $mainToken = ShopeeToken::query()
            ->where('env', $env)
            ->forApp(ShopeeToken::APP_MAIN);
        if ($shopId) {
            $mainToken->where('shop_id', (int) $shopId);
        }

        return [ShopeeClient::fromConfig(ShopeeToken::APP_MAIN), $mainToken->orderByDesc('id')->first()];
    }

    /**
     * @param array<string, mixed> $response
     * @return array<int, string>
     */
    private function extractCampaignIds(array $response): array
    {
        $payload = Arr::get($response, 'response');
        $payload = is_array($payload) ? $payload : $response;

        $items = [];
        foreach ([
            'campaign_list',
            'campaign_id_list',
            'campaigns',
            'item_list',
            'list',
            'items',
        ] as $key) {
            $value = Arr::get($payload, $key);
            if (is_array($value) && !empty($value)) {
                $items = $value;
                break;
            }
        }

        if (empty($items) && is_array($payload) && array_is_list($payload)) {
            $items = $payload;
        }

        $ids = [];
        foreach ($items as $item) {
            $campaignId = is_array($item)
                ? (Arr::get($item, 'campaign_id') ?? Arr::get($item, 'id') ?? Arr::get($item, 'campaignId'))
                : $item;

            if ($campaignId === null || $campaignId === '') {
                continue;
            }

            $ids[] = (string) $campaignId;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function dumpJson(string $label, array $payload): void
    {
        $this->line("=== {$label} ===");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }
}
