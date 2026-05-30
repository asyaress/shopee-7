<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            // Ensure newer columns exist (safe upgrade path)
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('orders', 'customer_phone')) {
                    $table->string('customer_phone', 50)->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn('orders', 'customer_email')) {
                    $table->string('customer_email')->nullable()->after('customer_phone');
                }
                if (!Schema::hasColumn('orders', 'customer_address')) {
                    $table->text('customer_address')->nullable()->after('customer_email');
                }
                if (!Schema::hasColumn('orders', 'customer_company')) {
                    $table->string('customer_company')->nullable()->after('customer_address');
                }
                if (!Schema::hasColumn('orders', 'customer_type')) {
                    $table->string('customer_type', 50)->nullable()->after('customer_company');
                }
                if (!Schema::hasColumn('orders', 'jenis_pengiriman')) {
                    $table->string('jenis_pengiriman', 50)->nullable()->after('completion_date');
                }
                if (!Schema::hasColumn('orders', 'jenis_transaksi')) {
                    $table->string('jenis_transaksi', 50)->nullable()->after('jenis_pengiriman');
                }
                if (!Schema::hasColumn('orders', 'price')) {
                    $table->decimal('price', 15, 2)->nullable()->after('notes');
                }
            });

            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_company')->nullable();
            $table->string('customer_type', 50)->nullable();

            // Legacy single-product fields (optional)
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();

            $table->date('order_date');
            $table->date('completion_date')->nullable();
            $table->string('jenis_pengiriman', 50)->nullable();
            $table->string('jenis_transaksi', 50)->default('Website');
            $table->string('status', 32)->default('pending');
            $table->text('notes')->nullable();

            // price = gross subtotal (sum item list). total_amount = net revenue (after fees), if available.
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();

            $table->timestamps();

            $table->index(['status']);
            $table->index(['order_date']);
            $table->index(['jenis_transaksi']);
        });
    }

    public function down(): void
    {
        // Keep production data by default.
        if (!Schema::hasTable('orders')) {
            return;
        }
    }
};
