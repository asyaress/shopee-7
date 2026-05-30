<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessDecisionLog extends Model
{
    protected $fillable = [
        'shop_id',
        'product_id',
        'decision_type',
        'title',
        'note',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
