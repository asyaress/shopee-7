<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "DB OK: " . config('database.connections.mysql.database') . "\n";
} catch (\Throwable $e) {
    echo "DB FAIL: " . $e->getMessage() . "\n";
    exit(1);
}
