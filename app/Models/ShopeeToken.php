<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeToken extends Model
{
    public const APP_MAIN = 'main';
    public const APP_ADS = 'ads';

    protected $table = 'shopee_tokens';

    protected $fillable = [
        'env',
        'app_type',
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

    public function scopeForApp($query, string $appType = self::APP_MAIN)
    {
        return $query->where('app_type', $appType);
    }

    public function isExpired(int $bufferSeconds = 0): bool
    {
        if (!$this->expire_at) {
            return false; // unknown
        }

        return now()->addSeconds($bufferSeconds)->greaterThanOrEqualTo($this->expire_at);
    }
}
