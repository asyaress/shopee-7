<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeProductCatalog;
use App\Services\Shopee\ShopeeProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncProductsCommand extends Command
{
    protected $signature = 'shopee:sync-products
        {--page_size=100 : Page size product (1-100)}
        {--env= : Override shopee.env (test/prod)}
        {--shop_id= : Override shop_id (kalau multi shop)}';

    protected $description = 'Sync Shopee products ke database lokal';

    public function handle(): int
    {
        $pageSize = (int) $this->option('page_size');
        $pageSize = max(1, min(100, $pageSize));

        $envOpt = $this->option('env');
        $shopIdOpt = $this->option('shop_id');

        $token = $this->getCurrentToken($envOpt, $shopIdOpt);
        if (!$token) {
            $this->error('Tidak ada token Shopee. Connect dulu di UI.');
            return self::FAILURE;
        }

        $client = ShopeeClient::fromConfig();
        $svc = new ShopeeProductSyncService($client);

        try {
            $this->info("Sync products env={$token->env} shop_id={$token->shop_id} statuses=" . implode(',', ShopeeProductCatalog::itemStatuses()) . " ...");
            $summary = $svc->syncAll($token, $pageSize);

            $relink = $svc->relinkOrderItems((int) $token->shop_id);

            $msg = "DONE products: Created={$summary['created']} Updated={$summary['updated']} Processed={$summary['processed']} Relinked={$relink['linked']}";
            $this->info($msg);
            Log::info('[CRON] '.$msg, ['env'=>$token->env,'shop_id'=>$token->shop_id,'page_size'=>$pageSize]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal sync products: '.$e->getMessage());
            Log::error('[CRON] sync-products failed', [
                'env' => $token->env ?? null,
                'shop_id' => $token->shop_id ?? null,
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
}
