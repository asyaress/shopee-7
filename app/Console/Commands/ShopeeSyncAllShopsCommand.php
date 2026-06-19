<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeAppContextResolver;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeOrderSyncService;
use App\Services\Shopee\ShopeeProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncAllShopsCommand extends Command
{
    protected $signature = 'shopee:sync-all-shops
        {--days=7 : Hari order}
        {--ads_days= : Hari ads}
        {--page_size=100 : Page produk}';

    protected $description = 'Sync produk + order + ads untuk SEMUA toko terhubung';

    public function handle(): int
    {
        if (!config('shopee.cron_enabled', false) && !$this->option('no-interaction')) {
            // tetap jalan jika dipanggil manual
        }

        $env = config('shopee.env', 'test');
        $tokens = ShopeeToken::query()->where('env', $env)->forApp(ShopeeToken::APP_MAIN)->orderBy('shop_id')->get();

        if ($tokens->isEmpty()) {
            $this->error('Tidak ada token Shopee.');

            return self::FAILURE;
        }

        $days = max(1, min(90, (int) $this->option('days')));
        $adsDays = max(1, min(90, (int) ($this->option('ads_days') ?: config('shopee.ads_sync_days', 30))));
        $pageSize = max(1, min(100, (int) $this->option('page_size')));
        $mainClient = ShopeeClient::fromConfig(ShopeeToken::APP_MAIN);

        foreach ($tokens as $token) {
            $this->info("=== Shop {$token->shop_id} ===");

            try {
                $p = (new ShopeeProductSyncService($mainClient))->syncAll($token, $pageSize);
                $this->line("  Products C{$p['created']} U{$p['updated']}");

                $o = (new ShopeeOrderSyncService($mainClient))->syncRecent($token, $days);
                $this->line("  Orders C{$o['created']} U{$o['updated']}");

                [$adsService, $syncToken, $adsSources] = (new ShopeeAppContextResolver())
                    ->buildAdsSyncService((int) $token->shop_id, $env);
                $a = $adsService->sync($syncToken, $adsDays);
                $this->line("  Ads {$adsSources} saved={$a['saved']}");

                $b = (new ShopeeBcgSyncService($mainClient))->sync($token);
                $this->line("  BCG saved={$b['saved']} skipped={$b['skipped']}");
            } catch (\Throwable $e) {
                $this->warn("  Gagal shop {$token->shop_id}: " . $e->getMessage());
                Log::error('[sync-all-shops] failed', ['shop_id' => $token->shop_id, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
