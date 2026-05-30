<?php

namespace App\Services\Shopee;

use App\Models\ShopeeToken;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopeeClient
{
    public function __construct(
        private readonly int $partnerId,
        private readonly string $partnerKey,
        private readonly string $host,
        private readonly string $env,
        private readonly int $refreshBufferSeconds = 300,
    ) {
    }

    public static function fromConfig(): self
    {
        $env = config('shopee.env', 'test');
        $partnerId = (int) config('shopee.partner_id');
        $partnerKey = self::normalizePartnerKey((string) config('shopee.partner_key'));
        $hosts = config('shopee.hosts');
        $host = $hosts[$env] ?? $hosts['test'];
        $buffer = (int) config('shopee.refresh_buffer', 300);

        if (!$partnerId || $partnerKey === '') {
            throw new \RuntimeException('Shopee partner credentials are not configured. Set SHOPEE_PARTNER_ID & SHOPEE_PARTNER_KEY.');
        }

        return new self($partnerId, $partnerKey, $host, $env, $buffer);
    }

    public function buildAuthPartnerUrl(?string $state = null): string
    {
        $path = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $redirectUrl = self::resolveRedirectUrl();

        if ($redirectUrl === '') {
            throw new \RuntimeException('SHOPEE_REDIRECT_URL is not configured.');
        }

        $sign = $this->signPublic($path, $timestamp);

        $query = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'redirect' => $redirectUrl,
        ];

        if ($state) {
            $query['state'] = $state;
        }

        return rtrim($this->host, '/') . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchange authorization code for access_token + refresh_token.
     */
    public function getAccessToken(string $code, int $shopId): array
    {
        $path = '/api/v2/auth/token/get';
       $timestamp = time();
        $sign = $this->signPublic($path, $timestamp);

        $url = rtrim($this->host, '/') . $path;
        $query = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];

        $body = [
            'partner_id' => $this->partnerId,
            'shop_id' => (int) $shopId,
            'code' => $code,
        ];

        $res = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($url . '?' . http_build_query($query), $body);

        return $this->normalizeResponse($res->json(), 'auth.token.get');
    }

    /**
     * Refresh access token using refresh_token.
     */
    public function refreshAccessToken(int $shopId, string $refreshToken): array
    {
        $path = '/api/v2/auth/access_token/get';
        $timestamp = time();
        $sign = $this->signPublic($path, $timestamp);

        $url = rtrim($this->host, '/') . $path;
        $query = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];

        $body = [
            'partner_id' => $this->partnerId,
            'shop_id' => (int) $shopId,
            'refresh_token' => $refreshToken,
        ];

        $res = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($url . '?' . http_build_query($query), $body);

        return $this->normalizeResponse($res->json(), 'auth.access_token.get');
    }

    public function ensureValidToken(ShopeeToken $token): ShopeeToken
    {
        if (!$token->refresh_token) {
            return $token; // cannot refresh
        }

        if (!$token->isExpired($this->refreshBufferSeconds)) {
            return $token;
        }

        $data = $this->refreshAccessToken((int) $token->shop_id, $token->refresh_token);

        $expireIn = (int) Arr::get($data, 'expire_in', 0);
        $obtainedAt = now();
        $expireAt = $expireIn ? $obtainedAt->copy()->addSeconds($expireIn) : null;

        $token->forceFill([
            'access_token' => Arr::get($data, 'access_token', $token->access_token),
            'refresh_token' => Arr::get($data, 'refresh_token', $token->refresh_token),
            'expire_in' => $expireIn ?: $token->expire_in,
            'obtained_at' => $obtainedAt,
            'expire_at' => $expireAt,
            'raw' => $data,
        ])->save();

        return $token->refresh();
    }

    /**
     * Call a private API (requires access_token + shop_id).
     */
    public function requestPrivate(string $method, string $path, array $params, ShopeeToken $token): array
    {
        $token = $this->ensureValidToken($token);

        $timestamp = time();
        $sign = $this->signPrivate($path, $timestamp, $token->access_token, (int) $token->shop_id);

        $baseQuery = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'access_token' => $token->access_token,
            'shop_id' => (int) $token->shop_id,
            'sign' => $sign,
        ];

        $url = rtrim($this->host, '/') . $path;

        $method = strtoupper($method);

        $http = Http::timeout(30)->acceptJson();

        if ($method === 'GET') {
            $res = $http->get($url, array_merge($baseQuery, $params));
        } else {
            // Shopee v2 mostly uses POST with JSON bodies, but some allow query params.
            $res = $http->asJson()->post($url . '?' . http_build_query($baseQuery), $params);
        }

        $json = $res->json();

        // If auth error, try refresh once (if possible)
        if (is_array($json) && (Arr::get($json, 'error') === 'error_auth')) {
            Log::warning('Shopee API returned error_auth. Attempting token refresh and retry.', [
                'path' => $path,
                'shop_id' => $token->shop_id,
                'env' => $this->env,
            ]);

            $token = $this->ensureValidToken($token->refresh());

            $timestamp = time();
            $sign = $this->signPrivate($path, $timestamp, $token->access_token, (int) $token->shop_id);
            $baseQuery = [
                'partner_id' => $this->partnerId,
                'timestamp' => $timestamp,
                'access_token' => $token->access_token,
                'shop_id' => (int) $token->shop_id,
                'sign' => $sign,
            ];

            if ($method === 'GET') {
                $res = $http->get($url, array_merge($baseQuery, $params));
            } else {
                $res = $http->asJson()->post($url . '?' . http_build_query($baseQuery), $params);
            }

            $json = $res->json();
        }

        return $this->normalizeResponse($json, $path);
    }

    /**
     * Payment endpoint untuk detail potongan / fee (escrow breakdown).
     *
     * Shopee Open Platform v2 biasanya menyediakan endpoint:
     *   /api/v2/payment/get_escrow_detail?order_sn=...
     */
    public function getEscrowDetail(ShopeeToken $token, string $orderSn): array
    {
        return $this->requestPrivate('GET', '/api/v2/payment/get_escrow_detail', [
            'order_sn' => $orderSn,
        ], $token);
    }


    // Used by /api/v2/auth/token/get

private function signPublic(string $path, int $timestamp): string
{
    $base = (string) $this->partnerId . $path . (string) $timestamp;

    return hash_hmac('sha256', $base, $this->partnerKey, false);
}

private function signPrivate(string $path, int $timestamp, string $accessToken, int $shopId): string
{
    $base = (string) $this->partnerId . $path . (string) $timestamp . $accessToken . (string) $shopId;

    return hash_hmac('sha256', $base, $this->partnerKey, false);
}

/**
 * Shopee docs: HMAC-SHA256(partner_id + path + timestamp, partner_key as UTF-8 string).
 * Default: use key exactly as copied from Console (including shpk prefix if present).
 */
private static function normalizePartnerKey(string $key): string
{
    $key = trim($key);
    if ($key === '') {
        return '';
    }

    if (config('shopee.partner_key_strip_shpk') && str_starts_with($key, 'shpk')) {
        return substr($key, 4);
    }

    return $key;
}

/**
 * Callback must be HTTPS in production and match the domain in Shopee Console.
 */
private static function resolveRedirectUrl(): string
{
    $url = trim((string) config('shopee.redirect_url', ''));

    if ($url === '') {
        $appUrl = rtrim(trim((string) config('app.url', '')), '/');
        if ($appUrl !== '') {
            $url = $appUrl . '/integrations/shopee/callback';
        }
    }

    if ($url === '') {
        return '';
    }

    if (config('shopee.env') === 'prod' && str_starts_with($url, 'http://')) {
        $url = 'https://' . substr($url, 7);
    }

    return $url;
}


    private function normalizeResponse($json, string $context): array
    {
        if (!is_array($json)) {
            throw new \RuntimeException("Shopee API returned non-JSON response ({$context}).");
        }

        // Most v2 endpoints return: { error: "", message: "", response: {...}, request_id: "" }
        $error = Arr::get($json, 'error');
        if ($error && $error !== '') {
            $message = Arr::get($json, 'message', '');
            Log::warning('Shopee API error', [
                'context' => $context,
                'error' => $error,
                'message' => $message,
                'request_id' => Arr::get($json, 'request_id'),
            ]);
            throw new \RuntimeException("Shopee API error ({$error}): {$message}");
        }

        return Arr::get($json, 'response', $json);
    }


}
