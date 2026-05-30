<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'hpp_amount')) {
                $table->decimal('hpp_amount', 15, 2)->nullable()->after('base_price');
            }

            if (!Schema::hasColumn('products', 'packaging_type')) {
                // fixed | percent
                $table->string('packaging_type', 16)->nullable()->after('hpp_amount');
            }

            if (!Schema::hasColumn('products', 'packaging_value')) {
                // kalau fixed = rupiah, kalau percent = angka persen (mis 1 = 1%)
                $table->decimal('packaging_value', 15, 2)->nullable()->after('packaging_type');
            }
        });
    }

    public function down(): void
    {
        // amanin data production: tidak drop kolom di down
        if (!Schema::hasTable('products')) return;
    }
};
