<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_variants')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'hpp_amount')) {
                $table->decimal('hpp_amount', 15, 2)->nullable()->after('stock');
            }
            if (!Schema::hasColumn('product_variants', 'packaging_type')) {
                $table->string('packaging_type', 16)->nullable()->after('hpp_amount');
            }
            if (!Schema::hasColumn('product_variants', 'packaging_value')) {
                $table->decimal('packaging_value', 15, 2)->nullable()->after('packaging_type');
            }
        });
    }

    public function down(): void
    {
        // production safe: tidak drop kolom
    }
};
