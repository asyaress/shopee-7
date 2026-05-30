<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            // Safe upgrade path: ensure key columns exist
            Schema::table('order_items', function (Blueprint $table) {
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

            return;
        }

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();

            // External identifiers (Shopee)
            $table->string('external_platform', 32)->nullable();
            $table->unsignedBigInteger('external_item_id')->nullable();
            $table->unsignedBigInteger('external_model_id')->nullable();
            $table->string('external_sku', 128)->nullable();

            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['product_id']);
            $table->index(['external_platform', 'external_item_id', 'external_model_id'], 'order_items_ext_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }
    }
};
