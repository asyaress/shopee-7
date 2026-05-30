<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shopee_product_ads_daily')) {
            return;
        }

        Schema::create('shopee_product_ads_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('external_item_id', 64)->index();
            $table->date('report_date')->index();
            $table->decimal('spend', 15, 2)->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('gmv', 15, 2)->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->decimal('roas', 12, 4)->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'external_item_id', 'report_date'], 'shopee_ads_daily_unique');
        });
    }

    public function down(): void
    {
        // keep data in production
    }
};
