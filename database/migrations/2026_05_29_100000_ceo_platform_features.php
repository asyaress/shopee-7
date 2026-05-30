<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shop_monthly_costs')) {
            Schema::table('shop_monthly_costs', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_monthly_costs', 'target_net_profit')) {
                    $table->decimal('target_net_profit', 15, 2)->nullable()->after('operational_amount');
                }
                if (!Schema::hasColumn('shop_monthly_costs', 'target_gross')) {
                    $table->decimal('target_gross', 15, 2)->nullable()->after('target_net_profit');
                }
                if (!Schema::hasColumn('shop_monthly_costs', 'ad_budget_cap')) {
                    $table->decimal('ad_budget_cap', 15, 2)->nullable()->after('target_gross');
                }
            });
        }

        if (!Schema::hasTable('business_decision_logs')) {
            Schema::create('business_decision_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('decision_type', 64);
                $table->string('title');
                $table->text('note')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ceo_alert_logs')) {
            Schema::create('ceo_alert_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('alert_key', 128);
                $table->string('severity', 16)->default('warning');
                $table->string('title');
                $table->text('message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['shop_id', 'alert_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ceo_alert_logs');
        Schema::dropIfExists('business_decision_logs');
    }
};
