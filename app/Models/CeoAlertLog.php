<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoAlertLog extends Model
{
    protected $fillable = [
        'shop_id',
        'alert_key',
        'severity',
        'title',
        'message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
