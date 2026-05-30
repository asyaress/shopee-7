<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Order::with(['customer', 'product']);

        // Apply same filters as in controller
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }

        if ($this->request->filled('customer_id')) {
            $query->where('customer_id', $this->request->customer_id);
        }

        if ($this->request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $this->request->date_from);
        }

        if ($this->request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $this->request->date_to);
        }

        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Pesanan',
            'Nama Customer',
            'Perusahaan',
            'Telepon',
            'Nama Produk',
            'Kategori',
            'Quantity',
            'Harga per Unit',
            'Total Harga',
            'Tanggal Pesan',
            'Tanggal Selesai',
            'Durasi (Hari)',
            'Status',
            'Catatan',
            'Tanggal Dibuat'
        ];
    }

    public function map($order): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $order->order_number,
            $order->customer->name,
            $order->customer->company ?? '-',
            $order->customer->phone ?? '-',
            $order->product_name,
            $order->product->category ?? 'Custom',
            $order->quantity,
            $order->price ? 'Rp ' . number_format($order->price, 0, ',', '.') : '-',
            $order->total_amount ? 'Rp ' . number_format($order->total_amount, 0, ',', '.') : '-',
            $order->order_date->format('d/m/Y'),
            $order->completion_date->format('d/m/Y'),
            $order->duration,
            $order->status_indonesian,
            $order->notes ?? '-',
            $order->created_at->format('d/m/Y H:i')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['rgb' => 'dc2626']
                ]
            ],
        ];
    }
}
