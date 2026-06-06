<?php

namespace App\Http\Controllers;

use App\Models\ShopeeToken;
use App\Services\Shopee\ShopeeClient;
use App\Services\Shopee\ShopeeAdsSyncService;
use App\Services\Shopee\ShopeeOrderSyncService;
use App\Services\Shopee\ShopeeProductSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ShopeeIntegrationController extends Controller
{
    public function index(): View
    {
        $mainToken = $this->getCurrentToken(ShopeeToken::APP_MAIN);
        $adsConfigured = ShopeeClient::isConfigured(ShopeeToken::APP_ADS);
        $adsToken = $adsConfigured ? $this->getCurrentToken(ShopeeToken::APP_ADS) : null;

        return view('shopee.index', [
            'token' => $mainToken,
            'mainToken' => $mainToken,
            'adsToken' => $adsToken,
            'adsConfigured' => $adsConfigured,
            'env' => config('shopee.env', 'test'),
        ]);
    }

    public function connect(Request $request, string $appType = ShopeeToken::APP_MAIN): RedirectResponse
    {
        $appType = $this->normalizeAppType($appType);
        if (!ShopeeClient::isConfigured($appType)) {
            return back()->with('error', 'Credential Shopee ' . $this->appLabel($appType) . ' belum lengkap di .env.');
        }

        $client = ShopeeClient::fromConfig($appType);

        // You can store anything you want in state (CSRF, user id, etc.).
        $state = $this->buildState($appType, (string) $request->session()->token());

        return redirect()->away($client->buildAuthPartnerUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = (string) $request->query('code', '');
        $shopId = (int) $request->query('shop_id', 0);

        if ($code === '' || $shopId <= 0) {
            return redirect()->route('shopee.index')
                ->with('error', 'Callback dari Shopee tidak lengkap (code/shop_id kosong).');
        }

        if (!Schema::hasColumn('shopee_tokens', 'app_type')) {
            return redirect()->route('shopee.index')
                ->with('error', 'Database belum update. Jalankan: php artisan migrate --force lalu Connect ulang.');
        }

        try {
            $appType = $this->parseAppTypeFromState((string) $request->query('state', ''));
            $client = ShopeeClient::fromConfig($appType);
            $data = $client->getAccessToken($code, $shopId);

            $expireIn = (int) Arr::get($data, 'expire_in', 0);
            $obtainedAt = now();
            $expireAt = $expireIn ? $obtainedAt->copy()->addSeconds($expireIn) : null;

            $this->persistToken($shopId, $appType, $data, $obtainedAt, $expireAt, $expireIn);

            return redirect()->route('shopee.index')
                ->with('success', 'Shopee ' . $this->appLabel($appType) . ' berhasil terhubung. Token tersimpan.');
        } catch (\Throwable $e) {
            Log::error('Shopee OAuth callback failed', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('shopee.index')
                ->with('error', 'Gagal connect Shopee: ' . $e->getMessage());
        }
    }

    public function disconnect(Request $request, string $appType = ShopeeToken::APP_MAIN): RedirectResponse
    {
        $appType = $this->normalizeAppType($appType);
        $env = config('shopee.env', 'test');
        $shopId = (int) ($request->input('shop_id') ?: \App\Support\ShopeeShopContext::shopId() ?: config('shopee.shop_id'));

        $query = ShopeeToken::query()->where('env', $env);

        if ($shopId > 0) {
            $query->where('shop_id', $shopId);
        }

        if (Schema::hasColumn('shopee_tokens', 'app_type')) {
            $query->forApp($appType);
        }

        $deleted = $query->delete();

        if ($deleted === 0) {
            return redirect()->route('shopee.index')
                ->with('error', 'Tidak ada koneksi ' . $this->appLabel($appType) . ' yang bisa diputus.');
        }

        return redirect()->route('shopee.index')
            ->with('success', 'Koneksi ' . $this->appLabel($appType) . ' diputus. Klik Connect untuk hubungkan ulang.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', (int) config('shopee.sync_days', 7));
        $days = max(1, min(90, $days));

        $token = $this->getCurrentToken(ShopeeToken::APP_MAIN);
        if (!$token) {
            return redirect()->route('shopee.index')
                ->with('error', 'Belum ada token Shopee Main App. Klik Connect dulu.');
        }

        $service = new ShopeeOrderSyncService(ShopeeClient::fromConfig(ShopeeToken::APP_MAIN));

        try {
            $summary = $service->syncRecent($token, $days);
        } catch (\Throwable $e) {
            return redirect()->route('shopee.index')
                ->with('error', 'Gagal sync: ' . $e->getMessage());
        }

        return redirect()->route('shopee.index')
            ->with('success', "Sync selesai. Created: {$summary['created']}, Updated: {$summary['updated']}, Processed: {$summary['processed']}");
    }

    public function syncProducts(Request $request): RedirectResponse
    {
        $token = $this->getCurrentToken(ShopeeToken::APP_MAIN);
        if (!$token) {
            return redirect()->route('shopee.index')
                ->with('error', 'Belum ada token Shopee Main App. Klik Connect dulu.');
        }

        $pageSize = (int) $request->input('page_size', 100);
        $service = new ShopeeProductSyncService(ShopeeClient::fromConfig(ShopeeToken::APP_MAIN));

        try {
            $summary = $service->syncAll($token, $pageSize);
        } catch (\Throwable $e) {
            return redirect()->route('shopee.index')
                ->with('error', 'Gagal sync produk: ' . $e->getMessage());
        }

        return redirect()->route('shopee.index')
            ->with('success', "Sync produk selesai. Created: {$summary['created']}, Updated: {$summary['updated']}, Processed: {$summary['processed']}");
    }

    public function syncAll(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', (int) config('shopee.sync_days', 7));
        $days = max(1, min(90, $days));
        $pageSize = (int) $request->input('page_size', 100);

        $mainToken = $this->getCurrentToken(ShopeeToken::APP_MAIN);
        if (!$mainToken) {
            return redirect()->route('shopee.index')
                ->with('error', 'Belum ada token Shopee Main App. Klik Connect dulu.');
        }

        $adsMsg = '';
        try {
            $mainClient = ShopeeClient::fromConfig(ShopeeToken::APP_MAIN);
            $productSvc = new ShopeeProductSyncService($mainClient);
            $productSummary = $productSvc->syncAll($mainToken, $pageSize);

            $orderSvc = new ShopeeOrderSyncService($mainClient);
            $orderSummary = $orderSvc->syncRecent($mainToken, $days);

            try {
                $adsDays = (int) config('shopee.ads_sync_days', 30);
                [$adsClient, $adsToken, $adsAppType] = $this->resolveAdsContext((int) $mainToken->shop_id);
                $adsSummary = (new ShopeeAdsSyncService($adsClient))->sync($adsToken, $adsDays);
                $adsMsg = " Ads: saved={$adsSummary['saved']}.";
            } catch (\Throwable $adsEx) {
                $adsMsg = ' Ads: ' . $adsEx->getMessage();
            }
        } catch (\Throwable $e) {
            return redirect()->route('shopee.index')
                ->with('error', 'Gagal sync semua: ' . $e->getMessage());
        }

        return redirect()->route('shopee.index')
            ->with('success',
                "Sync ALL selesai. Produk: C{$productSummary['created']}/U{$productSummary['updated']}/P{$productSummary['processed']}. " .
                "Order: C{$orderSummary['created']}/U{$orderSummary['updated']}/P{$orderSummary['processed']}." . $adsMsg
            );
    }

    private function getCurrentToken(string $appType = ShopeeToken::APP_MAIN, ?int $shopIdOverride = null): ?ShopeeToken
    {
        $env = config('shopee.env', 'test');
        $shopId = $shopIdOverride ?: (int) (\App\Support\ShopeeShopContext::shopId() ?: config('shopee.shop_id'));

        $q = ShopeeToken::where('env', $env)->forApp($appType);

        if ($shopId) {
            $q->where('shop_id', (int) $shopId);
        }

        return $q->orderByDesc('id')->first();
    }

    private function resolveAdsContext(int $shopId): array
    {
        if (ShopeeClient::isConfigured(ShopeeToken::APP_ADS)) {
            $adsToken = $this->getCurrentToken(ShopeeToken::APP_ADS, $shopId);
            if (!$adsToken) {
                throw new \RuntimeException('App Ads Service sudah diisi di .env, tapi toko ini belum di-connect ke app Ads Service.');
            }

            return [ShopeeClient::fromConfig(ShopeeToken::APP_ADS), $adsToken, ShopeeToken::APP_ADS];
        }

        $mainToken = $this->getCurrentToken(ShopeeToken::APP_MAIN, $shopId);
        if (!$mainToken) {
            throw new \RuntimeException('Belum ada token Shopee Main App.');
        }

        return [ShopeeClient::fromConfig(ShopeeToken::APP_MAIN), $mainToken, ShopeeToken::APP_MAIN];
    }

    private function normalizeAppType(string $appType): string
    {
        return $appType === ShopeeToken::APP_ADS ? ShopeeToken::APP_ADS : ShopeeToken::APP_MAIN;
    }

    private function appLabel(string $appType): string
    {
        return $appType === ShopeeToken::APP_ADS ? 'Ads Service' : 'Main App';
    }

    private function buildState(string $appType, string $csrf): string
    {
        return base64_encode(json_encode([
            'app_type' => $appType,
            'csrf' => $csrf,
        ]));
    }

    private function parseAppTypeFromState(string $state): string
    {
        if ($state === '') {
            return ShopeeToken::APP_MAIN;
        }

        $decoded = json_decode(base64_decode($state, true) ?: '', true);
        if (!is_array($decoded)) {
            return ShopeeToken::APP_MAIN;
        }

        return $this->normalizeAppType((string) ($decoded['app_type'] ?? ShopeeToken::APP_MAIN));
    }

    private function persistToken(
        int $shopId,
        string $appType,
        array $data,
        \Illuminate\Support\Carbon $obtainedAt,
        ?\Illuminate\Support\Carbon $expireAt,
        int $expireIn,
    ): void {
        $keys = [
            'env' => config('shopee.env', 'test'),
            'shop_id' => $shopId,
        ];

        if (Schema::hasColumn('shopee_tokens', 'app_type')) {
            $keys['app_type'] = $appType;
        }

        $values = [
            'partner_id' => (int) (config("shopee.apps.{$appType}.partner_id") ?: config('shopee.partner_id')),
            'access_token' => (string) Arr::get($data, 'access_token', ''),
            'refresh_token' => (string) Arr::get($data, 'refresh_token', ''),
            'expire_in' => $expireIn ?: null,
            'obtained_at' => $obtainedAt,
            'expire_at' => $expireAt,
            'raw' => $data,
        ];

        if (Schema::hasColumn('shopee_tokens', 'app_type')) {
            $values['app_type'] = $appType;
        }

        ShopeeToken::updateOrCreate($keys, $values);
    }

public function debugAuthVariants()
{
    $partnerId  = trim((string) config('shopee.partner_id'));
    $partnerKey = trim((string) config('shopee.partner_key'));
    $env        = (string) config('shopee.env', 'prod');
    $hosts      = config('shopee.hosts', []);
    $host       = rtrim((string) ($hosts[$env] ?? $hosts['prod'] ?? ''), '/');
    $redirect   = (string) config('shopee.redirect_url');

    $path = '/api/v2/shop/auth_partner';
    $ts = (int) request('ts', time());

    $base = $partnerId . $path . $ts;

    // Key variants
    $key_raw = $partnerKey;

    $key_no_prefix = str_starts_with($partnerKey, 'shpk') ? substr($partnerKey, 4) : $partnerKey;

    $key_hex2bin = null;
    if (str_starts_with($partnerKey, 'shpk')) {
        $hex = substr($partnerKey, 4);
        if ($hex !== '' && ctype_xdigit($hex) && (strlen($hex) % 2 === 0)) {
            $key_hex2bin = hex2bin($hex) ?: null;
        }
    }

    $sign_raw       = hash_hmac('sha256', $base, $key_raw, false);
    $sign_no_prefix = hash_hmac('sha256', $base, $key_no_prefix, false);
    $sign_hex2bin   = $key_hex2bin ? hash_hmac('sha256', $base, $key_hex2bin, false) : null;

    // Build URLs (RFC3986)
    $buildUrl = function($sign) use ($host,$path,$partnerId,$ts,$redirect) {
        return $host.$path.'?'.http_build_query([
            'partner_id' => $partnerId,
            'timestamp'  => $ts,
            'sign'       => $sign,
            'redirect'   => $redirect,
        ], '', '&', PHP_QUERY_RFC3986);
    };

    return response()->json([
        'server_time' => ['unix'=>time(), 'utc'=>gmdate('c'), 'local'=>date('c')],
        'partner_id' => $partnerId,
        'ts' => $ts,
        'base' => $base,
        'key_len' => strlen($partnerKey),
        'key_prefix' => substr($partnerKey, 0, 4),
        'after_shpk_is_hex' => (str_starts_with($partnerKey,'shpk') ? ctype_xdigit(substr($partnerKey,4)) : null),
        'after_shpk_len' => (str_starts_with($partnerKey,'shpk') ? strlen(substr($partnerKey,4)) : null),

        'sign_raw' => $sign_raw,
        'url_raw'  => $buildUrl($sign_raw),

        'sign_no_prefix' => $sign_no_prefix,
        'url_no_prefix'  => $buildUrl($sign_no_prefix),

        'sign_hex2bin' => $sign_hex2bin,
        'url_hex2bin'  => $sign_hex2bin ? $buildUrl($sign_hex2bin) : null,
    ]);
}
public function signMatrix()
{
    $partnerId  = (string) config('services.shopee.partner_id');
    $partnerKey = trim((string) config('services.shopee.partner_key'));

    $env  = (string) config('shopee.env', 'prod');
    $hosts = config('shopee.hosts', []);
    $host = rtrim((string) ($hosts[$env] ?? $hosts['prod'] ?? ''), '/');
    $path = '/api/v2/shop/auth_partner';
    $ts   = time();

    $redirect = config('services.shopee.redirect_url'); // full callback url
    $state    = 'teststate'; // optional, biar konsisten

    // kandidat key
    $keys = [];

    $keys['K1_raw'] = $partnerKey;

    if (str_starts_with($partnerKey, 'shpk')) {
        $hex = substr($partnerKey, 4);
        $keys['K2_no_shpk'] = $hex;

        if ($hex !== '' && strlen($hex) % 2 === 0 && ctype_xdigit($hex)) {
            $bin = hex2bin($hex);
            if ($bin !== false) $keys['K3_hex2bin_after_shpk'] = $bin;
        }
    }

    // kandidat base string
    $bases = [
        'B1_pid+path+ts' => fn() => $partnerId . $path . $ts,
        // beberapa orang “nebak” Shopee include redirect/state (buat verifikasi cepat)
        'B2_pid+path+ts+redirect' => fn() => $partnerId . $path . $ts . $redirect,
        'B3_pid+path+ts+redirect+state' => fn() => $partnerId . $path . $ts . $redirect . $state,
    ];

    $out = [];
    foreach ($keys as $kName => $kVal) {
        foreach ($bases as $bName => $bFn) {
            $base = $bFn();
            $sign = hash_hmac('sha256', $base, $kVal, false);

            $url = $host . $path . '?' . http_build_query([
                'partner_id' => $partnerId,
                'timestamp'  => $ts,
                'sign'       => $sign,
                'redirect'   => $redirect,
                'state'      => $state,
            ], '', '&', PHP_QUERY_RFC3986);

            $out[] = [
                'case' => "$kName + $bName",
                'sign' => $sign,
                'base' => $base,
                'url'  => $url,
            ];
        }
    }

    return response()->json($out);
}


public function debugAuthCheck()
{
    abort_unless(config('app.debug'), 404);

$host = rtrim(
    config('services.shopee.host')
    ?: env('SHOPEE_HOST')
    ?: env('SHOPEE_HOST_PROD')
    ?: 'https://partner.shopeemobile.com',
'/');
    $partnerId = (string) (config('services.shopee.partner_id', env('SHOPEE_PARTNER_ID')));
    $partnerKey = (string) (config('services.shopee.partner_key', env('SHOPEE_PARTNER_KEY')));
    $redirect = (string) (config('services.shopee.redirect_url', env('SHOPEE_REDIRECT_URL')));

    $path = '/api/v2/shop/auth_partner';

    // pakai server_time Shopee biar gak debat waktu server lokal
    $timeResp = Http::timeout(10)->get($host.'/api/v2/public/get_time')->json();
    $ts = (int)($timeResp['response']['server_time'] ?? time());

    $base = $partnerId.$path.$ts;
    $sign = hash_hmac('sha256', $base, $partnerKey, false);

    $query = [
        'partner_id' => $partnerId,
        'timestamp'  => $ts,
        'sign'       => $sign,
        'redirect'   => $redirect,
        'state'      => 'debugstate',
    ];

    $url = $host.$path.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

    // lakukan request dari SERVER (ini ngetes apakah Shopee menerima sign atau tidak)
    $resp = Http::timeout(10)->withoutRedirecting()->get($url);

    return response()->json([
        'host' => $host,
        'partner_id' => $partnerId,
        'ts' => $ts,
        'base' => $base,
        'sign' => $sign,
        'url' => $url,
        'server_status' => $resp->status(),
        'server_location' => $resp->header('Location'),
        'server_body_snippet' => substr($resp->body(), 0, 300),
    ]);
}


}
