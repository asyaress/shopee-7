<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            // supaya item Shopee bisa di-upsert dengan aman (safe if already exists)
            if (!Schema::hasColumn('order_items', 'external_platform')) {
                $table->string('external_platform', 32)->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('order_items', 'external_item_id')) {
                $table->unsignedBigInteger('external_item_id')->nullable()->after('external_platform');
            }
            if (!Schema::hasColumn('order_items', 'external_model_id')) {
                $table->unsignedBigInteger('external_model_id')->nullable()->after('external_item_id');
            }
            if (!Schema::hasColumn('order_items', 'external_sku')) {
                $table->string('external_sku', 128)->nullable()->after('external_model_id');
            }
        });

        // Add index best-effort
        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index(['external_platform', 'external_item_id', 'external_model_id'], 'order_items_ext_idx');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // Intentionally noop in production.
    }
};
