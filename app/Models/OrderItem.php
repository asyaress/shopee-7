<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'external_platform',
        'external_item_id',
        'external_model_id',
        'external_sku',
        'product_name',
        'quantity',
        'price',
        'total_amount',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    // Relationship dengan Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relationship dengan Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Auto calculate total amount when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            if ($orderItem->price && $orderItem->quantity) {
                $orderItem->total_amount = $orderItem->price * $orderItem->quantity;
            }
        });
    }
}
