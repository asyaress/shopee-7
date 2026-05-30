<?php

return [
    // Set to 'test' for sandbox, 'prod' for production.
    'env' => env('SHOPEE_ENV', 'test'),

    // Optional: set true if sign fails with shpk-prefixed keys from Shopee Console
    'partner_key_strip_shpk' => (bool) env('SHOPEE_PARTNER_KEY_STRIP_SHPK', false),

    'partner_id' => (int) env('SHOPEE_PARTNER_ID'),
    'partner_key' => env('SHOPEE_PARTNER_KEY'),

    // Full callback URL (must be under the domain you set in Shopee Open Platform)
    'redirect_url' => env('SHOPEE_REDIRECT_URL'),

    // Hosts
    // Sandbox host: https://partner.test-stable.shopeemobile.com
    // Production host: https://partner.shopeemobile.com
    'hosts' => [
        'test' => env('SHOPEE_HOST_TEST', 'https://partner.test-stable.shopeemobile.com'),
        'prod' => env('SHOPEE_HOST_PROD', 'https://partner.shopeemobile.com'),
    ],

    // Safety buffer to refresh access token before expiry (seconds)
    'refresh_buffer' => (int) env('SHOPEE_REFRESH_BUFFER', 300),

    // Shopee get_order_list: time_to - time_from must be <= 15 days (use 14 to be safe)
    'order_list_max_days' => (int) env('SHOPEE_ORDER_LIST_MAX_DAYS', 14),

    // Default sync window (days)
    'sync_days' => (int) env('SHOPEE_SYNC_DAYS', 7),

    // Optional: auto-sync via Laravel scheduler
    'cron_enabled' => (bool) env('SHOPEE_CRON_ENABLED', false),
    'cron_sync_days' => (int) env('SHOPEE_CRON_SYNC_DAYS', env('SHOPEE_SYNC_DAYS', 7)),
    'cron_product_page_size' => (int) env('SHOPEE_CRON_PRODUCT_PAGE_SIZE', 100),
    'cron_frequency' => env('SHOPEE_CRON_FREQUENCY', 'hourly'), // hourly|everyThirtyMinutes|daily

    // Optional fields untuk /api/v2/order/get_order_detail.
    // Bisa disesuaikan jika ada field yang tidak tersedia di region tertentu.
    // Default di sini fokus untuk: customer, item_list, dan beberapa info pengiriman.
    'order_detail_optional_fields' => array_values(array_filter(array_map('trim', explode(',', env(
        'SHOPEE_ORDER_DETAIL_FIELDS',
        'buyer_username,recipient_address,item_list,pay_time,shipping_carrier,note'
    ))))),

    // Optional: lock to a single shop_id. If empty, will use the latest connected shop.
    'shop_id' => env('SHOPEE_SHOP_ID'),

    // Ads API sync window (days)
    'ads_sync_days' => (int) env('SHOPEE_ADS_SYNC_DAYS', 30),

    // BCG auto-sync (days, matches get_item_extra_info views window)
    'bcg_sync_days' => (int) env('BCG_SYNC_DAYS', 30),

    // Ads endpoints (Shopee Open Platform v2 — requires Marketing/Ads permission)
    'ads_endpoints' => [
        'product_daily' => '/api/v2/ads/get_product_campaign_daily_performance',
        'shop_daily' => '/api/v2/ads/get_all_cpc_ads_daily_performance',
    ],

    'storefront_host' => env('SHOPEE_STOREFRONT_HOST', 'shopee.co.id'),
    'seller_host' => env('SHOPEE_SELLER_HOST', 'seller.shopee.co.id'),
];
