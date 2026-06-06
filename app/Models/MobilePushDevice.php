<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilePushDevice extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'device_name',
        'push_token',
        'push_enabled',
        'app_version',
        'last_seen_at',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
