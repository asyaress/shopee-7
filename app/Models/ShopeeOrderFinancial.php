<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeOrderFinancial extends Model
{
    protected $table = 'shopee_order_financials';

    protected $fillable = [
        'order_id',
        'order_sn',
        'shop_id',
        'currency',
        'buyer_total_amount',
        'shipping_fee_buyer',
        'item_total_amount',
        'coin_used',
        'voucher_from_seller',
        'voucher_from_shopee',
        'promotion',
        'commission_fee',
        'service_fee',
        'transaction_fee',
        'seller_income',
        'raw',
    ];

    protected $casts = [
        'buyer_total_amount' => 'decimal:2',
        'shipping_fee_buyer' => 'decimal:2',
        'item_total_amount' => 'decimal:2',
        'coin_used' => 'decimal:2',
        'voucher_from_seller' => 'decimal:2',
        'voucher_from_shopee' => 'decimal:2',
        'promotion' => 'decimal:2',
        'commission_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'transaction_fee' => 'decimal:2',
        'seller_income' => 'decimal:2',
        'raw' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
