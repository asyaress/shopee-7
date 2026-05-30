<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopeeSettlementRelease extends Model
{
    protected $fillable = [
        'shop_id', 'order_sn', 'order_id', 'released_at', 'net_amount', 'source',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'net_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
