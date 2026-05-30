<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncAdsCommand extends Command
{
    protected $signature = 'shopee:sync-ads
        {--days= : Override SHOPEE_ADS_SYNC_DAYS}
        {--env= : Override shopee.env}
        {--shop_id= : Override shop_id}';

    protected $description = 'Sync Shopee product ads performance ke database lokal';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('shopee.ads_sync_days', 30));
        $days = max(1, min(90, $days));

        $token = $this->getToken();
        if (!$token) {
            $this->error('Tidak ada token Shopee. Connect dulu di halaman Kelola Data.');
            return self::FAILURE;
        }

        try {
            $svc = new ShopeeAdsSyncService(ShopeeClient::fromConfig());
            $this->info("Sync ads env={$token->env} shop_id={$token->shop_id} days={$days} ...");
            $result = $svc->sync($token, $days);
            $msg = "DONE ads: saved={$result['saved']} skipped={$result['skipped']}";
            $this->info($msg);
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $this->warn($err);
                }
            }
            Log::info('[CRON] ' . $msg, $result);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal sync ads: ' . $e->getMessage());
            Log::error('[CRON] sync-ads failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    private function getToken(): ?ShopeeToken
    {
        $env = $this->option('env') ?: config('shopee.env', 'test');
        $shopId = $this->option('shop_id') ?: config('shopee.shop_id');

        $q = ShopeeToken::query()->where('env', $env);
        if ($shopId) {
            $q->where('shop_id', (int) $shopId);
        }

        return $q->orderByDesc('id')->first();
    }
}
