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


class ShopeeIntegrationController extends Controller
{
    public function index(): View
    {
        $token = $this->getCurrentToken();

        return view('shopee.index', [
            'token' => $token,
            'env' => config('shopee.env', 'test'),
        ]);
    }

    public function connect(): RedirectResponse
    {
        $client = ShopeeClient::fromConfig();

        // You can store anything you want in state (CSRF, user id, etc.).
        $state = csrf_token();

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

        $client = ShopeeClient::fromConfig();
        $data = $client->getAccessToken($code, $shopId);

        $expireIn = (int) Arr::get($data, 'expire_in', 0);
        $obtainedAt = now();
        $expireAt = $expireIn ? $obtainedAt->copy()->addSeconds($expireIn) : null;

        ShopeeToken::updateOrCreate(
            [
                'env' => config('shopee.env', 'test'),
                'shop_id' => $shopId,
            ],
            [
                'partner_id' => (int) config('shopee.partner_id'),
                'access_token' => (string) Arr::get($data, 'access_token', ''),
                'refresh_token' => (string) Arr::get($data, 'refresh_token', ''),
                'expire_in' => $expireIn ?: null,
                'obtained_at' => $obtainedAt,
                'expire_at' => $expireAt,
                'raw' => $data,
            ]
        );

        return redirect()->route('manage.index')
            ->with('success', 'Shopee berhasil terhubung. Token tersimpan.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', (int) config('shopee.sync_days', 7));
        $days = max(1, min(90, $days));

        $token = $this->getCurrentToken();
        if (!$token) {
            return redirect()->route('shopee.index')
                ->with('error', 'Belum ada token Shopee. Klik Connect dulu.');
        }

        $service = new ShopeeOrderSyncService(ShopeeClient::fromConfig());

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
        $token = $this->getCurrentToken();
        if (!$token) {
            return redirect()->route('shopee.index')
                ->with('error', 'Belum ada token Shopee. Klik Connect dulu.');
        }

        $pageSize = (int) $request->input('page_size', 100);
        $service = new ShopeeProductSyncService(ShopeeClient::fromConfig());

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

        $token = $this->getCurrentToken();
        if (!$token) {
            return redirect()->route('shopee.index')
                ->with('error', 'Belum ada token Shopee. Klik Connect dulu.');
        }

        $adsMsg = '';
        try {
            $client = ShopeeClient::fromConfig();
            $productSvc = new ShopeeProductSyncService($client);
            $productSummary = $productSvc->syncAll($token, $pageSize);

            $orderSvc = new ShopeeOrderSyncService($client);
            $orderSummary = $orderSvc->syncRecent($token, $days);

            try {
                $adsDays = (int) config('shopee.ads_sync_days', 30);
                $adsSummary = (new ShopeeAdsSyncService($client))->sync($token, $adsDays);
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

    private function getCurrentToken(): ?ShopeeToken
    {
        $env = config('shopee.env', 'test');
        $shopId = config('shopee.shop_id');

        $q = ShopeeToken::where('env', $env);

        if ($shopId) {
            $q->where('shop_id', (int) $shopId);
        }

        return $q->orderByDesc('id')->first();
    }

public function debugAuthVariants()
{
    $partnerId  = trim((string) config('shopee.partner_id'));
    $partnerKey = trim((string) config('shopee.partner_key'));
    $host       = rtrim((string) config('shopee.hosts.test'), '/');
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

    $host = 'https://partner.test-stable.shopeemobile.com';
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
