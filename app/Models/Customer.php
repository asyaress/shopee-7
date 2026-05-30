<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'company',
        'type',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationship dengan Order
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scope untuk customer aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessor untuk display name
    public function getDisplayNameAttribute()
    {
        if ($this->type === 'company' && $this->company) {
            return $this->company . ' (' . $this->name . ')';
        }
        return $this->name;
    }

    // Method untuk mendapatkan total pesanan
    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    // Method untuk mendapatkan total nilai pesanan
    public function getTotalOrderValueAttribute()
    {
        return $this->orders()->sum('total_amount');
    }
}
