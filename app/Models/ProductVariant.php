<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'external_platform',
        'external_model_id',
        'name',
        'sku',
        'price',
        'stock',
        'raw',
                'hpp_amount',
        'packaging_type',
        'packaging_value',

    ];

    protected $casts = [
        'price' => 'decimal:2',
        'raw' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
        // fallback: kalau variant null => pakai product
    public function getEffectiveHppAmountAttribute()
    {
        return (float) ($this->hpp_amount ?? $this->product?->hpp_amount ?? 0);
    }

    public function getEffectivePackagingTypeAttribute()
    {
        return $this->packaging_type ?? $this->product?->packaging_type ?? 'fixed';
    }

    public function getEffectivePackagingValueAttribute()
    {
        return (float) ($this->packaging_value ?? $this->product?->packaging_value ?? 0);
    }

    public function packagingCostForPrice(float $price): float
    {
        $type = $this->effective_packaging_type;
        $val  = $this->effective_packaging_value;

        if ($type === 'percent') {
            return ($price * $val) / 100.0;
        }
        return $val;
    }

}
