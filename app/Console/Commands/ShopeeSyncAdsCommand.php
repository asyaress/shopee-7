<?php

namespace App\Console\Commands;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncAdsCommand extends Command
{
    protected $signature = 'shopee:sync-ads
        {--year= : Tarik 1 tahun penuh, contoh 2026}
        {--from= : Tanggal mulai YYYY-MM-DD}
        {--to= : Tanggal akhir YYYY-MM-DD}
        {--days= : Override SHOPEE_ADS_SYNC_DAYS}
        {--pause=2 : Jeda detik antar chunk untuk mengurangi rate limit}
        {--env= : Override shopee.env}
        {--shop_id= : Override shop_id}';

    protected $description = 'Sync Shopee product ads performance ke database lokal';

    public function handle(): int
    {
        [$client, $token, $appType] = $this->resolveAdsContext();
        if (!$token || !$client) {
            $this->error('Tidak ada token Shopee. Connect dulu di halaman Kelola Data.');
            return self::FAILURE;
        }

        try {
            $svc = new ShopeeAdsSyncService($client);
            [$modeLabel, $result] = $this->runSync($svc, $token);
            $msg = "DONE ads: saved={$result['saved']} skipped={$result['skipped']}";
            $this->info($modeLabel);
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

    /**
     * @return array{0:string,1:array{saved:int,skipped:int,errors:array<int,string>}}
     */
    private function runSync(ShopeeAdsSyncService $svc, ShopeeToken $token): array
    {
        $pause = max(0, (int) $this->option('pause'));
        $modeLabel = '';

        if ($year = (string) $this->option('year')) {
            if (!preg_match('/^\d{4}$/', $year)) {
                throw new \RuntimeException('Format --year harus YYYY, contoh 2026.');
            }

            $start = Carbon::create((int) $year, 1, 1)->startOfDay();
            $end = Carbon::create((int) $year, 12, 31)->endOfDay();
            $modeLabel = "Sync ads app={$token->app_type} env={$token->env} shop_id={$token->shop_id} year={$year} pause={$pause}s ...";
            $result = $svc->syncBetween($token, $start, $end, $pause);

            return [$modeLabel, $result];
        }

        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        if ($from !== '' || $to !== '') {
            $start = $from !== '' ? Carbon::parse($from)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
            $end = $to !== '' ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();
            $modeLabel = "Sync ads app={$token->app_type} env={$token->env} shop_id={$token->shop_id} range={$start->toDateString()}..{$end->toDateString()} pause={$pause}s ...";
            $result = $svc->syncBetween($token, $start, $end, $pause);

            return [$modeLabel, $result];
        }

        $days = (int) ($this->option('days') ?: config('shopee.ads_sync_days', 30));
        $days = max(1, min(90, $days));
        $modeLabel = "Sync ads app={$token->app_type} env={$token->env} shop_id={$token->shop_id} days={$days} pause={$pause}s ...";
        $result = $svc->syncBetween(
            $token,
            Carbon::now()->subDays($days - 1)->startOfDay(),
            Carbon::now()->endOfDay(),
            $pause
        );

        return [$modeLabel, $result];
    }

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

            return [ShopeeClient::fromConfig(ShopeeToken::APP_ADS), $resolved, ShopeeToken::APP_ADS];
        }

        $mainToken = ShopeeToken::query()
            ->where('env', $env)
            ->forApp(ShopeeToken::APP_MAIN);
        if ($shopId) {
            $mainToken->where('shop_id', (int) $shopId);
        }

        return [ShopeeClient::fromConfig(ShopeeToken::APP_MAIN), $mainToken->orderByDesc('id')->first(), ShopeeToken::APP_MAIN];
    }
}
