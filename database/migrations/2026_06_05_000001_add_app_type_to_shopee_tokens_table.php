<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shopee_tokens')) {
            return;
        }

        if (!Schema::hasColumn('shopee_tokens', 'app_type')) {
            Schema::table('shopee_tokens', function (Blueprint $table) {
                $table->string('app_type', 16)->default('main')->after('env');
                $table->index(['env', 'app_type', 'shop_id'], 'shopee_tokens_env_app_shop_idx');
            });

            DB::table('shopee_tokens')
                ->whereNull('app_type')
                ->update(['app_type' => 'main']);
        }

        Schema::table('shopee_tokens', function (Blueprint $table) {
            try {
                $table->dropUnique('shopee_tokens_env_shop_id_unique');
            } catch (\Throwable) {
                // old unique may already be absent
            }

            try {
                $table->unique(['env', 'shop_id', 'app_type'], 'shopee_tokens_env_shop_app_unique');
            } catch (\Throwable) {
                // unique may already exist
            }
        });
    }

    public function down(): void
    {
        // keep production data
    }
};
