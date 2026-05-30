<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'orders' => 'Pesanan',
    'products' => 'Produk',
    'shopee_tokens' => 'Token Shopee',
    'shopee_order_financials' => 'Financial Shopee',
    'shopee_product_ads_daily' => 'Ads harian (migrasi)',
];

echo "Database: " . config('database.connections.mysql.database') . "\n\n";

foreach ($tables as $table => $label) {
    try {
        $n = DB::table($table)->count();
        echo sprintf("  %-28s %s\n", $label . ':', number_format($n));
    } catch (\Throwable $e) {
        echo sprintf("  %-28s %s\n", $label . ':', '— (' . $e->getMessage() . ')');
    }
}

$shops = DB::table('shopee_tokens')->pluck('shop_id')->unique();
echo "\nShop ID di token: " . $shops->implode(', ') . "\n";
