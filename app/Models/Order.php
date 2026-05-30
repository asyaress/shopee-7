<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_company',
        'customer_type',
        'order_date',
        'completion_date',
        'jenis_pengiriman', // <-- tambahkan di sini!
        'jenis_transaksi',
        'status',
        'notes',
        'price',
        'total_amount'
    ];

    protected $casts = [
        'order_date' => 'date',          // date, bukan datetime
        'completion_date' => 'date',
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship dengan OrderItems
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Detail potongan/fee Shopee (opsional)
    public function shopeeFinancial()
    {
        return $this->hasOne(ShopeeOrderFinancial::class);
    }

    // (Optional) Relationship ke Product untuk compatibility, jika perlu
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Accessor untuk mendapatkan total quantity
    public function getTotalQuantityAttribute()
    {
        return $this->orderItems->sum('quantity');
    }

    // Accessor untuk mendapatkan nama produk-produk
    public function getProductNamesAttribute()
    {
        return $this->orderItems->pluck('product_name')->join(', ');
    }

    // Accessor untuk mendapatkan durasi dalam hari
    public function getDurationAttribute()
    {
        if ($this->order_date && $this->completion_date) {
            return Carbon::parse($this->order_date)->diffInDays(Carbon::parse($this->completion_date));
        }
        return 0;
    }

    // Accessor status Indonesia
    public function getStatusIndonesianAttribute()
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'in_progress' => 'Sedang Proses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status
        };
    }

    // Scope pesanan hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('order_date', Carbon::today());
    }

    // Scope pesanan yang sedang proses/pending
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    // Method untuk generate order number otomatis
    public static function generateOrderNumber()
    {
        $year = date('Y');
        $month = date('m');
        $latestOrder = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $sequence = str_pad($latestOrder + 1, 3, '0', STR_PAD_LEFT);

        return "TSG-{$year}{$month}-{$sequence}";
    }

    // (Opsional) Recalculate total amount dari orderItems
    public function recalculateTotal()
    {
        $this->total_amount = $this->orderItems->sum('total_amount');
        $this->save();
    }
}
