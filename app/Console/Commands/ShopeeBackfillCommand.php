<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeBcgSyncService;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeOrderSyncService;
use App\Services\Shopee\ShopeeProductCatalog;
use App\Services\Shopee\ShopeeProductSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeBackfillCommand extends Command
{
    protected $signature = 'shopee:backfill
        {--from= : Tanggal mulai order (Y-m-d), contoh 2020-01-01}
        {--to= : Tanggal akhir order (Y-m-d), default hari ini}
        {--page_size=100 : Page size produk (1-100)}
        {--ads-days=90 : Hari iklan terakhir (max 90, batas API Shopee)}
        {--skip-products : Lewati sync produk}
        {--skip-orders : Lewati sync order}
        {--skip-ads : Lewati sync iklan}
        {--skip-bcg : Lewati sync BCG}
        {--env= : Override shopee.env (test/prod)}
        {--shop_id= : Override shop_id}';

    protected $description = 'Tarik data Shopee historis: produk + order dari tanggal tertentu + ads + BCG';

    public function handle(): int
    {
        $fromOpt = trim((string) $this->option('from'));
        if ($fromOpt === '' && !$this->option('skip-orders')) {
            $this->error('Wajib isi --from=YYYY-MM-DD (tanggal toko mulai jual / data pertama yang ingin ditarik).');
            $this->line('Contoh: php artisan shopee:backfill --from=2020-01-01');

            return self::FAILURE;
        }

        $token = $this->getCurrentToken($this->option('env'), $this->option('shop_id'));
        if (!$token) {
            $this->error('Tidak ada token Shopee. Connect dulu di Integrasi Shopee.');

            return self::FAILURE;
        }

        $mainClient = ShopeeClient::fromConfig(ShopeeToken::APP_MAIN);
        $this->info("Backfill env={$token->env} shop_id={$token->shop_id}");

        try {
            if (!$this->option('skip-products')) {
                $pageSize = max(1, min(100, (int) $this->option('page_size')));
                $statuses = implode(', ', ShopeeProductCatalog::itemStatuses());
                $this->info("1/4 Sync produk (termasuk arsip: {$statuses})...");
                $productSvc = new ShopeeProductSyncService($mainClient);
                $p = $productSvc->syncAll($token, $pageSize);
                $this->info("   Produk: created={$p['created']} updated={$p['updated']} processed={$p['processed']}");

                $relink = $productSvc->relinkOrderItems((int) $token->shop_id);
                $this->info("   Relink order_items → produk: {$relink['linked']} baris");
            }

            if (!$this->option('skip-orders')) {
                $from = Carbon::parse($fromOpt)->startOfDay();
                $to = $this->option('to')
                    ? Carbon::parse((string) $this->option('to'))->endOfDay()
                    : now()->endOfDay();

                if ($from->gte($to)) {
                    $this->error('--from harus lebih awal dari --to');

                    return self::FAILURE;
                }

                $this->info("2/4 Sync order {$from->toDateString()} → {$to->toDateString()} (auto-chunk 14 hari)...");
                $this->warn('   Proses bisa lama jika toko sudah bertahun-tahun. Jangan interrupt.');

                $o = (new ShopeeOrderSyncService($mainClient))->syncSince(
                    $token,
                    $from->timestamp,
                    $to->timestamp
                );
                $this->info("   Order: created={$o['created']} updated={$o['updated']} processed={$o['processed']}");
            }

            if (!$this->option('skip-ads')) {
                $adsDays = max(1, min(90, (int) $this->option('ads-days')));
                $this->info("3/4 Sync iklan {$adsDays} hari terakhir (batas API Shopee ~90 hari)...");
                try {
                    [$adsClient, $adsToken] = $this->resolveAdsContext((int) $token->shop_id, $this->option('env'));
                    $a = (new ShopeeAdsSyncService($adsClient))->sync($adsToken, $adsDays);
                    $this->info("   Ads: saved={$a['saved']} skipped={$a['skipped']}");
                } catch (\Throwable $e) {
                    $this->warn('   Ads dilewati: ' . $e->getMessage());
                }
            }

            if (!$this->option('skip-bcg')) {
                $this->info('4/4 Sync BCG funnel (views + konversi)...');
                try {
                    $b = (new ShopeeBcgSyncService($mainClient))->sync($token);
                    $this->info("   BCG: saved={$b['saved']} skipped={$b['skipped']}");
                } catch (\Throwable $e) {
                    $this->warn('   BCG dilewati: ' . $e->getMessage());
                }
            }

            $this->newLine();
            $this->info('Backfill selesai.');
            $this->line('Langkah manual: isi HPP produk di Kelola Data → Products agar laporan profit akurat.');

            Log::info('[backfill] done', ['shop_id' => $token->shop_id, 'from' => $fromOpt]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backfill gagal: ' . $e->getMessage());
            Log::error('[backfill] failed', ['shop_id' => $token->shop_id, 'error' => $e->getMessage()]);

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

        return [ShopeeClient::fromConfig(ShopeeToken::APP_MAIN), $this->getCurrentToken($env, $shopId)];
    }
}
