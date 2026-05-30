<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeToken extends Model
{
    protected $table = 'shopee_tokens';

    protected $fillable = [
        'env',
        'partner_id',
        'shop_id',
        'access_token',
        'refresh_token',
        'expire_in',
        'obtained_at',
        'expire_at',
        'raw',
    ];

    protected $casts = [
        'obtained_at' => 'datetime',
        'expire_at' => 'datetime',
        'raw' => 'array',
    ];

    public function isExpired(int $bufferSeconds = 0): bool
    {
        if (!$this->expire_at) {
            return false; // unknown
        }

        return now()->addSeconds($bufferSeconds)->greaterThanOrEqualTo($this->expire_at);
    }
}
