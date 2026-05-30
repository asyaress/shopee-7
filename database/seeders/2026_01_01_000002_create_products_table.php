<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->decimal('base_price', 15, 2)->nullable();
                $table->string('unit', 50)->default('pcs');
                $table->boolean('is_active')->default(true);
                $table->json('specifications')->nullable();

                // External platform fields (Shopee, etc.)
                $table->string('external_platform', 32)->nullable();
                $table->unsignedBigInteger('external_shop_id')->nullable();
                $table->unsignedBigInteger('external_item_id')->nullable();
                $table->string('external_sku', 128)->nullable();
                $table->string('image_url')->nullable();
                $table->string('external_status', 64)->nullable();
                $table->json('raw')->nullable();

                $table->timestamps();

                $table->index(['is_active']);
                $table->index(['external_platform', 'external_shop_id', 'external_item_id'], 'products_ext_idx');
            });

            return;
        }

        // Table exists: ensure external columns exist (safe for upgrading existing DB)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'external_platform')) {
                $table->string('external_platform', 32)->nullable()->after('id');
            }
            if (!Schema::hasColumn('products', 'external_shop_id')) {
                $table->unsignedBigInteger('external_shop_id')->nullable()->after('external_platform');
            }
            if (!Schema::hasColumn('products', 'external_item_id')) {
                $table->unsignedBigInteger('external_item_id')->nullable()->after('external_shop_id');
            }
            if (!Schema::hasColumn('products', 'external_sku')) {
                $table->string('external_sku', 128)->nullable()->after('external_item_id');
            }
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url')->nullable()->after('external_sku');
            }
            if (!Schema::hasColumn('products', 'external_status')) {
                $table->string('external_status', 64)->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('products', 'raw')) {
                $table->json('raw')->nullable()->after('external_status');
            }
        });

        // Add index if missing (best-effort)
        try {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['external_platform', 'external_shop_id', 'external_item_id'], 'products_ext_idx');
            });
        } catch (\Throwable $e) {
            // ignore (likely already exists)
        }
    }

    public function down(): void
    {
        // keep existing data; do not drop products table on rollback in production.
        // For fresh installs, you can drop manually if needed.
        if (!Schema::hasTable('products')) {
            return;
        }
    }
};
