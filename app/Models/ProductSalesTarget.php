<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSalesTarget extends Model
{
    protected $fillable = [
        'shop_id', 'product_id', 'year_month', 'target_gross', 'target_units',
    ];

    protected $casts = [
        'target_gross' => 'decimal:2',
        'target_units' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
