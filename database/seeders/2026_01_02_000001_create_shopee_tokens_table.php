<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_tokens', function (Blueprint $table) {
            $table->id();

            $table->string('env', 10)->default('test'); // test|prod
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('shop_id');

            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->unsignedInteger('expire_in')->nullable(); // seconds
            $table->timestamp('obtained_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->unique(['env', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_tokens');
    }
};
