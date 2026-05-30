<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shopee_order_financials')) {
            return;
        }

        Schema::create('shopee_order_financials', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id')->unique();
            $table->string('order_sn', 64)->index();
            $table->unsignedBigInteger('shop_id')->nullable()->index();
            $table->string('currency', 8)->nullable();

            // "gross" (dibayar pembeli)
            $table->decimal('buyer_total_amount', 14, 2)->nullable();
            $table->decimal('shipping_fee_buyer', 14, 2)->nullable();
            $table->decimal('item_total_amount', 14, 2)->nullable();

            // Diskon/potongan (voucher/coins/promo)
            $table->decimal('coin_used', 14, 2)->nullable();
            $table->decimal('voucher_from_seller', 14, 2)->nullable();
            $table->decimal('voucher_from_shopee', 14, 2)->nullable();
            $table->decimal('promotion', 14, 2)->nullable();

            // Fees (dipotong Shopee)
            $table->decimal('commission_fee', 14, 2)->nullable();
            $table->decimal('service_fee', 14, 2)->nullable();
            $table->decimal('transaction_fee', 14, 2)->nullable();

            // Net income (yang diterima seller) - field kunci untuk laporan
            $table->decimal('seller_income', 14, 2)->nullable();

            $table->json('raw')->nullable();

            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_order_financials');
    }
};
