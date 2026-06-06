<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mobile_push_devices')) {
            Schema::create('mobile_push_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('platform', 16);
                $table->string('device_name', 100);
                $table->string('push_token', 500)->nullable();
                $table->boolean('push_enabled')->default(false);
                $table->string('app_version', 32)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'platform', 'device_name'], 'mobile_push_devices_user_platform_device_unique');
            });
        }

        if (!Schema::hasTable('mobile_alert_reads')) {
            Schema::create('mobile_alert_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ceo_alert_log_id')->constrained('ceo_alert_logs')->cascadeOnDelete();
                $table->timestamp('read_at');
                $table->timestamps();
                $table->unique(['user_id', 'ceo_alert_log_id'], 'mobile_alert_reads_user_alert_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_alert_reads');
        Schema::dropIfExists('mobile_push_devices');
    }
};
