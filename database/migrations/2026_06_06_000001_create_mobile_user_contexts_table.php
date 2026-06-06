<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_user_contexts')) {
            return;
        }

        Schema::create('mobile_user_contexts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('active_shop_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'active_shop_id'], 'mobile_user_contexts_user_shop_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_user_contexts');
    }
};
