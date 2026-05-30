<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'base_price',
        'unit',
        'is_active',
        'specifications',

        // external (Shopee, etc.)
        'external_platform',
        'external_shop_id',
        'external_item_id',
        'external_sku',
        'image_url',
        'external_status',
        'raw',
        'hpp_amount',
    'packaging_type',
    'packaging_value',
        
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'specifications' => 'array',
        'raw' => 'array',
            'hpp_amount' => 'decimal:2',
    'packaging_value' => 'decimal:2',

    ];

    // Relationship dengan Order (via OrderItems)
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Relationship dengan OrderItems
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Scope untuk produk aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope berdasarkan kategori
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessor untuk format harga
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->base_price, 0, ',', '.');
    }

    // Method untuk mendapatkan total pesanan produk ini
    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    // Method untuk mendapatkan total quantity yang dipesan via order items
    public function getTotalQuantityOrderedAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }
    
    public function getPackagingCostEstimateAttribute(): ?float
{
    if ($this->packaging_value === null) return null;

    $type = $this->packaging_type ?: 'fixed';

    if ($type === 'percent') {
        if ($this->base_price === null) return null;
        return (float) (($this->base_price * $this->packaging_value) / 100);
    }

    return (float) $this->packaging_value;
}
}
