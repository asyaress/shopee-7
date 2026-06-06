<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeOrderSyncService;
use App\Services\Shopee\ShopeeProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncAllCommand extends Command
{
    protected $signature = 'shopee:sync-all
        {--days=7 : Ambil order terakhir berapa hari}
        {--ads_days= : Override SHOPEE_ADS_SYNC_DAYS}
        {--page_size=100 : Page size product (1-100)}
        {--env= : Override shopee.env (test/prod)}
        {--shop_id= : Override shop_id (kalau multi shop)}';

    protected $description = 'Sync Shopee products + orders + ads ke database lokal';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $days = max(1, min(90, $days));

        $pageSize = (int) $this->option('page_size');
        $pageSize = max(1, min(100, $pageSize));

        $envOpt = $this->option('env');
        $shopIdOpt = $this->option('shop_id');

        $token = $this->getCurrentToken($envOpt, $shopIdOpt);
        if (!$token) {
            $this->error('Tidak ada token Shopee. Connect dulu di UI.');
            return self::FAILURE;
        }

        $mainClient = ShopeeClient::fromConfig(ShopeeToken::APP_MAIN);

        try {
            $this->info("Sync ALL env={$token->env} shop_id={$token->shop_id} ...");

            $pSvc = new ShopeeProductSyncService($mainClient);
            $p = $pSvc->syncAll($token, $pageSize);
            $this->info("Products: C{$p['created']} U{$p['updated']} P{$p['processed']}");

            $oSvc = new ShopeeOrderSyncService($mainClient);
            $o = $oSvc->syncRecent($token, $days);
            $this->info("Orders: C{$o['created']} U{$o['updated']} P{$o['processed']}");

            $adsDays = (int) ($this->option('ads_days') ?: config('shopee.ads_sync_days', 30));
            try {
                [$adsClient, $adsToken] = $this->resolveAdsContext((int) $token->shop_id, $envOpt);
                $aSvc = new ShopeeAdsSyncService($adsClient);
                $a = $aSvc->sync($adsToken, $adsDays);
                $this->info("Ads: saved={$a['saved']} skipped={$a['skipped']}");
            } catch (\Throwable $adsEx) {
                $this->warn('Ads sync skipped: ' . $adsEx->getMessage());
            }

            try {
                $b = (new ShopeeBcgSyncService($mainClient))->sync($token);
                $this->info("BCG: saved={$b['saved']} skipped={$b['skipped']}");
            } catch (\Throwable $bcgEx) {
                $this->warn('BCG sync skipped: ' . $bcgEx->getMessage());
            }

            Log::info('[CRON] sync-all done', [
                'env'=>$token->env,'shop_id'=>$token->shop_id,'days'=>$days,'page_size'=>$pageSize,
                'products'=>$p,'orders'=>$o,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal sync ALL: '.$e->getMessage());
            Log::error('[CRON] sync-all failed', [
                'env' => $token->env ?? null,
                'shop_id' => $token->shop_id ?? null,
                'days' => $days,
                'page_size' => $pageSize,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    private function getCurrentToken(?string $envOverride, $shopIdOverride): ?ShopeeToken
    {
        $env = $envOverride ?: config('shopee.env', 'test');
        $shopId = $shopIdOverride ?: config('shopee.shop_id');

        $q = ShopeeToken::query()->where('env', $env)->forApp(ShopeeToken::APP_MAIN);

        if ($shopId) {
            $q->where('shop_id', (int) $shopId);
        }

        return $q->orderByDesc('id')->first();
    }

    private function resolveAdsContext(int $shopId, ?string $envOverride = null): array
    {
        $env = $envOverride ?: config('shopee.env', 'test');

        if (ShopeeClient::isConfigured(ShopeeToken::APP_ADS)) {
            $adsToken = ShopeeToken::query()
                ->where('env', $env)
                ->forApp(ShopeeToken::APP_ADS)
                ->where('shop_id', $shopId)
                ->orderByDesc('id')
                ->first();

            if (!$adsToken) {
                throw new \RuntimeException('App Ads Service sudah diisi di .env, tapi token Ads untuk shop ini belum terhubung.');
            }

            return [ShopeeClient::fromConfig(ShopeeToken::APP_ADS), $adsToken];
        }

        $mainToken = ShopeeToken::query()
            ->where('env', $env)
            ->forApp(ShopeeToken::APP_MAIN)
            ->where('shop_id', $shopId)
            ->orderByDesc('id')
            ->first();

        if (!$mainToken) {
            throw new \RuntimeException('Tidak ada token Shopee Main App.');
        }

        return [ShopeeClient::fromConfig(ShopeeToken::APP_MAIN), $mainToken];
    }
}
