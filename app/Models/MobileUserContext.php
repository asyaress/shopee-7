<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileUserContext extends Model
{
    protected $fillable = [
        'user_id',
        'active_shop_id',
    ];

    protected $casts = [
        'active_shop_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
