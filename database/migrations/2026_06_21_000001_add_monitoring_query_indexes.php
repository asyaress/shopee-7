<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk query monitoring (order_date + status + jenis) — percepat laporan & snapshot chatbot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! $this->hasIndex('orders', 'orders_monitoring_date_status_idx')) {
                    $table->index(['order_date', 'status'], 'orders_monitoring_date_status_idx');
                }
                if (! $this->hasIndex('orders', 'orders_jenis_date_idx')) {
                    $table->index(['jenis_transaksi', 'order_date'], 'orders_jenis_date_idx');
                }
            });
        }

        if (Schema::hasTable('shopee_product_ads_daily')) {
            Schema::table('shopee_product_ads_daily', function (Blueprint $table) {
                if (! $this->hasIndex('shopee_product_ads_daily', 'ads_daily_shop_date_idx')) {
                    $table->index(['shop_id', 'report_date'], 'ads_daily_shop_date_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_monitoring_date_status_idx');
                $table->dropIndex('orders_jenis_date_idx');
            });
        }

        if (Schema::hasTable('shopee_product_ads_daily')) {
            Schema::table('shopee_product_ads_daily', function (Blueprint $table) {
                $table->dropIndex('ads_daily_shop_date_idx');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $conn = Schema::getConnection();
        $db = $conn->getDatabaseName();
        $rows = $conn->select(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return ((int) ($rows[0]->c ?? 0)) > 0;
    }
};
