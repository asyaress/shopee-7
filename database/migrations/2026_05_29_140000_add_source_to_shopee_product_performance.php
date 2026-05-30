<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shopee_product_performance')) {
            return;
        }

        Schema::table('shopee_product_performance', function (Blueprint $table) {
            if (!Schema::hasColumn('shopee_product_performance', 'source')) {
                $table->string('source', 16)->default('auto')->after('conversion_rate');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('shopee_product_performance')
            && Schema::hasColumn('shopee_product_performance', 'source')) {
            Schema::table('shopee_product_performance', fn (Blueprint $t) => $t->dropColumn('source'));
        }
    }
};
