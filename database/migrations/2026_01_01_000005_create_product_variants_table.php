<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            return;
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            $table->string('external_platform', 32)->nullable();
            $table->unsignedBigInteger('external_model_id')->nullable();
            $table->string('name')->nullable();
            $table->string('sku', 128)->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['product_id']);
            $table->index(['external_platform', 'external_model_id'], 'product_variants_ext_idx');
        });
    }

    public function down(): void
    {
        // Keep production data by default.
        if (!Schema::hasTable('product_variants')) {
            return;
        }
    }
};
