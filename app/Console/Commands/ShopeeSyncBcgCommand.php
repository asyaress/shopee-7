<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncBcgCommand extends Command
{
    protected $signature = 'shopee:sync-bcg
        {--env= : Override shopee.env (test/prod)}
        {--shop_id= : Override shop_id}';

    protected $description = 'Sync BCG performa produk (views API + qty order) ke database lokal';

    public function handle(): int
    {
        $token = $this->resolveToken();
        if (!$token) {
            $this->error('Tidak ada token Shopee. Connect dulu di UI.');

            return self::FAILURE;
        }

        try {
            $client = ShopeeClient::fromConfig();
            $result = (new ShopeeBcgSyncService($client))->sync($token);

            $this->info(sprintf(
                'BCG sync shop=%d: saved=%d skipped=%d items=%d (%s — %s)',
                $token->shop_id,
                $result['saved'],
                $result['skipped'],
                $result['items'],
                $result['period_start'],
                $result['period_end']
            ));

            if (!empty($result['message'])) {
                $this->warn($result['message']);
            }

            Log::info('[BCG sync] done', array_merge(['shop_id' => $token->shop_id], $result));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('BCG sync gagal: ' . $e->getMessage());
            Log::error('[BCG sync] failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    private function resolveToken(): ?ShopeeToken
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
