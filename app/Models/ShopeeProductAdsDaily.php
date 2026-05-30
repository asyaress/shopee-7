<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopeeProductAdsDaily extends Model
{
    protected $table = 'shopee_product_ads_daily';

    protected $fillable = [
        'shop_id',
        'product_id',
        'external_item_id',
        'report_date',
        'spend',
        'impressions',
        'clicks',
        'gmv',
        'orders',
        'roas',
        'raw',
    ];

    protected $casts = [
        'report_date' => 'date',
        'spend' => 'decimal:2',
        'gmv' => 'decimal:2',
        'roas' => 'decimal:4',
        'raw' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
