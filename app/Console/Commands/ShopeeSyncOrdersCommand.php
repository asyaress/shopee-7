<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeOrderSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncOrdersCommand extends Command
{
    protected $signature = 'shopee:sync-orders
        {--days=7 : Ambil order terakhir berapa hari}
        {--env= : Override shopee.env (test/prod)}
        {--shop_id= : Override shop_id (kalau multi shop)}';

    protected $description = 'Sync Shopee orders (recent) ke database lokal';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $days = max(1, min(90, $days));

        $envOpt = $this->option('env');
        $shopIdOpt = $this->option('shop_id');

        $token = $this->getCurrentToken($envOpt, $shopIdOpt);
        if (!$token) {
            $this->error('Tidak ada token Shopee. Connect dulu di UI.');
            return self::FAILURE;
        }

        $client = ShopeeClient::fromConfig();
        $svc = new ShopeeOrderSyncService($client);

        try {
            $this->info("Sync orders env={$token->env} shop_id={$token->shop_id} days={$days} ...");
            $summary = $svc->syncRecent($token, $days);

            $msg = "DONE orders: Created={$summary['created']} Updated={$summary['updated']} Processed={$summary['processed']}";
            $this->info($msg);
            Log::info('[CRON] '.$msg, ['env'=>$token->env,'shop_id'=>$token->shop_id,'days'=>$days]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal sync orders: '.$e->getMessage());
            Log::error('[CRON] sync-orders failed', [
                'env' => $token->env ?? null,
                'shop_id' => $token->shop_id ?? null,
                'days' => $days,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    private function getCurrentToken(?string $envOverride, $shopIdOverride): ?ShopeeToken
    {
        $env = $envOverride ?: config('shopee.env', 'test');
        $shopId = $shopIdOverride ?: config('shopee.shop_id');

        $q = ShopeeToken::query()->where('env', $env);

        if ($shopId) {
            $q->where('shop_id', (int) $shopId);
        }

        return $q->orderByDesc('id')->first();
    }
}
