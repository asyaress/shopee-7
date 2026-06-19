<?php

namespace App\Services\Shopee;

use App\Models\ShopeeToken;

class ShopeeAppContextResolver
{
    public function token(string $appType, ?string $envOverride = null, ?int $shopId = null): ?ShopeeToken
    {
        $env = $envOverride ?: config('shopee.env', 'test');

        $query = ShopeeToken::query()
            ->where('env', $env)
            ->forApp($appType);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * @return array{0:ShopeeAdsSyncService,1:ShopeeToken,2:string}
     */
    public function buildAdsSyncService(int $shopId, ?string $envOverride = null): array
    {
        $mainToken = $this->token(ShopeeToken::APP_MAIN, $envOverride, $shopId);
        if (!$mainToken) {
            throw new \RuntimeException('Belum ada token Shopee Main App.');
        }

        $mainClient = ShopeeClient::fromConfig(ShopeeToken::APP_MAIN);

        $adsClient = null;
        $adsToken = null;
        $amsClient = null;
        $amsToken = null;
        $sources = [];

        if (ShopeeClient::isConfigured(ShopeeToken::APP_ADS)) {
            $adsToken = $this->token(ShopeeToken::APP_ADS, $envOverride, $shopId);
            if (!$adsToken) {
                throw new \RuntimeException('Credential Ads App sudah diisi, tapi toko ini belum di-connect ke Ads App.');
            }

            $adsClient = ShopeeClient::fromConfig(ShopeeToken::APP_ADS);
            $sources[] = ShopeeToken::APP_ADS;
        }

        if (ShopeeClient::isConfigured(ShopeeToken::APP_AMS)) {
            $amsToken = $this->token(ShopeeToken::APP_AMS, $envOverride, $shopId);
            if (!$amsToken) {
                throw new \RuntimeException('Credential AMS App sudah diisi, tapi toko ini belum di-connect ke AMS App.');
            }

            $amsClient = ShopeeClient::fromConfig(ShopeeToken::APP_AMS);
            $sources[] = ShopeeToken::APP_AMS;
        }

        return [
            new ShopeeAdsSyncService($mainClient, $adsClient, $adsToken, $amsClient, $amsToken),
            $mainToken,
            $sources === [] ? ShopeeToken::APP_MAIN : implode('+', $sources),
        ];
    }
}
