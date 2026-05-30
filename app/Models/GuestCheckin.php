<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'name',
        'division',
        'arrived_at',
        'signature_path',
        'signature_sha256',
        'device_id',
        'operator_id',
        'raw_payload',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
