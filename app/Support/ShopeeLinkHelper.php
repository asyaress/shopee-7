<?php

namespace App\Support;

class ShopeeLinkHelper
{
    public static function productUrl(?int $shopId, ?int $itemId): ?string
    {
        if (!$itemId) {
            return null;
        }

        $host = config('shopee.storefront_host', 'shopee.co.id');

        return "https://{$host}/product/{$shopId}/{$itemId}";
    }

    public static function adsProductUrl(?int $shopId, ?int $itemId): ?string
    {
        if (!$shopId || !$itemId) {
            return null;
        }

        $host = config('shopee.seller_host', 'seller.shopee.co.id');

        return "https://{$host}/portal/marketing/pas/product/{$itemId}?shopId={$shopId}";
    }

    public static function sellerPerformanceUrl(?int $shopId): ?string
    {
        if (!$shopId) {
            return null;
        }

        $host = config('shopee.seller_host', 'seller.shopee.co.id');

        return "https://{$host}/portal/product/list/all?shopId={$shopId}";
    }
}
