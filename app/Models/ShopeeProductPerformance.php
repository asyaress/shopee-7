<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopeeProductPerformance extends Model
{
    public const SOURCE_AUTO = 'auto';
    public const SOURCE_IMPORT = 'import';

    protected $table = 'shopee_product_performance';

    protected $fillable = [
        'shop_id', 'product_id', 'external_item_id', 'product_name', 'parent_sku',
        'period_start', 'period_end', 'visitors', 'page_views', 'units_sold',
        'sales_gmv', 'conversion_rate', 'source', 'raw',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sales_gmv' => 'decimal:2',
        'conversion_rate' => 'decimal:4',
        'raw' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
