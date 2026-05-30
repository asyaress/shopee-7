<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopMonthlyCost extends Model
{
    protected $table = 'shop_monthly_costs';

    protected $fillable = [
        'shop_id',
        'year_month',
        'operational_amount',
        'target_net_profit',
        'target_gross',
        'target_units',
        'ad_budget_cap',
        'notes',
    ];

    protected $casts = [
        'operational_amount' => 'decimal:2',
        'target_net_profit' => 'decimal:2',
        'target_gross' => 'decimal:2',
        'target_units' => 'integer',
        'ad_budget_cap' => 'decimal:2',
    ];
}
