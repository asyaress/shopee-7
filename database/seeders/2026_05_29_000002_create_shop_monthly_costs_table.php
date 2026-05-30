<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shop_monthly_costs')) {
            return;
        }

        Schema::create('shop_monthly_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->default(0)->index();
            $table->string('year_month', 7)->index(); // YYYY-MM
            $table->decimal('operational_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'year_month'], 'shop_monthly_costs_unique');
        });
    }

    public function down(): void
    {
        // keep data in production
    }
};
