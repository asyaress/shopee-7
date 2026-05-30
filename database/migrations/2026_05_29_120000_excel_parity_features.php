<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shopee_product_performance')) {
            Schema::create('shopee_product_performance', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('external_item_id')->index();
                $table->string('product_name')->nullable();
                $table->string('parent_sku')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedInteger('visitors')->default(0);
                $table->unsignedInteger('page_views')->default(0);
                $table->unsignedInteger('units_sold')->default(0);
                $table->decimal('sales_gmv', 15, 2)->default(0);
                $table->decimal('conversion_rate', 8, 4)->nullable();
                $table->json('raw')->nullable();
                $table->timestamps();
                $table->unique(['shop_id', 'external_item_id', 'period_start', 'period_end'], 'shopee_perf_unique');
            });
        }

        if (!Schema::hasTable('product_sales_targets')) {
            Schema::create('product_sales_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('year_month', 7);
                $table->decimal('target_gross', 15, 2)->nullable();
                $table->unsignedInteger('target_units')->nullable();
                $table->timestamps();
                $table->unique(['shop_id', 'product_id', 'year_month'], 'product_sales_target_unique');
            });
        }

        if (!Schema::hasTable('shopee_settlement_releases')) {
            Schema::create('shopee_settlement_releases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('order_sn', 64)->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->timestamp('released_at')->nullable();
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->string('source', 32)->default('estimate');
                $table->timestamps();
                $table->unique(['shop_id', 'order_sn'], 'settlement_release_unique');
            });
        }

        if (Schema::hasTable('shop_monthly_costs')) {
            Schema::table('shop_monthly_costs', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_monthly_costs', 'target_units')) {
                    $table->unsignedInteger('target_units')->nullable()->after('target_gross');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_settlement_releases');
        Schema::dropIfExists('product_sales_targets');
        Schema::dropIfExists('shopee_product_performance');

        if (Schema::hasTable('shop_monthly_costs') && Schema::hasColumn('shop_monthly_costs', 'target_units')) {
            Schema::table('shop_monthly_costs', fn (Blueprint $t) => $t->dropColumn('target_units'));
        }
    }
};
